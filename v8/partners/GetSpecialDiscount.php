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
$bookmark = "0";
$maxDiscount = 0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $id = $_GET['id'];
    $checkSpecial = mysqli_query($db_conn, "SELECT sm.max_disc AS max FROM `special_member` sm JOIN partner p ON p.id_master = sm.id_master WHERE sm.phone='$token->phone' AND p.id='$id' AND sm.deleted_at IS NULL");
    if(mysqli_num_rows($checkSpecial)>0){
        while($row = mysqli_fetch_assoc($checkSpecial)){
            $maxDiscount = $row['max'];
        }
        $success =1;
        $status =200;
        $msg = "Success";
    }else{
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "max_discount"=>$maxDiscount]);
?>