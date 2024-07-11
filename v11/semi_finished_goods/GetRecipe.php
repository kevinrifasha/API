<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

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

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $rawID = $_GET['id'];
    // $q = mysqli_query($db_conn, "SELECT r.id, r.id_raw, r.qty, r.id_metric,rw.name AS rawName, me.name AS metricName FROM recipe r JOIN raw_material rw ON rw.id=r.id_raw JOIN metric me ON me.id=r.id_metric WHERE r.sfg_id='$rawID' AND r.id_raw!=0 AND r.deleted_at IS NULL");
    $q = mysqli_query($db_conn, "SELECT r.id, r.id_raw, r.qty, r.id_metric,rw.name AS rawName, me.name AS metricName, SUM(rms.stock) AS stock FROM recipe r JOIN raw_material rw ON rw.id=r.id_raw JOIN metric me ON me.id=r.id_metric JOIN raw_material_stock rms ON rms.id_raw_material = r.id_raw WHERE r.sfg_id='$rawID' AND r.id_raw!=0 AND r.deleted_at IS NULL GROUP BY id_raw");
    $res1 = array();
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    } else {
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "recipes" => $res]);
