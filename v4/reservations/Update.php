<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
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
    // POST DATA
    // $data = json_decode(json_encode($_POST));
    $data = json_decode(file_get_contents('php://input'));
    $now = date("Y-m-d H:i:s");
    if(
        isset($data->name) && !empty($data->name)
        && isset($data->id) && !empty($data->id)
    ){
        $id = $data->id;
        $insert = mysqli_query($db_conn,"UPDATE `reservations` SET `name`='$data->name',`description`='$data->description',`persons`='$data->persons',`minimum_transaction`='$data->minimum_transaction',`booking_price`='$data->booking_price',`duration`='$data->duration',`duration_metric`='$data->duration_metric',`reservation_time`='$data->reservation_time',`end_time`='$data->end_time',`status`='$data->status', `updated_at`=NOW(), partner_notes='$data->partner_notes', `phone`='$data->phone', `table_id`='$data->table_id' WHERE `id`='$id'");

        if($insert){
            $msg = "Success";
            $success = 1;
            $status=200;
        }else{
            $msg = "Failed";
            $success = 1;
            $status=204;
        }
    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;  
    }

}
        
$signupJson = json_encode(["msg"=>$msg, "success"=>$success,"status"=>$status]);  
http_response_code($status);
echo $signupJson;
    
?>
     