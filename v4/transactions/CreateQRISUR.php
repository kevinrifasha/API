<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once '../../includes/DbOperation.php';
require '../../includes/functions.php';
require '../../includes/ValidatorV4.php';

$fs = new functions();
// date_default_timezone_set('Asia/Jakarta');
// POST DATA
$db = new DbOperation();
$validator = new ValidatorV4();
$err = "";

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
$tokenizer = new Token();
$token = '';
$res = array();
$res1 = array();

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

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

function getService($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT service FROM `partner` WHERE id='$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['service'];
    } else {
        return 0;
    }
}

function getTaxEnabled($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT tax FROM `partner` WHERE id='$id'");
    $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
    $tax = $res[0]['tax'];
    return (float) $tax;
}

function getChargeEwallet($db_conn)
{
    $q = mysqli_query($db_conn, "SELECT value FROM settings WHERE name = 'charge_ewallet'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return $res[0]['value'];
    } else {
        return 0;
    }
}

function getChargeXendit($db_conn)
{
    $q = mysqli_query($db_conn, "SELECT value FROM settings WHERE name = 'charge_xendit'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['value'];
    } else {
        return 0;
    }
}

function getHideCharge($idPartner, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT partner.hide_charge FROM `master`
    JOIN partner ON master.id = partner.id_master
    WHERE partner.id ='$idPartner' ");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['hide_charge'];
    } else {
        return 0;
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

function getIDVR($phone, $vr, $trx, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT id  FROM `user_voucher_ownership` WHERE `userid` LIKE '$phone' AND `id_voucher`='$vr' AND `transaksi_id` IS NULL ORDER BY id ASC limit 1");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id = $res[0]['id'];
        $update = mysqli_query($db_conn, "UPDATE `user_voucher_ownership` SET `transaksi_id`='$trx' WHERE id='$id'");
        return $update;
    } else {
        return 0;
    }
}
$id = "";

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $data = json_decode(file_get_contents('php://input'));
    if($data->paymentMethod == 14 || $data->paymentMethod == "14"){
        $validator->checkShiftIDActive($db_conn, $token);
        $partnerID = $token->id_partner;
        if (
            isset($data->customer_phone)
            && isset($data->customer_name)
            && isset($data->total)
            && isset($data->paymentMethod)
            && !empty($data->customer_phone)
            && !empty($data->customer_name)
            && !empty($data->total)
            && !empty($data->paymentMethod)
        ) {
            $is_takeaway = $data->is_takeaway;
            $trxDate = date("ymd");
            $pid = (int) $partnerID;
            
            // $recursive = 0;
            // if($recursive > 0){
                if ($is_takeaway == 1 || $is_takeaway == '1') {
                    $id = generateTransactionID($db_conn, "TA", $trxDate, $pid);
                } elseif (isset($data->surcharge_type) && !empty($data->surcharge_type)) {
                    $id = generateTransactionID($db_conn, "ET", $trxDate, $pid);
                } else {
                    $id = generateTransactionID($db_conn, "DI", $trxDate, $pid);
                }
            // } else{
            //     $id = "DI/230619/415/000011";
            // }
            $employeeID = 0;
            if (isset($data->employeeID) && !empty($data->employeeID)) {
                $employeeID = $data->employeeID;
            } else {
                $employeeID = 0;
            }
            $phone = $data->customer_phone;
            $is_pos = 1;
            $pax = 1;
            $cpm_id = $data->cpm_id ?? 0;
            $name = $data->customer_name;
            $reference_id = $data->reference_id;
            $paymentMethod = $data->paymentMethod;
            $collector_id = $token->id;
            $total = $data->total;
            $tableCode = $data->tableCode;
            if (isset($data->surcharge_type) && !empty($data->surcharge_type)) {
                $tableCode = $data->surcharge_type;
            }
            $surcharge_id = 0;
            if (isset($data->surcharge_id) && !empty($data->surcharge_id)) {
                $surcharge_id = $data->surcharge_id;
            }
            if (isset($data->pax) && !empty($data->pax)) {
                $pax = $data->pax;
            }
            $surcharge_percent = 0;
            if (isset($data->surcharge_percent) && !empty($data->surcharge_percent)) {
                $surcharge_percent = $data->surcharge_percent;
            }
            $is_queue = $data->is_queue;
            $id_voucher = "";
            if (isset($data->id_voucher) && !empty($data->id_voucher)) {
                $id_voucher = $data->id_voucher;
            }
            $id_voucher_redeemable = "";
            if (isset($data->id_voucher_redeemable) && !empty($data->id_voucher_redeemable)) {
                $id_voucher_redeemable = $data->id_voucher_redeemable;
            }
            $notes = $data->notes;
            $foodcourtID = 0;
            if (isset($data->foodcourtID) && !empty($data->foodcourtID)) {
                $foodcourtID = $data->foodcourtID;
            }
            $diskon_spesial = 0;
            if (isset($data->diskon_spesial) && !empty($data->diskon_spesial)) {
                $diskon_spesial = $total * ceil($data->diskon_spesial) / 100;;
            }
            $program_discount = 0;
            if (isset($data->program_discount) && !empty($data->program_discount)) {
                $program_discount = $data->program_discount;
            }
            $employee_discount = 0;
            if (isset($data->employee_discount) && !empty($data->employee_discount)) {
                $employee_discount = $data->employee_discount;
            }
            $employee_discount_percent = 0;
            if (isset($data->employee_discount_percent) && !empty($data->employee_discount_percent)) {
                $employee_discount_percent = $data->employee_discount_percent;
            }
            $rounding = 0;
            if (isset($data->rounding) && !empty($data->rounding)) {
                $rounding = $data->rounding;
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
            $customer_email = "";
            if (isset($data->customer_email) && !empty($data->customer_email)) {
                $customer_email = $data->customer_email;
            }
            $delivery_status_tracking = "0";
            if (isset($data->delivery_status_tracking) && !empty($data->delivery_status_tracking)) {
                $delivery_status_tracking = $data->delivery_status_tracking;
            }
    
            $status = 0;
            $service = getService($partnerID, $db_conn);
            $tax = getTaxEnabled($partnerID, $db_conn);
            $charge_ewallet = getChargeEwallet($db_conn);
            $charge_xendit = getChargeXendit($db_conn);
            // $hide = getHideCharge($partnerID, $db_conn);
            // $status = 2;
            $pstatus = getStatus($partnerID, $db_conn);
            $today = date("Y-m-d H:i:s");
            // $charge_ur =(int)getChargeUr($pstatus, $hide, $db_conn, $partnerID);
            $charge_ur = 0;
            $shiftID = (int)getShiftID($partnerID, $db_conn);
            $isConsignment = 0;
            if (isset($data->isConsignment) && !empty($data->isConsignment)) {
                if ($data->isConsignment == "1") {
                    $isConsignment = 1;
                    $service = 0;
                    $tax = 0;
                }
            }
            if (isset($data->surcharge_type) && !empty($data->surcharge_type)) {
                $service = $data->service ?? 0;
                $tax = $data->tax ?? 0;
            }
            //validate user
            $validateUser = mysqli_query($db_conn, "SELECT count(id) FROM users WHERE phone='$phone' AND deleted_at IS NULL");
            if (mysqli_num_rows($validateUser) == 0) {
                $insertUser = mysqli_query($db_conn, "INSERT INTO users SET name='$name', email='$email', phone='$phone', master_id='$token->id_master'");
            }
    
            if ($paymentMethod == '11' || $paymentMethod == 11) {
                $status = 5;
                $collector_id = 0;
            }
            $getOverride = mysqli_query($db_conn, "SELECT allow_override_stock,is_queue_tracking FROM partner WHERE id='$partnerID'");
            $override = mysqli_fetch_all($getOverride, MYSQLI_ASSOC);
            $allowOverride = (int)$override[0]['allow_override_stock'];
            if ((int)$override[0]['is_queue_tracking'] == 1) {
                $is_queue = 1;
            }
    
            $lastQueue = 0;
            if ((int)$is_queue == 1) {
    
                $qLQ = mysqli_query($db_conn, "SELECT MAX(queue) as LastQueue FROM transaksi WHERE id_partner = '$partnerID' AND DATE(jam) = DATE('$today') LIMIT 1");
                if (mysqli_num_rows($qLQ) > 0) {
                    $lq = mysqli_fetch_all($qLQ, MYSQLI_ASSOC);
                    $lastQueue = (int) $lq[0]['LastQueue'];
                    $lastQueue += 1;
                } else {
                    $lastQueue = 1;
                }
            }
            $is_queue = $lastQueue;
            $idx = 0;
            $i = 0;
            $il = 0;
            $dataDetail = $data->detail;
            $boolQty = true;
            $qr = "";
    
            if ($allowOverride == 0) {
                foreach ($dataDetail as $cart) {
                    $items[$il] = new \stdClass();
                    $items[$il]->id = $cart->id_menu;
                    $items[$il]->quantity = $cart->qty;
                    $menuQ = mysqli_query($db_conn, "SELECT nama, harga, stock, is_recipe, track_stock FROM `menu` WHERE id='$cart->id_menu'");
                    $mn = mysqli_fetch_all($menuQ, MYSQLI_ASSOC);
                    $items[$il]->trackStock = $mn[0]['track_stock'];
                    $items[$il]->name = $mn[0]['nama'];
                    $items[$il]->price = $mn[0]['harga'];
                    $trackStock = (int) $mn[0]['track_stock'];
                    $stockMenu = (int) $mn[0]['stock'];
                    $is_recipeMenu = $mn[0]['is_recipe'];
                    $id_menu = $cart->id_menu;
                    if ($trackStock == 1) {
                        if ($stockMenu < $cart->qty) {
                            $boolQty = false;
                            $res1[$idx]["nama"] = $items[$il]->name;
                            if ((int)$is_recipeMenu == 1) {
                                // $qr = "SELECT r.id_raw, (r.qty*$cart->qty) AS needed, rm.name, IFNULL(SUM(rms.stock),0) AS inStock, (IFNULL(rms.stock,0)-(r.qty*$cart->qty)) AS difference FROM `recipe` r JOIN raw_material rm ON r.id_raw = rm.id LEFT JOIN raw_material_stock rms ON rms.id_raw_material = rm.id WHERE r.`id_menu` = '$id_menu' AND r.deleted_at IS NULL AND (IFNULL(rms.stock,0)-(r.qty*$cart->qty))<0 GROUP BY r.id";
                                $qr = "SELECT r.id_raw, rm.name, (r.qty*$cart->qty) AS needed, IFNULL(SUM(rms.stock),0) AS inStock, (IFNULL(SUM(rms.stock),0)-(r.qty*$cart->qty)) AS difference FROM `recipe` r JOIN raw_material rm ON r.id_raw = rm.id LEFT JOIN raw_material_stock rms ON rms.id_raw_material = rm.id WHERE r.`id_menu` = '$id_menu' AND r.deleted_at IS NULL AND rm.deleted_at IS NULL AND rms.deleted_at IS NULL GROUP BY rm.id";
                                $getRecipe = mysqli_query($db_conn, $qr);
                                if (mysqli_num_rows($getRecipe) > 0) {
                                    while ($row = mysqli_fetch_assoc($getRecipe)) {
                                        if ((int)$row['difference'] < 0) {
                                            $res1[$idx]["rm"][$i]["name"] = $row['name'];
                                            $res1[$idx]["rm"][$i]["needed"] = $row['needed'];
                                            $res1[$idx]["rm"][$i]["inStock"] = $row['inStock'];
                                            $res1[$idx]["rm"][$i]["difference"] = $row['difference'];
                                            $i++;
                                        }
                                    }
                                }
                            } else {
                                $res1[$idx]["rm"] = [];
                            }
                            $idx += 1;
                        }
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
            }
    
            if (isset($id_voucher) && !empty($id_voucher) && strlen($id_voucher) > 0) {
                $bool = true;
                $q = mysqli_query($db_conn, "SELECT type_id, is_percent, discount, enabled, total_usage, prerequisite FROM voucher WHERE code='$id_voucher' AND partner_id='$partnerID' AND DATE(NOW()) BETWEEN DATE(valid_from) AND DATE(valid_until) AND enabled='1' ORDER BY id DESC LIMIT 1");
                if (mysqli_num_rows($q) > 0) {
                    $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                    $prerequisite = json_decode($res[0]['prerequisite']);
    
                    $tempPromo = 0;
                    if (isset($prerequisite->min)) {
                        if ((int) $prerequisite->min > $total) {
                            $bool = false;
                        }
                    }
                    if (isset($prerequisite->transaction) && !empty($prerequisite->transaction)) {
                        if ($prerequisite->transaction != $transaction_type) {
                            $bool = false;
                        }
                    }
                    if ($res[0]['type_id'] == '1') {
                        if ($res[0]['is_percent'] == "1") {
                            $tempPromo = ceil(((int) $res[0]['discount'] * $total) / 100);
                        } else {
                            $tempPromo = (int) $res[0]['discount'];
                        }
                    } else if ($res[0]['type_id'] == '3') {
                        $tempTot = 0;
                        foreach ($dataDetail as $cart) {
    
                            if (isset($cart->status) && !empty($cart->status)) {
                                if ($cart->status == 4) {
                                } else {
                                    if (isset($cart->is_program) && !empty($cart->is_program)) {
                                        // $totalProgram += (int) $cart->harga;
                                    } else {
                                        // $total += (int) $cart->harga_satuan *(int) $cart->qty;
                                        $qC = mysqli_query($db_conn, "SELECT id_category FROM `menu` WHERE id='$cart->id_menu'");
                                        if (mysqli_num_rows($qC) > 0) {
                                            $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                            $a = explode(",", $prerequisite->category_id);
                                            foreach ($a as $value) {
                                                if ($resC[0]['id_category'] == $value) {
                                                    $tempTot += (int) $cart->harga_satuan * (int) $cart->qty;
                                                }
                                            }
                                        }
                                    }
                                }
                            } else {
                                if (isset($cart->is_program) && !empty($cart->is_program)) {
                                    // $totalProgram += (int) $cart->harga;
                                } else {
                                    // $total += (int) $cart->harga_satuan *(int) $cart->qty;
                                    $qC = mysqli_query($db_conn, "SELECT id_category FROM `menu` WHERE id='$cart->id_menu'");
                                    if (mysqli_num_rows($qC) > 0) {
                                        $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                        $a = explode(",", $prerequisite->category_id);
                                        foreach ($a as $value) {
                                            if ($resC[0]['id_category'] == $value) {
                                                $tempTot += (int) $cart->harga_satuan * (int) $cart->qty;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if ($res[0]['is_percent'] == "1") {
                            $tempPromo = ceil(((int) $res[0]['discount'] * $tempTot) / 100);
                        } else {
                            $tempPromo = (int) $res[0]['discount'];
                        }
                    } else {
                        $tempTot = 0;
                        foreach ($dataDetail as $cart) {
    
                            if (isset($cart->status) && !empty($cart->status)) {
                                if ($cart->status == 4) {
                                } else {
                                    if (isset($cart->is_program) && !empty($cart->is_program)) {
                                    } else {
                                        if ($cart->id_menu == $prerequisite->menu_id) {
                                            $tempTot += (int) $cart->harga_satuan * (int) $cart->qty;
                                        }
                                    }
                                }
                            } else {
                                if (isset($cart->is_program) && !empty($cart->is_program)) {
                                } else {
                                    if ($cart->id_menu == $prerequisite->menu_id) {
                                        $tempTot += (int) $cart->harga_satuan * (int) $cart->qty;
                                    }
                                }
                            }
                        }
                        if ($res[0]['is_percent'] == "1") {
                            $tempPromo = ceil(((int) $res[0]['discount'] * $tempTot) / 100);
                        } else {
                            $tempPromo = (int) $res[0]['discount'];
                        }
                    }
    
                    if ($bool == true) {
                        if (isset($prerequisite->max)) {
                            if ((int) $prerequisite->max < $tempPromo) {
                                $tempPromo = (int) $prerequisite->max;
                            }
                        }
                        $promo = $tempPromo;
                    } else {
                        $promo = 0;
                    }
                } else {
                    $q = mysqli_query($db_conn, "SELECT voucher.type_id, voucher.is_percent, voucher.discount, voucher.enabled, voucher.total_usage, voucher.prerequisite FROM voucher JOIN partner ON voucher.master_id=partner.id_master WHERE voucher.code='$data->id_voucher' AND partner.id='$data->partnerID' AND DATE(NOW()) BETWEEN DATE(voucher.valid_from) AND DATE(voucher.valid_until) AND enabled='1' ORDER BY voucher.id DESC LIMIT 1");
                    $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                    $prerequisite = json_decode($res[0]['prerequisite']);
                    $bool = true;
                    $tempPromo = 0;
                    if (isset($prerequisite->min)) {
                        if ((int) $prerequisite->min > $total) {
                            $bool = false;
                        }
                    }
                    if ($res[0]['type_id'] == '1') {
                        if ($res[0]['is_percent'] == "1") {
                            $tempPromo = ceil(((int) $res[0]['discount'] * $total) / 100);
                        } else {
                            $tempPromo = (int) $res[0]['discount'];
                        }
                    } else if ($res[0]['type_id'] == '3') {
                        $tempTot = 0;
                        foreach ($dataDetail as $cart) {
    
                            if (isset($cart->status) && !empty($cart->status)) {
                                if ($cart->status == 4) {
                                } else {
                                    if (isset($cart->is_program) && !empty($cart->is_program)) {
                                        // $totalProgram += (int) $cart->harga;
                                    } else {
                                        // $total += (int) $cart->harga_satuan *(int) $cart->qty;
                                        $qC = mysqli_query($db_conn, "SELECT id_category FROM `menu` WHERE id='$cart->id_menu'");
                                        if (mysqli_num_rows($qC) > 0) {
                                            $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                            $a = explode(",", $prerequisite->category_id);
                                            foreach ($a as $value) {
                                                if ($resC[0]['id_category'] == $value) {
                                                    $tempTot += (int) $cart->harga_satuan * (int) $cart->qty;
                                                }
                                            }
                                        }
                                    }
                                }
                            } else {
                                if (isset($cart->is_program) && !empty($cart->is_program)) {
                                    // $totalProgram += (int) $cart->harga;
                                } else {
                                    // $total += (int) $cart->harga_satuan *(int) $cart->qty;
                                    $qC = mysqli_query($db_conn, "SELECT id_category FROM `menu` WHERE id='$cart->id_menu'");
                                    if (mysqli_num_rows($qC) > 0) {
                                        $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                        $a = explode(",", $prerequisite->category_id);
                                        foreach ($a as $value) {
                                            if ($resC[0]['id_category'] == $value) {
                                                $tempTot += (int) $cart->harga_satuan * (int) $cart->qty;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if ($res[0]['is_percent'] == "1") {
                            $tempPromo = ceil(((int) $res[0]['discount'] * $tempTot) / 100);
                        } else {
                            $tempPromo = (int) $res[0]['discount'];
                        }
                    } else {
                        $tempTot = 0;
                        foreach ($dataDetail as $cart) {
    
                            if (isset($cart->status) && !empty($cart->status)) {
                                if ($cart->status == 4) {
                                } else {
                                    if (isset($cart->is_program) && !empty($cart->is_program)) {
                                    } else {
                                        if ($cart->id_menu == $prerequisite->menu_id) {
                                            $tempTot += (int) $cart->harga_satuan * (int) $cart->qty;
                                        }
                                    }
                                }
                            } else {
                                if (isset($cart->is_program) && !empty($cart->is_program)) {
                                } else {
                                    if ($cart->id_menu == $prerequisite->menu_id) {
                                        $tempTot += (int) $cart->harga_satuan * (int) $cart->qty;
                                    }
                                }
                            }
                        }
                        if ($res[0]['is_percent'] == "1") {
                            $tempPromo = ceil(((int) $res[0]['discount'] * $tempTot) / 100);
                        } else {
                            $tempPromo = (int) $res[0]['discount'];
                        }
                    }
    
                    if ($bool == true) {
                        if (isset($prerequisite->max)) {
                            if ((int) $prerequisite->max < $tempPromo) {
                                $tempPromo = (int) $prerequisite->max;
                            }
                        }
                        $promo = $tempPromo;
                    } else {
                        $promo = 0;
                    }
                }
            }
            // $boolQty = true;
            // ini matiin stok
            if ($boolQty == true) {
                $COMMIT = mysqli_query($db_conn, "
                    START TRANSACTION;
                    SAVEPOINT '$id';
                    COMMIT;
                    ");
                $sql = "START TRANSACTION; ";
                if ($paymentMethod == '12') {
                    $insertAP = mysqli_query($db_conn, "INSERT INTO account_receivables SET master_id='$token->id_master', partner_id='$partnerID', user_name='$data->arName', user_phone='$data->arPhone', company='$data->arCompany', created_by='$token->id', transaction_id='$id', shift_id='$shiftID'");
                    $status = '7';
                } else if ($paymentMethod == '13') {
                    $updateDP = mysqli_query($db_conn, "UPDATE down_payments SET transaction_id='$id', used_at=NOW() WHERE id='$data->dpID'");
                }
                if (isset($data->group_id) && !empty($data->group_id)) {
                    $group_id = $data->group_id;
                    if ($paymentMethod == '11') {
                        $sql .= "INSERT INTO transaksi(id, jam, phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, group_id, shift_id, employee_discount, surcharge_id, surcharge_percent, customer_email, is_pos, cpm_id, pax, employee_discount_percent, rounding, program_discount) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', $group_id, '$shiftID', '$employee_discount', '$surcharge_id', '$surcharge_percent', '$customer_email', '$is_pos', '$cpm_id','$pax','$employee_discount_percent', '$rounding', '$program_discount'); ";
                    } else if ($paymentMethod == '12') {
                        $status = '7';
                        $sql .= "INSERT INTO transaksi(id, jam,  phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, group_id, shift_id, employee_discount, surcharge_id, surcharge_percent, customer_email, is_pos, cpm_id,pax, employee_discount_percent, is_ar, rounding, program_discount) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', $group_id, '$shiftID', '$employee_discount', '$surcharge_id', '$surcharge_percent', '$customer_email', '$is_pos', '$cpm_id','$pax','$employee_discount_percent','1', $rounding, '$program_discount' ); ";
                    } else if ($paymentMethod == '13') {
                        if ((int)$status == 2) {
                            $sql .= "INSERT INTO transaksi(id, jam,  phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, group_id, shift_id, employee_discount, surcharge_id, surcharge_percent, customer_email, is_pos, cpm_id,pax, employee_discount_percent, dp_id, dp_total, paid_date, rounding, program_discount, collector_id) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$data->remainingPM', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', $group_id, '$shiftID', '$employee_discount', '$surcharge_id', '$surcharge_percent', '$customer_email', '$is_pos', '$cpm_id','$pax','$employee_discount_percent', '$data->dpID', '$data->dpTotal', NOW(), $rounding, '$program_discount', '$collector_id'); ";
                        } else {
                            $sql .= "INSERT INTO transaksi(id, jam,  phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, group_id, shift_id, employee_discount, surcharge_id, surcharge_percent, customer_email, is_pos, cpm_id,pax, employee_discount_percent, dp_id, dp_total, rounding, program_discount) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$data->remainingPM', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', $group_id, '$shiftID', '$employee_discount', '$surcharge_id', '$surcharge_percent', '$customer_email', '$is_pos', '$cpm_id','$pax','$employee_discount_percent', '$data->dpID', '$data->dpTotal', $rounding, '$program_discount'); ";
                        }
                    } else {
                        if ($delivery_status_tracking == '1') {
                            $status = '1';
                        }
                        if((int)$status == 2){
                            $sql .= "INSERT INTO transaksi(id, jam,  phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, group_id, shift_id, employee_discount, surcharge_id, surcharge_percent, paid_date, customer_email, is_pos, cpm_id,pax, employee_discount_percent, rounding, program_discount, collector_id) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', $group_id, '$shiftID', '$employee_discount', '$surcharge_id', '$surcharge_percent', '$today', '$customer_email', '$is_pos', '$cpm_id','$pax','$employee_discount_percent',$rounding, '$program_discount','$collector_id'); ";
                        } else {
                            $sql .= "INSERT INTO transaksi(id, jam,  phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, group_id, shift_id, employee_discount, surcharge_id, surcharge_percent, paid_date, customer_email, is_pos, cpm_id,pax, employee_discount_percent, rounding, program_discount) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', $group_id, '$shiftID', '$employee_discount', '$surcharge_id', '$surcharge_percent', '$today', '$customer_email', '$is_pos', '$cpm_id','$pax','$employee_discount_percent',$rounding, '$program_discount'); ";
                        }
                    }
                } else {
                    if ($paymentMethod == '11') {
                        $sql .= "INSERT INTO transaksi(id, jam, phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, shift_id, employee_discount, surcharge_id, surcharge_percent, customer_email, is_pos, cpm_id,pax, employee_discount_percent, rounding, program_discount, collector_id) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', '$shiftID', '$employee_discount', '$surcharge_id', '$surcharge_percent', '$customer_email', '$is_pos', '$cpm_id','$pax','$employee_discount_percent', $rounding, '$program_discount', '$collector_id'); ";
                    } else if ($paymentMethod == '12') {
                        $status = '7';
                        $sql .= "INSERT INTO transaksi(id, jam, phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, shift_id, employee_discount, surcharge_id, surcharge_percent, customer_email, is_pos, cpm_id,pax, employee_discount_percent, is_ar, rounding,  program_discount) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', '$shiftID', '$employee_discount', '$surcharge_id', '$surcharge_percent', '$customer_email', '$is_pos', '$cpm_id','$pax','$employee_discount_percent','1', $rounding, ' $program_discount'); ";
                    } else if ($paymentMethod == '13') {
                        if ((int)$status == 2) {
                            $sql .= "INSERT INTO transaksi(id, jam,  phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, shift_id, employee_discount, surcharge_id, surcharge_percent, customer_email, is_pos, cpm_id,pax, employee_discount_percent, dp_id, dp_total, paid_date, rounding, program_discount, collector_id) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$data->remainingPM', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', '$shiftID', '$employee_discount', '$surcharge_id', '$surcharge_percent', '$customer_email', '$is_pos', '$cpm_id','$pax','$employee_discount_percent', '$data->dpID', '$data->dpTotal', NOW(), $rounding, '$program_discount', '$collector_id'); ";
                        } else {
                            $sql .= "INSERT INTO transaksi(id, jam,  phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, shift_id, employee_discount, surcharge_id, surcharge_percent, customer_email, is_pos, cpm_id,pax, employee_discount_percent, dp_id, dp_total, rounding, program_discount) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$data->remainingPM', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', '$shiftID', '$employee_discount', '$surcharge_id', '$surcharge_percent', '$customer_email', '$is_pos', '$cpm_id','$pax','$employee_discount_percent', '$data->dpID', '$data->dpTotal', $rounding,'$program_discount'); ";
                        }
                    } else {
                        if ($delivery_status_tracking == '1') {
                            $status = '1';
                        }
                        
                        if((int)$status == 2){
                            $sql .= "INSERT INTO transaksi(id, jam, phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, shift_id, employee_discount, surcharge_id, surcharge_percent, paid_date, customer_email, is_pos, cpm_id,pax, employee_discount_percent, rounding, program_discount, collector_id) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', '$shiftID', '$employee_discount', '$surcharge_id', '$surcharge_percent', '$today', '$customer_email', '$is_pos', '$cpm_id','$pax','$employee_discount_percent', $rounding, '$program_discount', '$collector_id'); ";
                        }else {
                            $sql .= "INSERT INTO transaksi(id, jam, phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, shift_id, employee_discount, surcharge_id, surcharge_percent, paid_date, customer_email, is_pos, cpm_id,pax, employee_discount_percent, rounding, program_discount) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', '$shiftID', '$employee_discount', '$surcharge_id', '$surcharge_percent', '$today', '$customer_email', '$is_pos', '$cpm_id','$pax','$employee_discount_percent', $rounding, '$program_discount'); ";
                        }
                    }
                }
                // if($insert){
                
                if (strlen($id_voucher_redeemable) > 0) {
                    getIDVR($phone, $id_voucher_redeemable, $id, $db_conn);
                }
                $dataDetail = $data->detail;
                $idx = 0;
                $il = 0;
                $items = array();
    
                $boolCheck = true;
                // $insertDT = $db->insertDetailTransaksiAndroid($id, $dataDetail);
                // if ($insertDT==5) {
                // }else{
                //     $boolCheck=false;
                // }
                foreach ($dataDetail as $cart) {
    
                    $surcharge_change = 0;
                    if (isset($cart->surcharge_change) && !empty(isset($cart->surcharge_change)) && $cart->surcharge_change != 0) {
                        $surcharge_change = $cart->surcharge_change;
                    }
                    $is_program = 0;
                    if (isset($cart->is_program) && !empty(isset($cart->is_program)) && $cart->is_program != 0) {
                        $is_program = $cart->is_program;
                    }
    
                    $is_smart_waiter  = 0;
                    if (isset($cart->is_smart_waiter) && !empty(isset($cart->is_smart_waiter)) && $cart->is_smart_waiter != 0) {
                        $is_smart_waiter  = $cart->is_smart_waiter;
                    }
    
                    $surcharge_id = 0;
                    if(isset($cart->surcharge_id) && !empty(isset($cart->surcharge_id))){
                        $surcharge_id  = $cart->surcharge_id;
                    }
                    
                    $bundle_id = 0;
                    if(isset($cart->bundle_id) && !empty(isset($cart->bundle_id))){
                        $bundle_id  = $cart->bundle_id;
                    }
                    
                    $bundle_qty = 0;
                    if(isset($cart->bundle_qty) && !empty(isset($cart->bundle_qty))){
                        $bundle_qty  = $cart->bundle_qty;
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
                            $name = str_replace("'","\\\''",$vars->name);
                            $json .= "''name'':''" . $name . "'',";
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
                                $detailName = str_replace("'","\\\''",$detail->name);
                                $json .= "''name'':''" . $detailName . "'',";
                                $json .= "''price'':''" . ($detail->price ?? 0) . "'',";
                                //   $json .= "''name'':''" . $detail->name . "''}";
                                //   $json .= "''price'':''" . ($detail->price??0) . "''}";
                                //   $json .= "''cogs'':''" . ($detail->cogs??0) . "'}";
                                $json .= "''cogs'':''" . ($detail->cogs ?? 0) . "''}";
                                $j += 1;
                            }
                            $json .= "]}";
                        }
                        $json .= "]]'";
                    }
                    
                    $sql .= "INSERT INTO detail_transaksi(id_transaksi, id_menu, harga_satuan, qty, notes, harga,variant, is_program, surcharge_change, is_smart_waiter, server_id, is_consignment, surcharge_id, bundle_id, bundle_qty) VALUES ('$id', '$cart->id_menu', '$cart->harga_satuan', '$cart->qty', '$cart->notes', '$cart->harga', $json, $is_program, '$surcharge_change', '$is_smart_waiter','$employeeID', '$isConsignment','$surcharge_id','$bundle_id','$bundle_qty');";
                }
                $statusForOST = $status;
                if($paymentMethod == 11 || $paymentMethod == '11'){
                    $statusForOST = 0;
                }
                
                $sql .= "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`, `payment_method_before`, `payment_method_after`, `created_at`,`created_by`) VALUES ('$id', '0', '$statusForOST',  '0', '$paymentMethod', NOW(), '$token->id');";
    
                // if ($insertDT==5) {
                // }else{
                //     $boolCheck=false;
                // }
                $sql .= " COMMIT;";
                $execution = mysqli_multi_query($db_conn, $sql);
                if ($execution) {
                   do {
                    // fetch results
                        if($result = mysqli_use_result($db_conn)){
                            while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                $test1 = $row;
                                // if(count($row) > 17)
                                //     $orders[] = $row;
                                // elseif(count($row) == 6)
                                //     $inv[] = $row;
                            }
                        }
                        $c++;
                        if(!mysqli_more_results($db_conn)) break;
                        if(!mysqli_next_result($db_conn) || mysqli_errno($db_conn)) {
                            // report error
                            $err = mysqli_error($db_conn);
                            break;
                        }
                    } while(true);
                    mysqli_free_result($result);
    
                    if($err == ""){
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
                                $ch = $fs->stock_reduce($menuID, $qtyOrder, 0, $id_master, $partnerID, $isRecipe);
                            }
                        }
        
                        if (isset($data->customer_email) && !empty($data->customer_email) && $status == '2' && $paymentMethod != '11' && $paymentMethod != 11 && $paymentMethod != 12 && $paymentMethod != "12") {
                            $query = "SELECT transaksi.no_meja, transaksi.total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.program_discount, partner.name AS partner_name, users.email, users.name, partner.id AS partner_id, payment_method.nama AS payment_method_name, transaksi.customer_email FROM `transaksi` JOIN `partner` ON `partner`.`id`=`transaksi`.`id_partner` LEFT JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id='$id'";
                            $trxQ = mysqli_query($db_conn, $query);
                            $subtotal = 0;
                            $promo = 0;
                            $program_discount = 0;
                            $diskon_spesial = 0;
                            $employee_discount = 0;
                            $point = 0;
                            $service = 0;
                            $tax = 0;
                            $charge_ur = 0;
                            $payment_method_name = "";
                            $partner_name = "";
                            $partnerID = "";
                            $user_name = "";
                            $user_email = "";
                            $no_meja = "";
        
                            while ($row = mysqli_fetch_assoc($trxQ)) {
                                $payment_method_name = $row['payment_method_name'];
                                $partner_name = $row['partner_name'];
                                $partnerID = $row['partner_id'];
                                $user_name = $row['name'];
                                $user_email = $row['customer_email'];
                                if (isset($row['email']) && !empty($row['email'])) {
                                    $user_email = $row['email'];
                                }
                                $no_meja = $row['no_meja'];
                                $subtotal += (int) $row['total'];
                                $promo += (int) $row['promo'];
                                $program_discount += (int) $row['program_discount'];
                                $diskon_spesial += (int) $row['diskon_spesial'];
                                $employee_discount += (int) $row['employee_discount'];
                                $point += (int) $row['point'];
                                $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
                                $service += $tempS;
                                $charge_ur += (int) $row['charge_ur'];
                                $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
                                $tax += $tempT;
                            }
                            $subtotal = $subtotal;
                            $sales = $subtotal + $service + $tax + $charge_ur;
                            $promo = $promo;
                            $program_discount = $program_discount;
                            $diskon_spesial = $diskon_spesial;
                            $employee_discount = $employee_discount;
                            $point = $point;
                            $clean_sales = $sales - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
                            $service = $service;
                            $charge_ur = $charge_ur;
                            $tax = $tax;
                            $total = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax;
                            $dateNow = date('d M Y');
                            $timeNow = date("h:i");
        
                            $query = "SELECT template FROM `email_template` WHERE id=1";
                            $templateQ = mysqli_query($db_conn, $query);
                            if (mysqli_num_rows($templateQ) > 0) {
                                $templates = mysqli_fetch_all($templateQ, MYSQLI_ASSOC);
                                $template = $templates[0]['template'];
                                $template = str_replace('$strTottotal', $fs->rupiah($total), $template);
                                $template = str_replace('$dateNow ', $dateNow, $template);
                                $template = str_replace('$timeNow ', $timeNow, $template);
                                $template = str_replace('$name ', $user_name, $template);
                                $template = str_replace('$partnerName ', $partner_name, $template);
                                $template = str_replace('$no_meja ', $no_meja, $template);
                                $template = str_replace('$strTot ', $fs->rupiah($subtotal), $template);
                                $template = str_replace('$strServ ', $fs->rupiah($service), $template);
                                $template = str_replace('$strChargeUR ', $fs->rupiah($charge_ur), $template);
                                $template = str_replace('$strTax ', $fs->rupiah($tax), $template);
                                $template = str_replace('$strSUbtot ', $fs->rupiah($total), $template);
                                $template = str_replace('$type ', $payment_method_name, $template);
                                $template = str_replace('$id ', $id, $template);
                                if (substr($id, 0, 2) == "DI") {
                                    $template = str_replace('$trx_type', "Dine In", $template);
                                } elseif (substr($id, 0, 2) == "TA") {
                                    $template = str_replace('$trx_type', "Take Away", $template);
                                }
                                if (substr($id, 0, 2) == "DL") {
                                    $template = str_replace('$trx_type', "Delivery", $template);
                                } else {
                                    $template = str_replace('$trx_type', "Pre Order", $template);
                                }
                                if ($promo > 0) {
                                    $template = str_replace('$promo', '
                                                <tr>
                                                <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Promo Voucher</td>
                                                <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($promo) . ' </td>
                                            </tr>', $template);
                                } else {
                                    $template = str_replace('$promo', '', $template);
                                }
        
                                if ($diskon_spesial > 0) {
                                    $template = str_replace('$Diskon Spesial', '<tr>
                                                <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Diskon Spesial</td>
                                                <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($diskon_spesial) . '</td>
                                            </tr>', $template);
                                } else {
                                    $template = str_replace('$Diskon Spesial', '', $template);
                                }
        
                                if ($employee_discount > 0) {
                                    $template = str_replace('$Diskon Karyawan', '<tr>
                                                <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Diskon Karyawan</td>
                                                <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($employee_discount) . '</td>
                                            </tr>', $template);
                                } else {
                                    $template = str_replace('$Diskon Karyawan', '', $template);
                                }
                                if ($program_discount > 0) {
                                    $template = str_replace('$Diskon Program', '<tr>
                                                <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Diskon Program</td>
                                                <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($program_discount) . '</td>
                                            </tr>', $template);
                                } else {
                                    $template = str_replace('$Diskon Program', '', $template);
                                }
                            }
                            $query = "SELECT menu.nama,qty, detail_transaksi.harga FROM `detail_transaksi` JOIN menu ON menu.id=detail_transaksi.id_menu WHERE detail_transaksi.id_transaksi='$id' AND detail_transaksi.deleted_at IS NULL";
                            $detail_trx = mysqli_query($db_conn, $query);
        
                            $detail_transaction = "";
                            while ($row = mysqli_fetch_assoc($detail_trx)) {
                                $nama_menu = $row['nama'];
                                $qty_menu = $row['qty'];
                                $harga_menu = $row['harga'];
                                $detail_transaction .= '
                                            <tr>
                                            <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $nama_menu . ' X  ' . $qty_menu . ' </td>
                                            <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($harga_menu) . ' </td>
                                            </tr>';
                            }
                            $template = str_replace('$loop detail', $detail_transaction, $template);
                            $template = json_encode($template);
                            if (filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
                                $insertTe = mysqli_query($db_conn, "INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`, `created_at`) VALUES ('$user_email', '$partnerID', 'UR E-Receipt', $template, NOW())");
                            }
                        }
                        
                        if (
                            $paymentMethod != 0 || $paymentMethod != "0" ||
                            $paymentMethod != 1 || $paymentMethod != "1" ||
                            $paymentMethod != 2   || $paymentMethod != "2" ||
                            $paymentMethod != 3 || $paymentMethod != "3" ||
                            $paymentMethod != 4 || $paymentMethod != "4" ||
                            $paymentMethod != 6 || $paymentMethod != "6" ||
                            $paymentMethod != 10 || $paymentMethod != "10"
                        ) {
                            $allTrans = mysqli_query($db_conn, "SELECT t.id, t.id_partner, t.queue, t.status, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, p.nama AS payment_method, u.name AS uname FROM transaksi t JOIN payment_method p ON p.id=t.tipe_bayar JOIN users u ON t.phone=u.phone WHERE t.id='$id'");
                            $order = mysqli_fetch_assoc($allTrans);
                            $devTokens = $db->getPartnerDeviceTokens($partnerID);
                            foreach ($devTokens as $val) {
                                $mID = $db->getMembership($partnerID, $phone);
                                if ($mID == 0) {
                                    $isMembership = false;
                                } else {
                                    $isMembership = true;
                                }
                                $birth_date = $db->getBirthdate($phone);
                                $gender = $db->getGender($phone);
                            }
                            $msg = "Success";
                            $success = 1;
                            $status = 200;
                        }
                        
                            $xTotal = round(floatval(number_format((float)((($total - $promo - $program_discount - $diskon_spesial - $employee_discount) * ((100 + $service) / 100)) * ((100 + $tax)/100)) + $charge_ur, 2, '.', '')));
                            $params = [
                                // "external_id" => $newID,
                                "reference_id" => $id,
                                // "callback_url" => $_ENV["BASEURL"] . "xendit/qris/Callback.php",
                                "currency" => "IDR",
                                "amount" => $xTotal,
                                "type" => "DYNAMIC",
                                "channel_code" => "ID_DANA",
                                "metadata" => [
                                    "branch_code" => "tree_branch",
                                ],
                            ];
                            
                            $ch = curl_init();
                            $timestamp = new DateTime();
                            // curl_setopt($ch, CURLOPT_URL, $url);
                            $body = json_encode($params);
                            curl_setopt(
                            $ch,
                                CURLOPT_URL,
                                "https://" .
                                    $_ENV["XENDIT_URL"] .
                                    "/qr_codes"
                            );
                            
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                            curl_setopt($ch, CURLOPT_USERPWD, $_ENV['XENDIT_KEY']. ':' . '');
                            $headers = array();
                            $headers[] = 'Content-Type: application/json';
                            $headers[] = "api-version: 2022-07-31";
                            $headers[] = "webhook-url: " . $_ENV["BASEURL"] . "xendit/qris/CallbackQRISPOS.php";
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            $result = curl_exec($ch);
                            if (curl_errno($ch)) {
                                echo 'Error:' . curl_error($ch);
                            }
                            $ewallet_response = $result;
                            $ewallet_response_returned = json_decode($ewallet_response);
                            $qrString =  $ewallet_response_returned->qr_string;
                            $updateQR = mysqli_query($db_conn, "UPDATE transaksi SET qr_string='$qrString' WHERE id='$id'"); 
                            curl_close($ch);
                            $UpdateCallback = mysqli_query(
                                $db_conn,
                                "INSERT INTO `xendit_callbacks`(`transaction_id`, `value`, `created_at`) VALUES ('$id', '$result', NOW())"
                            );
                        
                    } else {
                        $msg = "Terjadi Kesalahan, Mohon Coba Lagi";
                        $success = 0;
                        $status = 203;  
                    }
                } else {
                    $sql = "START TRANSACTION; ROLLBACK TO '$id';";
                    $act = mysqli_multi_query($db_conn, $sql) or die(mysqli_error($db_conn));
                    $msg = "Failed Create Detail";
                    $success = 0;
                    $status = 204;
                }
                // }else{
                //     $msg = "Failed Create Transaction";
                //     $success = 0;
                //     $status=204;
                // }
            } else {
                $msg = "Stock ";
                foreach ($res1 as $x) {
                    $msg .= $x['nama'] . ", ";
                    if (empty($x['rm'])) {
                    } else {
                        $msg .= "bahan baku ";
                        foreach ($x['rm'] as $y) {
                            $msg .= $y['name'] . " (dibutuhkan " . (int)$y['needed'] . ", tersedia " . $y['inStock'] . ", selisih " . $y['difference'] . "), ";
                        }
                    }
                }
                $msg .= "tidak mencukupi";
                // $msg = "Stock Menu Tidak Mencukupi";
                // $msg = "Stock".$res1." tidak mencukupi";
                $success = 0;
                $status = 204;
            }
        } else {
            $success = 0;
            $msg = "Missing Mandatory Field";
            $status = 400;
        }
    } else {
        $success = 0;
        $msg = "Payment Method Is Not QRIS UR";
        $status = 400;
    }
    
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "unavailable" => $res1, "transaction_id" => $id, "qris_string"=>$qrString, "ewallet_response"=>$ewallet_response_returned]);

