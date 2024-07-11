<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header(
    "Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"
);
require __DIR__ . "/../../vendor/autoload.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../..");
$dotenv->load();

//import require file
require "../../db_connection.php";
require_once "../auth/Token.php";
require "../../includes/functions.php";
require_once "../../includes/DbOperation.php";

// date_default_timezone_set('Asia/Jakarta');

$err = "";
// POST DATA
$fs = new functions();
$db = new DbOperation();
$sql = "";
$generatedToken = "";
//init var
$headers = [];
$test = '';
$rx_http = "/\AHTTP_/";
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, "", $key);
        $rx_matches = [];
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode("_", $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) {
                $rx_matches[$ak_key] = ucfirst($ak_val);
            }
            $arh_key = implode("-", $rx_matches);
        }
        $headers[$arh_key] = $val;
    }
}
$today1 = date("Y-m-d");
$tokenizer = new Token();
$token = "";
$res = [];
$res1 = [];
$ewallet_response = "";
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
    $q = mysqli_query(
        $db_conn,
        "SELECT count(id) as id FROM `transaksi` WHERE id LIKE '%$code%' ORDER BY jam DESC LIMIT 1"
    );
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id1 = (int) $res[0]["id"];
        $index = (int) $id1 + 1;
        if ($index < 10) {
            $index = "00000" . $index;
        } elseif ($index < 100) {
            $index = "0000" . $index;
        } elseif ($index < 1000) {
            $index = "000" . $index;
        } elseif ($index < 10000) {
            $index = "00" . $index;
        } elseif ($index < 100000) {
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

function minUserPoint($phone, $id_voucher_r, $partner_id, $db_conn)
{
    $q = mysqli_query(
        $db_conn,
        "SELECT vr.point FROM membership_voucher vr JOIN partner p ON p.id_master=vr.id_master WHERE vr.code='$id_voucher_r' AND vr.deleted_at IS NULL AND p.id='$partner_id'"
    );
    $q1 = mysqli_query(
        $db_conn,
        "SELECT m.id, point, m.master_id FROM memberships m JOIN partner p ON p.id_master AND m.master_id WHERE m.user_phone='$phone' AND p.id='$partner_id' ORDER BY m.id DESC LIMIT 1"
    );
    $testData = mysqli_num_rows($q) > 0 && mysqli_num_rows($q1) > 0;
    if (mysqli_num_rows($q) > 0 && mysqli_num_rows($q1) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $pPay = (int) $res[0]["point"];
        $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        $uPoint = (int) $res1[0]["point"];
        $uID = $res1[0]["id"];
        $master_id = $res1[0]["master_id"];
        $point = $uPoint - $pPay;
        $update = mysqli_query(
            $db_conn,
            "UPDATE `memberships` SET point='$point' WHERE id='$uID'"
        );
        $insertPoints = mysqli_query(
            $db_conn,
            "INSERT INTO `points`(`master_id`, `user_phone`, `point`, `description`, `created_at`) VALUES ('$master_id', '$phone', '-$pPay', 'Redeem Voucher $id_voucher_r', NOW())"
        );
        return $uID;
    } else {
        return 0;
    }
}

// function addPoint($phone, $total, $partner_id, $db_conn)
// {
//     $q = mysqli_query(
//         $db_conn,
//         "SELECT harga_point, transaction_point_max FROM master m JOIN partner p ON p.id_master=m.id WHERE p.id='$partner_id'"
//     );
//     $q1 = mysqli_query(
//         $db_conn,
//         "SELECT m.id, m.master_id FROM memberships m JOIN partner p ON p.id_master=m.master_id WHERE m.user_phone='$phone' AND p.id='$partner_id' ORDER BY m.id DESC LIMIT 1"
//     );
//     if (mysqli_num_rows($q) > 0 && mysqli_num_rows($q1) > 0) {
//         $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
//         $harga_point = (int) $res[0]["harga_point"];
//         $transaction_point_max = (int) $res[0]["transaction_point_max"];
//         $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
//         $mID = $res1[0]["id"];
//         $master_id = $res1[0]["master_id"];
//         if ($harga_point > 0) {
//             $point = $total / $harga_point;
//             if ($point > $transaction_point_max) {
//                 $point = $transaction_point_max;
//             }
//             $update = mysqli_query(
//                 $db_conn,
//                 "UPDATE `memberships` SET point=point+'$point' WHERE id='$mID'"
//             );
//             $insertPoints = mysqli_query(
//                 $db_conn,
//                 "INSERT INTO `points`(`master_id`, `user_phone`, `point`, `description`, `created_at`) VALUES ('$master_id', '$phone', '$point', 'point tambahan dari transaksi', NOW())"
//             );
//         }
//         return 1;
//     } else {
//         return 0;
//     }
// }

function getStatus($id, $db_conn)
{
    $q = mysqli_query(
        $db_conn,
        "SELECT master.status FROM `master`
    JOIN partner ON master.id = partner.id_master
    WHERE partner.id ='$id' "
    );
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]["status"];
    } else {
        return 0;
    }
}

function getShiftID($id, $db_conn)
{
    $q = mysqli_query(
        $db_conn,
        "SELECT MAX(id) as id FROM `shift` WHERE partner_id='$id' AND deleted_at IS NULL"
    );
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]["id"];
    } else {
        return 0;
    }
}

function getChargeUr($status, $hide, $db_conn, $id)
{
    if ($status == "FULL" && $hide == 0) {
        $q = mysqli_query(
            $db_conn,
            "SELECT charge_ur as value FROM `partner` WHERE id='$id'"
        );
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            return (int) $res[0]["value"];
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
        $q = mysqli_query(
            $db_conn,
            "SELECT charge_ur_shipper as value FROM `partner` WHERE id='$id'"
        );
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            return (int) $res[0]["value"];
        } else {
            return 0;
        }
    } else {
        return 0;
    }
}

function getIDVR($phone, $vr, $trx, $db_conn)
{
    $q = mysqli_query(
        $db_conn,
        "SELECT id  FROM `user_voucher_ownership` WHERE `userid` LIKE '$phone' AND `voucherid`='$vr' AND `transaksi_id` IS NULL ORDER BY id ASC limit 1"
    );
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id = $res[0]["id"];
        $update = mysqli_query(
            $db_conn,
            "UPDATE `user_voucher_ownership` SET `transaksi_id`='$trx' WHERE id='$id'"
        );
        return $update;
    } else {
        return 0;
    }
}
$aos = 0;

function remove_emoji($string)
{
    // Match Enclosed Alphanumeric Supplement
    $regex_alphanumeric = "/[\x{1F100}-\x{1F1FF}]$variant_selectors/u";
    $clear_string = preg_replace($regex_alphanumeric, '', $string);

    // Match Miscellaneous Symbols and Pictographs
    $regex_symbols = "/[\x{1F300}-\x{1F5FF}]$variant_selectors/u";
    $clear_string = preg_replace($regex_symbols, '', $clear_string);

    // Match Emoticons
    $regex_emoticons = "/[\x{1F600}-\x{1F64F}]$variant_selectors/u";
    $clear_string = preg_replace($regex_emoticons, '', $clear_string);

    // Match Transport And Map Symbols
    $regex_transport = "/[\x{1F680}-\x{1F6FF}]$variant_selectors/u";
    $clear_string = preg_replace($regex_transport, '', $clear_string);

    // Match Supplemental Symbols and Pictographs
    $regex_supplemental = "/[\x{1F900}-\x{1F9FF}]$variant_selectors/u";
    $clear_string = preg_replace($regex_supplemental, '', $clear_string);

    // Match Miscellaneous Symbols
    $regex_misc = "/[\x{2600}-\x{26FF}]$variant_selectors/u";
    $clear_string = preg_replace($regex_misc, '', $clear_string);

    // Match Dingbats
    $regex_dingbats = "/[\x{2700}-\x{27BF}]$variant_selectors/u";
    $clear_string = preg_replace($regex_dingbats, '', $clear_string);

    $regex_weird_char = "/[^\x{0000}-\x{007F}]/u";
    $clear_string = preg_replace($regex_weird_char, '', $clear_string);

    return $clear_string;
}

function is_cart_valid($carts){
    foreach ($carts as $cart) {
        if ($cart->qty < 1){
            return false;
        }
    }
    return true;
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption("decrypt", $token));
// function createTrx(){
// $masterID = $token->masterID;
if (isset($tokenValidate["success"]) && $tokenValidate["success"] == 0) {
    $status = $tokenValidate["status"];
    $msg = $tokenValidate["msg"];
    $success = 0;
} else {
    $isAddDetail = false;
    $data = json_decode(file_get_contents("php://input"));
    if (
        isset($data->partnerID) &&
        isset($data->total) &&
        isset($data->paymentMethod) &&
        !empty($data->partnerID) &&
        !empty($data->paymentMethod) &&
        is_cart_valid($data->detail)
    ) {
        $qFP = mysqli_query($db_conn, "SELECT first_partner FROM users WHERE id='$token->id' AND first_partner IS NOT NULL");

        if (mysqli_num_rows($qFP) > 0) {
        } else {
            $insertFP = mysqli_query($db_conn, "UPDATE users SET first_partner='$data->partnerID' WHERE id='$token->id'");
        }

        $detailIDs = [];
        $is_takeaway = $data->is_takeaway;
        $is_delivery = 0;
        $trxDate = date("ymd");
        $pid = (int) $data->partnerID;
        if (isset($data->is_delivery) && !empty($data->is_delivery)) {
            $is_delivery = $data->is_delivery;
        }
        
        // $recursive = 0;
        // if($recursive > 0){
            if ($is_delivery == 1 || $is_delivery == "1") {
                $id = generateTransactionID($db_conn, "DL", $trxDate, $pid);
            } elseif ($is_takeaway == 1 || $is_takeaway == "1") {
                $id = generateTransactionID($db_conn, "TA", $trxDate, $pid);
            } else {
                $id = generateTransactionID($db_conn, "DI", $trxDate, $pid);
            }
        // } else {
            // $id = "DI/230619/271/000002";
        // }
        
        
        $phone = $token->phone;
        $partnerID = $data->partnerID;
        // $total = $data->total;
        $subtotal = $data->subtotal;
        $paymentMethod = $data->paymentMethod;
        $tableCode = $data->tableCode;
        if ($is_takeaway == 1 || $is_takeaway == "1") {
            $tableCode = "Take Away - " . $tableCode;
        }
        $is_queue = $data->is_queue;
        $id_voucher = $data->id_voucher;
        $rounding = 0;
        if (isset($data->rounding)) {
            $rounding = $data->rounding;
        }
        
        $id_voucher_redeemable = "";
        $masterID = "";

        $sqlMasterId = mysqli_query($db_conn, "SELECT id_master FROM partner WHERE id = '$partnerID' AND deleted_at IS NULL");
        $dataMasterId = mysqli_fetch_all($sqlMasterId, MYSQLI_ASSOC);
        $masterID = $dataMasterId[0]['id_master'];
        try {
            $getAOS = mysqli_query($db_conn, "SELECT allow_override_stock FROM partner WHERE id='$partnerID'");
            if (mysqli_num_rows($getAOS) > 0) {
                $resAOS = mysqli_fetch_all($getAOS, MYSQLI_ASSOC);
                $aos = (int)$resAOS[0]['allow_override_stock'];
            } else {
                $success = 0;
                $msg = "data tidak ditemukan";
                $status = 400;
            }
        } catch (Exception $e) {
            $msg = $e;
        }

        //REPEATER
        //check repeater
        $firstDayOfMonth = date("Y-m-01");
        $currentDate = date("Y-m-d");
        $queryRepeater = "SELECT COUNT(phone) as is_repeater FROM transaksi WHERE id_partner='$partnerID' AND deleted_at IS NULL AND DATE(transaksi.jam) BETWEEN DATE('$firstDayOfMonth') AND DATE('$currentDate') AND phone='$phone' AND source IN('sfWeb','sfApp') AND status NOT IN(3,4)";
        $sqlRepeater = mysqli_query($db_conn, $queryRepeater);
        $fetchRepeater = mysqli_fetch_all($sqlRepeater, MYSQLI_ASSOC);
        $sumRepeater = $fetchRepeater[0]['is_repeater'];
        $isRepeater = 0;
        if($sumRepeater > 0){
            $isRepeater = 1;
        } else {
            $isRepeater = 0;
        }
        //check repeater end
        //END REPEATER

        if (
            isset($data->id_voucher_redeemable) &&
            !empty($data->id_voucher_redeemable)
        ) {
            $id_voucher_redeemable = $data->id_voucher_redeemable;
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
        $is_takeaway = $data->is_takeaway;
        $notes = $data->notes;
        $foodcourtID = 0;
        if (isset($data->foodcourtID) && !empty($data->foodcourtID)) {
            $foodcourtID = $data->foodcourtID;
        }
        $promo = 0;
        if (isset($data->promo) && !empty($data->promo)) {
            $promo = $data->promo;
        }
        $point = 0;
        if (isset($data->point) && !empty($data->point)) {
            $point = $data->point;
        }
        $program_id = 0;
        if (isset($data->program_id) && !empty($data->program_id)) {
            $program_id = $data->program_id;
        }
        $program_discount = 0;
        if (isset($data->program_discount) && !empty($data->program_discount)) {
            $program_discount = $data->program_discount;
        }
        $trx_id_qr = 0;
        if (isset($data->trx_id_qr) && !empty($data->trx_id_qr)) {
            $trx_id_qr = $data->trx_id_qr;
        }
        $status = 0;
        $tax2 = 0;
        $q = mysqli_query(
            $db_conn,
            "SELECT tax, service, hide_charge, is_queue_tracking FROM `partner` WHERE id='$partnerID'"
        );
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $tax2 = $res[0]["tax"];
        $service = $res[0]["service"];
        $hide = $res[0]["hide_charge"];
        $tax = $tax2;
        if ((int)$res[0]['is_queue_tracking'] == 1) {
            $is_queue = 1;
        }
        $lastQueue = 0;
        if ((int)$is_queue == 1) {
            $qLQ = mysqli_query($db_conn, "SELECT queue as LastQueue FROM transaksi WHERE id_partner = '$partnerID' AND DATE(jam) = '$today1' ORDER BY queue DESC LIMIT 1");
            if (mysqli_num_rows($qLQ) > 0) {
                $lq = mysqli_fetch_all($qLQ, MYSQLI_ASSOC);
                $getQueue = (int) $lq[0]['LastQueue'];
                $lastQueue = $getQueue + 1;
            } else {
                $lastQueue = 1;
            }
        }
        $queueNumber = $lastQueue;
        $q = mysqli_query(
            $db_conn,
            "SELECT id, value FROM settings WHERE id IN(25,26)"
        );
        if (mysqli_num_rows($q) > 0) {
            while ($row = mysqli_fetch_assoc($q)) {
                if ($row["id"] == 25) {
                    $charge_ewallet = $row["value"];
                    $charge_xendit = $row["value"];
                } elseif ($row["id"] == 26 && (int) $paymentMethod == 2) {
                    $charge_ewallet = $row["value"];
                    $charge_xendit = $row["value"];
                }
            }
        } else {
            $charge_ewallet = 0;
            $charge_xendit = 0;
        }
        $status = getStatus($partnerID, $db_conn);
        $today = date("Y-m-d H:i:s");
        $charge_ur = (int) getChargeUr($status, $hide, $db_conn, $partnerID);
        if ($is_delivery == "1" || $is_delivery == 1) {
            if (strpos($delivery_detail, "Kurir Pribadi") !== false) {
            } else {
                $charge_ur = (int) getChargeUrShipper(
                    $status,
                    $hide,
                    $db_conn,
                    $partnerID
                );
            }
        }
        $charge_ur = 0;
        $shiftID = (int) getShiftID($partnerID, $db_conn);
        if ($paymentMethod == "11" || $paymentMethod == 11) {
            $status = 5;
        }
        //cek jika ada transaksi pending, maka tambah detail
        // isopenbill
        $getIsOpenBill = mysqli_query($db_conn, "SELECT open_bill, rounding_down_below, is_rounding, rounding_digits, is_temporary_qr FROM partner WHERE id='$data->partnerID' AND deleted_at IS NULL");
        $ob = mysqli_fetch_all($getIsOpenBill, MYSQLI_ASSOC);
        $isOB = $ob[0]['open_bill'];
        if($is_takeaway == 1 || $is_takeaway == "1"){
            $isOB = 0;
        }
        
        $isTemporaryQR = $ob[0]['is_temporary_qr'];
        $fetchedTrxQR = [];
        
        //validasi konsinyasi
        $sqlConsignmentValidation = "SELECT c.is_consignment FROM detail_transaksi dt LEFT JOIN transaksi t ON t.id = dt.id_transaksi LEFT JOIN menu m ON m.id = dt.id_menu LEFT JOIN categories c ON c.id = m.id_category WHERE dt.id_transaksi = '$trx_id_qr' AND dt.deleted_at IS NULL AND dt.status NOT IN (3,4) AND t.status NOT IN (3,4) AND t.paid_date IS NULL AND c.is_consignment = 1";
        $mqSqlConsignmentValidation = mysqli_query($db_conn, $sqlConsignmentValidation);
        if(mysqli_num_rows($mqSqlConsignmentValidation) > 0){
             echo json_encode([
                "success" => 0,
                "status" => 204,
                "msg" => "Terdapat Menu Konsinyasi Dalam Transaksi",
                "unavailable" => $res1,
                "transaction_id" => $id,
                "ewallet_response" => $ewallet_response,
                "data"=>$data,
                "is_membership"=>$isMembership,
                "token"=>$token,
                "total"=>$total,
                ]);
            return; 
        }

        //temp qr        
        if($isTemporaryQR == "1"){
            $tempQRQuery = "SELECT t.id, t.service, t.tax, t.id_partner, t.employee_discount_percent, t.total, t.charge_ur, t.promo, t.rounding, t.id_voucher FROM transaksi t WHERE t.deleted_at IS NULL AND t.status=5 AND t.id_partner='$data->partnerID' AND t.id='$trx_id_qr' AND t.deleted_at IS NULL AND t.paid_date IS NULL";
            $mqTempQR = mysqli_query($db_conn, $tempQRQuery);
            $fetchTempQR = mysqli_fetch_all($mqTempQR, MYSQLI_ASSOC);
            
            if($is_takeaway == "1"  || $is_takeaway == 1){
                $isTemporaryQR = 0;
            }
            
            //if there is no transactions
            else if(mysqli_num_rows($mqTempQR) < 1 && ($is_takeaway == "0" || $is_takeaway == 0) && $trx_id_qr != 0){
                echo json_encode([
                    "success" => 0,
                    "status" => 204,
                    "msg" => "Transaksi Sudah Dibayar, Panggil Waiter Untuk Mendapatkan QR Baru",
                    "unavailable" => $res1,
                    "transaction_id" => $id,
                    "ewallet_response" => $ewallet_response,
                    "data"=>$data,
                    "is_membership"=>$isMembership,
                    "token"=>$token,
                    "total"=>$total,
                    ]);
                return; 
            } else {
                 $isTemporaryQR = "1";
                 $fetchedTrxQR = $fetchTempQR;
            }
            
        }
        
        $rdb = $ob[0]['rounding_down_below'];
        $isRounding = $ob[0]['is_rounding'];
        $roundingDigits = $ob[0]['rounding_digits'];
        $validateTransactions = mysqli_query(
            $db_conn,
            "SELECT t.id, t.service, t.tax, t.id_partner, t.employee_discount_percent, t.total, t.charge_ur, t.promo, t.rounding, t.id_voucher FROM transaksi t WHERE t.deleted_at IS NULL AND t.status=5 AND t.id_partner='$data->partnerID' AND t.takeaway='$data->is_takeaway' AND phone='$token->phone' AND customer_name='$token->name' ORDER BY t.jam DESC LIMIT 1"
        );
        if ((($isOB == "1" && mysqli_num_rows($validateTransactions) > 0 && $isTemporaryQR == "0") || $isTemporaryQR == "1" )
            // && ($data->takeaway == 0 || $data->takeaway == "0") 
            && $data->partnerID != "000217" && $data->partnerID != "001912") {
            if($isOB == "1" && $isTemporaryQR == "0"){
                $res = mysqli_fetch_all($validateTransactions, MYSQLI_ASSOC);
            } else {
                $res = $fetchedTrxQR;
            }
            $isAddDetail = true;
            $details = [];
            $detailIdx = 0;
            // $res = mysqli_fetch_all($validateTransactions, MYSQLI_ASSOC);
            $transactionID = $res[0]["id"];
            $pservice = $res[0]["service"];
            $ptax = $res[0]["tax"];
            $edp = $res[0]["employee_discount_percent"];
            $total = $res[0]["total"];
            $rounding = (int)$res[0]["rounding"];
            $charge_ur = $res[0]["charge_ur"];
            $promo = $res[0]["promo"];  
            $voucherID = $res[0]["id_voucher"];
            $dataDetail = $data->detail;
            foreach ($dataDetail as $cart) {

                $notes = remove_emoji($cart->notes);

                $surcharge_change = 0;
                if (
                    isset($cart->surcharge_change) &&
                    !empty(isset($cart->surcharge_change)) &&
                    $cart->surcharge_change != 0
                ) {
                    $surcharge_change = $cart->surcharge_change;
                }

                $is_program = 0;
                if (
                    isset($cart->is_program) &&
                    !empty(isset($cart->is_program)) &&
                    $cart->is_program != 0
                ) {
                    $is_program = $cart->is_program;
                }

                $is_smart_waiter = 0;
                // if (
                //     isset($cart->is_smart_waiter) &&
                //     !empty(isset($cart->is_smart_waiter)) &&
                //     $cart->is_smart_waiter != 0
                // ) {
                //     $is_smart_waiter = $cart->is_smart_waiter;
                // }

                if (
                    isset($cart->isSmartWaiter) &&
                    !empty(isset($cart->isSmartWaiter)) &&
                    $cart->isSmartWaiter != 0
                ) {
                    $is_smart_waiter = $cart->isSmartWaiter;
                }

                // $menuQ = mysqli_query(
                //     $db_conn,
                //     "SELECT nama, harga, stock, is_recipe, harga_diskon FROM `menu` WHERE id='$cart->id' AND enabled='1'"
                // );
                // $mn = mysqli_fetch_all($menuQ, MYSQLI_ASSOC);
                // $price = $mn[0]['harga'];
                // if((int) $mn[0]['harga_diskon'] > 0){
                //     $price = $mn[0]['harga_diskon'];
                // }
                // $total += (int) $price * $cart->qty;
                
                // $total += (int) $mn[0]['price'] * $cart->qty;

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
                            // $vID = $detail->id;
                            // $menuQ = mysqli_query(
                            //     $db_conn,
                            //     "SELECT price  FROM `variant` WHERE `id` = '$vID'"
                            // );
                            // $mn = mysqli_fetch_all($menuQ, MYSQLI_ASSOC);
                            // $total += (int) $mn[0]['price'] * $cart->qty;
                            
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

                $isConsignment = 0;
                if (
                    isset($cart->isConsignment) &&
                    !empty($cart->isConsignment)
                ) {
                    if (
                        $cart->isConsignment == "1" ||
                        $cart->isConsignment == 1
                    ) {
                        $isConsignment = 1;
                        $service = 0;
                        $tax = 0;
                    }
                }
                $insertDetail = mysqli_query(
                    $db_conn,
                    "INSERT INTO detail_transaksi(id_transaksi, id_menu, harga_satuan, qty, notes, harga,variant, is_program, surcharge_change, is_smart_waiter,is_consignment) VALUES ('$transactionID', '$cart->id', '$cart->price', '$cart->qty', '$notes', '$cart->totalPrice', $json, $is_program, '$surcharge_change', '$is_smart_waiter', '$isConsignment');"
                );
                $iidDetail = mysqli_insert_id($db_conn);
                $details[$detailIdx] = $iidDetail;
                $qDT = mysqli_query(
                    $db_conn,
                    "SELECT dt.id_menu, dt.qty, dt.variant, m.is_recipe FROM `detail_transaksi` dt JOIN menu m ON m.id = dt.id_menu WHERE dt.id='$iidDetail' AND dt.deleted_at IS NULL"
                );
                if (mysqli_num_rows($qDT) > 0) {
                    $detailsTransaction = mysqli_fetch_all($qDT, MYSQLI_ASSOC);

                    $menusOrder = [];
                    $variantOrder = [];
                    $imo = 0;
                    $iv = 0;
                    foreach ($detailsTransaction as $value) {
                        $menusOrder[$imo]["id_menu"] = $value["id_menu"];
                        $menusOrder[$imo]["qty"] = $value["qty"];
                        $menusOrder[$imo]["is_recipe"] = $value["is_recipe"];
                        $cut = $value["variant"];
                        $cut = substr($cut, 11);
                        $cut = substr($cut, 0, -1);
                        $cut = str_replace("'", '"', $cut);
                        $menusOrder[$imo]["variant"] = json_decode($cut);
                        if ($menusOrder[$imo]["variant"] != null) {
                            foreach ($menusOrder[$imo]["variant"] as $value1) {
                                foreach ($value1->detail as $value2) {
                                    $variantOrder[$iv]["id"] = $value2->id;
                                    $variantOrder[$iv]["qty"] = $value2->qty;
                                    $ch = $fs->variant_stock_reduce(
                                        $value2->id,
                                        $value2->qty
                                    );
                                    $iv += 1;
                                }
                            }
                        }
                        $imo += 1;
                    }
                    //Menu
                    foreach ($menusOrder as $value) {
                        $qtyOrder = $value["qty"];
                        $menuID = $value["id_menu"];
                        $isRecipe = $value["is_recipe"];
                        // $ch = $fs->stock_reduce($menuID, $qtyOrder);
                        $ch = $fs->stock_reduce($menuID, $qtyOrder, 0, $masterID, $partnerID, $isRecipe);
                    }
                }
                $detailIdx++;
            }

            $qDT = mysqli_query(
                $db_conn,
                "SELECT harga_satuan, qty FROM `detail_transaksi` WHERE id_transaksi='$transactionID' AND deleted_at IS NULL"
            );
            
            $total = 0;
            if (mysqli_num_rows($qDT) > 0) {
                while ($row = mysqli_fetch_assoc($qDT)) {
                    $total += (int) $row["harga_satuan"] * (int) $row["qty"];
                }
            }
            
            $qMember = mysqli_query(
              $db_conn,
              "SELECT max_disc FROM special_member WHERE phone='$phone' AND id_master = '$masterID' "
            );
            
            $diskon_spesial = 0;
            while ($row = mysqli_fetch_assoc($qMember)){
                $diskon_spesial = ($total * ceil($row['max_disc'])) / 100;
            }
            
            $total_program = 0;
            if (isset($data->total_program) && !empty($data->total_program)) {
                $total_program = $data->total_program;
                $total += $total_program;
            }
            
            if ($edp == 0) {
                $discountValue = 0;
            } else {
                $discountValue = ceil(($total * $edp) / 100);
            }

            $service = ceil((($total - $discountValue) * $pservice) / 100);

            $tax = ceil(
                (($total - $discountValue + $service + $charge_ur) * $ptax) /
                    100
            );
            $gtotal = $total - $discountValue + $charge_ur + $service + $tax;
            
            $normalTotal = $gtotal;
            $roundingData = 0;
            $roundingInt = 0;
            $roundingString = "";
            $rounding = $rounding + (int)$data->rounding ?? 0;
            if($rounding != 0 && ($isRounding == 1 || $isRounding == "1")){
                // $roundingData = (int) $rounding;
                $roundingInt = $rdb;
                // if($roundingData < 0){
                //     $roundingInt = $rdb * (-1);
                // }
                
                $roundingString = (string) $rdb;

                if (strlen($roundingString) == 3) {
                        if ($gtotal % 1000 < $roundingInt) {
                            $gtotal = $gtotal - ($gtotal % 1000);
                        } else {
                            $gtotal = $gtotal + (1000 -  ($gtotal % 1000));
                        }
                } else if (strlen($roundingString) == 2) {
                        if ($gtotal % 100 < $roundingInt) {
                            $gtotal = $total - ($gtotal % 100);
                        } else {
                            $gtotal = $gtotal + (100 -  ($gtotal % 100));
                        }
                } else if (strlen($roundingString) == 1) {
                        if ($gtotal % 10 < $roundingInt) {
                            $gtotal = $gtotal - ($gtotal % 10);
                        } else {
                            $gtotal = $gtotal + (10 -  ($gtotal % 10));
                        }
                    }   
            }
            
            $rounding = $gtotal - $normalTotal;

            if ($insertDetail) {
                if($isTemporaryQR == "1"){
                    $sqlTrxProfileValidation = "SELECT customer_name, phone FROM transaksi WHERE id = '$trx_id_qr'";
                    $mqTrxProfileValidation  = mysqli_query($db_conn, $sqlTrxProfileValidation);
                    $fetchTrxProfileValidation = mysqli_fetch_all($mqTrxProfileValidation, MYSQLI_ASSOC);
                    
                    if($fetchTrxProfileValidation[0]['phone'] == "POS/PARTNER" || $fetchTrxProfileValidation[0]['phone'] == 'WAITERAPP' ){
                        $phone = $token->phone;
                        $customerName = $token->name;
                        $updateT = mysqli_query(
                            $db_conn,
                            "UPDATE `transaksi` SET `total`='$total', `employee_discount`='$discountValue', diskon_spesial=$diskon_spesial, promo='$promo', id_voucher='$voucherID', rounding=$rounding, phone='$phone', customer_name='$customerName' WHERE `id`='$transactionID'"
                        );
                    } else {
                        $updateT = mysqli_query(
                            $db_conn,
                            "UPDATE `transaksi` SET `total`='$total', `employee_discount`='$discountValue', promo='$promo', diskon_spesial=$diskon_spesial, id_voucher='$voucherID', rounding=$rounding WHERE `id`='$transactionID'"
                        );
                    }
                    $customerEmail = $data->email ?? "";
                
                } else {
                    $customerName = $res[0]['name'];
                    $customerEmail = $data->email ?? "";
                    $updateT = mysqli_query(
                        $db_conn,
                        "UPDATE `transaksi` SET `total`='$total', `employee_discount`='$discountValue', diskon_spesial=$diskon_spesial, promo='$promo', id_voucher='$voucherID', rounding=$rounding WHERE `id`='$transactionID'"
                    );
                }
                
                $id = $transactionID; 
                $success = 1;
                $status = 200;
                $msg = "Berhasil";
            } else {
                $success = 0;
                $status = 204;
                $msg = "Gagal. Mohon coba lagi";
            }
            // break;
        } else {
            $shiftID = (int)getShiftID($partnerID, $db_conn);
            if ($paymentMethod == '11' || $paymentMethod == 11) {
                if($isOB == 0 || $isOB == "0"){
                    $status = 0;
                }
                if($isOB == 1 || $isOB == "1"){
                    $status = 5;
                }
            }
            
            $total = 0;
            
            $total_program = 0;
            if (isset($data->total_program) && !empty($data->total_program)) {
                $total_program = $data->total_program;
                $total += $total_program;
            }

            $idx = 0;

            $il = 0;
            $dataDetail = $data->detail;
            $boolQty = true;
                foreach ($dataDetail as $cart) {
                    $items[$il] = new \stdClass();
                    $items[$il]->id = $cart->id;
                    $items[$il]->quantity = $cart->qty;
                    $menuQ = mysqli_query(
                        $db_conn,
                        "SELECT nama, harga, stock, is_recipe, harga_diskon FROM `menu` WHERE id='$cart->id' AND enabled='1'"
                    );
                    $mn = mysqli_fetch_all($menuQ, MYSQLI_ASSOC);
                    $items[$il]->name = $mn[0]["nama"];
                    $items[$il]->price = $mn[0]['harga'];
                    if((int) $mn[0]['harga_diskon'] > 0){
                        $items[$il]->price = $mn[0]['harga_diskon'];
                    }
                    $stockMenu = (int) $mn[0]["stock"];
                    $is_recipeMenu = $mn[0]["is_recipe"];
                    $id_menu = $cart->id;
                    $total += $items[$il]->price * $cart->qty;

                    if ($stockMenu < $cart->qty && 
                        ($aos == 0 || ($paymentMethod != '11' && $aos != 1))
                        ) {
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
                                $menuQ = mysqli_query(
                                    $db_conn,
                                    "SELECT name, stock, price  FROM `variant` WHERE `id` = '$vID'"
                                );
                                $mn = mysqli_fetch_all($menuQ, MYSQLI_ASSOC);
                                $stockMenu = (int) $mn[0]["stock"];
                                $total += (int) $mn[0]['price'] * $cart->qty;
                                if ($stockMenu < $cart->qty && 
                                    ($aos == 0 || ($paymentMethod != '11' && $aos != 1))
                                    ) {
                                    $boolQty = false;
                                    $res1[$idx]["nama"] = "Varian " . $mn[0]["name"];
                                    $idx += 1;
                                }
                                // if ($stockMenu < $cart->qty ($aos == 0 || ($paymentMethod != '11' && $aos != 1))) {
                                //     $boolQty = false;
                                //     $res1[$idx]["nama"] =
                                //         "Varian " . $mn[0]["name"];
                                //     $idx += 1;
                                // }
                            }
                        }
                    }
                }

            $customerName = "";
            $customerEmail = "";
            $selectCustomers = mysqli_query($db_conn, "SELECT id,name,email FROM users WHERE phone='$token->phone' AND deleted_at IS NULL");
            $res = mysqli_fetch_all($selectCustomers, MYSQLI_ASSOC);
        
            $customerName = $res[0]['name'];
            $customerEmail = $data->email ?? "";

            // DEVELOPMENT DISKON SPESIAL            
            $qMember = mysqli_query(
              $db_conn,
              "SELECT max_disc FROM special_member WHERE phone='$phone' AND id_master = '$masterID' "
            );
            
            $diskon_spesial = 0;
            while ($row = mysqli_fetch_assoc($qMember)){
                $diskon_spesial = ($total * ceil($row['max_disc'])) / 100;
            }
            if ($boolQty == true) {
                // mysqli_autocommit($db_conn,FALSE);
                $COMMIT = mysqli_query(
                    $db_conn,
                    "
                START TRANSACTION;
                SAVEPOINT '$id';
                COMMIT;
                "
                );
                $sql = "START TRANSACTION;";

                if (isset($data->rounding)) {
                    if ($paymentMethod == '11' || $paymentMethod == 11 || $paymentMethod == '1' || $paymentMethod == 1 || $paymentMethod == '2' || $paymentMethod == 2 || $paymentMethod == '3' || $paymentMethod == 3 || $paymentMethod == '4' || $paymentMethod == 4 || $paymentMethod == '6' || $paymentMethod == 6 || $paymentMethod == '10' || $paymentMethod == 10 || $paymentMethod == 14) {
                        $sql .= "INSERT INTO transaksi(`id`, `jam`, `phone`, `id_partner`, `no_meja`, `status`, `total`, `tipe_bayar`, `promo`, `point`, `queue`, `takeaway`, `notes`, `id_foodcourt`, `tax`, `service`, `charge_ewallet`, `charge_xendit`, `charge_ur`, `id_voucher`, `id_voucher_redeemable`, `diskon_spesial`, `shift_id`, `program_discount`, `program_id`, customer_name, customer_email, `source`, `rounding`, `is_repeat`) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$subtotal', '$paymentMethod', '$promo', '$point', '$queueNumber', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$shiftID', '$program_discount', '$program_id', '$customerName', '$customerEmail', 'sfWeb', '$data->rounding', '$isRepeater'); ";
                    } else {
                        $sql .= "INSERT INTO transaksi(`id`, `jam`, `phone`, `id_partner`, `no_meja`, `status`, `total`, `tipe_bayar`, `promo`, `point`, `queue`, `takeaway`, `notes`, `id_foodcourt`, `tax`, `service`, `charge_ewallet`, `charge_xendit`, `charge_ur`, `id_voucher`, `id_voucher_redeemable`, `diskon_spesial`, `program_discount`, `program_id`, customer_name, customer_email, `source`,`rounding`, `is_repeat`) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$subtotal', '$paymentMethod', '$promo', '$point', '$queueNumber', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$program_discount', '$program_id', '$customerName', '$customerEmail', 'sfWeb','$data->rounding', '$isRepeater'); ";
                    }
                } else {
                    if ($paymentMethod == '11' || $paymentMethod == 11 || $paymentMethod == '1' || $paymentMethod == 1 || $paymentMethod == '2' || $paymentMethod == 2 || $paymentMethod == '3' || $paymentMethod == 3 || $paymentMethod == '4' || $paymentMethod == 4 || $paymentMethod == '6' || $paymentMethod == 6 || $paymentMethod == '10' || $paymentMethod == 10 || $paymentMethod == 14) {
                        $sql .= "INSERT INTO transaksi(`id`, `jam`, `phone`, `id_partner`, `no_meja`, `status`, `total`, `tipe_bayar`, `promo`, `point`, `queue`, `takeaway`, `notes`, `id_foodcourt`, `tax`, `service`, `charge_ewallet`, `charge_xendit`, `charge_ur`, `id_voucher`, `id_voucher_redeemable`, `diskon_spesial`, `shift_id`, `program_discount`, `program_id`, customer_name, customer_email, `source`, `is_repeat`) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$subtotal', '$paymentMethod', '$promo', '$point', '$queueNumber', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$shiftID', '$program_discount', '$program_id', '$customerName', '$customerEmail', 'sfWeb', '$isRepeater'); ";
                    } else {
                        $sql .= "INSERT INTO transaksi(`id`, `jam`, `phone`, `id_partner`, `no_meja`, `status`, `total`, `tipe_bayar`, `promo`, `point`, `queue`, `takeaway`, `notes`, `id_foodcourt`, `tax`, `service`, `charge_ewallet`, `charge_xendit`, `charge_ur`, `id_voucher`, `id_voucher_redeemable`, `diskon_spesial`, `program_discount`, `program_id`, customer_name, customer_email, `source`, `is_repeat`) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$subtotal', '$paymentMethod', '$promo', '$point', '$queueNumber', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$program_discount', '$program_id', '$customerName', '$customerEmail', 'sfWeb', '$isRepeater'); ";
                    }
                }

                if ($is_delivery == "1" || $is_delivery == 1) {
                    if (strpos($delivery_detail, "Kurir Pribadi") !== false) {
                        // echo 'true';
                    } else {
                    }
                    $sql .= "INSERT INTO `delivery`(`transaksi_id`, `ongkir`, `rate_id`, `user_address_id`, `is_insurance`, `delivery_detail`, `distance`) VALUES ('$id', '$delivery_fee', '$rate_id', '$user_address_id', '$is_insurance', '$delivery_detail', '$distance'); ";
                }

                // if($insert){
                // addPoint($phone, $total, $partnerID, $db_conn);
                getIDVR($phone, $id_voucher_redeemable, $id, $db_conn);
                $dataDetail = $data->detail;
                $idx = 0;
                $il = 0;
                $items = [];

                $boolCheck = true;
                // $insertDT = $db->insertDetailTransaksiAndroid($id, $dataDetail);
                foreach ($dataDetail as $cart) {
                    $is_program = 0;
                    if (
                        isset($cart->is_program) &&
                        !empty(isset($cart->is_program)) &&
                        $cart->is_program != 0
                    ) {
                        $is_program = $cart->is_program;
                    }
                    $is_smart_waiter = 0;
                    if (
                        isset($cart->isSmartWaiter) &&
                        !empty(isset($cart->isSmartWaiter)) &&
                        $cart->isSmartWaiter != 0
                    ) {
                        $is_smart_waiter = $cart->isSmartWaiter;
                    }
                    // if (
                    //     isset($cart->is_smart_waiter) &&
                    //     !empty(isset($cart->is_smart_waiter)) &&
                    //     $cart->is_smart_waiter != 0
                    // ) {
                    //     $is_smart_waiter = $cart->is_smart_waiter;
                    // }
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
                            $name = str_replace("'", "\\\''", $vars->name);
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
                                $detailName = str_replace("'", "\\\''", $detail->name);
                                $json .= "''name'':''" . $detailName . "''}";
                                $j += 1;
                            }
                            $json .= "]}";
                        }
                        $json .= "]]'";
                        // $json = json_encode($json);
                    }
                    $tempH = $cart->price * $cart->qty;
                    $notes = remove_emoji($cart->notes);
                    $sql .= "INSERT INTO detail_transaksi(id_transaksi, id_menu, harga_satuan, qty, notes, harga,variant, is_program, is_smart_waiter) VALUES ('$id', '$cart->id', '$cart->price', '$cart->qty', '$notes', '$tempH', $json, '$is_program', '$is_smart_waiter');";
                }

                // if ($insertDT==5) {
                // }else{
                //     $boolCheck=false;
                // }
                $sql .= " COMMIT;";
                // $act= mysqli_multi_query($db_conn,$sql)or die(mysqli_error($db_conn));

                if (mysqli_multi_query($db_conn, $sql)) {
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

                    if(err == ""){
                        if ($status == 5) {
                        $qDT = mysqli_query(
                            $db_conn,
                            "SELECT dt.id_menu, dt.qty, dt.variant, m.is_recipe FROM `detail_transaksi` dt JOIN menu m ON m.id = dt.id_menu WHERE dt.id='$id' AND dt.deleted_at IS NULL"
                        );
                        if (mysqli_num_rows($qDT) > 0) {
                            $detailsTransaction = mysqli_fetch_all(
                                $qDT,
                                MYSQLI_ASSOC
                            );

                            $menusOrder = [];
                            $variantOrder = [];
                            $imo = 0;
                            $iv = 0;
                            foreach ($detailsTransaction as $value) {
                                $menusOrder[$imo]["id_menu"] =
                                    $value["id_menu"];
                                $menusOrder[$imo]["qty"] = $value["qty"];
                                $menusOrder[$imo]["is_recipe"] = $value["is_recipe"];
                                $cut = $value["variant"];
                                $cut = substr($cut, 11);
                                $cut = substr($cut, 0, -1);
                                $cut = str_replace("'", '"', $cut);
                                $menusOrder[$imo]["variant"] = json_decode(
                                    $cut
                                );
                                if ($menusOrder[$imo]["variant"] != null) {
                                    foreach ($menusOrder[$imo]["variant"]
                                        as $value1) {
                                        foreach ($value1->detail as $value2) {
                                            $variantOrder[$iv]["id"] =
                                                $value2->id;
                                            $variantOrder[$iv]["qty"] =
                                                $value2->qty;
                                            $ch = $fs->variant_stock_reduce(
                                                $value2->id,
                                                $value2->qty
                                            );
                                            $iv += 1;
                                        }
                                    }
                                }
                                $imo += 1;
                            }

                            //Menu
                            foreach ($menusOrder as $value) {
                                $qtyOrder = $value["qty"];
                                $menuID = $value["id_menu"];
                                $isRecipe = $value["is_recipe"];
                                // $ch = $fs->stock_reduce($menuID, $qtyOrder);
                                $ch = $fs->stock_reduce($menuID, $qtyOrder, 0, $masterID, $partnerID, $isRecipe);
                            }
                        }
                    }
                    }

                    if($err != ""){
                        // $newRecursive = (int)$recursive + 1;
                        // createTrx($newRecursive);
                        $msg = "Terjadi Kesalahan, Mohon Coba Lagi";
                        $success = 0;
                        $status = 203;  
                    } else if (
                        (($paymentMethod == "1" || $paymentMethod == 1) && ($total > 2000000 || $total < 100)) ||
                        (($paymentMethod == "2" || $paymentMethod == 2) && ($total > 2000000 || $total < 100)) ||
                        (($paymentMethod == "3" || $paymentMethod == 3) && ($total > 10000000 || $total < 100)) ||
                        (($paymentMethod == "4" || $paymentMethod == 4) && ($total > 10000000 || $total < 100)) ||
                        (($paymentMethod == "10" || $paymentMethod == 10) && ($total < 100)) ||
                        (($paymentMethod == "14" || $paymentMethod == 14) && ($total > 10000000 || $total < 1))
                    ) {
                        $msg = "Amount Not Valid";
                        $success = 0;
                        $status = 203;
                    } else {
                        if (
                            $paymentMethod == 5 ||
                            $paymentMethod == "5" ||
                            $paymentMethod == 7 ||
                            $paymentMethod == "7" ||
                            $paymentMethod == 8 ||
                            $paymentMethod == "8" ||
                            $paymentMethod == 9 ||
                            $paymentMethod == "9" ||
                            $paymentMethod == 11 ||
                            $paymentMethod == "11"
                        ) {
                            $tservice = ceil(
                                // (($total -
                                (($subtotal -
                                    $promo -
                                    $program_discount -
                                    $diskon_spesial) *
                                    $service) /
                                    100
                            );
                            $ttax = ceil(
                                // (($total -
                                (($subtotal -
                                    $promo -
                                    $program_discount -
                                    $diskon_spesial +
                                    $tservice +
                                    $charge_ur) *
                                    $tax) /
                                    100
                            );
                            $amount = (int) ceil(
                                // $total -
                                $subtotal -
                                    $promo -
                                    $program_discount -
                                    $diskon_spesial +
                                    $tservice +
                                    $ttax +
                                    $delivery_fee +
                                    $charge_ur +
                                    (int)$rounding
                            );
                        } else {
                            $pmq = mysqli_query(
                                $db_conn,
                                "SELECT nama FROM `payment_method` WHERE id='$paymentMethod'"
                            );
                            $pm = mysqli_fetch_all($pmq, MYSQLI_ASSOC);
                            $ewallet_type = $pm[0]["nama"];
                            $tservice = ceil(
                                // (($total -
                                (($subtotal -
                                    $promo -
                                    $program_discount -
                                    $diskon_spesial) *
                                    $service) /
                                    100
                            );
                            $ttax = ceil(
                                // (($total -
                                (($subtotal -
                                    $promo -
                                    $program_discount -
                                    $diskon_spesial +
                                    $tservice +
                                    $charge_ur) *
                                    $tax) /
                                    100
                            );
                            $amount = (int) ceil(
                                // $total -
                                $subtotal -
                                    $promo -
                                    $program_discount -
                                    $diskon_spesial +
                                    $tservice +
                                    $ttax +
                                    $delivery_fee +
                                    $charge_ur +
                                    (int)$rounding
                            );
                            $phone1 = substr($phone, 1);
                            $phone1 = "+62" . $phone1;
                            if (
                                $paymentMethod == "1" ||
                                $paymentMethod == 1 ||
                                $paymentMethod == "3" ||
                                $paymentMethod == 3 ||
                                $paymentMethod == "4" ||
                                $paymentMethod == 4 ||
                                $paymentMethod == "10" ||
                                $paymentMethod == 10 ||
                                $paymentMethod == "14" ||
                                $paymentMethod == 14
                            ) {
                                $redirect_url = $_ENV["XENDIT_REDIRECT_URL"] . "success?transactionID=" . $id . "&status=" . "1";
                                if ($paymentMethod == "1" || $paymentMethod == 1) {
                                    $params = [
                                        "external_id" => $id,
                                        "reference_id" => $id,
                                        "currency" => "IDR",
                                        "amount" => $amount,
                                        "checkout_method" => "ONE_TIME_PAYMENT",
                                        "channel_code" => "ID_OVO",
                                        "channel_properties" => [
                                            "mobile_number" => $phone1,
                                        ],
                                        "ewallet_type" => "ID_OVO",
                                        "phone" => $phone,
                                        "items" => $items,
                                    ];
                                } elseif (
                                    $paymentMethod == "3" ||
                                    $paymentMethod == 3
                                ) {
                                    $params = [
                                        "external_id" => $id,
                                        "reference_id" => $id,
                                        "currency" => "IDR",
                                        "amount" => $amount,
                                        "checkout_method" => "ONE_TIME_PAYMENT",
                                        "channel_code" => "ID_DANA",
                                        "channel_properties" => [
                                            "success_redirect_url" =>
                                            $redirect_url,
                                        ],
                                        "ewallet_type" => "ID_DANA",
                                        "phone" => $phone,
                                        "items" => $items,
                                    ];
                                } elseif (
                                    $paymentMethod == "4" ||
                                    $paymentMethod == 4
                                ) {
                                    $params = [
                                        "external_id" => $id,
                                        "reference_id" => $id,
                                        "currency" => "IDR",
                                        "amount" => $amount,
                                        "checkout_method" => "ONE_TIME_PAYMENT",
                                        "channel_code" => "ID_LINKAJA",
                                        "channel_properties" => [
                                            "success_redirect_url" =>

                                            $redirect_url,
                                        ],
                                        "ewallet_type" => "ID_LINKAJA",
                                        "phone" => $phone,
                                        "items" => $items,
                                    ];
                                } elseif (
                                    $paymentMethod == "10" ||
                                    $paymentMethod == 10
                                ) {
                                    $params = [
                                        "reference_id" => $id,
                                        "external_id" => $id,
                                        "currency" => "IDR",
                                        "amount" => $amount,
                                        "checkout_method" => "ONE_TIME_PAYMENT",
                                        "channel_code" => "ID_SHOPEEPAY",
                                        "channel_properties" => [
                                            "success_redirect_url" =>

                                            $redirect_url,
                                        ],
                                        "metadata" => [
                                            "branch_code" => "tree_branch",
                                        ],
                                    ];
                                } elseif (
                                    $paymentMethod == "14" ||
                                    $paymentMethod == 14
                                ) {
                                    $params = [
                                        // "external_id" => $id,
                                        "reference_id" => $id,
                                        // "callback_url" => $_ENV["BASEURL"] . "xendit/qris/Callback.php",
                                        "currency" => "IDR",
                                        "amount" => $amount,
                                        "type" => "DYNAMIC",
                                        "channel_code" => "ID_DANA",

                                        "metadata" => [
                                            "branch_code" => "tree_branch",
                                        ],
                                    ];
                                }
                                $ch = curl_init();
                                $timestamp = new DateTime();
                                $body = json_encode($params);
                                if (
                                    $paymentMethod == "14" ||
                                    $paymentMethod == 14
                                ) {
                                    curl_setopt(
                                        $ch,
                                        CURLOPT_URL,
                                        "https://" .
                                            $_ENV["XENDIT_URL"] .
                                            "/qr_codes"
                                    );
                                } else {
                                    curl_setopt(
                                        $ch,
                                        CURLOPT_URL,
                                        "https://" .
                                            $_ENV["XENDIT_URL"] .
                                            "/ewallets/charges"
                                    );
                                }

                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($ch, CURLOPT_POST, 1);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                                curl_setopt(
                                    $ch,
                                    CURLOPT_USERPWD,
                                    $_ENV["XENDIT_KEY"] . ":" . ""
                                );

                                $headers = [];
                                $headers[] = "Content-Type: application/json";
                                
                                if($paymentMethod == "14" ||
                                    $paymentMethod == 14){
                                    $headers[] = "api-version: 2022-07-31";
                                    $headers[] = "webhook-url: " . $_ENV["BASEURL"] . "xendit/qris/Callback.php";
                                }
                                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                $result = curl_exec($ch);
                                if (curl_errno($ch)) {
                                    echo "Error:" . curl_error($ch);
                                }
                                $ewallet_response = json_decode($result);
                                if (
                                    $paymentMethod == "14" ||
                                    $paymentMethod == 14
                                ) {

                                    $qrString = $ewallet_response->qr_string;
                                    $updateQR = mysqli_query($db_conn, "UPDATE transaksi SET qr_string='$qrString' WHERE id='$id'");
                                }

                                $UpdateCallback = mysqli_query(
                                    $db_conn,
                                    "INSERT INTO `xendit_callbacks`(`transaction_id`, `value`, `created_at`) VALUES ('$id', '$result', NOW())"
                                );
                                curl_close($ch);
                            } elseif (
                                $paymentMethod == "2" ||
                                $paymentMethod == 2
                            ) {
                                $auth = $_ENV["MIDTRANS_KEY"];
                                $params = [
                                    "payment_type" => "gopay",
                                    "transaction_details" => [
                                        "order_id" => $id,
                                        "gross_amount" => $amount,
                                    ],
                                    "customer_details" => [
                                        "phone" => $phone,
                                    ],
                                ];
                                $ch = curl_init();
                                $timestamp = new DateTime();
                                $body = json_encode($params);
                                curl_setopt(
                                    $ch,
                                    CURLOPT_URL,
                                    $_ENV["MIDTRANS_URL"]
                                );
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($ch, CURLOPT_POST, 1);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

                                $headers = [];
                                $headers[] = "Content-Type: application/json";
                                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                    "Accept: application/json",
                                    "Content-Type: application/json",
                                    "Authorization: $auth",
                                ]);
                                $result = curl_exec($ch);
                                if (curl_errno($ch)) {
                                    echo "Error:" . curl_error($ch);
                                }
                                $ewallet_response = json_decode($result);
                                curl_close($ch);
                                $UpdateCallback = mysqli_query(
                                    $db_conn,
                                    "INSERT INTO `xendit_callbacks`(`transaction_id`, `value`, `created_at`) VALUES ('$id', '$result', NOW())"
                                );
                            }
                        }
                        $jsonToken = json_encode([
                            "email" => "",
                            "phone" => $token->phone,
                            "created_at" => date("Y-m-d H:i:s"),
                            "expired" => 30000,
                            "id" => 0,
                        ]);
                        $generatedToken = $tokenizer->stringEncryption(
                            "encrypt",
                            $jsonToken
                        );
                        $msg = "Success";
                        $success = 1;
                        $status = 200;
                        // mysqli_close($db_conn);
                    }
                } else {
                    $sql = "START TRANSACTION; ROLLBACK TO '$id';";
                    ($act = mysqli_multi_query($db_conn, $sql)) or
                        die(mysqli_error($db_conn));
                    // mysqli_close($db_conn);
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
                $msg = "Stock Menu Tidak Mencukupi";
                $success = 0;
                $status = 204;
            }
        }
    } else {
        if (!is_cart_valid($data->detail)){
            $success = 0;
            $msg = "Cart item quantity cannot be 0";
            $status = 400;
        } else {
            $success = 0;
            $msg = "Missing Mandatory Field";
            $status = 400;
        }
    }
}

if ($success == 1) {
    $allTrans = mysqli_query(
        $db_conn,
        "SELECT t.id, t.id_partner, t.queue, t.status, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.point, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, t.rounding, p.nama AS payment_method, u.name AS uname FROM transaksi t JOIN payment_method p ON p.id=t.tipe_bayar JOIN users u ON t.phone=u.phone WHERE t.id='$id'"
    );
    $order = mysqli_fetch_assoc($allTrans);
    $devTokens = $db->getPartnerDeviceTokens($partnerID);
    $mID = $db->getMembership($partnerID, $phone);
    if ($mID == 0) {
        $isMembership = false;
    } 
    // else {
    //     $selectPoint = mysqli_query($db_conn,"SELECT point FROM memberships WHERE id='$mID'");
    //     $dataPoint = mysqli_fetch_all($selectPoint, MYSQLI_ASSOC);
        
    //     $selectPointPartner = mysqli_query($db_conn,"SELECT membership_point, membership_point_multiplier FROM partner WHERE id='$partnerID'");
    //     $dataPartner = mysqli_fetch_all($selectPointPartner, MYSQLI_ASSOC);
        
    //     $point = $dataPoint[0]["point"];
    //     $mp = $dataPartner[0]["membership_point"];
    //     $mpm = $dataPartner[0]["membership_point_multiplier"];
    //     if($mpm != 0 && $mp != 0){
    //         $trxPoint = floor($amount/$mpm) * $mp;
    //         $newPoint = $point + $trxPoint;
            
    //         $updatePoint = mysqli_query($db_conn, "UPDATE memberships SET point = '$newPoint' WHERE id='$mID'");
            
    //         $insertPoint = mysqli_query(
    //             $db_conn,
    //             "INSERT INTO `points`(`master_id`, `user_phone`, `point`, `description`, `created_at`) VALUES ('$masterID', '$phone', '$trxPoint', 'point tambahan dari transaksi', NOW())"
    //         );
    //     }
        
    //     $selectVoucherRedeemable = mysqli_query($db_conn, "SELECT id FROM membership_voucher WHERE code='$data->id_voucher_redeemable'");
        
    //     if(isset($data->id_voucher_redeemable) && mysqli_num_rows($selectVoucherRedeemable) > 0){
    //         // minUserPoint($token->phone, $data->id_voucher_redeemable, $partnerID, $db_conn);
    //         $qSVR = mysqli_query($db_conn,"SELECT vr.point FROM membership_voucher vr JOIN partner p ON p.id_master=vr.master_id WHERE vr.code='$data->id_voucher_redeemable' AND vr.deleted_at IS NULL AND p.id='$partnerID'"
    //         );
    //         $q1SVR = mysqli_query(
    //             $db_conn,
    //             "SELECT m.id, point, m.master_id FROM memberships m JOIN partner p ON p.id_master=m.master_id WHERE m.user_phone='$token->phone' AND p.id='$partnerID' ORDER BY m.id DESC LIMIT 1"
    //         );
    //         if (mysqli_num_rows($qSVR) > 0 && mysqli_num_rows($q1SVR) > 0) {
    //             $resSVR = mysqli_fetch_all($qSVR, MYSQLI_ASSOC);
    //             $pPay = (int) $resSVR[0]["point"];
    //             $res1SVR = mysqli_fetch_all($q1SVR, MYSQLI_ASSOC);
    //             $uPoint = (int) $res1SVR[0]["point"];
    //             $uID = $res1SVR[0]["id"];
    //             $master_id = $res1SVR[0]["master_id"];
    //             $point = $uPoint - $pPay;
    //             $updateDataPoints = mysqli_query(
    //                 $db_conn,
    //                 "UPDATE `memberships` SET point='$point' WHERE id='$uID'"
    //             );
    //             $insertPoints = mysqli_query(
    //                 $db_conn,
    //                 "INSERT INTO `points`(`master_id`, `user_phone`, `point`, `description`, `created_at`) VALUES ('$master_id', '$token->phone', '-$pPay', 'Redeem Voucher $data->id_voucher_redeemable', NOW())"
    //             );
                
    //             $insertVoucher = mysqli_query($db_conn, "INSERT INTO `user_voucher_ownership`(`userid`, `voucherid`, `transaksi_id`,`obtained`) VALUES ('$token->phone', '$data->id_voucher_redeemable','$id','1')");
    //         } 
    //     }

    //     $isMembership = true;
    // }
    
    $birth_date = $db->getBirthdate($phone);
    $gender = $db->getGender($phone);
    $title = "Pesanan Baru";
    $subtitle = "";
    $salesType = "";
    $channel = "rn-push-notification-channel";
    $pm = $paymentMethod;

    if ($is_delivery == 1 || $is_delivery == "1") {
        $subtitle = "Pesanan Delivery Baru";
        $salesType = "Delivery";
    } elseif ($is_takeaway == 1 || $is_takeaway == "1") {
        $subtitle = "Pesanan Takeaway Baru";
        $salesType = "Takeaway";
    }
    // elseif ($is_queue == 1 || $is_queue == "1") {
    //     $subtitle = "Pesanan Antrian Baru";
    //     $salesType = "Antrian";
    // }
    elseif ($isAddDetail) {
        $title = "Pesanan tambahan";
        $subtitle = "Pesanan tambahan";
        $subtitle = "Pesanan tambahan di Meja " . $tableCode;
        $detailIDs = $details;
    } else {
        $subtitle = "Pesanan Baru di Meja " . $tableCode;
        $salesType = $tableCode;
    }
    if (empty($details)) {
        $detailIDs = [];
    } else {
        $detailIDs = $details;
    }
        
        $statusAfter = 0;
        if($paymentMethod == 11 || $paymentMethod == "11"){
            $statusAfter = 5;
        }
        
        $insertOST = mysqli_query($db_conn, "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`, `payment_method_before` ,`payment_method_after`, `created_by` ) VALUES ('$id', '0','$statusAfter' ,'0', '$paymentMethod', '$token->id')");

    // disini kasih if kalau dari ewallet itu tidak ngirim notification
    if (
        $paymentMethod == 5 ||
        $paymentMethod == "5" ||
        $paymentMethod == 7 ||
        $paymentMethod == "7" ||
        $paymentMethod == 8 ||
        $paymentMethod == "8" ||
        $paymentMethod == 9 ||
        $paymentMethod == "9" ||
        $paymentMethod == 11 ||
        $paymentMethod == "11"
    ) {
        $message = $subtitle;
        $no_meja = $tableCode;
        $channel_id = "rn-push-notification-channel";
        $methodPay = $paymentMethod;
        $trxStatus = 5;
        $queue = $order["queue"];
        // $query = "SELECT t.queue from transaksi t WHERE t.id = '$id'";
        // $queryFetch = mysqli_query($db_conn, $query);
        // $fetch = mysqli_fetch_assoc($queryFetch);
        // $queue = $fetch['queue'];

        $id_trans = $id;
        $action = null;
        $birthDate = $birth_date;
        $type = "employee";
        $details = json_encode($detailIDs);
        $orders = json_encode($order);
        foreach ($devTokens as $val) {
            $dev_token = $val['token'];
            if ($birthDate == "") {
                $birthDate = "0005-05-01";
            }
            
            $qSelect = "SELECT e.id FROM employees e LEFT JOIN device_tokens dt ON dt.employee_id = e.id LEFT JOIN roles r ON r.id = e.role_id  WHERE r.is_order_notif=1 AND e.order_notification=1 AND e.deleted_at IS NULL AND dt.deleted_at IS NULL AND dt.tokens = '$dev_token' AND dt.id_partner = '$partnerID'";
            $selectForNotif = mysqli_query($db_conn, $qSelect);
            
            if(mysqli_num_rows($selectForNotif) > 0){
                $qInsert = "INSERT INTO `pending_notification`(`phone`, `partner_id`, `dev_token`, `title`, `message`, `no_meja`, `channel_id`, `method_pay`, `status`, `queue`, `id_trans`, `action`, `orders`, `gender`, `birth_date`, `is_membership`, `delivery_fee`, `type`,created_at, details) VALUES ('$phone', '$partnerID', '$dev_token', '$title', '$message', '$no_meja', '$channel_id', '$methodPay', '$trxStatus', '$queue', '$id_trans', '$action', '$orders', '$gender', '$birthDate', '$isMembership', '$delivery_fee', '$type', NOW(), '$details')";
                $insertNotif = mysqli_query($db_conn, $qInsert);
            }
        }
    }
}
echo json_encode([
    "success" => $success,
    "status" => $status,
    "msg" => $msg,
    "unavailable" => $res1,
    "transaction_id" => $id,
    "ewallet_response" => $ewallet_response,
    "data"=>$data,
    "is_membership"=>$isMembership,
    "token"=>$token,
    "total"=>$total,
    "trx_id_qr"=>$trx_id_qr
]);

// createTrx();
