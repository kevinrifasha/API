<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');


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

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenizer = new Token();
$tokens = $tokenizer->validate($token);
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
}else{

    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    if(
       isset($obj['id']) && !empty($obj['id'])
    ){
        $id=$obj['id'];
        $insert = mysqli_query($db_conn,"UPDATE `printer` SET deleted_at=NOW() WHERE `id`='$id'");

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
// Echo the message.

echo $signupJson;

?>
