<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
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
    
    $data = json_decode(file_get_contents('php://input'));
    $logo = $data->logo;
    $name = mysqli_real_escape_string($db_conn, $data->name);
    $address = mysqli_real_escape_string($db_conn,$data->address);
    $city = $data->city;
    $state = $data->state;
    $zip = $data->zip;
    $phone = $data->phone;
    $website = $data->website;
    $twitter = $data->twitter;
    $facebook = mysqli_real_escape_string($db_conn,$data->facebook);
    $instagram = $data->instagram;
    $notes = mysqli_real_escape_string($db_conn,$data->notes);
    
    if(isset($logo) && isset($name) && isset($address) && isset($city) && isset($state) && isset($zip) && isset($phone) && isset($website) && isset($twitter) && isset($website) && isset($twitter) && isset($facebook) && isset($instagram) && isset($notes) ){
        
        $insertToDB = mysqli_query($db_conn, "INSERT INTO partner_printer_template (partner_id, logo, name, address, city, state, zip, phone, website, twitter, facebook, instagram, notes) VALUES ('$partnerID', '$logo','$name','$address', '$city', '$state', '$zip', '$phone', '$website', '$twitter', '$facebook', '$instagram','$notes');");
        
        $msg = "Berhasil";
        $success = 1;
    } else {
        $status = 203;
        $msg = "Data Belum Lengkap";
        $success = 0;
    }
}


echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>
