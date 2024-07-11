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
$res = array();

//get token
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
    $data = json_decode(file_get_contents('php://input'));
    if( isset($data->pin) && !empty($data->pin) ){
        $pin = md5($data->pin);
        $data = mysqli_query($db_conn, "SELECT id FROM `users` WHERE phone='$token->phone' AND pin='$pin' AND deleted_at IS NULL");
        if(mysqli_num_rows($data) >0){
            $success =1;
            $status = 200;
            $msg = "PIN benar";
        }else{
            $success =0;
            $status = 400;
            $msg = "PIN salah";
        }
    }else{
        $success =0;
        $status = 400;
        $msg = "Missing Require Fields";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "details"=>$res]);