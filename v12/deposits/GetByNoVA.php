<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';
$permitted_chars = '0123456789';

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
    $id = $_GET['id'];
    
    $allSubscribe = mysqli_query($db_conn, "SELECT * FROM `master_deposit` WHERE deposit_code='$id'");
    
    if (mysqli_num_rows($allSubscribe) > 0) {
        $value= mysqli_fetch_all($allSubscribe, MYSQLI_ASSOC);
        $status = 200;
        $msg = "Success"; 
        $success = 0;
    } else {
        $status = 400;
        $msg = "Failed"; 
        $success = 0;
    }
}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
    http_response_code(200);
}else{
    http_response_code($status);
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "subscribes"=>$value]);  