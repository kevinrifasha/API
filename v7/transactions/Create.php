<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();


//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require '../../includes/functions.php';
require_once '../../includes/DbOperation.php';

// date_default_timezone_set('Asia/Jakarta');

// POST DATA
$fs = new functions();
$db = new DbOperation();
$sql = "";
//init var
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
$today1 = date('Y-m-d');
$tokenizer = new Token();
$token = '';
$res = array();
$res1 = array();
$ewallet_response = array();
$id = "";
$params = [];

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

//function
function generateTransactionID($db_conn, $type, $trxDate, $pid)
{
    $code = $type . "/" . $trxDate . "/" . $pid;
    // $q = mysqli_query($db_conn, "SELECT count(id) as id FROM `transaksi` WHERE id LIKE '%$code%' AND transaksi.deleted_at IS NULL ORDER BY jam DESC LIMIT 1");
    $q = mysqli_query($db_conn, "SELECT count(id) as id FROM `transaksi` WHERE id LIKE '%$code%' ORDER BY jam DESC LIMIT 1");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id1 = (int) $res[0]['id'];
        $index = (int) $id1 + 1;
        if ($index < 10) {
            $index = "00000" . $index;
        } else if ($index < 100) {
            $index = "0000" . $index;
        } else if ($index < 1000) {
            $index = "000" . $index;
        } else if ($index < 10000) {
            $index = "00" . $index;
        } else if ($index < 100000) {
            $index = "0" . $index;
        } else {
            $index = $index;
        }
        $code = $code . "/" . $index;
        return $code;
    } else {
        $code = $code . "/000001";
        return $code;
    }
}




function getStatus($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT master.status FROM `master`
    JOIN partner ON master.id = partner.id_master
    WHERE partner.id ='$id' ");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['status'];
    } else {
        return 0;
    }
}

function getShiftID($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT MAX(id) as id FROM `shift` WHERE partner_id='$id' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['id'];
    } else {
        return 0;
    }
}

function getChargeUr($status, $hide, $db_conn, $id)
{
    if ($status == "FULL" && $hide == 0) {
        $q = mysqli_query($db_conn, "SELECT charge_ur as value FROM `partner` WHERE id='$id'");
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            return (int) $res[0]['value'];
        } else {
            return 0;
        }
    } else {
        return 0;
    }
}
function getChargeUrShipper($status, $hide, $db_conn, $id)
{
    if ($status == "FULL" && $hide == 0) {
        $q = mysqli_query($db_conn, "SELECT charge_ur_shipper as value FROM `partner` WHERE id='$id'");
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            return (int) $res[0]['value'];
        } else {
            return 0;
        }
    } else {
        return 0;
    }
}

function array_some($array, $callback) {
    foreach ($array as $item) {
        if ($callback($item)) {
            return true;
        }
    }
    return false;
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $data = json_decode(file_get_contents('php://input'));
    if (
        isset($data->partnerID)
        && isset($data->total)
        && isset($data->paymentMethod)
        && !empty($data->partnerID)
        && !empty($data->total)
        && !empty($data->paymentMethod)
    ) {

        $is_takeaway = $data->is_takeaway ?? 0;
        $is_delivery = 0;
        $trxDate = date("ymd");
        $pid = (int) $data->partnerID;
        if (isset($data->is_delivery) && !empty($data->is_delivery)) {
            $is_delivery = $data->is_delivery;
        }
        if ($is_delivery == 1 || $is_delivery == '1') {
            $id = generateTransactionID($db_conn, "DL", $trxDate, $pid);
        } else if ($is_takeaway == 1 || $is_takeaway == '1') {
            $id = generateTransactionID($db_conn, "TA", $trxDate, $pid);
        } else {
            $id = generateTransactionID($db_conn, "DI", $trxDate, $pid);
        }
        $phone = "WAITERAPP";
        $partnerID = $data->partnerID;
        $total = $data->total;
        $paymentMethod = $data->paymentMethod;
        $tableCode = $data->tableCode;
        $is_queue = $data->is_queue ?? 0;
        $id_voucher = $data->id_voucher;
        $id_voucher_redeemable = "";
        if (isset($data->id_voucher_redeemable) && !empty($data->id_voucher_redeemable)) {
            $id_voucher_redeemable = $data->id_voucher_redeemable;
        }
        $pax = 1;
        if (isset($data->pax) && !empty($data->pax)) {
            $pax = $data->pax;
        }
        $delivery_fee = 0;
        if (isset($data->delivery_fee) && !empty($data->delivery_fee)) {
            $delivery_fee = (int) $data->delivery_fee;
        }
        $rate_id = 0;
        if (isset($data->rate_id) && !empty($data->rate_id)) {
            $rate_id = $data->rate_id;
        }
        $user_address_id = 0;
        if (isset($data->user_address_id) && !empty($data->user_address_id)) {
            $user_address_id = $data->user_address_id;
        }
        $is_insurance = 0;
        if (isset($data->is_insurance) && !empty($data->is_insurance)) {
            $is_insurance = $data->is_insurance;
        }
        $delivery_detail = "";
        if (isset($data->delivery_detail) && !empty($data->delivery_detail)) {
            $delivery_detail = $data->delivery_detail;
        }

        $distance = "0";
        if (isset($data->distance) && !empty($data->distance)) {
            $distance = $data->distance;
        }
        $is_takeaway = $data->is_takeaway ?? 0;
        $notes = $data->notes;
        $foodcourtID = 0;
        if (isset($data->foodcourtID) && !empty($data->foodcourtID)) {
            $foodcourtID = $data->foodcourtID;
        }
        $diskon_spesial = 0;
        if (isset($data->diskon_spesial) && !empty($data->diskon_spesial)) {
            $diskon_spesial = $total * ceil($data->diskon_spesial) / 100;;
        }
        $promo = 0;
        if (isset($data->promo) && !empty($data->promo)) {
            $promo = $data->promo;
        }
        $point = 0;
        if (isset($data->point) && !empty($data->point)) {
            $point = $data->point;
        }
        $total_program = 0;
        if (isset($data->total_program) && !empty($data->total_program)) {
            $total_program = $data->total_program;
            $total += $total_program;
        }
        $program_id = 0;
        if (isset($data->program_id) && !empty($data->program_id)) {
            $program_id = $data->program_id;
        }
        $program_discount = 0;
        if (isset($data->program_discount) && !empty($data->program_discount)) {
            $program_discount = $data->program_discount;
        }
        $status = 0;
        $tax2 = 0;
        $oct = "0";
        $q = mysqli_query($db_conn, "SELECT tax, service, hide_charge, open_close_table FROM `partner` WHERE id='$partnerID'");
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        
        $isConsign = false;
        foreach ($data->detail as $cart) {
            if ($cart->is_consignment == '1') {
                $isConsign = true;
                break;
            }
        }
        
        // $validateConsignment = array_some($data->detail, function($detail){
        //     if($detail->is_consignment == "1" || $detail['is_consignment'] == "1" ){
        //         return false;
        //     } else {
        //         return true;
        //     }
        // });

        
        if(!$isConsign){
            $tax2 = $res[0]['tax'];
            $service = $res[0]['service'];
        } else {
            $service = 0;
            $tax2 = 0;
        }
        
        $hide = $res[0]['hide_charge'];
        $oct = $res[0]['open_close_table'];
        $tax = $tax2;
        $q = mysqli_query($db_conn, "SELECT id, value FROM settings WHERE id IN(25,26)");
        if (mysqli_num_rows($q) > 0) {
            while ($row = mysqli_fetch_assoc($q)) {
                if ($row['id'] == 25) {
                    $charge_ewallet = $row['value'];
                    $charge_xendit = $row['value'];
                } else if ($row['id'] == 26 && (int)$paymentMethod == 2) {
                    $charge_ewallet = $row['value'];
                    $charge_xendit = $row['value'];
                }
            }
        } else {
            $charge_ewallet = 0;
            $charge_xendit = 0;
        }
        $status = getStatus($partnerID, $db_conn);
        $today = date("Y-m-d H:i:s");
        $charge_ur = (int)getChargeUr($status, $hide, $db_conn, $partnerID);
        if ($is_delivery == '1' || $is_delivery == 1) {
            if (strpos($delivery_detail, 'Kurir Pribadi') !== false) {
            } else {
                $charge_ur = (int)getChargeUrShipper($status, $hide, $db_conn, $partnerID);
            }
        }
        $charge_ur = 0;
        $shiftID = (int)getShiftID($partnerID, $db_conn);
        if ($paymentMethod == '11' || $paymentMethod == 11) {
            $status = 5;
        }
        // if($paymentMethod=='11' || $paymentMethod==11 ){
        //     $status = 5;
        //     $shiftID =(int)getShiftID($partnerID, $db_conn);
        // }
        // if( $paymentMethod=='1' || $paymentMethod==1 || $paymentMethod=='2' || $paymentMethod==2 || $paymentMethod=='3' || $paymentMethod==3 || $paymentMethod=='4' || $paymentMethod==4 || $paymentMethod=='6' || $paymentMethod==6 || $paymentMethod=='10' || $paymentMethod==10){
        //     $shiftID =(int)getShiftID($partnerID, $db_conn);
        // }

        $idx = 0;

        $il = 0;
        $dataDetail = $data->detail;
        $boolQty = true;
        foreach ($dataDetail as $cart) {
            $items[$il] = new \stdClass();
            $items[$il]->id = $cart->id_menu;
            $items[$il]->quantity = $cart->qty;
            $menuQ = mysqli_query($db_conn, "SELECT nama, harga, stock, is_recipe FROM `menu` WHERE id='$cart->id_menu' AND enabled='1'");
            $mn = mysqli_fetch_all($menuQ, MYSQLI_ASSOC);
            $items[$il]->name = $mn[0]['nama'];
            $items[$il]->price = $mn[0]['harga'];
            $stockMenu = (int) $mn[0]['stock'];
            $is_recipeMenu = $mn[0]['is_recipe'];
            $id_menu = $cart->id_menu;

            if ($stockMenu < $cart->qty) {
                $boolQty = false;
                $res1[$idx]["nama"] = $items[$il]->name;
                $idx += 1;
            }

            $il += 1;
        }
        foreach ($dataDetail as $cart) {
            $vQty = $cart->qty;
            if (!empty($cart->variant)) {
                $variant = $cart->variant;
                foreach ($variant as $vars) {
                    $dvariant = $vars->data_variant;
                    foreach ($dvariant as $detail) {
                        $vID = $detail->id;
                        $menuQ = mysqli_query($db_conn, "SELECT name, stock  FROM `variant` WHERE `id` = '$vID'");
                        $mn = mysqli_fetch_all($menuQ, MYSQLI_ASSOC);
                        $stockMenu = (int) $mn[0]['stock'];
                        if ($stockMenu < $cart->qty) {
                            $boolQty = false;
                            $res1[$idx]["nama"] = 'Varian ' . $mn[0]['name'];
                            $idx += 1;
                        }
                    }
                }
            }
        }
        $customerName = "";
        if (isset($data->customerName) && !empty($data->customerName)) {
            $customerName = $data->customerName;
        }
        $customerEmail = "";
        if ($boolQty == true) {
            $COMMIT = mysqli_query($db_conn, "
                START TRANSACTION;
                SAVEPOINT '$id';
                COMMIT;
                ");
            $sql = "START TRANSACTION;";
            if ($paymentMethod == '11' || $paymentMethod == 11 || $paymentMethod == '1' || $paymentMethod == 1 || $paymentMethod == '2' || $paymentMethod == 2 || $paymentMethod == '3' || $paymentMethod == 3 || $paymentMethod == '4' || $paymentMethod == 4 || $paymentMethod == '6' || $paymentMethod == 6 || $paymentMethod == '10' || $paymentMethod == 10) {
                $sql .= "INSERT INTO transaksi(`id`, `jam`, `phone`, `id_partner`, `no_meja`, `status`, `total`, `tipe_bayar`, `promo`, `point`, `queue`, `takeaway`, `notes`, `id_foodcourt`, `tax`, `service`, `charge_ewallet`, `charge_xendit`, `charge_ur`, `id_voucher`, `id_voucher_redeemable`, `diskon_spesial`, `shift_id`, `program_discount`, `program_id`, customer_name, customer_email, is_pos, source,pax) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$shiftID', '$program_discount', '$program_id', '$customerName', '$customerEmail', '1', 'waiterApp','$pax'); ";
            } else {
                $sql .= "INSERT INTO transaksi(`id`, `jam`, `phone`, `id_partner`, `no_meja`, `status`, `total`, `tipe_bayar`, `promo`, `point`, `queue`, `takeaway`, `notes`, `id_foodcourt`, `tax`, `service`, `charge_ewallet`, `charge_xendit`, `charge_ur`, `id_voucher`, `id_voucher_redeemable`, `diskon_spesial`, `program_discount`, `program_id`, customer_name, customer_email, is_pos, source,pax) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$program_discount', '$program_id', '$customerName', '$customerEmail', '1', 'waiterApp','$pax'); ";
            }
            if ($is_delivery == '1' || $is_delivery == 1) {
                if (strpos($delivery_detail, 'Kurir Pribadi') !== false) {
                    // echo 'true';
                } else {
                }
                $sql .= "INSERT INTO `delivery`(`transaksi_id`, `ongkir`, `rate_id`, `user_address_id`, `is_insurance`, `delivery_detail`, `distance`) VALUES ('$id', '$delivery_fee', '$rate_id', '$user_address_id', '$is_insurance', '$delivery_detail', '$distance'); ";
            }
            // if($insert){
            $dataDetail = $data->detail;
            $idx = 0;
            $il = 0;
            $items = array();

            $boolCheck = true;
            // $insertDT = $db->insertDetailTransaksiAndroid($id, $dataDetail);
            foreach ($dataDetail as $cart) {
                $is_program = 0;
                if (isset($cart->is_program) && !empty(isset($cart->is_program)) && $cart->is_program != 0) {
                    $is_program = $cart->is_program;
                }
                $is_smart_waiter = 0;
                if (isset($cart->is_smart_waiter) && !empty(isset($cart->is_smart_waiter)) && $cart->is_smart_waiter != 0) {
                    $is_smart_waiter = $cart->is_smart_waiter;
                }
                $json = "'[''variant'':[";
                $i = 0;
                if (empty($cart->variant)) {
                    $json = "''";
                } else {
                    $variant = $cart->variant;
                    foreach ($variant as $vars) {
                        if ($i == 0) {
                            $json .= "{";
                        } else {
                            $json .= ",{";
                        }
                        $json .= "''name'':''" . $vars->name . "'',";
                        $json .= "''id'':''" . $vars->id_variant . "'',";
                        $json .= "''tipe'':''" . $vars->type . "'',";
                        $json .= "''detail'':[";
                        $dvariant = $vars->data_variant;
                        $i += 1;
                        $j = 0;
                        foreach ($dvariant as $detail) {
                            if ($j == 0) {
                                $json .= "{";
                            } else {
                                $json .= ",{";
                            }

                            $json .= "''id'':''" . $detail->id . "'',";
                            $json .= "''qty'':''" . $detail->qty . "'',";
                            $json .= "''name'':''" . $detail->name . "''}";
                            $j += 1;
                        }
                        $json .= "]}";
                    }
                    $json .= "]]'";
                    // $json = json_encode($json);
                }
                $tempH = $cart->harga_satuan * $cart->qty;
                $sql .= "INSERT INTO detail_transaksi(id_transaksi, id_menu, harga_satuan, qty, notes, harga,variant, is_program, is_smart_waiter, server_id) VALUES ('$id', '$cart->id_menu', '$cart->harga_satuan', '$cart->qty', '$cart->notes', '$tempH', $json, '$is_program', '$is_smart_waiter', '$token->id');";
            }
            $sql .= " COMMIT;";
            if (mysqli_multi_query($db_conn, $sql)) {
                do {
                    if ($r = mysqli_store_result($db_conn)) {
                        mysqli_free_result($r);
                    }
                } while (mysqli_more_results($db_conn) && mysqli_next_result($db_conn));
                if ($oct == "1") {
                    $updateTable = mysqli_query($db_conn, "UPDATE meja SET is_seated=1, updated_at = NOW() WHERE idmeja='$tableCode'");
                }
                if ($status == 5) {
                    // $qDT = mysqli_query($db_conn, "SELECT id_menu, qty, variant FROM `detail_transaksi` WHERE id_transaksi='$id' AND deleted_at IS NULL");
                    $qDT = mysqli_query($db_conn, "SELECT dt.id_menu, dt.qty, dt.variant, m.is_recipe FROM `detail_transaksi` dt JOIN menu m ON m.id = dt.id_menu WHERE dt.id_transaksi='$id' AND dt.deleted_at IS NULL");
                    if (mysqli_num_rows($qDT) > 0) {
                        $detailsTransaction = mysqli_fetch_all($qDT, MYSQLI_ASSOC);

                        $menusOrder = array();
                        $variantOrder = array();
                        $imo = 0;
                        $iv = 0;
                        foreach ($detailsTransaction as $value) {
                            $menusOrder[$imo]['id_menu'] = $value['id_menu'];
                            $menusOrder[$imo]['qty'] = $value['qty'];
                            $menusOrder[$imo]["is_recipe"] = $value["is_recipe"];
                            $cut = $value['variant'];
                            $cut = substr($cut, 11);
                            $cut = substr($cut, 0, -1);
                            $cut = str_replace("'", '"', $cut);
                            $menusOrder[$imo]['variant'] = json_decode($cut);
                            if ($menusOrder[$imo]['variant'] != NULL) {

                                foreach ($menusOrder[$imo]['variant'] as $value1) {
                                    foreach ($value1->detail as $value2) {
                                        $variantOrder[$iv]['id'] = $value2->id;
                                        $variantOrder[$iv]['qty'] = $value2->qty;
                                        $ch = $fs->variant_stock_reduce($value2->id, $value2->qty);
                                        $iv += 1;
                                    }
                                }
                            }
                            $imo += 1;
                        }
                        $sqlMaster = mysqli_query($db_conn, "SELECT id_master FROM partner WHERE id = '$partnerID' AND deleted_at IS NULL");
                        $getMaster = mysqli_fetch_all($sqlMaster, MYSQLI_ASSOC);
                        $id_master = $getMaster[0]['id_master'];

                        //Menu
                        foreach ($menusOrder as $value) {
                            $qtyOrder = $value["qty"];
                            $menuID = $value['id_menu'];
                            $isRecipe = $value["is_recipe"];
                            // $ch = $fs->stock_reduce($menuID, $qtyOrder);
                            $ch = $fs->stock_reduce($menuID, $qtyOrder, 0, $id_master, $partnerID, $isRecipe);
                        }
                    }
                }
                $allTrans = mysqli_query($db_conn, "SELECT t.id, t.id_partner, t.queue, t.status, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, p.nama AS payment_method, u.name AS uname FROM transaksi t LEFT JOIN payment_method p ON p.id=t.tipe_bayar LEFT JOIN users u ON t.phone=u.phone WHERE t.id='$id'");
                $order = mysqli_fetch_assoc($allTrans);
                $devTokens = $db->getPartnerDeviceTokens($partnerID);
                foreach ($devTokens as $val) {
                    $birth_date = "0";
                    $gender = "0";
                    $isMembership = 0;
                    if ($is_delivery == 1 || $is_delivery == '1') {
                        $notif = $db->savePaymentNotification($val['token'], 'Pesanan Baru', 'Pesanan Delivery Baru', "Delivery", 'rn-push-notification-channel', $paymentMethod, 0, 0, $id, $partnerID, null, $order, $gender, $birth_date, $isMembership, $delivery_fee, "employee", '');
                    } else if ($is_takeaway == 1 || $is_takeaway == '1') {
                        $notif = $db->savePaymentNotification($val['token'], 'Pesanan Baru', 'Pesanan Takeaway Baru', 'Takeaway', 'rn-push-notification-channel', $paymentMethod, 0, 0, $id, $partnerID, null, $order, $gender, $birth_date, $isMembership, $delivery_fee, "employee", '');
                    } else if ($is_queue == 1 || $is_queue == '1') {
                        $notif = $db->savePaymentNotification($val['token'], 'Pesanan Baru', 'Pesanan Antrian Baru', 'Antrian', 'rn-push-notification-channel', $paymentMethod, 0, 0, $id, $partnerID, null, $order, $gender, $birth_date, $isMembership, $delivery_fee, "employee", '');
                    } else {
                        $notif = $db->savePaymentNotification($val['token'], 'Pesanan Baru', 'Pesanan Baru di Meja ' . $tableCode, $tableCode, 'rn-push-notification-channel', $paymentMethod, 0, 0, $id, $partnerID, null, $order, $gender, $birth_date, $isMembership, $delivery_fee, "employee", '');
                    }
                }
                $msg = "Success";
                $success = 1;
                $status = 200;
                // mysqli_close($db_conn);
            } else {
                $sql = "START TRANSACTION; ROLLBACK TO '$id';";
                $act = mysqli_multi_query($db_conn, $sql) or die(mysqli_error($db_conn));
                // mysqli_close($db_conn);
                $msg = "Failed Create Detail";
                $success = 0;
                $status = 204;
            }
            
            $statusAfter = 0;
            if($paymentMethod == 11 || $paymentMethod == "11"){
                $statusAfter = 5;
            }
            
            $insertOST = mysqli_query($db_conn, "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`, `payment_method_before` ,`payment_method_after`, `created_by` ) VALUES ('$id', '0','$statusAfter' ,'0', '$paymentMethod', '$token->id')");
            // }else{
            //     $msg = "Failed Create Transaction";
            //     $success = 0;
            //     $status=204;
            // }
        } else {
            $msg = "Stock Menu Tidak Mencukupi";
            $success = 0;
            $status = 204;
        }
    } else {
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "unavailable" => $res1, "transaction_id" => $id, "ewallet_response" => $ewallet_response]);
