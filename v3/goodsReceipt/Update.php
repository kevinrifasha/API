<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require '../../db_connection.php';
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
$token = '';

$idInsert = 0;
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$success=0;
$signupMsg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
}else{
    $obj = json_decode(file_get_contents('php://input'));
    $now = date("Y-m-d H:i:s");
    if(isset($obj->grID)){
        $qGR = "UPDATE goods_receipt SET notes='$obj->notes', sender='$obj->sender', recieve_date='$obj->receiveDate', delivery_order_number='$obj->don' WHERE id='$obj->grID'";
        $insertPaket = mysqli_query($db_conn,$qGR);
        if($insertPaket){
            $success =1;
            $status =200;
            $msg = "Berhasil ubah data";
        }else{
            $success =0;
            $status =204;
            $msg = "Gagal ubah data. Mohon coba lagi";
        }

    }else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }
}
    echo json_encode(["msg"=>$msg, "status"=>$status,"success"=>$success]);
 ?>
