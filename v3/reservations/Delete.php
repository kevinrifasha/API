<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
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
    $data = json_decode(file_get_contents('php://input'));
    $q = mysqli_query($db_conn, "UPDATE `reservations` SET `deleted_at`=NOW() WHERE `id`='$data->id'");
    if ($q) {
        $success = 1;
        $msg = "Success";
        $status = 200;
    }else{    
        $success = 0;
        $msg = "Data Not Found";
        $status = 204;
    }
}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
    http_response_code(200);
  }else{
    http_response_code($status);
  }   
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]); 
    
?>
     