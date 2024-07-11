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
    $obj = json_decode(file_get_contents('php://input'));
    if(
        isset($obj->partner_id) && !empty($obj->partner_id)
        ){
        
        if(
            (($obj->addt_surcharge > 0) && strlen($obj->add_charge_name) > 0)
            ||
            (($obj->addt_surcharge == 0) && strlen($obj->add_charge_name) == 0)
        ) {
            if(isset($obj->add_charge_name)){
                $sql = mysqli_query($db_conn, "INSERT INTO `surcharges`  (`partner_id`, `name`, `surcharge`, `additional_charge_name`, `additional_charge_value`, `tax`, `service`, `type`,`created_at`) VALUES ('$obj->partner_id', '$obj->name', '$obj->surcharge', '$obj->add_charge_name', '$obj->addt_surcharge', '$obj->tax', '$obj->service', '$obj->type', NOW())");
            } else {
                $tax = (int)$obj->tax;
                $surcharge = (int)$obj->surcharge;
                $service = (int)$obj->service;
                $sql = mysqli_query($db_conn, "INSERT INTO `surcharges`  (`partner_id`, `name`, `surcharge`, `tax`, `service`, `type`,`created_at`) VALUES ('$obj->partner_id', '$obj->name', '$surcharge', '$tax', '$service', '$obj->type', NOW())");
            }
            if($sql) {
                $success = 1;
                $status = 200;
                $msg = "Berhasil menambahkan data";
            }else{
                $success = 0;
                $status = 204;
                $msg = "Gagal menambahkan data. mohon coba lagi";
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