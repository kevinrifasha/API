<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require "../connection.php";
require_once("../../db_connection.php");
require_once("./../tokenModels/tokenManager.php");


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


if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {
    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
} else {
    if (isset($_GET['partnerId']) && !empty($_GET['partnerId'])) {
        $partnerId = $_GET['partnerId'];
        $query = "SELECT id, nama,harga FROM menu WHERE id_partner='$partnerId' AND enabled=1 AND deleted_at IS NULL";
        $q = mysqli_query($db_conn, $query);
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $success = 1;
            $msg = "Data ditemukan";
            $status = 200;
        } else {
            $res = null;
            $success = 0;
            $msg = "Data tidak ditemukan";
            $status = 204;
        }
    } else {
        $success = 0;
        $msg = "Missing required fields";
        $status = 400;
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "menus" => $res]);
