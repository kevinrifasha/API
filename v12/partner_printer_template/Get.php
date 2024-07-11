<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require '../../db_connection.php';
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

$idInsert = 0;
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt', $token));
$success = 0;
$msg = "";

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {
    $status = $tokens['status'];
    $signupMsg = $tokens['msg'];
} else {
    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 1;
    
    $partnerID = $tokenDecoded->partnerID;
    
    $q1 = "SELECT id, name, partner_id, name, address, city, state, zip, phone, website, twitter, facebook, instagram, notes, logo FROM partner_printer_template WHERE partner_id = '$partnerID' ";

    $printer_data = mysqli_query($db_conn, $q1);
    $data = [];
    
    if (mysqli_num_rows($printer_data) > 0) {
        $data = mysqli_fetch_assoc($printer_data);
        $msg = "Berhasil";
        $success = 1;
    } else {
        $data = [];
        $status = 204;
        $msg = "Data Tidak Ditemukan";
        $success = 0;
    }

}


echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "data"=>$data]);
?>
