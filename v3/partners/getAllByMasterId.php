<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
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
$masterId = $_GET['masterId'];
$success = 0;
$msg = 'Failed';
$res = array();

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {
    $status = $tokens['status'];
    $msg = $tokens['msg'];
} else {

    if (isset($masterId) && !empty($masterId)) {

        $sqlGetAll = mysqli_query($db_conn, "SELECT * FROM partner WHERE id_master = '$masterId' AND deleted_at IS NULL");

        if (mysqli_num_rows($sqlGetAll) > 0) {
            $data = mysqli_fetch_all($sqlGetAll, MYSQLI_ASSOC);

            $success = 1;
            $msg = 'Success';
            $status = 200;
        } else {
            $success = 0;
            $msg = 'Data Not Found';
            $status = 204;
        }
    } else {

        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;
    }
}

echo json_encode(["msg" => $msg, "status" => $status, "success" => $success, "partners" => $data]);
