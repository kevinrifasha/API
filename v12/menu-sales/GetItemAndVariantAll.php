<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$headers = array();
$rx_http = '/\AHTTP_/';
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
            $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
    }
}
$token = '';

foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt', $token));
$idMaster = $tokenDecoded->masterID;
$value = array();
$success = 0;
$msg = 'Failed';
// $reportV = array();
// $reportNonV = [];
// $res = [];
$array = [];
$all = "0";

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
} else {
    $arr = [];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];

    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    $total = 0;
    $totalS = 0;
    $totalQty = 0;
    $query = "";
  
    if($newDateFormat == 1){
      $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
      if(mysqli_num_rows($sqlPartner) > 0) {
          $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
          
          foreach($getPartners as $partner) {
              $id = $partner['partner_id'];
              $reportV = array();
              $reportNonV = [];
              $res = [];
              $i = 0;
              
              $query = "SELECT detail_transaksi.variant, detail_transaksi.is_program, detail_transaksi.id_transaksi,  detail_transaksi.harga, menu.nama, menu.id AS id_menu, menu.harga AS harga_satuan, transaksi.status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id, menu.hpp, menu.sku, c.name AS cat_name, c.id AS cat_id FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id JOIN categories c ON c.id = menu.id_category WHERE partner.id= '$id' AND transaksi.status IN (1,2,3,4) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
              
              $fav = mysqli_query($db_conn, $query);
              if (mysqli_num_rows($fav) > 0) {
          
                  while ($rowMenu = mysqli_fetch_assoc($fav)) {
                      $totalDiscount = 0;
                      $variant = $rowMenu['variant'];
                      $namaMenu = $rowMenu['nama'];
                      $qty = (int)$rowMenu['qty'];
                      $variant = substr($variant, 1, -1);
                      $var = "{" . $variant . "}";
                      $var = str_replace("'", '"', $var);
                      $var1 = json_decode($var, true);
                      $menuID = $rowMenu['id_menu'];
                      $menuCogs = $rowMenu['hpp'];
                      $transactionID = $rowMenu['id_transaksi'];
                      $singlePrice = $rowMenu['harga_satuan'];
                      $sku = $rowMenu['sku'];
                      $category = $rowMenu['cat_name'];
                      $categoryID = $rowMenu['cat_id'];
                      
                      $is_program = $rowMenu['is_program'];
                      if($is_program > 0) {
                          $singlePrice = $rowMenu['harga'];
                      }
          
                      $transcStatus = $rowMenu['status'];
                      $detailTranscID = $rowMenu['det_transc_id'];
                      
                      $reportVar = [];
                      $reportVar['category'] = $category;
                      $reportVar['cogs'] = (float)$menuCogs * $qty;
                      // $reportVar['gross_sales'] = (int)$singlePrice * (int)$qty;
                      $reportVar['id'] = "";
                      $reportVar['id_menu'] = $menuID;
                      $reportVar['menu_name'] = $namaMenu;
                      $reportVar['name'] = "";
                      $reportVar['net_sales'] = "";
                      $reportVar['price'] = 0;
                      $reportVar['qty'] = $qty;
                      if ($transcStatus == "4") {
                          $reportVar['refundQty'] = $qty;
                      } else {
                          $reportVar['refundQty'] = 0;
                      }
                      $reportVar['reportName'] = $namaMenu;
                      $reportVar['single_price'] = (int)$singlePrice;
                      $reportVar['sku'] = $sku;
                      
                      // get discount
                      // $queryDisc = "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.program_id, p.discount_type, p.prerequisite_menu, p.prerequisite_category FROM `transaksi` t JOIN programs p ON p.id = t.program_id WHERE t.id = '$transactionID'";
                      $queryDisc = "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.employee_discount_percent, t.id_voucher, t.program_id FROM `transaksi` t WHERE t.id = '$transactionID' AND t.organization='Natta'";
                      $sqlDisc = mysqli_query($db_conn, $queryDisc);
                      $dataDisc = mysqli_fetch_assoc($sqlDisc);
                      $promo_discount = 0;
                      $special_discount = $dataDisc['diskon_spesial'];
                      // $employee_discount = $dataDisc['employee_discount'];
                      // $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
                      // get discount end
                    
                      // $reportVar['totalDiscount'] = $totalDiscount;
                      if($reportVar['refundQty'] == 0) {
                          $reportVar['totalRefund'] = 0;
                      } else {
                          $reportVar['totalRefund'] = $reportVar['refundQty'] * $reportVar['single_price'];
                      }
                      $reportVar['transaction_id'] = $transactionID;
                      $reportVar['variant_price'] = 0;
                      $reportVar['vg_name'] = "";
                      // $reportVar['net_sales'] = $reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund'];
          
                      if (
                          isset($var1['variant'])
                          && !empty($var1['variant'])
                      ) {
                          $arrVar = $var1['variant'];
                          foreach ($arrVar as $arr) {
                              $v_id = 0;
                              $vg_name = $arr['name'];
                              $vg_id = $arr['id'];
                              $detail = $arr['detail'];
                              $v_qty = 0;
                              $v_refundQty = 0;
                              foreach ($detail as $det) {
                                  $v_name = $det['name'];
                                  $v_qty += $qty;
                                  if ($transcStatus == "4") {
                                      $v_refundQty += $qty;
                                  }
                                  $idx = 0;
                                  foreach ($reportV as $value) {
                                      if ($value['id'] == $det['id'] && $value['name'] == $v_name && $value['vg_name'] == $vg_name && $value['menu_name'] == $namaMenu) {
                                          $idx = $idx;
                                          break;
                                      } else {
                                          $idx += 1;
                                      }
                                  }
                                  $v_id = $idx;
                                  $dic[$v_id] = $det['id'];
                                  $reportVar['reportName'] .= "-" . $v_name;
          
                                  // get harga variant
                                  $idVariant = $det['id'];
                                  $sqlGetPrice = mysqli_query($db_conn, "SELECT v.price, v.cogs FROM `variant` v WHERE id = '$idVariant' ORDER BY id DESC");
                                  $fetchPrice = mysqli_fetch_assoc($sqlGetPrice);
                                  // $menuCogs = $fetchPrice['cogs'];
                                  $reportVar['cogs'] += $fetchPrice['cogs'];
                                  $variantPrice = $fetchPrice['price'];
                                  $reportVar['cogs'] += $fetchPrice['price'];
                                  // get harga variant end
                                  $reportVar['variant_price'] += (int)$variantPrice;
                              }
                          }
                          
                          $empDiscPercent = (int)$dataDisc['employee_discount_percent'];
                          if($empDiscPercent > 0) {
                              $employee_discount = (((int)$singlePrice + $reportVar['variant_price']) * $qty) * $empDiscPercent / 100;
                          } else {
                              $employee_discount = 0;
                          }
                          
                          // get discount voucher
                          $id_voucher = $dataDisc['id_voucher'];
                          if($id_voucher) {
                              $voucherDiscount = 0;
                              $sqlVoucher = mysqli_query($db_conn, "SELECT type_id, is_percent, discount, prerequisite FROM voucher WHERE code = '$id_voucher'");
                              $voucher = mysqli_fetch_assoc($sqlVoucher);
                              $prerequisite = json_decode($voucher['prerequisite']);
                              
                              if($voucher['is_percent'] == 1 && $voucher['type_id'] == 1) {
                                  // kalo discount trx percent
                                  $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                                  $promo_discount = $voucherDiscount;
                              } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 1) {
                                  // kalo discount trx rp
                                  $subtotal = 0;
                                  $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                                  $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                  $subtotal = $fetchTotal['total'];
                                  $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                                  $promo_discount = $voucherDiscount * $qty;
                              } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 2) {
                                  // discount menu percent
                                  if($menuID == $prerequisite->menu_id) {
                                      $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                                      $promo_discount = $voucherDiscount;
                                  }
                              } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 2) {
                                  // discount menu rp
                                  $subtotal = 0;
                                  if($menuID == $prerequisite->menu_id) {
                                      $sqlTotal = mysqli_query($db_conn, "SELECT harga AS total FROM detail_transaksi WHERE id_transaksi = '$transactionID'");
                                      $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                      $subtotal = $fetchTotal['total'];
                                      $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                                      $promo_discount = $voucherDiscount * $qty;
                                  }
                              } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 3) {
                                  // discount category menu percent
                                  if($categoryID == $prerequisite->category_id) {
                                      $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                                      $promo_discount = $voucherDiscount;
                                  }
                              } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 3) {
                                  // discount category menu rp
                                  $subtotal = 0;
                                  if($categoryID == $prerequisite->category_id) {
                                      $sqlTotal = mysqli_query($db_conn, "SELECT SUM(dt.harga) AS subtotal FROM `detail_transaksi` dt JOIN `menu` m ON m.id = dt.id_menu JOIN `categories` c ON c.id = m.id_category WHERE id_transaksi = '$transactionID' AND c.id = '$prerequisite->category_id'");
                                      $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                      $subtotal = $fetchTotal['subtotal'];
                                      $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                                      $promo_discount = $voucherDiscount * $qty;
                                  }
                              } 
                          }
                          // get discount voucher end
                          
                          // get program discount
                          $program_discount = 0;
                          $program_id = $dataDisc['program_id'];
                          if($program_id > 0) {
                              $discount = $dataDisc['program_discount'];
                              $sqlProgram = mysqli_query($db_conn, "SELECT master_program_id, discount_type, discount_percentage, prerequisite_menu, prerequisite_category FROM programs WHERE id = '$program_id'");
                              $fetchProgram = mysqli_fetch_assoc($sqlProgram);
                              $master_program_id = $fetchProgram['master_program_id'];
                              $discType = $fetchProgram['discount_type'];
                              $discPercent = $fetchProgram['discount_percentage'];
                              
                              if($master_program_id == 2 && $discType == 1) {
                                  // program discount trx
                                  $valueDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                                  $program_discount = $valueDiscount;
                              } else if ($master_program_id == 2 && $discType == 2) {
                                  // program discount menu
                                  $prerequisite = $fetchProgram['prerequisite_menu'];
                                  $prerequisite = json_decode($prerequisite);
                                  // periksa menu apakah ada di prerequisite atau tidak
                                  $isInPrerequisite = false;
                                  foreach($prerequisite as $req) {
                                      if($req->id == $menuID) {
                                          $isInPrerequisite = true;
                                      }
                                  }
                                  
                                  if($isInPrerequisite == true) {
                                      $valueDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                                      $program_discount = $valueDiscount;
                                  }
                              } else if ($master_program_id == 2 && $discType == 3) {
                                  // program discount category menu
                                  $prerequisite = $fetchProgram['prerequisite_category'];
                                  $prerequisite = json_decode($prerequisite);
                                  // periksa menu apakah ada di prerequisite atau tidak
                                  $isInPrerequisite = false;
                                  foreach($prerequisite as $req) {
                                      if($req->id == $categoryID) {
                                          $isInPrerequisite = true;
                                      }
                                  }
                                  
                                  if($isInPrerequisite == true) {
                                      $valueDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                                      $program_discount = $valueDiscount;
                                  }
                              }
                              
                          }
                          // get program discount end
                          
                          $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
          
                          $reportVar['totalDiscount'] = $totalDiscount;
                          $reportVar['gross_sales'] = ((int)$singlePrice + (int)$variantPrice) * (int)$qty;
                          $reportVar['net_sales'] = $reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund'];
                          $reportVar['gross_profit'] = $reportVar['net_sales'] - $reportVar['cogs'];
                          
                          array_push($reportV, $reportVar);
                      } 
                      
                      else {
                          // buat yang tidak ada variant nya
                          $dataMenu = [];
                          
                          $dataMenu['cogs'] = (double)$menuCogs * $qty;
                          $dataMenu['id'] = "";
                          $dataMenu['id_menu'] = $menuID;
                          $dataMenu['menu_name'] = $namaMenu;
                          $dataMenu['price'] = 0;
                          $dataMenu['variant_price'] = 0;
                          $dataMenu['qty'] = $qty;
                          $dataMenu['vg_name'] = "";
                          $dataMenu['name'] = "";
                          $dataMenu['sku'] = $sku;
                          $dataMenu['category'] = $category;
                          $dataMenu['single_price'] = $singlePrice;
                          $dataMenu['transaction_id'] = $transactionID;
                          $dataMenu['reportName'] = $namaMenu;
                          $dataMenu['totalRefund'] = 0;
                          $dataMenu['refundQty'] = 0;
                          $dataMenu['totalRefund'] = 0;
                          
                          // get refund
                          if ($transcStatus == "4") {
                              $dataMenu['refundQty'] = $qty;
                              $dataMenu['totalRefund'] = $dataMenu['refundQty'] * $dataMenu['single_price'];
                          }
                          // get refund end
                          
                          // get discount
                          $sqlDisc = mysqli_query($db_conn, "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.id_voucher, t.employee_discount_percent, t.program_id FROM `transaksi` t WHERE t.id = '$transactionID' AND t.organization='Natta'");
                          $dataDisc = mysqli_fetch_assoc($sqlDisc);
                          // $promo_discount = $dataDisc['promo'];
                          $promo_discount = 0;
                          $special_discount = $dataDisc['diskon_spesial'];
                          $empDiscPercent = (int)$dataDisc['employee_discount_percent'];
                          // $employee_discount = $dataDisc['employee_discount'];
                          
                          if($empDiscPercent > 0) {
                              $employee_discount = ($singlePrice * $qty) * $empDiscPercent / 100;
                          } else {
                              $employee_discount = 0;
                          }
                          
                          // get discount voucher
                          $id_voucher = $dataDisc['id_voucher'];
                          if($id_voucher) {
                              $voucherDiscount = 0;
                              $sqlVoucher = mysqli_query($db_conn, "SELECT type_id, is_percent, discount, prerequisite FROM voucher WHERE code = '$id_voucher'");
                              $voucher = mysqli_fetch_assoc($sqlVoucher);
                              $prerequisite = json_decode($voucher['prerequisite']);
                              
                              if($voucher['is_percent'] == 1 && $voucher['type_id'] == 1) {
                                  // kalo discount trx percent
                                  $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                  $promo_discount = $voucherDiscount;
                              } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 1) {
                                  // kalo discount trx rp
                                  $subtotal = 0;
                                  $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                                  $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                  $subtotal = $fetchTotal['total'];
                                  $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                                  $promo_discount = $voucherDiscount * $qty;
                              } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 2) {
                                  // discount menu percent
                                  if($menuID == $prerequisite->menu_id) {
                                      $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                      $promo_discount = $voucherDiscount;
                                  }
                              } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 2) {
                                  // discount menu rp
                                  $subtotal = 0;
                                  if($menuID == $prerequisite->menu_id) {
                                      $sqlTotal = mysqli_query($db_conn, "SELECT harga AS total FROM detail_transaksi WHERE id_transaksi = '$transactionID'");
                                      $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                      $subtotal = $fetchTotal['total'];
                                      $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                                      $promo_discount = $voucherDiscount * $qty;
                                  }
                              } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 3) {
                                  // discount category menu percent
                                  if($categoryID == $prerequisite->category_id) {
                                      $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                      $promo_discount = $voucherDiscount;
                                  }
                              } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 3) {
                                  // discount category menu rp
                                  $subtotal = 0;
                                  if($categoryID == $prerequisite->category_id) {
                                      $sqlTotal = mysqli_query($db_conn, "SELECT SUM(dt.harga) AS subtotal FROM `detail_transaksi` dt JOIN `menu` m ON m.id = dt.id_menu JOIN `categories` c ON c.id = m.id_category WHERE id_transaksi = '$transactionID' AND c.id = '$prerequisite->category_id'");
                                      $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                      $subtotal = $fetchTotal['subtotal'];
                                      $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                                      $promo_discount = $voucherDiscount * $qty;
                                  }
                              } 
                          }
                          // get discount voucher end
                          
                          // get program discount
                          $program_discount = 0;
                          $program_id = $dataDisc['program_id'];
                          if($program_id > 0) {
                              $discount = $dataDisc['program_discount'];
                              $sqlProgram = mysqli_query($db_conn, "SELECT master_program_id, discount_type, discount_percentage, prerequisite_menu, prerequisite_category FROM programs WHERE id = '$program_id'");
                              $fetchProgram = mysqli_fetch_assoc($sqlProgram);
                              $master_program_id = $fetchProgram['master_program_id'];
                              $discType = $fetchProgram['discount_type'];
                              $discPercent = $fetchProgram['discount_percentage'];
                              
                              if($master_program_id == 2 && $discType == 1) {
                                  // program discount trx
                                  $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                                  $program_discount = $valueDiscount;
                              } else if ($master_program_id == 2 && $discType == 2) {
                                  // program discount menu
                                  $prerequisite = $fetchProgram['prerequisite_menu'];
                                  $prerequisite = json_decode($prerequisite);
                                  // periksa menu apakah ada di prerequisite atau tidak
                                  $isInPrerequisite = false;
                                  foreach($prerequisite as $req) {
                                      if($req->id == $menuID) {
                                          $isInPrerequisite = true;
                                      }
                                  }
                                  
                                  if($isInPrerequisite == true) {
                                      $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                                      $program_discount = $valueDiscount;
                                  }
                                  
                              } else if ($master_program_id == 2 && $discType == 3) {
                                  // program discount category menu
                                  $prerequisite = $fetchProgram['prerequisite_category'];
                                  $prerequisite = json_decode($prerequisite);
                                  // periksa category menu apakah ada di prerequisite atau tidak
                                  $isInPrerequisite = false;
                                  foreach($prerequisite as $req) {
                                      if($req->id == $categoryID) {
                                          $isInPrerequisite = true;
                                      }
                                  }
                                  
                                  if($isInPrerequisite == true) {
                                      $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                                      $program_discount = $valueDiscount;
                                  }
                              }
                          }
                          // get program discount end
                          
                          $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
                          // get discount end
                          // $totalDiscount = 0;
                          
                          $dataMenu['totalDiscount'] = $totalDiscount;
                          $dataMenu['gross_sales'] = $dataMenu['single_price'] * $dataMenu['qty'];
                          $dataMenu['net_sales'] = $dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund'];
                          $dataMenu['gross_profit'] = $dataMenu['net_sales'] - $dataMenu['cogs'];
                          
                          array_push($reportNonV, $dataMenu);
                      }
                  }
                  
                  $result = array_reduce($reportV, function($carry, $item) { 
                          if(!isset($carry[$item['reportName']])){ 
                              $carry[$item['reportName']] = [
                                  'cogs'=>$item['cogs'],
                                  'category'=>$item['category'],
                                  'gross_profit'=>$item['gross_profit'],
                                  'gross_sales'=>$item['gross_sales'],
                                  'id_menu'=>$item['id_menu'],
                                  'menu_name'=>$item['menu_name'],
                                  'name'=>$item['name'],
                                  'net_sales'=>$item['net_sales'],
                                  'price'=>$item['price'],
                                  'qty'=>$item['qty'],
                                  'refundQty'=>$item['refundQty'],
                                  'reportName'=>$item['reportName'],
                                  'vg_name'=>$item['vg_name'],
                                  'sku'=>$item['sku'],
                                  'single_price'=>(int)$item['single_price'],
                                  'totalDiscount'=>$item['totalDiscount'],
                                  'totalRefund'=>$item['totalRefund'],
                                  'variant_price'=>$item['variant_price'],
                              ]; 
                          } else { 
                              $carry[$item['reportName']]['cogs'] += $item['cogs']; 
                              $carry[$item['reportName']]['gross_sales'] += $item['gross_sales']; 
                              $carry[$item['reportName']]['gross_profit'] += $item['gross_profit']; 
                              $carry[$item['reportName']]['net_sales'] += $item['net_sales']; 
                              $carry[$item['reportName']]['qty'] += $item['qty']; 
                              $carry[$item['reportName']]['refundQty'] += $item['refundQty']; 
                              $carry[$item['reportName']]['totalRefund'] += $item['totalRefund']; 
                              $carry[$item['reportName']]['totalDiscount'] += $item['totalDiscount']; 
                          } 
                          
                          return $carry; 
                      });
                      
                  $reportV = [];
                  foreach($result as $val) {
                      array_push($reportV, $val);
                  }
                  
                  if(count($reportNonV) > 0) {
                      $result = array_reduce($reportNonV, function($carry, $item){ 
                          if(!isset($carry[$item['id_menu']])){ 
                              $carry[$item['id_menu']] = [
                                  'id_menu'=>$item['id_menu'],
                                  'id'=>$item['id'],
                                  'menu_name'=>$item['menu_name'],
                                  'vg_name'=>$item['vg_name'],
                                  'sku'=>$item['sku'],
                                  'category'=>$item['category'],
                                  'cogs'=>$item['cogs'],
                                  'gross_profit'=>$item['gross_profit'],
                                  'gross_sales'=>$item['gross_sales'],
                                  'name'=>$item['name'],
                                  'net_sales'=>$item['net_sales'],
                                  'price'=>$item['price'],
                                  'qty'=>$item['qty'],
                                  'refundQty'=>$item['refundQty'],
                                  'reportName'=>$item['reportName'],
                                  'single_price'=>(int)$item['single_price'],
                                  'totalDiscount'=>$item['totalDiscount'],
                                  'totalRefund'=>$item['totalRefund'],
                                  'variant_price'=>$item['variant_price'],
                              ]; 
                          } else { 
                              $carry[$item['id_menu']]['gross_profit'] += $item['gross_profit']; 
                              $carry[$item['id_menu']]['gross_sales'] += $item['gross_sales']; 
                              $carry[$item['id_menu']]['net_sales'] += $item['net_sales']; 
                              $carry[$item['id_menu']]['price'] += $item['price']; 
                              $carry[$item['id_menu']]['qty'] += $item['qty']; 
                              $carry[$item['id_menu']]['refundQty'] += $item['refundQty']; 
                              $carry[$item['id_menu']]['totalDiscount'] += $item['totalDiscount']; 
                              $carry[$item['id_menu']]['totalRefund'] += $item['totalRefund']; 
                              $carry[$item['id_menu']]['variant_price'] += $item['variant_price']; 
                          } 
                          
                          return $carry; 
                      });
                      
                      foreach($result as $val) {
                          if($val['net_sales'] == 0){
                              $val['grossMargin'] = "N/A";
                          } else {
                              $val['grossMargin'] = round(($val['gross_profit'] / $val['net_sales']) * 100, 2);
                          }
                          array_push($res, $val);
                      }
                      
                      foreach($reportV as $val) {
                          $val['net_sales'] = $val['gross_sales'] - $val['totalDiscount'] - $val['totalRefund'];
                          $val['gross_profit'] = $val['net_sales'] - $val['cogs'];
                          
                          if($val['net_sales'] == 0){
                              $val['grossMargin'] = "N/A";
                          } else {
                              $val['grossMargin'] = round(($val['gross_profit'] / $val['net_sales']) * 100, 2);
                          }
                          
                          array_push($res, $val);
                      }
                      
                  }
          
                  
              } 
              
              $partner['itemSales'] = $res;
                
              if(count($res) > 0) {
                  array_push($array, $partner);
              }
              
          }
          
          $success = 1;
          $status = 200;
          $msg = "Success";
      } else {
          $success = 0;
          $status = 203;
          $msg = "Data Not Found";
      }
    } 
    else 
    {
      $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
      if(mysqli_num_rows($sqlPartner) > 0) {
          $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
          
          foreach($getPartners as $partner) {
              $id = $partner['partner_id'];
              $reportV = array();
              $reportNonV = [];
              $res = [];
              $i = 0;
              
              $query = "SELECT detail_transaksi.variant, detail_transaksi.is_program, detail_transaksi.id_transaksi,  detail_transaksi.harga, menu.nama, menu.id AS id_menu, menu.harga AS harga_satuan, transaksi.status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id, menu.hpp, menu.sku, c.name AS cat_name, c.id AS cat_id FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id JOIN categories c ON c.id = menu.id_category WHERE partner.id= '$id' AND transaksi.status IN (1,2,3,4) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
              
              $fav = mysqli_query($db_conn, $query);
              if (mysqli_num_rows($fav) > 0) {
          
                  while ($rowMenu = mysqli_fetch_assoc($fav)) {
                      $totalDiscount = 0;
                      $variant = $rowMenu['variant'];
                      $namaMenu = $rowMenu['nama'];
                      $qty = (int)$rowMenu['qty'];
                      $variant = substr($variant, 1, -1);
                      $var = "{" . $variant . "}";
                      $var = str_replace("'", '"', $var);
                      $var1 = json_decode($var, true);
                      $menuID = $rowMenu['id_menu'];
                      $menuCogs = $rowMenu['hpp'];
                      $transactionID = $rowMenu['id_transaksi'];
                      $singlePrice = $rowMenu['harga_satuan'];
                      $sku = $rowMenu['sku'];
                      $category = $rowMenu['cat_name'];
                      $categoryID = $rowMenu['cat_id'];
                      
                      $is_program = $rowMenu['is_program'];
                      if($is_program > 0) {
                          $singlePrice = $rowMenu['harga'];
                      }
          
                      $transcStatus = $rowMenu['status'];
                      $detailTranscID = $rowMenu['det_transc_id'];
                      
                      $reportVar = [];
                      $reportVar['category'] = $category;
                      $reportVar['cogs'] = (float)$menuCogs * $qty;
                      // $reportVar['gross_sales'] = (int)$singlePrice * (int)$qty;
                      $reportVar['id'] = "";
                      $reportVar['id_menu'] = $menuID;
                      $reportVar['menu_name'] = $namaMenu;
                      $reportVar['name'] = "";
                      $reportVar['net_sales'] = "";
                      $reportVar['price'] = 0;
                      $reportVar['qty'] = $qty;
                      if ($transcStatus == "4") {
                          $reportVar['refundQty'] = $qty;
                      } else {
                          $reportVar['refundQty'] = 0;
                      }
                      $reportVar['reportName'] = $namaMenu;
                      $reportVar['single_price'] = (int)$singlePrice;
                      $reportVar['sku'] = $sku;
                      
                      // get discount
                      // $queryDisc = "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.program_id, p.discount_type, p.prerequisite_menu, p.prerequisite_category FROM `transaksi` t JOIN programs p ON p.id = t.program_id WHERE t.id = '$transactionID'";
                      $queryDisc = "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.employee_discount_percent, t.id_voucher, t.program_id FROM `transaksi` t WHERE t.id = '$transactionID' AND t.organization='Natta'";
                      $sqlDisc = mysqli_query($db_conn, $queryDisc);
                      $dataDisc = mysqli_fetch_assoc($sqlDisc);
                      $promo_discount = 0;
                      $special_discount = $dataDisc['diskon_spesial'];
                      // $employee_discount = $dataDisc['employee_discount'];
                      // $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
                      // get discount end
                    
                      // $reportVar['totalDiscount'] = $totalDiscount;
                      if($reportVar['refundQty'] == 0) {
                          $reportVar['totalRefund'] = 0;
                      } else {
                          $reportVar['totalRefund'] = $reportVar['refundQty'] * $reportVar['single_price'];
                      }
                      $reportVar['transaction_id'] = $transactionID;
                      $reportVar['variant_price'] = 0;
                      $reportVar['vg_name'] = "";
                      // $reportVar['net_sales'] = $reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund'];
          
                      if (
                          isset($var1['variant'])
                          && !empty($var1['variant'])
                      ) {
                          $arrVar = $var1['variant'];
                          foreach ($arrVar as $arr) {
                              $v_id = 0;
                              $vg_name = $arr['name'];
                              $vg_id = $arr['id'];
                              $detail = $arr['detail'];
                              $v_qty = 0;
                              $v_refundQty = 0;
                              foreach ($detail as $det) {
                                  $v_name = $det['name'];
                                  $v_qty += $qty;
                                  if ($transcStatus == "4") {
                                      $v_refundQty += $qty;
                                  }
                                  $idx = 0;
                                  foreach ($reportV as $value) {
                                      if ($value['id'] == $det['id'] && $value['name'] == $v_name && $value['vg_name'] == $vg_name && $value['menu_name'] == $namaMenu) {
                                          $idx = $idx;
                                          break;
                                      } else {
                                          $idx += 1;
                                      }
                                  }
                                  $v_id = $idx;
                                  $dic[$v_id] = $det['id'];
                                  $reportVar['reportName'] .= "-" . $v_name;
          
                                  // get harga variant
                                  $idVariant = $det['id'];
                                  $sqlGetPrice = mysqli_query($db_conn, "SELECT v.price, v.cogs FROM `variant` v WHERE id = '$idVariant' ORDER BY id DESC");
                                  $fetchPrice = mysqli_fetch_assoc($sqlGetPrice);
                                  // $menuCogs = $fetchPrice['cogs'];
                                  $reportVar['cogs'] += $fetchPrice['cogs'];
                                  $variantPrice = $fetchPrice['price'];
                                  $reportVar['cogs'] += $fetchPrice['price'];
                                  // get harga variant end
                                  $reportVar['variant_price'] += (int)$variantPrice;
                              }
                          }
                          
                          $empDiscPercent = (int)$dataDisc['employee_discount_percent'];
                          if($empDiscPercent > 0) {
                              $employee_discount = (((int)$singlePrice + $reportVar['variant_price']) * $qty) * $empDiscPercent / 100;
                          } else {
                              $employee_discount = 0;
                          }
                          
                          // get discount voucher
                          $id_voucher = $dataDisc['id_voucher'];
                          if($id_voucher) {
                              $voucherDiscount = 0;
                              $sqlVoucher = mysqli_query($db_conn, "SELECT type_id, is_percent, discount, prerequisite FROM voucher WHERE code = '$id_voucher'");
                              $voucher = mysqli_fetch_assoc($sqlVoucher);
                              $prerequisite = json_decode($voucher['prerequisite']);
                              
                              if($voucher['is_percent'] == 1 && $voucher['type_id'] == 1) {
                                  // kalo discount trx percent
                                  $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                                  $promo_discount = $voucherDiscount;
                              } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 1) {
                                  // kalo discount trx rp
                                  $subtotal = 0;
                                  $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                                  $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                  $subtotal = $fetchTotal['total'];
                                  $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                                  $promo_discount = $voucherDiscount * $qty;
                              } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 2) {
                                  // discount menu percent
                                  if($menuID == $prerequisite->menu_id) {
                                      $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                                      $promo_discount = $voucherDiscount;
                                  }
                              } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 2) {
                                  // discount menu rp
                                  $subtotal = 0;
                                  if($menuID == $prerequisite->menu_id) {
                                      $sqlTotal = mysqli_query($db_conn, "SELECT harga AS total FROM detail_transaksi WHERE id_transaksi = '$transactionID'");
                                      $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                      $subtotal = $fetchTotal['total'];
                                      $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                                      $promo_discount = $voucherDiscount * $qty;
                                  }
                              } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 3) {
                                  // discount category menu percent
                                  if($categoryID == $prerequisite->category_id) {
                                      $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                                      $promo_discount = $voucherDiscount;
                                  }
                              } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 3) {
                                  // discount category menu rp
                                  $subtotal = 0;
                                  if($categoryID == $prerequisite->category_id) {
                                      $sqlTotal = mysqli_query($db_conn, "SELECT SUM(dt.harga) AS subtotal FROM `detail_transaksi` dt JOIN `menu` m ON m.id = dt.id_menu JOIN `categories` c ON c.id = m.id_category WHERE id_transaksi = '$transactionID' AND c.id = '$prerequisite->category_id'");
                                      $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                      $subtotal = $fetchTotal['subtotal'];
                                      $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                                      $promo_discount = $voucherDiscount * $qty;
                                  }
                              } 
                          }
                          // get discount voucher end
                          
                          // get program discount
                          $program_discount = 0;
                          $program_id = $dataDisc['program_id'];
                          if($program_id > 0) {
                              $discount = $dataDisc['program_discount'];
                              $sqlProgram = mysqli_query($db_conn, "SELECT master_program_id, discount_type, discount_percentage, prerequisite_menu, prerequisite_category FROM programs WHERE id = '$program_id'");
                              $fetchProgram = mysqli_fetch_assoc($sqlProgram);
                              $master_program_id = $fetchProgram['master_program_id'];
                              $discType = $fetchProgram['discount_type'];
                              $discPercent = $fetchProgram['discount_percentage'];
                              
                              if($master_program_id == 2 && $discType == 1) {
                                  // program discount trx
                                  $valueDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                                  $program_discount = $valueDiscount;
                              } else if ($master_program_id == 2 && $discType == 2) {
                                  // program discount menu
                                  $prerequisite = $fetchProgram['prerequisite_menu'];
                                  $prerequisite = json_decode($prerequisite);
                                  // periksa menu apakah ada di prerequisite atau tidak
                                  $isInPrerequisite = false;
                                  foreach($prerequisite as $req) {
                                      if($req->id == $menuID) {
                                          $isInPrerequisite = true;
                                      }
                                  }
                                  
                                  if($isInPrerequisite == true) {
                                      $valueDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                                      $program_discount = $valueDiscount;
                                  }
                              } else if ($master_program_id == 2 && $discType == 3) {
                                  // program discount category menu
                                  $prerequisite = $fetchProgram['prerequisite_category'];
                                  $prerequisite = json_decode($prerequisite);
                                  // periksa menu apakah ada di prerequisite atau tidak
                                  $isInPrerequisite = false;
                                  foreach($prerequisite as $req) {
                                      if($req->id == $categoryID) {
                                          $isInPrerequisite = true;
                                      }
                                  }
                                  
                                  if($isInPrerequisite == true) {
                                      $valueDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                                      $program_discount = $valueDiscount;
                                  }
                              }
                              
                          }
                          // get program discount end
                          
                          $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
          
                          $reportVar['totalDiscount'] = $totalDiscount;
                          $reportVar['gross_sales'] = ((int)$singlePrice + (int)$variantPrice) * (int)$qty;
                          $reportVar['net_sales'] = $reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund'];
                          $reportVar['gross_profit'] = $reportVar['net_sales'] - $reportVar['cogs'];
                          
                          array_push($reportV, $reportVar);
                      } 
                      
                      else {
                          // buat yang tidak ada variant nya
                          $dataMenu = [];
                          
                          $dataMenu['cogs'] = (double)$menuCogs * $qty;
                          $dataMenu['id'] = "";
                          $dataMenu['id_menu'] = $menuID;
                          $dataMenu['menu_name'] = $namaMenu;
                          $dataMenu['price'] = 0;
                          $dataMenu['variant_price'] = 0;
                          $dataMenu['qty'] = $qty;
                          $dataMenu['vg_name'] = "";
                          $dataMenu['name'] = "";
                          $dataMenu['sku'] = $sku;
                          $dataMenu['category'] = $category;
                          $dataMenu['single_price'] = $singlePrice;
                          $dataMenu['transaction_id'] = $transactionID;
                          $dataMenu['reportName'] = $namaMenu;
                          $dataMenu['totalRefund'] = 0;
                          $dataMenu['refundQty'] = 0;
                          $dataMenu['totalRefund'] = 0;
                          
                          // get refund
                          if ($transcStatus == "4") {
                              $dataMenu['refundQty'] = $qty;
                              $dataMenu['totalRefund'] = $dataMenu['refundQty'] * $dataMenu['single_price'];
                          }
                          // get refund end
                          
                          // get discount
                          $sqlDisc = mysqli_query($db_conn, "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.id_voucher, t.employee_discount_percent, t.program_id FROM `transaksi` t WHERE t.id = '$transactionID' AND t.organization='Natta'");
                          $dataDisc = mysqli_fetch_assoc($sqlDisc);
                          // $promo_discount = $dataDisc['promo'];
                          $promo_discount = 0;
                          $special_discount = $dataDisc['diskon_spesial'];
                          $empDiscPercent = (int)$dataDisc['employee_discount_percent'];
                          // $employee_discount = $dataDisc['employee_discount'];
                          
                          if($empDiscPercent > 0) {
                              $employee_discount = ($singlePrice * $qty) * $empDiscPercent / 100;
                          } else {
                              $employee_discount = 0;
                          }
                          
                          // get discount voucher
                          $id_voucher = $dataDisc['id_voucher'];
                          if($id_voucher) {
                              $voucherDiscount = 0;
                              $sqlVoucher = mysqli_query($db_conn, "SELECT type_id, is_percent, discount, prerequisite FROM voucher WHERE code = '$id_voucher'");
                              $voucher = mysqli_fetch_assoc($sqlVoucher);
                              $prerequisite = json_decode($voucher['prerequisite']);
                              
                              if($voucher['is_percent'] == 1 && $voucher['type_id'] == 1) {
                                  // kalo discount trx percent
                                  $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                  $promo_discount = $voucherDiscount;
                              } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 1) {
                                  // kalo discount trx rp
                                  $subtotal = 0;
                                  $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                                  $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                  $subtotal = $fetchTotal['total'];
                                  $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                                  $promo_discount = $voucherDiscount * $qty;
                              } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 2) {
                                  // discount menu percent
                                  if($menuID == $prerequisite->menu_id) {
                                      $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                      $promo_discount = $voucherDiscount;
                                  }
                              } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 2) {
                                  // discount menu rp
                                  $subtotal = 0;
                                  if($menuID == $prerequisite->menu_id) {
                                      $sqlTotal = mysqli_query($db_conn, "SELECT harga AS total FROM detail_transaksi WHERE id_transaksi = '$transactionID'");
                                      $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                      $subtotal = $fetchTotal['total'];
                                      $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                                      $promo_discount = $voucherDiscount * $qty;
                                  }
                              } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 3) {
                                  // discount category menu percent
                                  if($categoryID == $prerequisite->category_id) {
                                      $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                      $promo_discount = $voucherDiscount;
                                  }
                              } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 3) {
                                  // discount category menu rp
                                  $subtotal = 0;
                                  if($categoryID == $prerequisite->category_id) {
                                      $sqlTotal = mysqli_query($db_conn, "SELECT SUM(dt.harga) AS subtotal FROM `detail_transaksi` dt JOIN `menu` m ON m.id = dt.id_menu JOIN `categories` c ON c.id = m.id_category WHERE id_transaksi = '$transactionID' AND c.id = '$prerequisite->category_id'");
                                      $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                      $subtotal = $fetchTotal['subtotal'];
                                      $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                                      $promo_discount = $voucherDiscount * $qty;
                                  }
                              } 
                          }
                          // get discount voucher end
                          
                          // get program discount
                          $program_discount = 0;
                          $program_id = $dataDisc['program_id'];
                          if($program_id > 0) {
                              $discount = $dataDisc['program_discount'];
                              $sqlProgram = mysqli_query($db_conn, "SELECT master_program_id, discount_type, discount_percentage, prerequisite_menu, prerequisite_category FROM programs WHERE id = '$program_id'");
                              $fetchProgram = mysqli_fetch_assoc($sqlProgram);
                              $master_program_id = $fetchProgram['master_program_id'];
                              $discType = $fetchProgram['discount_type'];
                              $discPercent = $fetchProgram['discount_percentage'];
                              
                              if($master_program_id == 2 && $discType == 1) {
                                  // program discount trx
                                  $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                                  $program_discount = $valueDiscount;
                              } else if ($master_program_id == 2 && $discType == 2) {
                                  // program discount menu
                                  $prerequisite = $fetchProgram['prerequisite_menu'];
                                  $prerequisite = json_decode($prerequisite);
                                  // periksa menu apakah ada di prerequisite atau tidak
                                  $isInPrerequisite = false;
                                  foreach($prerequisite as $req) {
                                      if($req->id == $menuID) {
                                          $isInPrerequisite = true;
                                      }
                                  }
                                  
                                  if($isInPrerequisite == true) {
                                      $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                                      $program_discount = $valueDiscount;
                                  }
                                  
                              } else if ($master_program_id == 2 && $discType == 3) {
                                  // program discount category menu
                                  $prerequisite = $fetchProgram['prerequisite_category'];
                                  $prerequisite = json_decode($prerequisite);
                                  // periksa category menu apakah ada di prerequisite atau tidak
                                  $isInPrerequisite = false;
                                  foreach($prerequisite as $req) {
                                      if($req->id == $categoryID) {
                                          $isInPrerequisite = true;
                                      }
                                  }
                                  
                                  if($isInPrerequisite == true) {
                                      $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                                      $program_discount = $valueDiscount;
                                  }
                              }
                          }
                          // get program discount end
                          
                          $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
                          // get discount end
                          // $totalDiscount = 0;
                          
                          $dataMenu['totalDiscount'] = $totalDiscount;
                          $dataMenu['gross_sales'] = $dataMenu['single_price'] * $dataMenu['qty'];
                          $dataMenu['net_sales'] = $dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund'];
                          $dataMenu['gross_profit'] = $dataMenu['net_sales'] - $dataMenu['cogs'];
                          
                          array_push($reportNonV, $dataMenu);
                      }
                  }
                  
                  $result = array_reduce($reportV, function($carry, $item) { 
                          if(!isset($carry[$item['reportName']])){ 
                              $carry[$item['reportName']] = [
                                  'cogs'=>$item['cogs'],
                                  'category'=>$item['category'],
                                  'gross_profit'=>$item['gross_profit'],
                                  'gross_sales'=>$item['gross_sales'],
                                  'id_menu'=>$item['id_menu'],
                                  'menu_name'=>$item['menu_name'],
                                  'name'=>$item['name'],
                                  'net_sales'=>$item['net_sales'],
                                  'price'=>$item['price'],
                                  'qty'=>$item['qty'],
                                  'refundQty'=>$item['refundQty'],
                                  'reportName'=>$item['reportName'],
                                  'vg_name'=>$item['vg_name'],
                                  'sku'=>$item['sku'],
                                  'single_price'=>(int)$item['single_price'],
                                  'totalDiscount'=>$item['totalDiscount'],
                                  'totalRefund'=>$item['totalRefund'],
                                  'variant_price'=>$item['variant_price'],
                              ]; 
                          } else { 
                              $carry[$item['reportName']]['cogs'] += $item['cogs']; 
                              $carry[$item['reportName']]['gross_sales'] += $item['gross_sales']; 
                              $carry[$item['reportName']]['gross_profit'] += $item['gross_profit']; 
                              $carry[$item['reportName']]['net_sales'] += $item['net_sales']; 
                              $carry[$item['reportName']]['qty'] += $item['qty']; 
                              $carry[$item['reportName']]['refundQty'] += $item['refundQty']; 
                              $carry[$item['reportName']]['totalRefund'] += $item['totalRefund']; 
                              $carry[$item['reportName']]['totalDiscount'] += $item['totalDiscount']; 
                          } 
                          
                          return $carry; 
                      });
                      
                  $reportV = [];
                  foreach($result as $val) {
                      array_push($reportV, $val);
                  }
                  
                  if(count($reportNonV) > 0) {
                      $result = array_reduce($reportNonV, function($carry, $item){ 
                          if(!isset($carry[$item['id_menu']])){ 
                              $carry[$item['id_menu']] = [
                                  'id_menu'=>$item['id_menu'],
                                  'id'=>$item['id'],
                                  'menu_name'=>$item['menu_name'],
                                  'vg_name'=>$item['vg_name'],
                                  'sku'=>$item['sku'],
                                  'category'=>$item['category'],
                                  'cogs'=>$item['cogs'],
                                  'gross_profit'=>$item['gross_profit'],
                                  'gross_sales'=>$item['gross_sales'],
                                  'name'=>$item['name'],
                                  'net_sales'=>$item['net_sales'],
                                  'price'=>$item['price'],
                                  'qty'=>$item['qty'],
                                  'refundQty'=>$item['refundQty'],
                                  'reportName'=>$item['reportName'],
                                  'single_price'=>(int)$item['single_price'],
                                  'totalDiscount'=>$item['totalDiscount'],
                                  'totalRefund'=>$item['totalRefund'],
                                  'variant_price'=>$item['variant_price'],
                              ]; 
                          } else { 
                              $carry[$item['id_menu']]['gross_profit'] += $item['gross_profit']; 
                              $carry[$item['id_menu']]['gross_sales'] += $item['gross_sales']; 
                              $carry[$item['id_menu']]['net_sales'] += $item['net_sales']; 
                              $carry[$item['id_menu']]['price'] += $item['price']; 
                              $carry[$item['id_menu']]['qty'] += $item['qty']; 
                              $carry[$item['id_menu']]['refundQty'] += $item['refundQty']; 
                              $carry[$item['id_menu']]['totalDiscount'] += $item['totalDiscount']; 
                              $carry[$item['id_menu']]['totalRefund'] += $item['totalRefund']; 
                              $carry[$item['id_menu']]['variant_price'] += $item['variant_price']; 
                          } 
                          
                          return $carry; 
                      });
                      
                      foreach($result as $val) {
                          if($val['net_sales'] == 0){
                              $val['grossMargin'] = "N/A";
                          } else {
                              $val['grossMargin'] = round(($val['gross_profit'] / $val['net_sales']) * 100, 2);
                          }
                          array_push($res, $val);
                      }
                      
                      foreach($reportV as $val) {
                          $val['net_sales'] = $val['gross_sales'] - $val['totalDiscount'] - $val['totalRefund'];
                          $val['gross_profit'] = $val['net_sales'] - $val['cogs'];
                          
                          if($val['net_sales'] == 0){
                              $val['grossMargin'] = "N/A";
                          } else {
                              $val['grossMargin'] = round(($val['gross_profit'] / $val['net_sales']) * 100, 2);
                          }
                          
                          array_push($res, $val);
                      }
                      
                  }
          
                  
              } 
              
              $partner['itemSales'] = $res;
                
              if(count($res) > 0) {
                  array_push($array, $partner);
              }
              
          }
          
          $success = 1;
          $status = 200;
          $msg = "Success";
      } else {
          $success = 0;
          $status = 203;
          $msg = "Data Not Found";
      }
    }

}

echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "data" => $array]);