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
$type;

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $obj = json_decode(file_get_contents('php://input'));
    if(
        isset($obj->id) && !empty($obj->id)
        ){
            
            if($obj->type) {
                $type = $obj->type;
            } else {
                $type = "Percentage";
            }
       
        if(
            (($obj->addt_surcharge > 0) && strlen($obj->add_charge_name) > 0)
            ||
            (($obj->addt_surcharge == 0) && strlen($obj->add_charge_name) == 0)
        ) {
            
        if(isset($obj->add_charge_name)){
            $sql = mysqli_query($db_conn, "UPDATE `surcharges` SET `name`='$obj->name',`surcharge`='$obj->surcharge',`additional_charge_name`='$obj->add_charge_name',`additional_charge_value`='$obj->addt_surcharge',`tax`='$obj->tax',`service`='$obj->service',`type`='$type',`updated_at`=NOW() WHERE `id`='$obj->id';");
        } else {
            $sql = mysqli_query($db_conn, "UPDATE `surcharges` SET `name`='$obj->name',`surcharge`='$obj->surcharge',`tax`='$obj->tax',`service`='$obj->service',`type`='$type',`updated_at`=NOW() WHERE `id`='$obj->id';");
            
        }
            if($sql) {
                $success = 1;
                $status = 200;
                $msg = "Berhasil ubah data";
            }else{
                $success = 0;
                $status = 204;
                $msg = "Gagal ubah data. mohon coba lagi";
            }
        } else {
            $success = 0;
            $status = 400;
            $msg = "Mohon lengkapi data Additional Charge!";
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