<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../tokenModels/tokenManager.php"); 
require_once("../../partnerModels/partnerManager.php"); 
require_once("../../connection.php");
require '../../../db_connection.php';
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
    if(isset($obj->name) && !empty($obj->name)){
        $obj->name=str_replace("'","''",$obj->name);
        // $sql = mysqli_query($db_conn, "UPDATE `suppliers` SET name='$obj->name', master_id='$tokenDecoded->masterID', address='$obj->address', phone='$obj->phone', email='$obj->email', updated_at=NOW() WHERE id='$obj->id'");
        $sql = mysqli_query($db_conn, "UPDATE `partner` SET stall_id='$obj->stall_id', name='$obj->name', phone='$obj->phone', img_map='$obj->img_map', thumbnail='$obj->thumbnail', updated_at=NOW(), status='$obj->status' WHERE id='$obj->id'");
        if($sql) {
            $success = 1;
            $status = 200;
            $msg = "Berhasil ubah data";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Gagal ubah data. mohon coba lagi";
        }
    }else{
        $success = 0;
        $status = 204;
        $msg = "Mohon lengkapi form";
    }
    
    
}
// echo "a";
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);  

?>