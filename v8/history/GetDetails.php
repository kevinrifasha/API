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
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../..");
$dotenv->load();

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
$res = [];
$delivery = [];
$address = [];
$preOrder = [];
$rated = "0";
$tStatus = "0";

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
        "details" => $res,
        "delivery" => $delivery,
        "address" => $address,
        "transactionStatus" => $tStatus,
        "rated" => $rated,
    ]);
    
} else {
    $id = $_GET["id"];

    $query = "SELECT id, id_transaksi, id_menu, harga_satuan, qty, notes, harga, variant, status, nama, queue, takeaway, img_data, diskon_spesial, status, employee_discount, rated, thumbnail, tenant_name FROM ( SELECT a.id, a.id_transaksi, a.id_menu, a.harga_satuan, a.qty, a.notes, a.harga, a.variant, b.nama, c.queue, c.takeaway, b.img_data, c.diskon_spesial, c.status, c.employee_discount, c.rated, b.thumbnail, d.name AS tenant_name  FROM detail_transaksi a, menu b, transaksi c, partner d WHERE a.id_transaksi = '$id' AND a.id_menu = b.id AND a.id_transaksi = c.id AND c.deleted_at IS NULL AND d.id=b.id_partner AND a.deleted_at IS NULL AND a.status!=4 ) AS tmp ";
    $q = mysqli_query($db_conn, $query);

    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $rated = $res[0]["rated"];
        $tStatus = $res[0]["status"];
        $res[0]["is_delivery"] = "0";
        $qD = mysqli_query(
            $db_conn,
            "SELECT ongkir, rate_id, user_address_id, delivery_detail, is_insurance FROM `delivery` WHERE transaksi_id='$id'"
        );
        if (mysqli_num_rows($qD) > 0) {
            $delivery = mysqli_fetch_all($qD, MYSQLI_ASSOC);
            $res[0]["is_delivery"] = "1";
            if ($delivery[0]["user_address_id"] !== "0") {
                $uID = $delivery[0]["user_address_id"];
                $qA = mysqli_query(
                    $db_conn,
                    "SELECT recipient_name, recipient_phone, address, note, longitude, latitude, shipper_location FROM `addresses` WHERE id='$uID'"
                );
                if (mysqli_num_rows($qA) > 0) {
                    $resA = mysqli_fetch_all($qA, MYSQLI_ASSOC);
                    $address = $resA[0];
                }
            }
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    } else {
        $query = "SELECT id, id_transaksi, id_menu, harga_satuan, qty, notes, harga, variant, status, nama, queue, takeaway, img_data, thumbnail, diskon_spesial, employee_discount, rated, pre_order_id, tenant_name FROM (SELECT a.id, a.id_transaksi, a.id_menu, a.harga_satuan, a.qty, a.notes, a.harga, a.variant, a.status, b.name as nama, c.queue, c.takeaway, b.image as img_data, b.thumbnail, c.diskon_spesial, c.employee_discount, c.rated, c.pre_order_id, d.name AS tenant_name FROM detail_transaksi a, pre_order_menus b, transaksi c, partner d WHERE a.id_transaksi = '$id' AND a.id_menu = b.id AND a.id_transaksi = c.id AND d.id=b.partner_id ) AS tmp ";
        $q = mysqli_query($db_conn, $query);

        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $rated = $res[0]["rated"];
            $tStatus = $res[0]["status"];
            $res[0]["is_delivery"] = "0";
            $qD = mysqli_query(
                $db_conn,
                "SELECT ongkir, rate_id, user_address_id, delivery_detail, is_insurance FROM `delivery` WHERE transaksi_id='$id'"
            );
            if (mysqli_num_rows($qD) > 0) {
                $delivery = mysqli_fetch_all($qD, MYSQLI_ASSOC);
                $res[0]["is_delivery"] = "1";
                if ($delivery[0]["user_address_id"] !== "0") {
                    $uID = $delivery[0]["user_address_id"];
                    $qA = mysqli_query(
                        $db_conn,
                        "SELECT recipient_name, recipient_phone, address, note, longitude, latitude, shipper_location FROM `addresses` WHERE id='$uID'"
                    );
                    if (mysqli_num_rows($qA) > 0) {
                        $resA = mysqli_fetch_all($qA, MYSQLI_ASSOC);
                        $address = $resA[0];
                    }
                }
            }
            if ($res[0]["pre_order_id"] != "0") {
                $poID = $res[0]["pre_order_id"];
                $checkPO = mysqli_query(
                    $db_conn,
                    "SELECT name, order_from, order_to, delivery_from, delivery_to FROM `pre_order_schedules` WHERE id='$poID'"
                );
                if (mysqli_num_rows($checkPO) > 0) {
                    $preOrder = mysqli_fetch_all($checkPO, MYSQLI_ASSOC);
                }
            }
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 200;
            $msg = "Data Not Found";
        }
    }
    
    // get tax, service, charge UR, special diskon, program discount
    $queryGet = "SELECT t.id, t.phone, t.tax, t.service, t.charge_ur, t.diskon_spesial, t.tipe_bayar, t.jam, t.promo, t.total, t.qr_string, t.program_discount, t.program_id, pm.nama, t.rounding FROM `transaksi` t LEFT JOIN `payment_method` pm ON pm.id = t.tipe_bayar WHERE t.id = '$id' and t.deleted_at IS NULL;";
    $sql = mysqli_query($db_conn,$queryGet);
    
    $dataFetch = mysqli_fetch_all($sql, MYSQLI_ASSOC);
    $chargeUR = $dataFetch[0]['charge_ur'];
    $service = $dataFetch[0]['service'];
    $tax = $dataFetch[0]['tax'];
    $specialDisc = $dataFetch[0]['diskon_spesial'];
    $pmName = $dataFetch[0]['nama'];
    $time = $dataFetch[0]['jam'];
    $total = $dataFetch[0]['total'];
    $program_discount = $dataFetch[0]['program_discount'];
    $program_id = $dataFetch[0]['program_id'];
    $qrString = $dataFetch[0]['qr_string'];
    $phone = $dataFetch[0]['phone'];
    $rounding = $dataFetch[0]['rounding'];
    $promo = $dataFetch[0]['promo'];
    $title = "";
    
    if($program_id) {
        $queryTitle = "SELECT title FROM programs WHERE id = '$program_id' AND deleted_at IS NULL";
        $sqlTitle = mysqli_query($db_conn, $queryTitle);
        $fetchTitle = mysqli_fetch_all($sqlTitle, MYSQLI_ASSOC);
        $title = $fetchTitle[0]['title'];
    }

    echo json_encode([
        "success" => $success,
        "status" => $status,
        "msg" => $msg,
        "details" => $res,
        "delivery" => $delivery,
        "address" => $address,
        "transactionStatus" => $tStatus,
        "rated" => $rated,
        "pre_order" => $preOrder,
        "tax"=>(double)$tax,
        "service"=>(double)$service,
        "chargeUR"=>(double)$chargeUR,
        "specialDiscount"=>(double)$specialDisc,
        "pmName"=>$pmName,
        "time"=>$time,
        "total"=>(int)$total,
        "program_discount"=>(int)$program_discount,
        "program_id"=>$program_id,
        "program_title"=>$title,
        "qrString"=>$qrString,
        "phone"=>$phone,
        "rounding"=>$rounding,
        "promo" =>$promo
    ]);
}
?>
