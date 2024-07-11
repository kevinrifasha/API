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
$headers = array();
    $rx_http = '/\AHTTP_/';
    foreach($_SERVER as $key => $val) {
      if( preg_match($rx_http, $key) ) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
          foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
          $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
      }
    }
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
    $obj = json_decode(file_get_contents('php://input'));
    if(
        isset($obj->name) && !empty($obj->name)
        ){
            if(!isset($obj->type)||empty($obj->type)){
                $obj->type='Percentage';
            }
        
            if(isset($obj->add_charge_name)){
                $sql = mysqli_query($db_conn, "INSERT INTO `surcharges`  (`partner_id`, `name`, `surcharge`, `additional_charge_name`, `additional_charge_value`, `tax`, `service`, `type`,`created_at`) VALUES ('$token->id_partner', '$obj->name', '$obj->surcharge', '$obj->add_charge_name', '$obj->additional_charge_value', '$obj->tax', '$obj->service', '$obj->type', NOW())");
            } else {
                $tax = (int)$obj->tax;
                $surcharge = (int)$obj->surcharge;
                $service = (int)$obj->service;
                $sql = mysqli_query($db_conn, "INSERT INTO `surcharges`  (`partner_id`, `name`, `surcharge`, `tax`, `service`, `type`,`created_at`) VALUES ('$token->id_partner', '$obj->name', '$surcharge', '$tax', '$service', '$obj->type', NOW())");
            }
        // $sql = mysqli_query($db_conn, "INSERT INTO `surcharges`(`partner_id`, `name`, `surcharge`, `created_at`, `type`, `additional_charge_name`, `additional_charge_value`, `service`, `tax`) VALUES ('$token->id_partner', '$obj->name', '$obj->surcharge', NOW(), '$obj->type', '$obj->additional_charge_name', '$obj->additional_charge_value', '$obj->service', '$obj->tax')");
            if($sql) {
                $success = 1;
                $status = 200;
                $msg = "Berhasil menambahkan data";
            }else{
                $success = 0;
                $status = 204;
                $msg = "Gagal menambahkan data. mohon coba lagi";
            }
    }else{
        $success = 0;
        $status = 204;
        $msg = "Mohon lengkapi form";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "surcharges"=>$res]);
?>