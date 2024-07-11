<?php


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
$idInsert = "";
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $today = date("Y-m-d");
    // POST DATA
    $obj = json_decode(file_get_contents('php://input'));
    // $expiredDate = date("Y-m-d", $obj->exp_date);
    $search = strtolower($obj->name);
    $obj->name = mysqli_real_escape_string($db_conn, $obj->name);
    $validator = mysqli_query($db_conn, "SELECT id FROM raw_material WHERE LOWER(name)='$search' AND id_partner='$token->id_partner' AND deleted_at IS NULL");
    if (mysqli_num_rows($validator) > 0) {
        $success = 0;
        $msg = "Nama bahan tidak boleh sama. Mohon gunakan nama lain";
        $status = 400;
    } else {
        $add1 = mysqli_query($db_conn, "INSERT INTO raw_material SET id_master = '$token->id_master', id_partner = '$token->id_partner', name = '$obj->name', reminder_allert = '$obj->reminderStock', id_metric = '$obj->metricID', unit_price='$obj->unitPrice', id_metric_price = '$obj->metricID', level=1");
        $idInsert = mysqli_insert_id($db_conn);
        if ($add1 != false) {
            // if((int)$obj->initialStock>0){
            $add = mysqli_query($db_conn, "INSERT INTO raw_material_stock SET id_raw_material = '$idInsert', stock = '$obj->initialStock', id_metric = '$obj->metricID'");
            $movement = mysqli_query($db_conn, "INSERT INTO stock_movements SET master_id='$token->id_master', partner_id='$token->id_partner', raw_id='$idInsert', metric_id='$obj->metricID', type=0, initial='$obj->initialStock', remaining='$obj->initialStock'");
            // }
            $rm = $obj->rawMaterials;
            foreach ($rm as $value) {
                $id_raw = $value->rawID;
                $qty = $value->qty;
                $id_metric = $value->metricID;
                $query = "INSERT INTO `recipe`(`sfg_id`, `id_raw`, `qty`, `id_metric`, `id_variant`) VALUES ('$idInsert', '$id_raw', '$qty', '$id_metric', '0')";
                $insert = mysqli_query($db_conn, $query);
            }
            $success = 1;
            $msg = "Berhasil tambah bahan baku";
            $status = 200;
        } else {
            $success = 0;
            $msg = "Gagal tambah data. Mohon coba lagi";
            $status = 400;
        }
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "id" => $idInsert]);
