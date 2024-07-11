<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../../db_connection.php';
require_once('../../auth/Token.php');

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
$iid = 0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{

    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    if(
        isset($obj->id) && !empty($obj->id)){
            
            $insert = mysqli_query($db_conn, "UPDATE `partner` SET `stall_id`='$obj->stall_id', `name`='$obj->name', `phone`='$obj->phone', `id_master`='$obj->id_master', `img_map`='$obj->img_map', `thumbnail`='$obj->thumbnail', `desc_map`='$obj->desc_map', `is_dine_in`='1', `is_open`='1', `is_temporary_close`='0', `is_attendance`='1', `jam_buka`='$obj->jam_buka', `jam_tutup`='$obj->jam_tutup', `url`='$obj->url', `is_email_report`='1', `is_centralized`='$obj->is_centralized', status='$obj->status' WHERE `id`='$obj->id'");
            // if(isset($obj->is_centralized) && !empty($obj->is_centralized)){
            // }else{
            //     $insert = mysqli_query($db_conn, "UPDATE INTO `partner` SET `stall_id`='$obj->stall_id', `name`='$obj->name', `phone`='$obj->phone', `tax`='$obj->tax', `service`='$obj->service', `id_master`='$obj->id_master', `img_map`='$obj->img_map', `thumbnail`='$obj->thumbnail', `desc_map`='$obj->desc_map', `is_dine_in`='1', `is_open`='1', `is_temporary_close`='0', `is_attendance`='1', `is_centralized`='0', `jam_buka`='$obj->jam_buka', `jam_tutup`='$obj->jam_tutup', `hide_charge`='$obj->hide_charge', `ovo_active`='$obj->ovo_active', `dana_active`='$obj->dana_active', `linkaja_active`='$obj->linkaja_active', `cc_active`='$obj->cc_active', `debit_active`='$obj->debit_active', `qris_active`='$obj->qris_active', `shopeepay_active`='$obj->shopeepay_active', `url`='$obj->url', `is_bluetooth`='$obj->is_bluetooth', `is_pos`='1', `is_email_report`='1', `is_table_management`='1' WHERE `id`='$obj->id'");
            // }
        if($insert){
            $success =1;
            $status =200;
            $msg = "Success";
        }else{
            $success =0;
            $status =200;
            $msg = "Failed";
        }
        
    }else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>