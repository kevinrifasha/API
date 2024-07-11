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
    $obj = json_decode(file_get_contents('php://input'));
    if(isset($obj->id) && isset($obj->accNo)&&isset($obj->name)){
        $query = "UPDATE partner_bank_accounts SET account_no='$obj->accNo', account_name='$obj->name' WHERE id='$obj->id'";
        $q = mysqli_query($db_conn, $query);
        if ($q) {
            $success =1;
            $status =200;
            $msg = "Berhasil ubah data";
        } else {
            $success =0;
            $status =204;
            $msg = "Gagal ubah data";
        }
    }else{
        $success =0;
        $status =400;
        $msg = "Data tidak lengkap";
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>