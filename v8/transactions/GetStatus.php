<?php


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header(
    "Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"
);

//import require file
require "../../db_connection.php";
require_once "../auth/Token.php";
require __DIR__ . "/../../vendor/autoload.php";
require_once "../../includes/DbOperation.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../..");
$dotenv->load();
$db = new DbOperation();

//init var
$headers = [];
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
$tokenizer = new Token();
$token = "";
$transactionStatus = "0";

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption("decrypt", $token));
if (isset($tokenValidate["success"]) && $tokenValidate["success"] == 0) {
    $status = $tokenValidate["status"];
    $msg = $tokenValidate["msg"];
    $success = 0;

    echo json_encode([
        "success" => $success,
        "status" => $status,
        "msg" => $msg,
    ]);
} else {
    $id = $_GET["id"];
    $phone = $_GET["phone"];
    $qr = '';
    if(isset($_GET["QR"])){
        $qr = $_GET["QR"];
    }

    // get status transaksi dari sini
    $transactionStatus = 0;
    $sqlGetByID = mysqli_query($db_conn, "SELECT t.status, t.tipe_bayar AS paymentMethod, t.created_at, t.id_partner, t.queue, t.qr_string, t.total, t.program_discount, t.promo, t.diskon_spesial, t.employee_discount, t.tax, t.service, t.charge_ur, t.rounding, pm.nama AS paymentMethodName, t.updated_at, t.paid_date FROM `transaksi` t JOIN `payment_method` pm ON pm.id=t.tipe_bayar WHERE t.id = '$id' AND t.deleted_at IS NULL");
    $dataByID = mysqli_fetch_all($sqlGetByID, MYSQLI_ASSOC);
    $transactionStatus = $dataByID[0]['status'];
    $paymentMethod = $dataByID[0]['paymentMethod'];
    $paymentMethodName = $dataByID[0]['paymentMethodName'];
    $qrString = $dataByID[0]['qr_string'];
    $total = (int)$dataByID[0]['total'];
    $created_at = $dataByID[0]['created_at'];
    $discount_program = (int)$dataByID[0]['program_discount'];
    $discount_promo = (int)$dataByID[0]['promo'];
    $discount_special = (int)$dataByID[0]['diskon_spesial'];
    $partnerID = $dataByID[0]['id_partner'];
    $discount_employee = (int)$dataByID[0]['employee_discount'];
    $rounding = (int)$dataByID[0]['rounding'];
    $totalDiscount = $discount_program + $discount_promo + $discount_special + $discount_employee;
    $service = $dataByID[0]['service'];
    $updatedAt = $dataByID[0]['updated_at'];
    $totalService = ceil((($total - $totalDiscount) * (float)$service) / 100);
    $tax = $dataByID[0]['tax'];
    $totalTax = ceil((($total - $totalDiscount + $totalService) * (float)$tax) / 100);
    $grandTotal = $total + $totalService + $totalTax + $rounding - $totalDiscount;
    $queue = $dataByID[0]['queue'];
    $paid_date = 
    $mID = $db->getMembership($partnerID, $phone);
    if ($mID == 0) {
        $isMembership = false;
    } else {
        $isMembership = true;
    }
    
    $sqlPartnerTempQR = "SELECT is_temporary_qr FROM partner WHERE partner.id = '$partnerID'";
    $mqPartnerTempQR = mysqli_query($db_conn, $sqlPartnerTempQR);
    $fetchPartnerTempQR = mysqli_fetch_all($mqPartnerTempQR, MYSQLI_ASSOC);
    $isTempQR = $fetchPartnerTempQR[0]['is_temporary_qr'];
    
    $sqlConsignmentValidation = "SELECT c.is_consignment FROM detail_transaksi dt LEFT JOIN transaksi t ON t.id = dt.id_transaksi LEFT JOIN menu m ON m.id = dt.id_menu LEFT JOIN categories c ON c.id = m.id_category WHERE dt.id_transaksi = '$id' AND dt.deleted_at IS NULL AND dt.status NOT IN (3,4) AND t.status NOT IN (3,4) AND t.paid_date IS NULL AND c.is_consignment = 1";
    $mqSqlConsignmentValidation = mysqli_query($db_conn, $sqlConsignmentValidation);
    
    if(mysqli_num_rows($mqSqlConsignmentValidation) > 0){
        echo json_encode([
            "success" => $success,
            "status" => $status,
            "msg" => "Terdapat Menu Konsinyasi Dalam Transaksi",
            "id" => $id,
            "transactionStatus" => $transactionStatus,
            "paymentMethod" => $paymentMethod,
            "paymentMethodName" => $paymentMethodName,
            "qrString" => $qrString,
            "total" => $grandTotal,
            "createdAt" => $created_at,
            "queue" => $queue,
            "isMembership" => $isMembership,
            "updatedAt" =>  $updatedAt
        ]);
        return;
    }

    $sqlTransactionPaid = "SELECT id FROM transaksi t WHERE t.paid_date IS NOT NULL WHERE id='$id'";
    $mqSqlTransasctionPaid = mysqli_query($db_conn, $sqlTransactionPaid);
    
    if(mysqli_num_rows($mqSqlTransasctionPaid) > 0){
        echo json_encode([
            "success" => $success,
            "status" => $status,
            "msg" => "Transaksi Sudah Dibayar, Panggil Waiter Untuk Mendapatkan QR Baru",
            "id" => $id,
            "transactionStatus" => $transactionStatus,
            "paymentMethod" => $paymentMethod,
            "paymentMethodName" => $paymentMethodName,
            "qrString" => $qrString,
            "total" => $grandTotal,
            "createdAt" => $created_at,
            "queue" => $queue,
            "isMembership" => $isMembership,
            "updatedAt" =>  $updatedAt
        ]);
        return;
    }
    
    if(($isTempQR == "1" || $isTempQR == 1)  && $qr != ''){
        echo json_encode([
            "success" => $success,
            "status" => $status,
            "msg" => "Mohon Scan QR Dari Waiter",
            "id" => $id,
            "transactionStatus" => $transactionStatus,
            "paymentMethod" => $paymentMethod,
            "paymentMethodName" => $paymentMethodName,
            "qrString" => $qrString,
            "total" => $grandTotal,
            "createdAt" => $created_at,
            "queue" => $queue,
            "isMembership" => $isMembership,
            "updatedAt" =>  $updatedAt
        ]);
        return;
    }

    if($isTempQR != "0" && $isTempQR != 0){
        $msg = "success";
        $status = 200;
        $success = 1;
    
        echo json_encode([
            "success" => $success,
            "status" => $status,
            "msg" => $msg,
            "id" => $id,
            "transactionStatus" => $transactionStatus,
            "paymentMethod" => $paymentMethod,
            "paymentMethodName" => $paymentMethodName,
            "qrString" => $qrString,
            "total" => $grandTotal,
            "createdAt" => $created_at,
            "queue" => $queue,
            "isMembership" => $isMembership,
            "updatedAt" =>  $updatedAt
        ]);
        return;
    }
    
    echo json_encode([
        "success" => 1,
        "status" => 200,
        "msg" =>"success",
        "id" => $id,
        "transactionStatus" => $transactionStatus,
        "paymentMethod" => $paymentMethod,
        "paymentMethodName" => $paymentMethodName,
        "qrString" => $qrString,
        "total" => $grandTotal,
        "createdAt" => $created_at,
        "queue" => $queue,
        "isMembership" => $isMembership,
        "updatedAt" =>  $updatedAt,
        "test"=>$mqSqlConsignmentValidation
    ]);
}
