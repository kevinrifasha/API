<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';

$headers = apache_request_headers();
$token = '';

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
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
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "openingHours"=>$data]);

?>