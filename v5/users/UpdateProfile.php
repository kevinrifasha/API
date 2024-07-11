<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
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
$iid = 0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $obj = json_decode(file_get_contents('php://input'));
$update = mysqli_query($db_conn, "UPDATE `users` SET name='$obj->name', Gender='$obj->gender', TglLahir='$obj->birth', email='$obj->email', updated_at=NOW() WHERE phone='$token->phone'");
    if($update){
    $success =1;
    $status = 200;
    $msg = "Update data diri berhasil";
    }else{
        $success=0;
        $status=204;
        $msg="Update gagal. Mohon coba lagi";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);