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
    $id = $obj->id;
    $add1 = mysqli_query($db_conn, "UPDATE raw_material SET deleted_at=NOW() WHERE id='$id'");
    if ($add1 != false) {
        $deleteExisting = mysqli_query($db_conn, "DELETE FROM recipe WHERE sfg_id='$id'");
        if ($deleteExisting) {
            $success = 1;
            $msg = "Berhasil hapus bahan baku";
            $status = 200;
        } else {
            $success = 0;
            $msg = "Gagal hapus resep existing. Mohon coba lagi";
            $status = 400;
        }
    } else {
        $success = 0;
        $msg = "Gagal hapus data. Mohon coba lagi";
        $status = 400;
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "id" => $idInsert]);
