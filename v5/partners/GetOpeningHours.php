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
$headers = apache_request_headers();
$tokenizer = new Token();
$token = '';
$resImages = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$bookmark = "0";
$maxDiscount = 0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $id = $_GET['id'];
    $q = mysqli_query($db_conn, "SELECT monday_open, tuesday_open, wednesday_open, thursday_open, friday_open, saturday_open, sunday_open, monday_closed, tuesday_closed, wednesday_closed, thursday_closed, friday_closed, saturday_closed, sunday_closed, monday_last_order, tuesday_last_order, wednesday_last_order, thursday_last_order, friday_last_order, saturday_last_order, sunday_last_order FROM partner_opening_hours WHERE partner_id='$id' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);

            $success =1;
            $status =200;
        $msg = "Success";
    } else {
        $res = array();
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "opening_hours"=>$res]);
?>