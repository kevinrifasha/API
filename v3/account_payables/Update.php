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
    // POST DATA
    $obj = json_decode(file_get_contents('php://input'));
    if(
        isset($obj->id) && !empty($obj->id)
    ){
        $insert = mysqli_query($db_conn,"UPDATE account_payables SET reference='$obj->reference', due_date='$obj->dueDate', updated_at=NOW() WHERE id='$obj->id'");
        if($insert){
            $msg = "Perubahan berhasil disimpan";
            $success = 1;
            $status=200;
        }else{
            $msg = "Gagal menyimpan perubahan";
            $success = 0;
            $status=204;
        }
    }else{
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 400;
    }

}
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]);

?>
