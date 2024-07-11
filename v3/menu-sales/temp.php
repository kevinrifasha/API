<?php
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
$reportV = array();
$all = "0";
$reportNonV = [];
$res = [];
$dpTotal = 0;
$allRefundTotal = 0;
$allDiscTotal = 0;
$totalService = 0;
$totalTax = 0;
$totalNetSales = 0;
$totalGrossSales = 0;
$allSubtotal = 0;

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
    $total = 0;
    $totalS = 0;
    $totalQty = 0;
    $query = "";

    $newDateFormat = 0;

    if (strlen($dateTo) !== 10 && strlen($dateFrom) !== 10) {
        $dateTo = str_replace("%20", " ", $dateTo);
        $dateFrom = str_replace("%20", " ", $dateFrom);
        $newDateFormat = 1;
    }

    if (isset($_GET['all'])) {
        $all = $_GET['all'];
    }

    if ($newDateFormat == 1) {

        if ($all !== "1") {
            $query = "SELECT detail_transaksi.variant, detail_transaksi.is_program, detail_transaksi.id_transaksi, detail_transaksi.harga, menu.nama, menu.id AS id_menu, detail_transaksi.harga_satuan as harga_satuan, detail_transaksi.status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id, menu.hpp, menu.sku, c.name AS cat_name, c.id AS cat_id, c.is_consignment, transaksi.id_partner FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id JOIN categories c ON c.id = menu.id_category WHERE partner.id= '$id' AND transaksi.status IN (1,2,3,4) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
        } else {
            $query = "SELECT detail_transaksi.variant, detail_transaksi.is_program, detail_transaksi.id_transaksi, detail_transaksi.harga, menu.nama, menu.id AS id_menu, detail_transaksi.harga_satuan as harga_satuan, detail_transaksi.status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id, partner.id AS id_partner, menu.hpp, menu.sku, c.name AS cat_name, c.id AS cat_id, c.is_consignment FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id JOIN categories c ON c.id = menu.id_category WHERE partner.id_master= '$idMaster' AND transaksi.status IN (1,2,3,4) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
        }

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
                $isConsignment = $rowMenu['is_consignment'];
                $partnerID = $rowMenu['id_partner'];

                // service
                $qService = "SELECT service FROM partner WHERE id = '$partnerID' AND deleted_at IS NULL";
                $sqlService = mysqli_query($db_conn, $qService);
                $fetchService = mysqli_fetch_assoc($sqlService);
                $partnerService = (int)$fetchService['service'];
                // service end

                // tax
                $qTax = "SELECT tax FROM partner WHERE id = '$partnerID' AND deleted_at IS NULL";
                $sqlTax = mysqli_query($db_conn, $qTax);
                $fetchTax = mysqli_fetch_assoc($sqlTax);
                $partnerTax = (float)$fetchTax['tax'];
                // tax end

                $is_program = $rowMenu['is_program'];
                if ($is_program > 0) {
                    $singlePrice = $rowMenu['harga'];
                }

                $transcStatus = $rowMenu['status'];
                $detailTranscID = $rowMenu['det_transc_id'];

                $reportVar = [];
                $reportVar['category'] = $category;
                $reportVar['cogs'] = (float)$menuCogs * $qty;
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
                $queryDisc = "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.employee_discount_percent, t.id_voucher, t.program_id FROM `transaksi` t WHERE t.id = '$transactionID'";
                $sqlDisc = mysqli_query($db_conn, $queryDisc);
                $dataDisc = mysqli_fetch_assoc($sqlDisc);
                $promo_discount = 0;
                $special_discount = $dataDisc['diskon_spesial'];

                if ($reportVar['refundQty'] == 0) {
                    $reportVar['totalRefund'] = 0;
                } else {
                    $reportVar['totalRefund'] = $reportVar['refundQty'] * $reportVar['single_price'];
                }
                $reportVar['transaction_id'] = $transactionID;
                $reportVar['variant_price'] = 0;
                $reportVar['vg_name'] = "";

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
                    if ($empDiscPercent > 0) {
                        $employee_discount = (((int)$singlePrice + $reportVar['variant_price']) * $qty) * $empDiscPercent / 100;
                    } else {
                        $employee_discount = 0;
                    }

                    // get discount voucher
                    $id_voucher = $dataDisc['id_voucher'];
                    if ($id_voucher) {
                        $voucherDiscount = 0;
                        $sqlVoucher = mysqli_query($db_conn, "SELECT type_id, is_percent, discount, prerequisite FROM voucher WHERE code = '$id_voucher'");
                        $voucher = mysqli_fetch_assoc($sqlVoucher);
                        $prerequisite = json_decode($voucher['prerequisite']);

                        if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 1) {
                            // kalo discount trx percent
                            $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                            $promo_discount = $voucherDiscount;
                        } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 1) {
                            // kalo discount trx rp
                            $subtotal = 0;
                            $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                            $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                            $subtotal = $fetchTotal['total'];
                            // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                            $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                            $promo_discount = $voucherDiscount * $qty;
                        } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 2) {
                            // discount menu percent
                            if ($menuID == $prerequisite->menu_id) {
                                // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                                $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                $promo_discount = $voucherDiscount;
                            }
                        } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 2) {
                            // discount menu rp
                            $subtotal = 0;
                            if ($menuID == $prerequisite->menu_id) {
                                $sqlTotal = mysqli_query($db_conn, "SELECT harga AS total FROM detail_transaksi WHERE id_transaksi = '$transactionID'");
                                $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                $subtotal = $fetchTotal['total'];
                                // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                                $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                                $promo_discount = $voucherDiscount * $qty;
                            }
                        } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 3) {
                            // discount category menu percent
                            if ($categoryID == $prerequisite->category_id) {
                                // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                                $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                $promo_discount = $voucherDiscount;
                            }
                        } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 3) {
                            // discount category menu rp
                            $subtotal = 0;
                            if ($categoryID == $prerequisite->category_id) {
                                $sqlTotal = mysqli_query($db_conn, "SELECT SUM(dt.harga) AS subtotal FROM `detail_transaksi` dt JOIN `menu` m ON m.id = dt.id_menu JOIN `categories` c ON c.id = m.id_category WHERE id_transaksi = '$transactionID' AND c.id = '$prerequisite->category_id'");
                                $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                $subtotal = $fetchTotal['subtotal'];
                                // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                                $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                                $promo_discount = $voucherDiscount * $qty;
                            }
                        }
                    }
                    // get discount voucher end

                    // get program discount
                    $program_discount = 0;
                    $program_id = $dataDisc['program_id'];
                    if ($program_id > 0) {
                        $discount = $dataDisc['program_discount'];
                        $sqlProgram = mysqli_query($db_conn, "SELECT master_program_id, discount_type, discount_percentage, prerequisite_menu, prerequisite_category FROM programs WHERE id = '$program_id'");
                        $fetchProgram = mysqli_fetch_assoc($sqlProgram);
                        $master_program_id = $fetchProgram['master_program_id'];
                        $discType = $fetchProgram['discount_type'];
                        $discPercent = $fetchProgram['discount_percentage'];

                        if ($master_program_id == 2 && $discType == 1) {
                            // program discount trx
                            // $valueDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                            $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                            $program_discount = $valueDiscount;
                        } else if ($master_program_id == 2 && $discType == 2) {
                            // program discount menu
                            $prerequisite = $fetchProgram['prerequisite_menu'];
                            $prerequisite = json_decode($prerequisite);
                            // periksa menu apakah ada di prerequisite atau tidak
                            $isInPrerequisite = false;
                            foreach ($prerequisite as $req) {
                                if ($req->id == $menuID) {
                                    $isInPrerequisite = true;
                                }
                            }

                            if ($isInPrerequisite == true) {
                                // $valueDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                                $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                                $program_discount = $valueDiscount;
                            }
                        } else if ($master_program_id == 2 && $discType == 3) {
                            // program discount category menu
                            $prerequisite = $fetchProgram['prerequisite_category'];
                            $prerequisite = json_decode($prerequisite);
                            // periksa menu apakah ada di prerequisite atau tidak
                            $isInPrerequisite = false;
                            foreach ($prerequisite as $req) {
                                if ($req->id == $categoryID) {
                                    $isInPrerequisite = true;
                                }
                            }

                            if ($isInPrerequisite == true) {
                                $valueDiscount = (($singlePrice  + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                                $program_discount = $valueDiscount;
                            }
                        }
                    }
                    // get program discount end

                    // get dp
                    $dpMenu = 0;
                    $queryDP = "SELECT amount FROM down_payments WHERE transaction_id = '$transactionID' AND deleted_at IS NULL";
                    $sqlDP = mysqli_query($db_conn, $queryDP);
                    if (mysqli_num_rows($sqlDP) > 0) {
                        $fetchDP = mysqli_fetch_assoc($sqlDP);
                        $amountDP = (float)$fetchDP['amount'];

                        $subtotal = 0;
                        $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                        $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                        $subtotal = $fetchTotal['total'];

                        $dpDiscount = (($singlePrice  + $reportVar['variant_price']) / $subtotal) * $amountDP;
                        $dpMenu = $dpDiscount * $qty;
                    }
                    // get dp end

                    if($transcStatus == 4) {
                        $totalDiscount = 0;
                    } else {
                        $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
                    }
                    $allDiscTotal += $totalDiscount;

                    $reportVar['dpMenu'] = (int)$dpMenu;
                    $reportVar['totalDiscount'] = $totalDiscount;
                    // $reportVar['gross_sales'] = ((int)$singlePrice + (int)$variantPrice) * (int)$qty;
                    $reportVar['gross_sales'] = (int)$singlePrice * (int)$qty;

                    if ($isConsignment == 0) {
                        $reportVar['service'] =
                            ($reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund']) * $partnerService / 100;
                        $reportVar['tax'] = (($reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund']) + $reportVar['service']) * $partnerTax / 100;
                    } else {
                        $reportVar['service'] = 0;
                        $reportVar['tax'] = 0;
                    }

                    if($transcStatus == 4) {
                        $reportVar['service'] = 0;
                        $reportVar['tax'] = 0;
                    }

                    $totalService += $reportVar['service'];
                    $totalTax += $reportVar['tax'];

                    $totalService += $reportVar['service'];
                    $totalTax += $reportVar['tax'];

                    $totalGrossSales += $reportVar['gross_sales'];
                    $reportVar['net_sales'] = $reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund'];
                    $reportVar['net_sales'] = $reportVar['net_sales'] + $reportVar['service'] + $reportVar['tax'];
                    $reportVar['net_sales'] = $reportVar['net_sales'] - $reportVar['dpMenu'] ;
                    
                    $allSubtotal += ($reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund']);
                    $totalNetSales += $reportVar['net_sales'];

                    $reportVar['gross_profit'] = $reportVar['net_sales'] - $reportVar['cogs'];
                    $allRefundTotal += $reportVar['totalRefund'];

                    array_push($reportV, $reportVar);
                } else {
                    // buat yang tidak ada variant nya
                    $dataMenu = [];

                    $dataMenu['cogs'] = (float)$menuCogs * $qty;
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
                    $sqlDisc = mysqli_query($db_conn, "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.id_voucher, t.employee_discount_percent, t.program_id FROM `transaksi` t WHERE t.id = '$transactionID'");
                    $dataDisc = mysqli_fetch_assoc($sqlDisc);
                    $promo_discount = 0;
                    $special_discount = $dataDisc['diskon_spesial'];
                    $empDiscPercent = (int)$dataDisc['employee_discount_percent'];
                    if ($empDiscPercent > 0) {
                        $employee_discount = ($singlePrice * $qty) * $empDiscPercent / 100;
                    } else {
                        $employee_discount = 0;
                    }

                    // get discount voucher
                    $id_voucher = $dataDisc['id_voucher'];
                    if ($id_voucher) {
                        $voucherDiscount = 0;
                        $sqlVoucher = mysqli_query($db_conn, "SELECT type_id, is_percent, discount, prerequisite FROM voucher WHERE code = '$id_voucher'");
                        $voucher = mysqli_fetch_assoc($sqlVoucher);
                        $prerequisite = json_decode($voucher['prerequisite']);

                        if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 1) {
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
                            if ($menuID == $prerequisite->menu_id) {
                                $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                $promo_discount = $voucherDiscount;
                            }
                        } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 2) {
                            // discount menu rp
                            $subtotal = 0;
                            if ($menuID == $prerequisite->menu_id) {
                                $sqlTotal = mysqli_query($db_conn, "SELECT harga AS total FROM detail_transaksi WHERE id_transaksi = '$transactionID'");
                                $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                $subtotal = $fetchTotal['total'];
                                $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                                $promo_discount = $voucherDiscount * $qty;
                            }
                        } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 3) {
                            // discount category menu percent
                            if ($categoryID == $prerequisite->category_id) {
                                $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                $promo_discount = $voucherDiscount;
                            }
                        } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 3) {
                            // discount category menu rp
                            $subtotal = 0;
                            if ($categoryID == $prerequisite->category_id) {
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
                    if ($program_id > 0) {
                        $discount = $dataDisc['program_discount'];
                        $sqlProgram = mysqli_query($db_conn, "SELECT master_program_id, discount_type, discount_percentage, prerequisite_menu, prerequisite_category FROM programs WHERE id = '$program_id'");
                        $fetchProgram = mysqli_fetch_assoc($sqlProgram);
                        $master_program_id = $fetchProgram['master_program_id'];
                        $discType = $fetchProgram['discount_type'];
                        $discPercent = $fetchProgram['discount_percentage'];

                        if ($master_program_id == 2 && $discType == 1) {
                            // program discount trx
                            $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                            $program_discount = $valueDiscount;
                        } else if ($master_program_id == 2 && $discType == 2) {
                            // program discount menu
                            $prerequisite = $fetchProgram['prerequisite_menu'];
                            $prerequisite = json_decode($prerequisite);
                            // periksa menu apakah ada di prerequisite atau tidak
                            $isInPrerequisite = false;
                            foreach ($prerequisite as $req) {
                                if ($req->id == $menuID) {
                                    $isInPrerequisite = true;
                                }
                            }

                            if ($isInPrerequisite == true) {
                                $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                                $program_discount = $valueDiscount;
                            }
                        } else if ($master_program_id == 2 && $discType == 3) {
                            // program discount category menu
                            $prerequisite = $fetchProgram['prerequisite_category'];
                            $prerequisite = json_decode($prerequisite);
                            // periksa category menu apakah ada di prerequisite atau tidak
                            $isInPrerequisite = false;
                            foreach ($prerequisite as $req) {
                                if ($req->id == $categoryID) {
                                    $isInPrerequisite = true;
                                }
                            }

                            if ($isInPrerequisite == true) {
                                $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                                $program_discount = $valueDiscount;
                            }
                        }
                    }
                    // get program discount end
                    // get discount end

                    // get dp
                    $dpMenu = 0;
                    $queryDP = "SELECT amount FROM down_payments WHERE transaction_id = '$transactionID' AND deleted_at IS NULL";
                    $sqlDP = mysqli_query($db_conn, $queryDP);
                    if (mysqli_num_rows($sqlDP) > 0) {
                        $fetchDP = mysqli_fetch_assoc($sqlDP);
                        $amountDP = (float)$fetchDP['amount'];

                        $subtotal = 0;
                        $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                        $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                        $subtotal = $fetchTotal['total'];

                        $dpDiscount = ($singlePrice / $subtotal) * $amountDP;
                        $dpMenu = $dpDiscount * $qty;
                    }
                    // get dp end

                    if($transcStatus == 4) {
                        $totalDiscount = 0;
                    } else {
                        $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
                    }
                    $allDiscTotal += $totalDiscount;

                    $dataMenu['dpMenu'] = (int)$dpMenu;
                    $dataMenu['totalDiscount'] = $totalDiscount;
                    $dataMenu['gross_sales'] = $dataMenu['single_price'] * $dataMenu['qty'];

                    if ($isConsignment == 0) {
                        $dataMenu['service'] =
                            ($dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund']) * $partnerService / 100;
                        $dataMenu['tax'] = (($dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund']) + $dataMenu['service']) * $partnerTax / 100;
                    } else {
                        $dataMenu['service'] = 0;
                        $dataMenu['tax'] = 0;
                    }

                    if($transcStatus == 4) {
                        $dataMenu['service'] = 0;
                        $dataMenu['tax'] = 0;
                    }

                    $totalService += $dataMenu['service'];
                    $totalTax += $dataMenu['tax'];

                    $totalGrossSales += $dataMenu['gross_sales'];
                    $dataMenu['net_sales'] = $dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund'];
                    $dataMenu['net_sales'] = $dataMenu['net_sales'] + $dataMenu['service'] + $dataMenu['tax'];
                    $dataMenu['net_sales'] = $dataMenu['net_sales'] - $dataMenu['dpMenu'];

                    $allSubtotal += ($dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund']);
                    $totalNetSales += $dataMenu['net_sales'];
                    $dataMenu['gross_profit'] = $dataMenu['net_sales'] - $dataMenu['cogs'];
                    $allRefundTotal += $dataMenu['totalRefund'];

                    array_push($reportNonV, $dataMenu);
                }
            }

            $result = array_reduce($reportV, function ($carry, $item) {
                if (!isset($carry[$item['reportName']])) {
                    $carry[$item['reportName']] = [
                        'cogs' => $item['cogs'],
                        'category' => $item['category'],
                        'dpMenu' => $item['dpMenu'],
                        'gross_profit' => $item['gross_profit'],
                        'gross_sales' => $item['gross_sales'],
                        'id_menu' => $item['id_menu'],
                        'menu_name' => $item['menu_name'],
                        'name' => $item['name'],
                        'net_sales' => $item['net_sales'],
                        'price' => $item['price'],
                        'qty' => $item['qty'],
                        'refundQty' => $item['refundQty'],
                        'reportName' => $item['reportName'],
                        'vg_name' => $item['vg_name'],
                        'sku' => $item['sku'],
                        'single_price' => (int)$item['single_price'],
                        'totalDiscount' => $item['totalDiscount'],
                        'totalRefund' => $item['totalRefund'],
                        'variant_price' => $item['variant_price'],
                    ];
                } else {
                    $carry[$item['reportName']]['cogs'] += $item['cogs'];
                    $carry[$item['reportName']]['dpMenu'] += $item['dpMenu'];
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
            foreach ($result as $val) {
                array_push($reportV, $val);
            }

            if (count($reportNonV) > 0) {
                $result = array_reduce($reportNonV, function ($carry, $item) {
                    if (!isset($carry[$item['id_menu']])) {
                        $carry[$item['id_menu']] = [
                            'id_menu' => $item['id_menu'],
                            'id' => $item['id'],
                            'menu_name' => $item['menu_name'],
                            'vg_name' => $item['vg_name'],
                            'sku' => $item['sku'],
                            'category' => $item['category'],
                            'dpMenu' => $item['dpMenu'],
                            'cogs' => $item['cogs'],
                            'gross_profit' => $item['gross_profit'],
                            'gross_sales' => $item['gross_sales'],
                            'name' => $item['name'],
                            'net_sales' => $item['net_sales'],
                            'price' => $item['price'],
                            'qty' => $item['qty'],
                            'refundQty' => $item['refundQty'],
                            'reportName' => $item['reportName'],
                            'single_price' => (int)$item['single_price'],
                            'totalDiscount' => $item['totalDiscount'],
                            'totalRefund' => $item['totalRefund'],
                            'variant_price' => $item['variant_price'],
                        ];
                    } else {
                        $carry[$item['id_menu']]['cogs'] += $item['cogs'];
                        $carry[$item['id_menu']]['dpMenu'] += $item['dpMenu'];
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

                foreach ($result as $val) {
                    if ($val['net_sales'] == 0) {
                        $val['grossMargin'] = "N/A";
                    } else {
                        $val['grossMargin'] = round(($val['gross_profit'] / $val['net_sales']) * 100, 2);
                    }
                    array_push($res, $val);
                }
            }

            foreach ($reportV as $val) {
                $val['net_sales'] = $val['gross_sales'] - $val['totalDiscount'] - $val['totalRefund'];
                $val['gross_profit'] = $val['net_sales'] - $val['cogs'];

                if ($val['net_sales'] == 0) {
                    $val['grossMargin'] = "N/A";
                } else {
                    $val['grossMargin'] = round(($val['gross_profit'] / $val['net_sales']) * 100, 2);
                }

                array_push($res, $val);
            }

            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    } else {
        if ($all !== "1") {
            $query = "SELECT detail_transaksi.variant, detail_transaksi.is_program, detail_transaksi.id_transaksi, detail_transaksi.harga, menu.nama, menu.id AS id_menu, detail_transaksi.harga_satuan as harga_satuan, detail_transaksi.status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id, menu.hpp, menu.sku, c.name AS cat_name, c.id AS cat_id, c.is_consignment, transaksi.id_partner FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id JOIN categories c ON c.id = menu.id_category WHERE partner.id= '$id' AND transaksi.status IN (1,2,3,4) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
        } else {
            $query = "SELECT detail_transaksi.variant, detail_transaksi.is_program, detail_transaksi.id_transaksi, detail_transaksi.harga, menu.nama, menu.id AS id_menu, detail_transaksi.harga_satuan as harga_satuan, detail_transaksi.status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id, partner.id AS id_partner, menu.hpp, menu.sku, c.name AS cat_name, c.id AS cat_id, c.is_consignment FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id JOIN categories c ON c.id = menu.id_category WHERE partner.id_master= '$idMaster' AND transaksi.status IN (1,2,3,4) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
        }

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
                $isConsignment = $rowMenu['is_consignment'];
                $partnerID = $rowMenu['id_partner'];

                // service
                $qService = "SELECT service FROM partner WHERE id = '$partnerID' AND deleted_at IS NULL";
                $sqlService = mysqli_query($db_conn, $qService);
                $fetchService = mysqli_fetch_assoc($sqlService);
                $partnerService = (int)$fetchService['service'];
                // service end

                // tax
                $qTax = "SELECT tax FROM partner WHERE id = '$partnerID' AND deleted_at IS NULL";
                $sqlTax = mysqli_query($db_conn, $qTax);
                $fetchTax = mysqli_fetch_assoc($sqlTax);
                $partnerTax = (float)$fetchTax['tax'];
                // tax end

                $is_program = $rowMenu['is_program'];
                if ($is_program > 0) {
                    $singlePrice = $rowMenu['harga'];
                }

                $transcStatus = $rowMenu['status'];
                $detailTranscID = $rowMenu['det_transc_id'];

                $reportVar = [];
                $reportVar['category'] = $category;
                $reportVar['cogs'] = (float)$menuCogs * $qty;
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
                $queryDisc = "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.employee_discount_percent, t.id_voucher, t.program_id FROM `transaksi` t WHERE t.id = '$transactionID'";
                $sqlDisc = mysqli_query($db_conn, $queryDisc);
                $dataDisc = mysqli_fetch_assoc($sqlDisc);
                $promo_discount = 0;
                $special_discount = $dataDisc['diskon_spesial'];

                if ($reportVar['refundQty'] == 0) {
                    $reportVar['totalRefund'] = 0;
                } else {
                    $reportVar['totalRefund'] = $reportVar['refundQty'] * $reportVar['single_price'];
                }
                $reportVar['transaction_id'] = $transactionID;
                $reportVar['variant_price'] = 0;
                $reportVar['vg_name'] = "";

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
                    if ($empDiscPercent > 0) {
                        // $employee_discount = (((int)$singlePrice + $reportVar['variant_price']) * $qty) * $empDiscPercent / 100;
                        $employee_discount = ((int)$singlePrice * $qty) * $empDiscPercent / 100;
                    } else {
                        $employee_discount = 0;
                    }

                    // get discount voucher
                    $id_voucher = $dataDisc['id_voucher'];
                    if ($id_voucher) {
                        $voucherDiscount = 0;
                        $sqlVoucher = mysqli_query($db_conn, "SELECT type_id, is_percent, discount, prerequisite FROM voucher WHERE code = '$id_voucher'");
                        $voucher = mysqli_fetch_assoc($sqlVoucher);
                        $prerequisite = json_decode($voucher['prerequisite']);

                        if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 1) {
                            // kalo discount trx percent
                            // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                            $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                            $promo_discount = $voucherDiscount;
                        } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 1) {
                            // kalo discount trx rp
                            $subtotal = 0;
                            $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                            $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                            $subtotal = $fetchTotal['total'];
                            // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                            $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                            $promo_discount = $voucherDiscount * $qty;
                        } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 2) {
                            // discount menu percent
                            if ($menuID == $prerequisite->menu_id) {
                                // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                                $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                $promo_discount = $voucherDiscount;
                            }
                        } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 2) {
                            // discount menu rp
                            $subtotal = 0;
                            if ($menuID == $prerequisite->menu_id) {
                                $sqlTotal = mysqli_query($db_conn, "SELECT harga AS total FROM detail_transaksi WHERE id_transaksi = '$transactionID'");
                                $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                $subtotal = $fetchTotal['total'];
                                // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                                $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                                $promo_discount = $voucherDiscount * $qty;
                            }
                        } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 3) {
                            // discount category menu percent
                            if ($categoryID == $prerequisite->category_id) {
                                // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                                $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                $promo_discount = $voucherDiscount;
                            }
                        } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 3) {
                            // discount category menu rp
                            $subtotal = 0;
                            if ($categoryID == $prerequisite->category_id) {
                                $sqlTotal = mysqli_query($db_conn, "SELECT SUM(dt.harga) AS subtotal FROM `detail_transaksi` dt JOIN `menu` m ON m.id = dt.id_menu JOIN `categories` c ON c.id = m.id_category WHERE id_transaksi = '$transactionID' AND c.id = '$prerequisite->category_id'");
                                $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                $subtotal = $fetchTotal['subtotal'];
                                // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                                $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                                $promo_discount = $voucherDiscount * $qty;
                            }
                        }
                    }
                    // get discount voucher end

                    // get program discount
                    $program_discount = 0;
                    $program_id = $dataDisc['program_id'];
                    if ($program_id > 0) {
                        $discount = $dataDisc['program_discount'];
                        $sqlProgram = mysqli_query($db_conn, "SELECT master_program_id, discount_type, discount_percentage, prerequisite_menu, prerequisite_category FROM programs WHERE id = '$program_id'");
                        $fetchProgram = mysqli_fetch_assoc($sqlProgram);
                        $master_program_id = $fetchProgram['master_program_id'];
                        $discType = $fetchProgram['discount_type'];
                        $discPercent = $fetchProgram['discount_percentage'];

                        if ($master_program_id == 2 && $discType == 1) {
                            // program discount trx
                            // $valueDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                            $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                            $program_discount = $valueDiscount;
                        } else if ($master_program_id == 2 && $discType == 2) {
                            // program discount menu
                            $prerequisite = $fetchProgram['prerequisite_menu'];
                            $prerequisite = json_decode($prerequisite);
                            // periksa menu apakah ada di prerequisite atau tidak
                            $isInPrerequisite = false;
                            foreach ($prerequisite as $req) {
                                if ($req->id == $menuID) {
                                    $isInPrerequisite = true;
                                }
                            }

                            if ($isInPrerequisite == true) {
                                // $valueDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                                $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                                $program_discount = $valueDiscount;
                            }
                        } else if ($master_program_id == 2 && $discType == 3) {
                            // program discount category menu
                            $prerequisite = $fetchProgram['prerequisite_category'];
                            $prerequisite = json_decode($prerequisite);
                            // periksa menu apakah ada di prerequisite atau tidak
                            $isInPrerequisite = false;
                            foreach ($prerequisite as $req) {
                                if ($req->id == $categoryID) {
                                    $isInPrerequisite = true;
                                }
                            }

                            if ($isInPrerequisite == true) {
                                $valueDiscount = (($singlePrice  + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                                $program_discount = $valueDiscount;
                            }
                        }
                    }
                    // get program discount end

                    // get dp
                    $dpMenu = 0;
                    $queryDP = "SELECT amount FROM down_payments WHERE transaction_id = '$transactionID' AND deleted_at IS NULL";
                    $sqlDP = mysqli_query($db_conn, $queryDP);
                    if (mysqli_num_rows($sqlDP) > 0) {
                        $fetchDP = mysqli_fetch_assoc($sqlDP);
                        $amountDP = (float)$fetchDP['amount'];

                        $subtotal = 0;
                        $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                        $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                        $subtotal = $fetchTotal['total'];

                        $dpDiscount = (($singlePrice  + $reportVar['variant_price']) / $subtotal) * $amountDP;
                        $dpMenu = $dpDiscount * $qty;
                        $dpTotal += $dpMenu;
                    }
                    // get dp end

                    if($transcStatus == 4) {
                        $totalDiscount = 0;
                    } else {
                        $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
                    }
                    $allDiscTotal += $totalDiscount;

                    $reportVar['dpMenu'] = (int)$dpMenu;
                    $reportVar['totalDiscount'] = $totalDiscount;
                    // $reportVar['gross_sales'] = ((int)$singlePrice + (int)$variantPrice) * (int)$qty;
                    $reportVar['gross_sales'] = (int)$singlePrice * (int)$qty;

                    if ($isConsignment == 0) {
                        $reportVar['service'] =
                            ($reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund']) * $partnerService / 100;
                        $reportVar['tax'] = (($reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund']) + $reportVar['service']) * $partnerTax / 100;
                    } else {
                        $reportVar['service'] = 0;
                        $reportVar['tax'] = 0;
                    }

                    if($transcStatus == 4) {
                        $reportVar['service'] = 0;
                        $reportVar['tax'] = 0;
                    }

                    $totalService += $reportVar['service'];
                    $totalTax += $reportVar['tax'];

                    $totalGrossSales += $reportVar['gross_sales'];
                    $reportVar['net_sales'] = $reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund'];
                    $reportVar['net_sales'] = $reportVar['net_sales'] + $reportVar['service'] + $reportVar['tax'];
                    $reportVar['net_sales'] = $reportVar['net_sales'] - $reportVar['dpMenu'] ;
                    
                    $allSubtotal += ($reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund']);
                    $totalNetSales += $reportVar['net_sales'];

                    $reportVar['gross_profit'] = $reportVar['net_sales'] - $reportVar['cogs'];
                    $allRefundTotal += $reportVar['totalRefund'];

                    array_push($reportV, $reportVar);
                } else {
                    // buat yang tidak ada variant nya
                    $dataMenu = [];

                    $dataMenu['cogs'] = (float)$menuCogs * $qty;
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
                    $sqlDisc = mysqli_query($db_conn, "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.id_voucher, t.employee_discount_percent, t.program_id FROM `transaksi` t WHERE t.id = '$transactionID'");
                    $dataDisc = mysqli_fetch_assoc($sqlDisc);
                    $promo_discount = 0;
                    $special_discount = $dataDisc['diskon_spesial'];
                    $empDiscPercent = (int)$dataDisc['employee_discount_percent'];
                    if ($empDiscPercent > 0) {
                        $employee_discount = ($singlePrice * $qty) * $empDiscPercent / 100;
                    } else {
                        $employee_discount = 0;
                    }

                    // get discount voucher
                    $id_voucher = $dataDisc['id_voucher'];
                    if ($id_voucher) {
                        $voucherDiscount = 0;
                        $sqlVoucher = mysqli_query($db_conn, "SELECT type_id, is_percent, discount, prerequisite FROM voucher WHERE code = '$id_voucher'");
                        $voucher = mysqli_fetch_assoc($sqlVoucher);
                        $prerequisite = json_decode($voucher['prerequisite']);

                        if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 1) {
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
                            if ($menuID == $prerequisite->menu_id) {
                                $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                $promo_discount = $voucherDiscount;
                            }
                        } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 2) {
                            // discount menu rp
                            $subtotal = 0;
                            if ($menuID == $prerequisite->menu_id) {
                                $sqlTotal = mysqli_query($db_conn, "SELECT harga AS total FROM detail_transaksi WHERE id_transaksi = '$transactionID'");
                                $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                $subtotal = $fetchTotal['total'];
                                $voucherDiscount = ($singlePrice / $subtotal) * $voucher['discount'];
                                $promo_discount = $voucherDiscount * $qty;
                            }
                        } else if ($voucher['is_percent'] == 1 && $voucher['type_id'] == 3) {
                            // discount category menu percent
                            if ($categoryID == $prerequisite->category_id) {
                                $voucherDiscount = ($singlePrice * $qty) * $voucher['discount'] / 100;
                                $promo_discount = $voucherDiscount;
                            }
                        } else if ($voucher['is_percent'] == 0 && $voucher['type_id'] == 3) {
                            // discount category menu rp
                            $subtotal = 0;
                            if ($categoryID == $prerequisite->category_id) {
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
                    if ($program_id > 0) {
                        $discount = $dataDisc['program_discount'];
                        $sqlProgram = mysqli_query($db_conn, "SELECT master_program_id, discount_type, discount_percentage, prerequisite_menu, prerequisite_category FROM programs WHERE id = '$program_id'");
                        $fetchProgram = mysqli_fetch_assoc($sqlProgram);
                        $master_program_id = $fetchProgram['master_program_id'];
                        $discType = $fetchProgram['discount_type'];
                        $discPercent = $fetchProgram['discount_percentage'];

                        if ($master_program_id == 2 && $discType == 1) {
                            // program discount trx
                            $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                            $program_discount = $valueDiscount;
                        } else if ($master_program_id == 2 && $discType == 2) {
                            // program discount menu
                            $prerequisite = $fetchProgram['prerequisite_menu'];
                            $prerequisite = json_decode($prerequisite);
                            // periksa menu apakah ada di prerequisite atau tidak
                            $isInPrerequisite = false;
                            foreach ($prerequisite as $req) {
                                if ($req->id == $menuID) {
                                    $isInPrerequisite = true;
                                }
                            }

                            if ($isInPrerequisite == true) {
                                $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                                $program_discount = $valueDiscount;
                            }
                        } else if ($master_program_id == 2 && $discType == 3) {
                            // program discount category menu
                            $prerequisite = $fetchProgram['prerequisite_category'];
                            $prerequisite = json_decode($prerequisite);
                            // periksa category menu apakah ada di prerequisite atau tidak
                            $isInPrerequisite = false;
                            foreach ($prerequisite as $req) {
                                if ($req->id == $categoryID) {
                                    $isInPrerequisite = true;
                                }
                            }

                            if ($isInPrerequisite == true) {
                                $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                                $program_discount = $valueDiscount;
                            }
                        }
                    }
                    // get program discount end

                    // get dp
                    $dpMenu = 0;
                    $queryDP = "SELECT amount FROM down_payments WHERE transaction_id = '$transactionID' AND deleted_at IS NULL";
                    $sqlDP = mysqli_query($db_conn, $queryDP);
                    if (mysqli_num_rows($sqlDP) > 0) {
                        $fetchDP = mysqli_fetch_assoc($sqlDP);
                        $amountDP = (float)$fetchDP['amount'];

                        $subtotal = 0;
                        $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                        $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                        $subtotal = $fetchTotal['total'];

                        $dpDiscount = ($singlePrice / $subtotal) * $amountDP;
                        $dpMenu = $dpDiscount * $qty;
                        $dpTotal += $dpMenu;
                    }
                    // get dp end

                    if($transcStatus == 4) {
                        $totalDiscount = 0;
                    } else {
                        $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
                    }
                    $allDiscTotal += $totalDiscount;
                    // get discount end

                    $dataMenu['dpMenu'] = (int)$dpMenu;
                    $dataMenu['totalDiscount'] = $totalDiscount;
                    $dataMenu['gross_sales'] = $dataMenu['single_price'] * $dataMenu['qty'];

                    if ($isConsignment == 0) {
                        $dataMenu['service'] =
                            ($dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund']) * $partnerService / 100;
                        $dataMenu['tax'] = (($dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund']) + $dataMenu['service']) * $partnerTax / 100;
                    } else {
                        $dataMenu['service'] = 0;
                        $dataMenu['tax'] = 0;
                    }

                    if($transcStatus == 4) {
                        $dataMenu['service'] = 0;
                        $dataMenu['tax'] = 0;
                    }

                    $totalService += $dataMenu['service'];
                    $totalTax += $dataMenu['tax'];

                    $totalGrossSales += $dataMenu['gross_sales'];
                    $dataMenu['net_sales'] = $dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund'];
                    $dataMenu['net_sales'] = $dataMenu['net_sales'] + $dataMenu['service'] + $dataMenu['tax'];
                    $dataMenu['net_sales'] = $dataMenu['net_sales'] - $dataMenu['dpMenu'];

                    $allSubtotal += ($dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund']);
                    $totalNetSales += $dataMenu['net_sales'];
                    $dataMenu['gross_profit'] = $dataMenu['net_sales'] - $dataMenu['cogs'];
                    $allRefundTotal += $dataMenu['totalRefund'];

                    array_push($reportNonV, $dataMenu);
                }
            }

            $result = array_reduce($reportV, function ($carry, $item) {
                if (!isset($carry[$item['reportName']])) {
                    $carry[$item['reportName']] = [
                        'cogs' => $item['cogs'],
                        'category' => $item['category'],
                        'service' => $item['service'],
                        'tax' => $item['tax'],
                        'depMenu' => $item['dpMenu'],
                        'gross_profit' => $item['gross_profit'],
                        'gross_sales' => $item['gross_sales'],
                        'id_menu' => $item['id_menu'],
                        'menu_name' => $item['menu_name'],
                        'name' => $item['name'],
                        'net_sales' => $item['net_sales'],
                        'price' => $item['price'],
                        'qty' => $item['qty'],
                        'refundQty' => $item['refundQty'],
                        'reportName' => $item['reportName'],
                        'vg_name' => $item['vg_name'],
                        'sku' => $item['sku'],
                        'single_price' => (int)$item['single_price'],
                        'totalDiscount' => $item['totalDiscount'],
                        'totalRefund' => $item['totalRefund'],
                        'variant_price' => $item['variant_price'],
                    ];
                } else {
                    $carry[$item['reportName']]['cogs'] += $item['cogs'];
                    $carry[$item['reportName']]['service'] += $item['service'];
                    $carry[$item['reportName']]['tax'] += $item['tax'];
                    $carry[$item['reportName']]['dpMenu'] += $item['dpMenu'];
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
            foreach ($result as $val) {
                array_push($reportV, $val);
            }

            if (count($reportNonV) > 0) {
                $result = array_reduce($reportNonV, function ($carry, $item) {
                    if (!isset($carry[$item['id_menu']])) {
                        $carry[$item['id_menu']] = [
                            'id_menu' => $item['id_menu'],
                            'id' => $item['id'],
                            'menu_name' => $item['menu_name'],
                            'vg_name' => $item['vg_name'],
                            'sku' => $item['sku'],
                            'category' => $item['category'],
                            'service' => $item['service'],
                            'tax' => $item['tax'],
                            'dpMenu' => $item['dpMenu'],
                            'cogs' => $item['cogs'],
                            'gross_profit' => $item['gross_profit'],
                            'gross_sales' => $item['gross_sales'],
                            'name' => $item['name'],
                            'net_sales' => $item['net_sales'],
                            'price' => $item['price'],
                            'qty' => $item['qty'],
                            'refundQty' => $item['refundQty'],
                            'reportName' => $item['reportName'],
                            'single_price' => (int)$item['single_price'],
                            'totalDiscount' => $item['totalDiscount'],
                            'totalRefund' => $item['totalRefund'],
                            'variant_price' => $item['variant_price'],
                        ];
                    } else {
                        $carry[$item['id_menu']]['cogs'] += $item['cogs'];
                        $carry[$item['id_menu']]['service'] += $item['service'];
                        $carry[$item['id_menu']]['tax'] += $item['tax'];
                        $carry[$item['id_menu']]['dpMenu'] += $item['dpMenu'];
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

                foreach ($result as $val) {
                    if ($val['net_sales'] == 0) {
                        $val['grossMargin'] = "N/A";
                    } else {
                        $val['grossMargin'] = round(($val['gross_profit'] / $val['net_sales']) * 100, 2);
                    }
                    array_push($res, $val);
                }
            }

            foreach ($reportV as $val) {
                $val['gross_profit'] = $val['net_sales'] - $val['cogs'];

                if ($val['net_sales'] == 0) {
                    $val['grossMargin'] = "N/A";
                } else {
                    $val['grossMargin'] = round(($val['gross_profit'] / $val['net_sales']) * 100, 2);
                }

                array_push($res, $val);
            }

            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    }
}

echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "itemSales" => $res, "dpdTotal" => $dpTotal, "refundTotal"=>$allRefundTotal, "totalDiscountAll"=>$allDiscTotal, "totalService"=>$totalService, "totalTax"=>$totalTax, "totalGrossSales"=>$totalGrossSales, "totalNetSales"=>$totalNetSales, "allSubtotal"=>$allSubtotal]);
