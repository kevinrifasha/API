<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../db_connection.php';
require_once('../auth/Token.php');

$headers = array();
$rx_http = '/\AHTTP_/';
foreach($_SERVER as $key => $val) {
    if( preg_match($rx_http, $key) ) {
    $arh_key = preg_replace($rx_http, '', $key);
    $rx_matches = array();
    // do some nasty string manipulations to restore the original letter case
    // this should work in most cases
    $rx_matches = explode('_', $arh_key);
    if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
        foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
        $arh_key = implode('-', $rx_matches);
    }
    $headers[$arh_key] = $val;
    }
}
$tokenizer = new Token();
$token = '';
$res = array();
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    $partnerID=$_GET['partnerID'];
    $q = mysqli_query($db_conn, "SELECT monday_open, tuesday_open, wednesday_open, thursday_open, friday_open, saturday_open, sunday_open, monday_closed, tuesday_closed, wednesday_closed, thursday_closed, friday_closed, saturday_closed, sunday_closed, monday_last_order, tuesday_last_order, wednesday_last_order, thursday_last_order, friday_last_order, saturday_last_order, sunday_last_order FROM partner_opening_hours WHERE partner_id='$partnerID' AND deleted_at IS NULL");
    if(mysqli_num_rows($q) > 0) {
        $data = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
    
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "openingHours"=>$data]);
?>