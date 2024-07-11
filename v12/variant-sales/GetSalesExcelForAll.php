<?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: access");
// header("Access-Control-Allow-Methods: GET");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// require_once("./../tokenModels/tokenManager.php");
// require_once("../connection.php");
// require '../../db_connection.php';
// require  __DIR__ . '/../../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
// $dotenv->load();

// $headers = array();
// $rx_http = '/\AHTTP_/';
// foreach ($_SERVER as $key => $val) {
//     if (preg_match($rx_http, $key)) {
//         $arh_key = preg_replace($rx_http, '', $key);
//         $rx_matches = array();
//         // do some nasty string manipulations to restore the original letter case
//         // this should work in most cases
//         $rx_matches = explode('_', $arh_key);
//         if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
//             foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
//             $arh_key = implode('-', $rx_matches);
//         }
//         $headers[$arh_key] = $val;
//     }
// }
// $token = '';

// foreach ($headers as $header => $value) {
//     if ($header == "Authorization" || $header == "AUTHORIZATION") {
//         $token = substr($value, 7);
//     }
// }

// $db = connectBase();
// $tokenizer = new TokenManager($db);
// $tokens = $tokenizer->validate($token);
// $tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt', $token));
// $idMaster = $tokenDecoded->masterID;
// $value = array();
// $success = 0;
// $msg = 'Failed';

// if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

//     $status = $tokens['status'];
//     $msg = $tokens['msg'];
//     $success = 0;
// } else {
//     $arr = [];
//     $i = 0;
//     $id = $_GET['id'];
//     $dateTo = $_GET['dateTo'];
//     $dateFrom = $_GET['dateFrom'];
//     $total = 0;
//     $totalS = 0;
    
//     $array = [];
    
//     $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
//     if(mysqli_num_rows($sqlPartner) > 0) {
//         $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
//         foreach($getPartners as $partner) {
//             $id = $partner['partner_id'];
//             $reportV = array();
//             $totalQty = 0;
           
//             $query = "SELECT detail_transaksi.variant, detail_transaksi.id_transaksi, menu.nama, menu.id AS id_menu, menu.harga AS harga_satuan, menu.hpp, transaksi.status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE partner.id= '$id' AND transaksi.status IN (1,2,3,4) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
            
//             $fav = mysqli_query($db_conn, $query);
//             if (mysqli_num_rows($fav) > 0) {
        
//                 while ($rowMenu = mysqli_fetch_assoc($fav)) {
//                     $variant = $rowMenu['variant'];
//                     $namaMenu = $rowMenu['nama'];
//                     $qty = (int)$rowMenu['qty'];
//                     $variant = substr($variant, 1, -1);
//                     $var = "{" . $variant . "}";
//                     $var = str_replace("'", '"', $var);
//                     $var1 = json_decode($var, true);
//                     $menuID = $rowMenu['id_menu'];
//                     $transactionID = $rowMenu['id_transaksi'];
//                     $singlePrice = $rowMenu['harga_satuan'];
//                     $menuCogs = $rowMenu['hpp'];
//                     $transcStatus = $rowMenu['status'];
//                     $detailTranscID = $rowMenu['det_transc_id'];
        
//                     if (
//                         isset($var1['variant'])
//                         && !empty($var1['variant'])
//                     ) {
//                         $arrVar = $var1['variant'];
//                         foreach ($arrVar as $arr) {
//                             $v_id = 0;
//                             $vg_name = $arr['name'];
//                             $detail = $arr['detail'];
//                             $v_qty = 0;
//                             $v_refundQty = 0;
//                             foreach ($detail as $det) {
//                                 $v_name = $det['name'];
//                                 $v_qty += $qty;
//                                 if ($transcStatus == "4") {
//                                     $v_refundQty += $qty;
//                                 }
//                                 $idx = 0;
//                                 foreach ($reportV as $value) {
//                                     if ($value['id'] == $det['id'] && $value['name'] == $v_name && $value['vg_name'] == $vg_name && $value['menu_name'] == $namaMenu) {
//                                         $idx = $idx;
//                                         break;
//                                     } else {
//                                         $idx += 1;
//                                     }
//                                 }
//                                 $v_id = $idx;
//                                 $dic[$v_id] = $det['id'];
//                                 $reportV[$v_id]['id'] = $det['id'];
                                
//                                 ($reportV[$v_id]['qty'] ?? $reportV[$v_id]['qty'] = 0 ) ? $reportV[$v_id]['qty'] += $v_qty : $reportV[$v_id]['qty'] = $v_qty;
                                
//                                 ($reportV[$v_id]['refundQty'] ?? $reportV[$v_id]['refundQty'] = 0 ) ? $reportV[$v_id]['refundQty'] += $v_refundQty : $reportV[$v_id]['refundQty'] = $v_refundQty;
//                                 $reportV[$v_id]['name'] = $v_name;
//                                 $reportV[$v_id]['vg_name'] = $vg_name;
//                                 $reportV[$v_id]['menu_name'] = $namaMenu;
//                                 $reportV[$v_id]['reportName'] = $namaMenu . "-" . $vg_name . "-" . $v_name;
//                                 $reportV[$v_id]['id_menu'] = $menuID;
//                                 $reportV[$v_id]['transaction_id'] = $transactionID;
//                                 $reportV[$v_id]['cogs'] = (float)$menuCogs * ($reportV[$v_id]['qty'] - $reportV[$v_id]['refundQty']);
//                                 $reportV[$v_id]['single_price'] = (int)$singlePrice;
        
//                                 // get harga variant
//                                 $idVariant = $det['id'];
//                                 $sqlGetPrice = mysqli_query($db_conn, "SELECT v.price FROM `variant` v WHERE id = '$idVariant' ORDER BY id DESC");
//                                 $fetchPrice = mysqli_fetch_assoc($sqlGetPrice);
//                                 $variantPrice = $fetchPrice['price'];
//                                 // get harga variant end
        
//                                 $reportV[$v_id]['variant_price'] = (int)$variantPrice;
//                                 $reportV[$v_id]['price'] = (int)$variantPrice + (int)$singlePrice;
//                                 $reportV[$v_id]['gross_sales'] = $reportV[$v_id]['price'] * $reportV[$v_id]['qty'];
//                                 $reportV[$v_id]['totalRefund'] = $reportV[$v_id]['refundQty'] * $reportV[$v_id]['price'];
        
//                                 // get discount
//                                 $sqlDisc = mysqli_query($db_conn, "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount FROM `transaksi` t WHERE id = '$transactionID'");
//                                 $dataDisc = mysqli_fetch_assoc($sqlDisc);
//                                 $program_discount = $dataDisc['program_discount'];
//                                 $promo_discount = $dataDisc['promo'];
//                                 $special_discount = $dataDisc['diskon_spesial'];
//                                 $employee_discount = $dataDisc['employee_discount'];
//                                 $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
//                                 // get discount end
        
//                                 $reportV[$v_id]['totalDiscount'] = $totalDiscount;
//                                 $reportV[$v_id]['net_sales'] = $reportV[$v_id]['gross_sales'] - $reportV[$v_id]['totalDiscount'] - $reportV[$v_id]['totalRefund'];
//                                 $reportV[$v_id]['gross_profit'] = $reportV[$v_id]['net_sales'] - $reportV[$v_id]['cogs'];
//                                 // $reportV[$v_id]['gross_margin'] = ($reportV[$v_id]['gross_profit'] / $reportV[$v_id]['net_sales']) * 100;
        
//                                 $totalQty += $v_qty;
//                             }
//                         }
        
//                         foreach ($reportV as $key => $row) {
//                             $qty[$key] = array();
//                             $qty[$key] = $row['qty'];
//                             $menu_name[$key] = $row['menu_name'];
//                         }
        
//                         // you can use array_column() instead of the above code
//                         $qty  = array_column($reportV, 'qty');
//                         $menu_name = array_column($reportV, 'menu_name');
        
//                         // Sort the data with qty descending, menu_name ascending
//                         // Add $data as the last parameter, to sort by the common key
//                         array_multisort($qty, SORT_DESC, $menu_name, SORT_ASC, $reportV);
//                     }
//                 }
//             } 
            
//             $partner['variantSales'] = $reportV;
//             $partner['totalQty'] = $totalQty;
            
//             if(count($reportV) > 0) {
//                 array_push($array, $partner);
//             }
//         }
        
//         $success = 1;
//         $status = 200;
//         $msg = "Success";
//     } else {
//         $success = 0;
//         $status = 203;
//         $msg = "Data not found";
//     }
// }

// echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "data" => $array]);

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

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
} else {
    $arr = [];
    $i = 0;
    $id = $_GET['id'];
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
    
    $array = [];
    
    if($newDateFormat == 1)
    {
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $id = $partner['partner_id'];
                $reportV = array();
                $totalQty = 0;
               
                $query = "SELECT detail_transaksi.variant, detail_transaksi.id_transaksi, menu.nama, menu.id AS id_menu, menu.harga AS harga_satuan, menu.hpp, transaksi.status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE partner.id= '$id' AND transaksi.status IN (1,2,3,4) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
                
                $fav = mysqli_query($db_conn, $query);
                if (mysqli_num_rows($fav) > 0) {
            
                    while ($rowMenu = mysqli_fetch_assoc($fav)) {
                        $variant = $rowMenu['variant'];
                        $namaMenu = $rowMenu['nama'];
                        $qty = (int)$rowMenu['qty'];
                        $variant = substr($variant, 1, -1);
                        $var = "{" . $variant . "}";
                        $var = str_replace("'", '"', $var);
                        $var1 = json_decode($var, true);
                        $menuID = $rowMenu['id_menu'];
                        $transactionID = $rowMenu['id_transaksi'];
                        $singlePrice = $rowMenu['harga_satuan'];
                        $menuCogs = $rowMenu['hpp'];
                        $transcStatus = $rowMenu['status'];
                        $detailTranscID = $rowMenu['det_transc_id'];
            
                        if (
                            isset($var1['variant'])
                            && !empty($var1['variant'])
                        ) {
                            $arrVar = $var1['variant'];
                            foreach ($arrVar as $arr) {
                                $v_id = 0;
                                $vg_name = $arr['name'];
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
                                    $reportV[$v_id]['id'] = $det['id'];
                                    
                                    ($reportV[$v_id]['qty'] ?? $reportV[$v_id]['qty'] = 0 ) ? $reportV[$v_id]['qty'] += $v_qty : $reportV[$v_id]['qty'] = $v_qty;
                                    
                                    ($reportV[$v_id]['refundQty'] ?? $reportV[$v_id]['refundQty'] = 0 ) ? $reportV[$v_id]['refundQty'] += $v_refundQty : $reportV[$v_id]['refundQty'] = $v_refundQty;
                                    $reportV[$v_id]['name'] = $v_name;
                                    $reportV[$v_id]['vg_name'] = $vg_name;
                                    $reportV[$v_id]['menu_name'] = $namaMenu;
                                    $reportV[$v_id]['reportName'] = $namaMenu . "-" . $vg_name . "-" . $v_name;
                                    $reportV[$v_id]['id_menu'] = $menuID;
                                    $reportV[$v_id]['transaction_id'] = $transactionID;
                                    $reportV[$v_id]['cogs'] = (float)$menuCogs * ($reportV[$v_id]['qty'] - $reportV[$v_id]['refundQty']);
                                    $reportV[$v_id]['single_price'] = (int)$singlePrice;
            
                                    // get harga variant
                                    $idVariant = $det['id'];
                                    $sqlGetPrice = mysqli_query($db_conn, "SELECT v.price FROM `variant` v WHERE id = '$idVariant' ORDER BY id DESC");
                                    $fetchPrice = mysqli_fetch_assoc($sqlGetPrice);
                                    $variantPrice = $fetchPrice['price'];
                                    // get harga variant end
            
                                    $reportV[$v_id]['variant_price'] = (int)$variantPrice;
                                    $reportV[$v_id]['price'] = (int)$variantPrice + (int)$singlePrice;
                                    $reportV[$v_id]['gross_sales'] = $reportV[$v_id]['price'] * $reportV[$v_id]['qty'];
                                    $reportV[$v_id]['totalRefund'] = $reportV[$v_id]['refundQty'] * $reportV[$v_id]['price'];
            
                                    // get discount
                                    $sqlDisc = mysqli_query($db_conn, "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount FROM `transaksi` t WHERE id = '$transactionID' AND organization='Natta'");
                                    $dataDisc = mysqli_fetch_assoc($sqlDisc);
                                    $program_discount = $dataDisc['program_discount'];
                                    $promo_discount = $dataDisc['promo'];
                                    $special_discount = $dataDisc['diskon_spesial'];
                                    $employee_discount = $dataDisc['employee_discount'];
                                    $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
                                    // get discount end
            
                                    $reportV[$v_id]['totalDiscount'] = $totalDiscount;
                                    $reportV[$v_id]['net_sales'] = $reportV[$v_id]['gross_sales'] - $reportV[$v_id]['totalDiscount'] - $reportV[$v_id]['totalRefund'];
                                    $reportV[$v_id]['gross_profit'] = $reportV[$v_id]['net_sales'] - $reportV[$v_id]['cogs'];
                                    // $reportV[$v_id]['gross_margin'] = ($reportV[$v_id]['gross_profit'] / $reportV[$v_id]['net_sales']) * 100;
            
                                    $totalQty += $v_qty;
                                }
                            }
            
                            foreach ($reportV as $key => $row) {
                                $qty[$key] = array();
                                $qty[$key] = $row['qty'];
                                $menu_name[$key] = $row['menu_name'];
                            }
            
                            // you can use array_column() instead of the above code
                            $qty  = array_column($reportV, 'qty');
                            $menu_name = array_column($reportV, 'menu_name');
            
                            // Sort the data with qty descending, menu_name ascending
                            // Add $data as the last parameter, to sort by the common key
                            array_multisort($qty, SORT_DESC, $menu_name, SORT_ASC, $reportV);
                        }
                    }
                } 
                
                $partner['variantSales'] = $reportV;
                $partner['totalQty'] = $totalQty;
                
                if(count($reportV) > 0) {
                    array_push($array, $partner);
                }
            }
            
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 203;
            $msg = "Data not found";
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
                $totalQty = 0;
               
                $query = "SELECT detail_transaksi.variant, detail_transaksi.id_transaksi, menu.nama, menu.id AS id_menu, menu.harga AS harga_satuan, menu.hpp, transaksi.status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE partner.id= '$id' AND transaksi.status IN (1,2,3,4) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
                
                $fav = mysqli_query($db_conn, $query);
                if (mysqli_num_rows($fav) > 0) {
            
                    while ($rowMenu = mysqli_fetch_assoc($fav)) {
                        $variant = $rowMenu['variant'];
                        $namaMenu = $rowMenu['nama'];
                        $qty = (int)$rowMenu['qty'];
                        $variant = substr($variant, 1, -1);
                        $var = "{" . $variant . "}";
                        $var = str_replace("'", '"', $var);
                        $var1 = json_decode($var, true);
                        $menuID = $rowMenu['id_menu'];
                        $transactionID = $rowMenu['id_transaksi'];
                        $singlePrice = $rowMenu['harga_satuan'];
                        $menuCogs = $rowMenu['hpp'];
                        $transcStatus = $rowMenu['status'];
                        $detailTranscID = $rowMenu['det_transc_id'];
            
                        if (
                            isset($var1['variant'])
                            && !empty($var1['variant'])
                        ) {
                            $arrVar = $var1['variant'];
                            foreach ($arrVar as $arr) {
                                $v_id = 0;
                                $vg_name = $arr['name'];
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
                                    $reportV[$v_id]['id'] = $det['id'];
                                    
                                    ($reportV[$v_id]['qty'] ?? $reportV[$v_id]['qty'] = 0 ) ? $reportV[$v_id]['qty'] += $v_qty : $reportV[$v_id]['qty'] = $v_qty;
                                    
                                    ($reportV[$v_id]['refundQty'] ?? $reportV[$v_id]['refundQty'] = 0 ) ? $reportV[$v_id]['refundQty'] += $v_refundQty : $reportV[$v_id]['refundQty'] = $v_refundQty;
                                    $reportV[$v_id]['name'] = $v_name;
                                    $reportV[$v_id]['vg_name'] = $vg_name;
                                    $reportV[$v_id]['menu_name'] = $namaMenu;
                                    $reportV[$v_id]['reportName'] = $namaMenu . "-" . $vg_name . "-" . $v_name;
                                    $reportV[$v_id]['id_menu'] = $menuID;
                                    $reportV[$v_id]['transaction_id'] = $transactionID;
                                    $reportV[$v_id]['cogs'] = (float)$menuCogs * ($reportV[$v_id]['qty'] - $reportV[$v_id]['refundQty']);
                                    $reportV[$v_id]['single_price'] = (int)$singlePrice;
            
                                    // get harga variant
                                    $idVariant = $det['id'];
                                    $sqlGetPrice = mysqli_query($db_conn, "SELECT v.price FROM `variant` v WHERE id = '$idVariant' ORDER BY id DESC");
                                    $fetchPrice = mysqli_fetch_assoc($sqlGetPrice);
                                    $variantPrice = $fetchPrice['price'];
                                    // get harga variant end
            
                                    $reportV[$v_id]['variant_price'] = (int)$variantPrice;
                                    $reportV[$v_id]['price'] = (int)$variantPrice + (int)$singlePrice;
                                    $reportV[$v_id]['gross_sales'] = $reportV[$v_id]['price'] * $reportV[$v_id]['qty'];
                                    $reportV[$v_id]['totalRefund'] = $reportV[$v_id]['refundQty'] * $reportV[$v_id]['price'];
            
                                    // get discount
                                    $sqlDisc = mysqli_query($db_conn, "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount FROM `transaksi` t WHERE id = '$transactionID' AND organization='Natta'");
                                    $dataDisc = mysqli_fetch_assoc($sqlDisc);
                                    $program_discount = $dataDisc['program_discount'];
                                    $promo_discount = $dataDisc['promo'];
                                    $special_discount = $dataDisc['diskon_spesial'];
                                    $employee_discount = $dataDisc['employee_discount'];
                                    $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
                                    // get discount end
            
                                    $reportV[$v_id]['totalDiscount'] = $totalDiscount;
                                    $reportV[$v_id]['net_sales'] = $reportV[$v_id]['gross_sales'] - $reportV[$v_id]['totalDiscount'] - $reportV[$v_id]['totalRefund'];
                                    $reportV[$v_id]['gross_profit'] = $reportV[$v_id]['net_sales'] - $reportV[$v_id]['cogs'];
                                    // $reportV[$v_id]['gross_margin'] = ($reportV[$v_id]['gross_profit'] / $reportV[$v_id]['net_sales']) * 100;
            
                                    $totalQty += $v_qty;
                                }
                            }
            
                            foreach ($reportV as $key => $row) {
                                $qty[$key] = array();
                                $qty[$key] = $row['qty'];
                                $menu_name[$key] = $row['menu_name'];
                            }
            
                            // you can use array_column() instead of the above code
                            $qty  = array_column($reportV, 'qty');
                            $menu_name = array_column($reportV, 'menu_name');
            
                            // Sort the data with qty descending, menu_name ascending
                            // Add $data as the last parameter, to sort by the common key
                            array_multisort($qty, SORT_DESC, $menu_name, SORT_ASC, $reportV);
                        }
                    }
                } 
                
                $partner['variantSales'] = $reportV;
                $partner['totalQty'] = $totalQty;
                
                if(count($reportV) > 0) {
                    array_push($array, $partner);
                }
            }
            
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 203;
            $msg = "Data not found";
        }
    }

}

echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "data" => $array]);

