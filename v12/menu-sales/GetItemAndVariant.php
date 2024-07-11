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
$reportNonV = array();
$res = array();
$resIndex = 0;
$dpTotal = 0;
$allRefundTotal = 0;
$allDiscTotal = 0;
$totalService = 0;
$totalTax = 0;
$totalCollected = 0;
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
            //var append programs
            $appendVoucher = "(SELECT t.id as transactionID, voucher.id, voucher.type_id, voucher.is_percent, voucher.discount, voucher.prerequisite FROM voucher LEFT JOIN transaksi t ON voucher.id = t.id_voucher) as vTemp";

            $appendProgram = "(SELECT master_program_id, discount_type, discount_percentage, prerequisite_menu, prerequisite_category, id FROM programs) as pTemp";
            
            $appendDisc = "(SELECT dt.id, t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.id_voucher, t.employee_discount_percent, t.program_id,dt.id as detail_id ,SUM(dt.qty) as counter, t.total, vTemp.type_id, vTemp.discount, vTemp.prerequisite, vTemp.is_percent, pTemp.master_program_id, pTemp.discount_type, pTemp.discount_percentage, pTemp.prerequisite_menu, pTemp.prerequisite_category FROM `transaksi` t LEFT JOIN detail_transaksi dt ON dt.id_transaksi = t.id LEFT JOIN " . $appendVoucher . " ON vTemp.id = t.id_voucher LEFT JOIN " . $appendProgram .  " ON pTemp.id = t.program_id WHERE t.deleted_at is NULL AND t.organization='Natta' AND dt.deleted_at is NULL AND dt.status !=4 GROUP BY dt.id) discTemp";
            
            $query = "SELECT detail_transaksi.id, detail_transaksi.variant, detail_transaksi.is_program, detail_transaksi.id_transaksi, detail_transaksi.harga, menu.nama, menu.id AS id_menu, detail_transaksi.harga_satuan as harga_satuan, detail_transaksi.status AS menu_status, transaksi.status AS transc_status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id, menu.hpp, menu.sku, c.name AS cat_name, c.id AS cat_id, c.is_consignment, transaksi.id_partner, transaksi.tax, transaksi.service, transaksi.total as totalTrx, transaksi.rounding ,discTemp.id, discTemp.program_discount, discTemp.promo, discTemp.diskon_spesial, discTemp.employee_discount, discTemp.id_voucher, discTemp.employee_discount_percent, discTemp.program_id, discTemp.detail_id, discTemp.counter, discTemp.total, discTemp.type_id, discTemp.discount, discTemp.prerequisite, discTemp.is_percent, dp.amount FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id JOIN categories c ON c.id = menu.id_category LEFT JOIN " . $appendDisc . " ON discTemp.id = detail_transaksi.id LEFT JOIN down_payments dp ON dp.transaction_id = transaksi.id  WHERE partner.id= '$id' AND transaksi.status IN (1,2,3,4) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
            
            
        } else {
            $query = "SELECT detail_transaksi.id, detail_transaksi.variant, detail_transaksi.is_program, detail_transaksi.id_transaksi, detail_transaksi.harga, menu.nama, menu.id AS id_menu, detail_transaksi.harga_satuan as harga_satuan, detail_transaksi.status AS menu_status, transaksi.status AS transc_status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id, partner.id AS id_partner, menu.hpp, menu.sku, c.name AS cat_name, c.id AS cat_id, c.is_consignment FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id JOIN categories c ON c.id = menu.id_category WHERE partner.id_master= '$idMaster' AND transaksi.status IN (1,2,3,4) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
        }

        $fav = mysqli_query($db_conn, $query);
        if (mysqli_num_rows($fav) > 0) {

            // $var_id_menu = "";
            // $var_id = "";
            // $var_menu_name = "";
            // $var_vg_name = "";
            // $var_sku = "";
            // $var_category = 0;
            // $var_service = 0;
            // $var_tax = 0;
            // $var_dpMenu = 0;
            // $var_rounding = 0;
            // $var_cogs = 0;
            // $var_gross_profit = 0;
            // $var_gross_sales = 0;
            // $var_name = "";
            // $var_total_collected = 0;
            // $var_total_before_rounding = 0;
            // $var_net_sales = 0;
            // $var_price = 0;
            // $var_qty = 0;
            // $var_refundQty = 0;
            // $var_reportName = "";
            // $var_single_price = 0;
            // $var_totalDiscount = 0;
            // $var_totalRefund = 0;
            // $var_variant_price = 0;
            // $var_grossMargin = 0;

            // $nonVar_id_menu = "";
            // $nonVar_id = "";
            // $nonVar_menu_name = "";
            // $nonVar_vg_name = "";
            // $nonVar_sku = "";
            // $nonVar_category = "";
            // $nonVar_service = 0;
            // $nonVar_tax = 0;
            // $nonVar_dpMenu = 0;
            // $nonVar_rounding = 0;
            // $nonVar_cogs = 0;
            // $nonVar_gross_profit = 0;
            // $nonVar_gross_sales = 0;
            // $nonVar_name = "";
            // $nonVar_total_collected = 0;
            // $nonVar_total_before_rounding = 0;
            // $nonVar_net_sales = 0;
            // $nonVar_price = 0;
            // $nonVar_qty = 0;
            // $nonVar_refundQty = 0;
            // $nonVar_reportName = "";
            // $nonVar_single_price = 0;
            // $nonVar_totalDiscount = 0;
            // $nonVar_totalRefund = 0;
            // $nonVar_variant_price = 0;
            // $nonVar_grossMargin = 0;

            $nonV = 0;
            $v = 0;
            while ($rowMenu = mysqli_fetch_assoc($fav)) {
                $totalDiscount = 0;
                $detail_id = $rowMenu['id'];
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
                // $qService = "SELECT service FROM transaksi WHERE id = '$transactionID' AND deleted_at IS NULL";
                // $sqlService = mysqli_query($db_conn, $qService);
                // $fetchService = mysqli_fetch_assoc($sqlService);
                // $partnerService = (int)$fetchService['service'];
                $partnerService = (int)$rowMenu['service'];
                // service end

                // tax
                // $qTax = "SELECT tax FROM transaksi WHERE id = '$transactionID' AND deleted_at IS NULL";
                // $sqlTax = mysqli_query($db_conn, $qTax);
                // $fetchTax = mysqli_fetch_assoc($sqlTax);
                // $partnerTax = (float)$fetchTax['tax'];
                $partnerTax = (float)$rowMenu['tax'];
                // tax end

                $is_program = $rowMenu['is_program'];
                if ($is_program > 0) {
                    $singlePrice = $rowMenu['harga'];
                }

                $transcStatus = $rowMenu['transc_status'];
                $menuStatus = $rowMenu['menu_status'];
                $detailTranscID = $rowMenu['det_transc_id'];

                $reportVar = [];
                $reportVar['category'] = $category;
                $reportVar['cogs'] = (float)$menuCogs * $qty;
                $reportVar['id'] = "";
                $reportVar['id_menu'] = $menuID;
                $reportVar['menu_name'] = $namaMenu;
                $reportVar['name'] = "";
                $reportVar['total_before_rounding'] = "";
                $reportVar['total_collected'] = "";
                $reportVar['net_sales'] = "";
                $reportVar['price'] = 0;
                $reportVar['qty'] = $qty;
                if ($transcStatus == "4" || $menuStatus == "4") {
                    $reportVar['refundQty'] = $qty;
                } else {
                    $reportVar['refundQty'] = 0;
                }
                $reportVar['reportName'] = $namaMenu;
                $reportVar['single_price'] = (int)$singlePrice;
                $reportVar['sku'] = $sku;

                // get discount
                // $queryDisc = "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.employee_discount_percent, t.id_voucher, t.program_id FROM `transaksi` t WHERE t.id = '$transactionID'";
                // $queryDisc = "SELECT dt.id, t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.id_voucher, t.employee_discount_percent, t.program_id,dt.id as detail_id ,SUM(dt.qty) as counter, t.total FROM `transaksi` t LEFT JOIN detail_transaksi dt ON dt.id_transaksi = t.id WHERE t.id = '$transactionID' AND t.deleted_at is NULL AND dt.deleted_at is NULL AND dt.status !=4 GROUP BY dt.id_transaksi ";
                // $sqlDisc = mysqli_query($db_conn, $queryDisc);
                // $dataDisc = mysqli_fetch_assoc($sqlDisc);
                $promo_discount = 0;
                $special_discount = $rowMenu['diskon_spesial'];

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
                            if ($transcStatus == "4" || $menuStatus == "4") {
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

                    $empDiscPercent = (int)$rowMenu['employee_discount_percent'];
                    if ($empDiscPercent > 0) {
                        // $employee_discount = (((int)$singlePrice + $reportVar['variant_price']) * $qty) * $empDiscPercent / 100;
                        $employee_discount = ((int)$singlePrice * $qty) * $empDiscPercent / 100;
                    } else {
                        $employee_discount = 0;
                    }

                    // get discount voucher
                    $id_voucher = $rowMenu['id_voucher'];
                    if ($id_voucher) {
                        $voucherDiscount = 0;
                        // $sqlVoucher = mysqli_query($db_conn, "SELECT type_id, is_percent, discount, prerequisite FROM voucher WHERE code = '$id_voucher'");
                        // $voucher = mysqli_fetch_assoc($sqlVoucher);
                        $prerequisite = json_decode($rowMenu['prerequisite']);

                        if ($rowMenu['is_percent'] == 1 && $rowMenu['type_id'] == 1) {
                            // kalo discount trx percent
                            // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                            $voucherDiscount = ($singlePrice * $qty) * $rowMenu['discount'] / 100;
                            $promo_discount = $voucherDiscount;
                        } else if ($rowMenu['is_percent'] == 0 && $rowMenu['type_id'] == 1) {
                            // kalo discount trx rp
                            $subtotal = 0;
                            // $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                            // $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                            $subtotal = $rowMenu['totalTrx'];
                            // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                            $voucherDiscount = ($singlePrice / $subtotal) * $rowMenu['discount'];
                            $promo_discount = $voucherDiscount * $qty;
                        } else if ($rowMenu['is_percent'] == 1 && $rowMenu['type_id'] == 2) {
                            // discount menu percent
                            if ($menuID == $prerequisite->menu_id) {
                                // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                                $voucherDiscount = ($singlePrice * $qty) * $rowMenu['discount'] / 100;
                                $promo_discount = $voucherDiscount;
                            }
                        } else if ($rowMenu['is_percent'] == 0 && $rowMenu['type_id'] == 2) {
                            // discount menu rp
                            $subtotal = 0;
                            if ($menuID == $prerequisite->menu_id) {
                                // $sqlTotal = mysqli_query($db_conn, "SELECT harga AS total FROM detail_transaksi WHERE id_transaksi = '$transactionID'");
                                // $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                $subtotal = $rowMenu['total'];
                                // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                                $voucherDiscount = ($singlePrice / $subtotal) * $rowMenu['discount'];
                                $promo_discount = $voucherDiscount * $qty;
                            }
                        } else if ($rowMenu['is_percent'] == 1 && $rowMenu['type_id'] == 3) {
                            // discount category menu percent
                            if ($categoryID == $prerequisite->category_id) {
                                // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $voucher['discount'] / 100;
                                $voucherDiscount = ($singlePrice * $qty) * $rowMenu['discount'] / 100;
                                $promo_discount = $voucherDiscount;
                            }
                        } else if ($rowMenu['is_percent'] == 0 && $rowMenu['type_id'] == 3) {
                            // discount category menu rp
                            $subtotal = 0;
                            if ($categoryID == $prerequisite->category_id) {
                                $sqlTotal = mysqli_query($db_conn, "SELECT SUM(dt.harga) AS subtotal FROM `detail_transaksi` dt JOIN `menu` m ON m.id = dt.id_menu JOIN `categories` c ON c.id = m.id_category WHERE id_transaksi = '$transactionID' AND c.id = '$prerequisite->category_id'");
                                $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                $subtotal = $fetchTotal['subtotal'];
                                // $voucherDiscount = (($singlePrice + $reportVar['variant_price']) / $subtotal) * $voucher['discount'];
                                $voucherDiscount = ($singlePrice / $subtotal) * $rowMenu['discount'];
                                $promo_discount = $voucherDiscount * $qty;
                            }
                        }
                    }
                    // get discount voucher end

                    // get program discount
                    $program_discount = 0;
                    $program_id = $rowMenu['program_id'];
                    $discount = $rowMenu['program_discount'];
                    if ($program_id > 0) {
                        // $sqlProgram = mysqli_query($db_conn, "SELECT master_program_id, discount_type, discount_percentage, prerequisite_menu, prerequisite_category FROM programs WHERE id = '$program_id'");
                        // $fetchProgram = mysqli_fetch_assoc($sqlProgram);
                        $master_program_id = $rowMenu['master_program_id'];
                        $discType = $rowMenu['discount_type'];
                        $discPercent = $rowMenu['discount_percentage'];

                        if ($master_program_id == 2 && $discType == 1) {
                            // program discount trx
                            // $valueDiscount = (($singlePrice + $reportVar['variant_price']) * $qty) * $discPercent / 100;
                            $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                            $program_discount = $valueDiscount;
                        } else if ($master_program_id == 2 && $discType == 2) {
                            // program discount menu
                            $prerequisite = $rowMenu['prerequisite_menu'];
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
                            $prerequisite = $rowMenu['prerequisite_category'];
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
                    } else if($discount > 0) {
                        $program_discount = $discount * ($qty * $singlePrice) / $rowMenu["total"];
                    }
                    // get program discount end

                    // get dp
                    $dpMenu = 0;
                    // $queryDP = "SELECT amount FROM down_payments WHERE transaction_id = '$transactionID' AND deleted_at IS NULL";
                    // $sqlDP = mysqli_query($db_conn, $queryDP);
                    if (mysqli_num_rows($sqlDP) > 0) {
                        // $fetchDP = mysqli_fetch_assoc($sqlDP);
                        $amountDP = (float)$rowMenu['amount'];

                        $subtotal = 0;
                        // $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                        // $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                        $subtotal = $rowMenu['totalTrx'];

                        $dpDiscount = (($singlePrice  + $reportVar['variant_price']) / $subtotal) * $amountDP;
                        $dpMenu = $dpDiscount * $qty;
                        $dpTotal += $dpMenu;
                    }
                    // get dp end

                    // get rounding
                    $rounding = 0;
                    // $queryRounding = "SELECT rounding FROM transaksi WHERE id = '$transactionID' AND deleted_at IS NULL";
                    // $sqlRounding = mysqli_query($db_conn, $queryRounding);
                    if ($rowMenu["rounding"]) {
                        // $fetchRounding = mysqli_fetch_assoc($sqlRounding);
                        $amountRounding = (int)$rowMenu['rounding'];

                        $subtotal = 0;
                        // $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                        // $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                        $subtotal = $rowMenu['totalTrx'];

                        $roundingPerDetail = ($singlePrice / $subtotal) * $amountRounding;
                        $rounding = $roundingPerDetail * $qty;
                    }
                    // get rounding end

                    if($transcStatus == 4 || $menuStatus == 4) {
                        $totalDiscount = 0;
                    } else {
                        $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
                    }
                    $allDiscTotal += $totalDiscount;

                    $reportVar['dpMenu'] = (int)$dpMenu;
                    $reportVar['rounding'] = (int)$rounding;
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

                    if($transcStatus == 4 || $menuStatus == 4) {
                        $reportVar['service'] = 0;
                        $reportVar['tax'] = 0;
                    }

                    $totalService += $reportVar['service'];
                    $totalTax += $reportVar['tax'];

                    $totalGrossSales += $reportVar['gross_sales'];
                    $reportVar['total_collected'] = $reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund'];
                    $reportVar['net_sales'] = $reportVar['total_collected'];
                    $reportVar['total_collected'] = $reportVar['total_collected'] + $reportVar['service'] + $reportVar['tax'];
                    $reportVar['total_collected'] = $reportVar['total_collected'] - $reportVar['dpMenu'] + $reportVar['rounding'];
                    $reportVar['total_before_rounding'] = $reportVar['total_collected'] - $reportVar['rounding']; 
                    if($reportVar['total_before_rounding'] <= 0){
                        $reportVar['rounding'] = 0;
                        $reportVar['total_collected'] = 0;
                    }
                    if($reportVar['total_collected'] <= 0 ){
                        $reportVar['total_collected'] = 0; 
                    }
                    $allSubtotal += ($reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund']);
                    $totalCollected += $reportVar['total_collected'];

                    $reportVar['gross_profit'] = $reportVar['total_collected'] - $reportVar['cogs'];
                    $reportVar['grossMargin'] = round(($reportVar['gross_profit'] / $reportVar['total_collected']) * 100, 2);
                    $allRefundTotal += $reportVar['totalRefund'];
                    
                    //recreating array reduce with in the loop so it will onlybe looped once
                    // if($var_reportName == "" || $var_menu_name != $reportVar["reportName"]){
                    //     $data = [
                    //         'cogs' => $var_cogs,
                    //         'category' => $var_category,
                    //         'service' => $var_service,
                    //         'tax' => $var_tax,
                    //         'dpMenu' => $var_dpMenu,
                    //         'rounding' => $var_rounding,
                    //         'gross_profit' => $var_gross_profit,
                    //         'gross_sales' => $var_gross_sales,
                    //         'id_menu' => $var_id_menu,
                    //         'menu_name' => $var_menu_name,
                    //         'name' => $var_name,
                    //         'total_collected' => $var_total_collected,
                    //         'total_before_rounding' => $var_total_before_rounding,
                    //         'net_sales' => $var_net_sales,
                    //         'price' => $var_price,
                    //         'qty' => $var_qty,
                    //         'refundQty' => $var_refundQty,
                    //         'reportName' => $var_reportName,
                    //         'vg_name' => $var_vg_name,
                    //         'sku' => $var_sku,
                    //         'single_price' => (int)$var_single_price,
                    //         'totalDiscount' => $var_totalDiscount,
                    //         'totalRefund' => $var_totalRefund,
                    //         'variant_price' => $var_variant_price,
                    //         'grossMargin' => $grossMargin,
                    //     ];
                    //     if($var_reportName != ""){
                    //         $res[$resIndex] = $data;
                    //         $resIndex++;
                    //     }
                        
                    //     $var_id_menu = $reportVar["id_menu"];
                    //     $var_id = $reportVar["id"];
                    //     $var_menu_name = $reportVar["menu_name"];
                    //     $var_vg_name = $reportVar["vg_name"];
                    //     $var_sku = $reportVar["sku"];
                    //     $var_category = $reportVar["category"];
                    //     $var_service = $reportVar["service"];
                    //     $var_tax = $reportVar["tax"];
                    //     $var_dpMenu = $reportVar["dpMenu"];
                    //     $var_rounding = $reportVar["rounding"];
                    //     $var_cogs = $reportVar["cogs"];
                    //     $var_gross_profit = $reportVar["gross_profit"];
                    //     $var_gross_sales = $reportVar["gross_sales"];
                    //     $var_name = $reportVar["name"];
                    //     $var_total_collected = $reportVar["total_collected"];
                    //     $var_total_before_rounding = $reportVar["total_before_rounding"];
                    //     $var_net_sales = $reportVar["net_sales"];
                    //     $var_price = $reportVar["price"];
                    //     $var_qty = $reportVar["qty"];
                    //     $var_refundQty = $reportVar["refundQty"];
                    //     $var_reportName = $reportVar["reportName"];
                    //     $var_single_price = $reportVar["single_price"];
                    //     $var_totalDiscount = $reportVar["totalDiscount"];
                    //     $var_totalRefund = $reportVar["totalRefund"];
                    //     $var_variant_price = $reportVar["variant_price"];
                    //     $var_grossMargin = $reportVar["grossMargin"];
                    // } else {
                    //     $var_service += $reportVar["service"];
                    //     $var_tax += $reportVar["tax"];
                    //     $var_dpMenu += $reportVar["dpMenu"];
                    //     $var_rounding += $reportVar["rounding"];
                    //     $var_cogs += $reportVar["cogs"];
                    //     $var_gross_profit += $reportVar["gross_profit"];
                    //     $var_gross_sales += $reportVar["gross_sales"];
                    //     $var_total_collected += $reportVar["total_collected"];
                    //     $var_total_before_rounding += $reportVar["total_before_rounding"];
                    //     $var_net_sales += $reportVar["net_sales"];
                    //     $var_price += $reportVar["price"];
                    //     $var_qty += $reportVar["qty"];
                    //     $var_refundQty += $reportVar["refundQty"];
                    //     $var_totalDiscount += $reportVar["totalDiscount"];
                    //     $var_totalRefund += $reportVar["totalRefund"];
                    //     $var_grossMargin += $reportVar["grossMargin"];
                    // }

                    $reportV[$v] = $reportVar;
                    $v++;

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
                    if ($transcStatus == "4" || $menuStatus == "4") {
                        $dataMenu['refundQty'] = $qty;
                        $dataMenu['totalRefund'] = $dataMenu['refundQty'] * $dataMenu['single_price'];
                    }
                    // get refund end

                    // get discount
                    // $sqlDisc = mysqli_query($db_conn, "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.id_voucher, t.employee_discount_percent, t.program_id FROM `transaksi` t WHERE t.id = '$transactionID'");
                    // $sqlDisc = mysqli_query($db_conn, "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.id_voucher, t.employee_discount_percent, t.program_id, SUM(dt.qty) as counter, t.total FROM `transaksi` t LEFT JOIN detail_transaksi dt ON dt.id_transaksi = t.id WHERE t.id = '$transactionID' AND t.deleted_at is NULL AND dt.deleted_at is NULL AND dt.status !=4 GROUP BY dt.id_transaksi");
                    // $dataDisc = mysqli_fetch_assoc($sqlDisc);
                    // $promo_discount = 0;
                    $special_discount = $rowMenu['diskon_spesial'];
                    $empDiscPercent = (int)$rowMenu['employee_discount_percent'];
                    if ($empDiscPercent > 0) {
                        $employee_discount = ($singlePrice * $qty) * $empDiscPercent / 100;
                    } else {
                        $employee_discount = 0;
                    }

                    // get discount voucher
                    $id_voucher = $rowMenu['id_voucher'];
                    if ($id_voucher) {
                        $voucherDiscount = 0;
                        // $sqlVoucher = mysqli_query($db_conn, "SELECT type_id, is_percent, discount, prerequisite FROM voucher WHERE code = '$id_voucher'");
                        // $voucher = mysqli_fetch_assoc($sqlVoucher);
                        // $prerequisite = json_decode($voucher['prerequisite']);

                        if ($rowMenu['is_percent'] == 1 && $rowMenu['type_id'] == 1) {
                            // kalo discount trx percent
                            $voucherDiscount = ($singlePrice * $qty) * $rowMenu['discount'] / 100;
                            $promo_discount = $voucherDiscount;
                        } else if ($rowMenu['is_percent'] == 0 && $rowMenu['type_id'] == 1) {
                            // kalo discount trx rp
                            $subtotal = 0;
                            // $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                            // $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                            $subtotal = $rowMenu['totalTrx'];
                            $voucherDiscount = ($singlePrice / $subtotal) * $rowMenu['discount'];
                            $promo_discount = $voucherDiscount * $qty;
                        } else if ($rowMenu['is_percent'] == 1 && $rowMenu['type_id'] == 2) {
                            // discount menu percent
                            if ($menuID == $prerequisite->menu_id) {
                                $voucherDiscount = ($singlePrice * $qty) * $rowMenu['discount'] / 100;
                                $promo_discount = $voucherDiscount;
                            }
                        } else if ($rowMenu['is_percent'] == 0 && $rowMenu['type_id'] == 2) {
                            // discount menu rp
                            $subtotal = 0;
                            if ($menuID == $prerequisite->menu_id) {
                                // $sqlTotal = mysqli_query($db_conn, "SELECT harga AS total FROM detail_transaksi WHERE id_transaksi = '$transactionID'");
                                // $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                $subtotal = $rowMenu['harga'];
                                $voucherDiscount = ($singlePrice / $subtotal) * $rowMenu['discount'];
                                $promo_discount = $voucherDiscount * $qty;
                            }
                        } else if ($rowMenu['is_percent'] == 1 && $rowMenu['type_id'] == 3) {
                            // discount category menu percent
                            if ($categoryID == $prerequisite->category_id) {
                                $voucherDiscount = ($singlePrice * $qty) * $rowMenu['discount'] / 100;
                                $promo_discount = $voucherDiscount;
                            }
                        } else if ($rowMenu['is_percent'] == 0 && $rowMenu['type_id'] == 3) {
                            // discount category menu rp
                            $subtotal = 0;
                            if ($categoryID == $prerequisite->category_id) {
                                $sqlTotal = mysqli_query($db_conn, "SELECT SUM(dt.harga) AS subtotal FROM `detail_transaksi` dt JOIN `menu` m ON m.id = dt.id_menu JOIN `categories` c ON c.id = m.id_category WHERE id_transaksi = '$transactionID' AND c.id = '$prerequisite->category_id'");
                                $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                                $subtotal = $fetchTotal['subtotal'];
                                $voucherDiscount = ($singlePrice / $subtotal) * $rowMenu['discount'];
                                $promo_discount = $voucherDiscount * $qty;
                            }
                        }
                    }
                    // get discount voucher end

                    // get program discount
                    $program_discount = 0;
                    $program_id = $rowMenu['program_id'];
                    $discount = $rowMenu['program_discount'];
                    if ($program_id > 0) {
                        // $sqlProgram = mysqli_query($db_conn, "SELECT master_program_id, discount_type, discount_percentage, prerequisite_menu, prerequisite_category FROM programs WHERE id = '$program_id'");
                        // $fetchProgram = mysqli_fetch_assoc($sqlProgram);
                        $master_program_id = $rowMenu['master_program_id'];
                        $discType = $rowMenu['discount_type'];
                        $discPercent = $rowMenu['discount_percentage'];

                        if ($master_program_id == 2 && $discType == 1) {
                            // program discount trx
                            $valueDiscount = ($singlePrice * $qty) * $discPercent / 100;
                            $program_discount = $valueDiscount;
                        } else if ($master_program_id == 2 && $discType == 2) {
                            // program discount menu
                            $prerequisite = $rowMenu['prerequisite_menu'];
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
                            $prerequisite = $rowMenu['prerequisite_category'];
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
                    } else if($discount > 0){
                        $program_discount = $discount *($qty * $singlePrice) / $rowMenu["total"];
                    }
                    // get program discount end

                    // get dp
                    $dpMenu = 0;
                    // $queryDP = "SELECT amount FROM down_payments WHERE transaction_id = '$transactionID' AND deleted_at IS NULL";
                    // $sqlDP = mysqli_query($db_conn, $queryDP);
                    if ($rowMenu['amount']) {
                        // $fetchDP = mysqli_fetch_assoc($sqlDP);
                        $amountDP = (float)$rowMenu['amount'];

                        $subtotal = 0;
                        // $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                        // $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                        $subtotal = $rowMenu['totalTrx'];

                        $dpDiscount = ($singlePrice / $subtotal) * $amountDP;
                        $dpMenu = $dpDiscount * $qty;
                        $dpTotal += $dpMenu;
                    }
                    // get dp end

                    // get rounding
                    $rounding = 0;
                    // $queryRounding = "SELECT rounding FROM transaksi WHERE id = '$transactionID' AND deleted_at IS NULL";
                    // $sqlRounding = mysqli_query($db_conn, $queryRounding);
                    if ($rowMenu["rounding"] > 0) {
                        // $fetchRounding = mysqli_fetch_assoc($sqlRounding);
                        $amountRounding = (int)$rowMenu['rounding'];

                        $subtotal = 0;
                        // $sqlTotal = mysqli_query($db_conn, "SELECT total FROM transaksi WHERE id = '$transactionID'");
                        // $fetchTotal = mysqli_fetch_assoc($sqlTotal);
                        $subtotal = $fetchTotal['totalTrx'];

                        $roundingPerDetail = ($singlePrice / $subtotal) * $amountRounding;
                        $rounding = $roundingPerDetail * $qty;
                    }
                    // get rounding end

                    if($transcStatus == 4 || $menuStatus == 4) {
                        $totalDiscount = 0;
                    } else {
                        $totalDiscount = (int)$program_discount + (int)$promo_discount + (int)$special_discount + (int)$employee_discount;
                    }
                    $allDiscTotal += $totalDiscount;
                    // get discount end

                    $dataMenu['dpMenu'] = (int)$dpMenu;
                    $dataMenu['rounding'] = (int)$rounding;
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

                    if($transcStatus == 4 || $menuStatus == 4) {
                        $dataMenu['service'] = 0;
                        $dataMenu['tax'] = 0;
                    }

                    $totalService += $dataMenu['service'];
                    $totalTax += $dataMenu['tax'];

                    $totalGrossSales += $dataMenu['gross_sales'];
                    $dataMenu['total_collected'] = $dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund'];
                    $dataMenu['net_sales'] = $dataMenu['total_collected'];
                    $dataMenu['total_collected'] = $dataMenu['total_collected'] + $dataMenu['service'] + $dataMenu['tax'];
                    $dataMenu['total_collected'] = $dataMenu['total_collected'] - $dataMenu['dpMenu'] + $dataMenu['rounding'];
                    $dataMenu['total_before_rounding'] = $dataMenu['total_collected'] - $dataMenu['rounding'];
                    if($dataMenu['total_collected'] <= 0 ){
                        $dataMenu['total_collected'] = 0; 
                    }
                    if($dataMenu['total_before_rounding'] <= 0){
                        $dataMenu['rounding'] = 0;
                        $dataMenu['total_collected'] = 0;
                    }
                    $allSubtotal += ($dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund']);
                    $totalCollected += $dataMenu['total_collected'];
                    $dataMenu['gross_profit'] = $dataMenu['total_collected'] - $dataMenu['cogs'];
                    $dataMenu['grossMargin'] = round(($dataMenu['gross_profit'] / $dataMenu['total_collected']) * 100, 2);
                    $allRefundTotal += $dataMenu['totalRefund'];

                    $reportNonV[$nonV] = $dataMenu;
                    $nonV++;
                    
                //   if($nonVar_id_menu == "" || $nonVar_id_menu != $dataMenu["id_menu"]){
                //         $data = [
                //             'cogs' => $nonVar_cogs,
                //             'category' => $nonVar_category,
                //             'service' => $nonVar_service,
                //             'tax' => $nonVar_tax,
                //             'dpMenu' => $nonVar_dpMenu,
                //             'rounding' => $nonVar_rounding,
                //             'gross_profit' => $nonVar_gross_profit,
                //             'gross_sales' => $nonVar_gross_sales,
                //             'id_menu' => $nonVar_id_menu,
                //             'menu_name' => $nonVar_menu_name,
                //             'name' => $nonVar_name,
                //             'total_collected' => $nonVar_total_collected,
                //             'total_before_rounding' => $nonVar_total_before_rounding,
                //             'net_sales' => $nonVar_net_sales,
                //             'price' => $nonVar_price,
                //             'qty' => $nonVar_qty,
                //             'refundQty' => $nonVar_refundQty,
                //             'reportName' => $nonVar_reportName,
                //             'vg_name' => $nonVar_vg_name,
                //             'sku' => $nonVar_sku,
                //             'single_price' => (int)$nonVar_single_price,
                //             'totalDiscount' => $nonVar_totalDiscount,
                //             'totalRefund' => $nonVar_totalRefund,
                //             'variant_price' => $nonVar_variant_price,
                //             'grossMargin' => $nonVar_grossMargin,
                //         ];
                //         if($nonVar_id_menu != ""){
                //             $res[$resIndex] = $data;
                //             $resIndex++;
                //         }
                        
                //         $nonVar_id_menu = $dataMenu["id_menu"];
                //         $nonVar_id = $dataMenu["id"];
                //         $nonVar_menu_name = $dataMenu["menu_name"];
                //         $nonVar_vg_name = $dataMenu["vg_name"];
                //         $nonVar_sku = $dataMenu["sku"];
                //         $nonVar_category = $dataMenu["category"];
                //         $nonVar_service = $dataMenu["service"];
                //         $nonVar_tax = $dataMenu["tax"];
                //         $nonVar_dpMenu = $dataMenu["dpMenu"];
                //         $nonVar_rounding = $dataMenu["rounding"];
                //         $nonVar_cogs = $dataMenu["cogs"];
                //         $nonVar_gross_profit = $dataMenu["gross_profit"];
                //         $nonVar_gross_sales = $dataMenu["gross_sales"];
                //         $nonVar_name = $dataMenu["name"];
                //         $nonVar_total_collected = $dataMenu["total_collected"];
                //         $nonVar_total_before_rounding = $dataMenu["total_before_rounding"];
                //         $nonVar_net_sales = $dataMenu["net_sales"];
                //         $nonVar_price = $dataMenu["price"];
                //         $nonVar_qty = $dataMenu["qty"];
                //         $nonVar_refundQty = $dataMenu["refundQty"];
                //         $nonVar_reportName = $dataMenu["reportName"];
                //         $nonVar_single_price = $dataMenu["single_price"];
                //         $nonVar_totalDiscount = $dataMenu["totalDiscount"];
                //         $nonVar_totalRefund = $dataMenu["totalRefund"];
                //         $nonVar_variant_price = $dataMenu["variant_price"];
                //         $nonVar_grossMargin = $dataMenu["grossMargin"];
                //     } else {
                //         $nonVar_service += $dataMenu["service"];
                //         $nonVar_tax += $dataMenu["tax"];
                //         $nonVar_dpMenu += $dataMenu["dpMenu"];
                //         $nonVar_rounding += $dataMenu["rounding"];
                //         $nonVar_cogs += $dataMenu["cogs"];
                //         $nonVar_gross_profit += $dataMenu["gross_profit"];
                //         $nonVar_gross_sales += $dataMenu["gross_sales"];
                //         $nonVar_total_collected += $dataMenu["total_collected"];
                //         $nonVar_total_before_rounding += $dataMenu["total_before_rounding"];
                //         $nonVar_net_sales += $dataMenu["net_sales"];
                //         $nonVar_price += $dataMenu["price"];
                //         $nonVar_qty += $dataMenu["qty"];
                //         $nonVar_refundQty += $dataMenu["refundQty"];
                //         $nonVar_totalDiscount += $dataMenu["totalDiscount"];
                //         $nonVar_totalRefund += $dataMenu["totalRefund"];
                //         $nonVar_grossMargin += $dataMenu["grossMargin"];
                //     }
                }
            }

            if (count($reportNonV) > 0) {
                $result1 = array_reduce($reportNonV, function ($carry, $item) {
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
                            'rounding' => $item['rounding'],
                            'cogs' => $item['cogs'],
                            'gross_profit' => $item['gross_profit'],
                            'gross_sales' => $item['gross_sales'],
                            'name' => $item['name'],
                            'total_collected' => $item['total_collected'],
                            'total_before_rounding' => $item['total_before_rounding'],
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
                        $carry[$item['id_menu']]['rounding'] += $item['rounding'];
                        $carry[$item['id_menu']]['gross_profit'] += $item['gross_profit'];
                        $carry[$item['id_menu']]['gross_sales'] += $item['gross_sales'];
                        $carry[$item['id_menu']]['total_collected'] += $item['total_collected'];
                        $carry[$item['id_menu']]['total_before_rounding'] += $item['total_before_rounding'];
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

                
                foreach ($result1 as $val) {
                    if ($val['total_collected'] <= 0) {
                        $val['grossMargin'] = 0;
                        $val['total_collected'] = 0;
                    } else {
                        $val['grossMargin'] = round(($val['gross_profit'] / $val['total_collected']) * 100, 2);
                    }
                    $res[$resIndex] = $val;
                    $resIndex++;
                }
            }

            $result = array_reduce($reportV, function ($carry, $item) {
                if (!isset($carry[$item['reportName']])) {
                    $carry[$item['reportName']] = [
                        'cogs' => $item['cogs'],
                        'category' => $item['category'],
                        'service' => $item['service'],
                        'tax' => $item['tax'],
                        'dpMenu' => $item['dpMenu'],
                        'rounding' => $item['rounding'],
                        'gross_profit' => $item['gross_profit'],
                        'gross_sales' => $item['gross_sales'],
                        'id_menu' => $item['id_menu'],
                        'menu_name' => $item['menu_name'],
                        'name' => $item['name'],
                        'total_collected' => $item['total_collected'],
                        'total_before_rounding' => $item['total_before_rounding'],
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
                        'variant_price' => $item['variant_price']
                    ];
                } else {
                    $carry[$item['reportName']]['cogs'] += $item['cogs'];
                    $carry[$item['reportName']]['service'] += $item['service'];
                    $carry[$item['reportName']]['tax'] += $item['tax'];
                    $carry[$item['reportName']]['dpMenu'] += $item['dpMenu'];
                    $carry[$item['reportName']]['rounding'] += $item['rounding'];
                    $carry[$item['reportName']]['gross_sales'] += $item['gross_sales'];
                    $carry[$item['reportName']]['gross_profit'] += $item['gross_profit'];
                    $carry[$item['reportName']]['total_collected'] += $item['total_collected'];
                    $carry[$item['reportName']]['total_before_rounding'] += $item['total_before_rounding'];
                    $carry[$item['reportName']]['net_sales'] += $item['net_sales'];
                    $carry[$item['reportName']]['qty'] += $item['qty'];
                    $carry[$item['reportName']]['refundQty'] += $item['refundQty'];
                    $carry[$item['reportName']]['totalRefund'] += $item['totalRefund'];
                    $carry[$item['reportName']]['totalDiscount'] += $item['totalDiscount'];
                }

                return $carry;
            });

            $reportV = $result;
            // $reportV = [];
            // foreach ($result as $val) {
            //     array_push($reportV, $val);
            // }
            
            foreach ($reportV as $val) {
                $val['gross_profit'] = $val['total_collected'] - $val['cogs'];

                if ($val['total_collected'] == 0) {
                    $val['grossMargin'] = "N/A";
                } else {
                    $val['grossMargin'] = round(($val['gross_profit'] / $val['total_collected']) * 100, 2);
                }

                $res[$resIndex] = $val;
                $resIndex++;
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
            $query = "SELECT detail_transaksi.variant, detail_transaksi.is_program, detail_transaksi.id_transaksi, detail_transaksi.harga, menu.nama, menu.id AS id_menu, detail_transaksi.harga_satuan as harga_satuan, detail_transaksi.status AS menu_status, transaksi.status AS transc_status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id, menu.hpp, menu.sku, c.name AS cat_name, c.id AS cat_id, c.is_consignment, transaksi.id_partner FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id JOIN categories c ON c.id = menu.id_category WHERE partner.id= '$id' AND transaksi.status IN (1,2,3,4) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
        } else {
            $query = "SELECT detail_transaksi.variant, detail_transaksi.is_program, detail_transaksi.id_transaksi, detail_transaksi.harga, menu.nama, menu.id AS id_menu, detail_transaksi.harga_satuan as harga_satuan, detail_transaksi.status AS menu_status, transaksi.status AS transc_status, detail_transaksi.qty, detail_transaksi.id AS det_transc_id, partner.id AS id_partner, menu.hpp, menu.sku, c.name AS cat_name, c.id AS cat_id, c.is_consignment FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id JOIN categories c ON c.id = menu.id_category WHERE partner.id_master= '$idMaster' AND transaksi.status IN (1,2,3,4) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY detail_transaksi.id_transaksi DESC";
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
                $qService = "SELECT service FROM transaksi WHERE id = '$transactionID' AND deleted_at IS NULL";
                $sqlService = mysqli_query($db_conn, $qService);
                $fetchService = mysqli_fetch_assoc($sqlService);
                $partnerService = (int)$fetchService['service'];
                // service end
        
                // tax
                $qTax = "SELECT tax FROM transaksi WHERE id = '$transactionID' AND deleted_at IS NULL";
                $sqlTax = mysqli_query($db_conn, $qTax);
                $fetchTax = mysqli_fetch_assoc($sqlTax);
                $partnerTax = (float)$fetchTax['tax'];
                // tax end
        
                $is_program = $rowMenu['is_program'];
                if ($is_program > 0) {
                    $singlePrice = $rowMenu['harga'];
                }
        
                $transcStatus = $rowMenu['transc_status'];
                $menuStatus = $rowMenu['menu_status'];
                $detailTranscID = $rowMenu['det_transc_id'];
        
                $reportVar = [];
                $reportVar['category'] = $category;
                $reportVar['cogs'] = (float)$menuCogs * $qty;
                $reportVar['id'] = "";
                $reportVar['id_menu'] = $menuID;
                $reportVar['menu_name'] = $namaMenu;
                $reportVar['name'] = "";
                $reportVar['total_collected'] = "";
                $reportVar['net_sales'] = "";
                $reportVar['price'] = 0;
                $reportVar['qty'] = $qty;
                if ($transcStatus == "4" || $menuStatus == "4") {
                    $reportVar['refundQty'] = $qty;
                } else {
                    $reportVar['refundQty'] = 0;
                }
                $reportVar['reportName'] = $namaMenu;
                $reportVar['single_price'] = (int)$singlePrice;
                $reportVar['sku'] = $sku;
        
                // get discount
                // $queryDisc = "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.employee_discount_percent, t.id_voucher, t.program_id FROM `transaksi` t WHERE t.id = '$transactionID'";
                // $queryDisc =  "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.id_voucher, t.employee_discount_percent, t.program_id, SUM(dt.qty) as counter, t.total FROM `transaksi` t LEFT JOIN detail_transaksi dt ON dt.id_transaksi = t.id WHERE t.id = '$transactionID' AND t.deleted_at is NULL AND dt.deleted_at is NULL AND dt.status !=4 GROUP BY dt.id_transaksi";
                // $sqlDisc = mysqli_query($db_conn, $queryDisc);
                // $dataDisc = mysqli_fetch_assoc($sqlDisc);
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
                            if ($transcStatus == "4" || $menuStatus == "4") {
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
                    $discount = $dataDisc['program_discount'];
                    if ($program_id > 0) {
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
                    } else if($discount > 0){
                        $program_discount = ($qty * $singlePrice) / $dataDisc["total"];
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
        
                    if($transcStatus == 4 || $menuStatus == 4) {
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
        
                    if($transcStatus == 4 || $menuStatus == 4) {
                        $reportVar['service'] = 0;
                        $reportVar['tax'] = 0;
                    }
        
                    $totalService += $reportVar['service'];
                    $totalTax += $reportVar['tax'];
        
                    $totalGrossSales += $reportVar['gross_sales'];
                    $reportVar['total_collected'] = $reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund'];
                    $reportVar['net_sales'] = $reportVar['total_collected'];
                    $reportVar['total_collected'] = $reportVar['total_collected'] + $reportVar['service'] + $reportVar['tax'];
                    $reportVar['total_collected'] = $reportVar['total_collected'] - $reportVar['dpMenu'] ;
                    
                    $allSubtotal += ($reportVar['gross_sales'] - $reportVar['totalDiscount'] - $reportVar['totalRefund']);
                    $totalCollected += $reportVar['total_collected'];
    
                    $reportVar['gross_profit'] = $reportVar['total_collected'] - $reportVar['cogs'];
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
                    if ($transcStatus == "4" || $menuStatus == "4") {
                        $dataMenu['refundQty'] = $qty;
                        $dataMenu['totalRefund'] = $dataMenu['refundQty'] * $dataMenu['single_price'];
                    }
                    // get refund end
        
                    // get discount
                    // $sqlDisc = mysqli_query($db_conn, "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.id_voucher, t.employee_discount_percent, t.program_id FROM `transaksi` t WHERE t.id = '$transactionID'");
                    $sqlDisc = mysqli_query($db_conn, "SELECT t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.id_voucher, t.employee_discount_percent, t.program_id, SUM(dt.qty) as counter, t.total FROM `transaksi` t LEFT JOIN detail_transaksi dt ON dt.id_transaksi = t.id WHERE t.id = '$transactionID' AND t.organization='Natta' AND t.deleted_at is NULL AND dt.deleted_at is NULL AND dt.status !=4 GROUP BY dt.id_transaksi");
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
                    $discount = $dataDisc['program_discount'];
                    $program_id = $dataDisc['program_id'];
                    if ($program_id > 0) {
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
                    } else if($discount > 0){
                        $program_discount = ($qty * $singlePrice) / $dataDisc["total"];
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
        
                    if($transcStatus == 4 || $menuStatus == 4) {
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
        
                    if($transcStatus == 4 || $menuStatus == 4) {
                        $dataMenu['service'] = 0;
                        $dataMenu['tax'] = 0;
                    }
        
                    $totalService += $dataMenu['service'];
                    $totalTax += $dataMenu['tax'];
        
                    $totalGrossSales += $dataMenu['gross_sales'];
                    $dataMenu['total_collected'] = $dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund'];
                    $dataMenu['net_sales'] = $dataMenu['total_collected'];
                    $dataMenu['total_collected'] = $dataMenu['total_collected'] + $dataMenu['service'] + $dataMenu['tax'];
                    $dataMenu['total_collected'] = $dataMenu['total_collected'] - $dataMenu['dpMenu'];
        
                    $allSubtotal += ($dataMenu['gross_sales'] - $dataMenu['totalDiscount'] - $dataMenu['totalRefund']);
                    $totalCollected += $dataMenu['total_collected'];
                    $dataMenu['gross_profit'] = $dataMenu['total_collected'] - $dataMenu['cogs'];
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
                        'dpMenu' => $item['dpMenu'],
                        'gross_profit' => $item['gross_profit'],
                        'gross_sales' => $item['gross_sales'],
                        'id_menu' => $item['id_menu'],
                        'menu_name' => $item['menu_name'],
                        'name' => $item['name'],
                        'total_collected' => $item['total_collected'],
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
                    $carry[$item['reportName']]['total_collected'] += $item['total_collected'];
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
                            'total_collected' => $item['total_collected'],
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
                        $carry[$item['id_menu']]['total_collected'] += $item['total_collected'];
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
                    if ($val['total_collected'] <= 0) {
                        $val['grossMargin'] = "N/A";
                        $val['total_collected'] = 0;
                    } else {
                        $val['grossMargin'] = round(($val['gross_profit'] / $val['total_collected']) * 100, 2);
                    }
                    array_push($res, $val);
                }
            }
        
            foreach ($reportV as $val) {
                $val['gross_profit'] = $val['total_collected'] - $val['cogs'];
        
                if ($val['total_collected'] == 0) {
                    $val['grossMargin'] = "N/A";
                } else {
                    $val['grossMargin'] = round(($val['gross_profit'] / $val['total_collected']) * 100, 2);
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

echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "itemSales" => $res, "dpTotal" => $dpTotal, "refundTotal"=>$allRefundTotal, "totalDiscountAll"=>$allDiscTotal, "totalService"=>$totalService, "totalTax"=>$totalTax, "totalGrossSales"=>$totalGrossSales, "totalCollected"=>$totalCollected, "allSubtotal"=>$allSubtotal]);

