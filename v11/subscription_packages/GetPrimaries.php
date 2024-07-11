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
    $res=array(
        array(
            "id"=>"2",
            "name"=>"Lite",
            "description"=>"",
            "price"=>"169000",
            "type"=>"Monthly"
        ),
        array(
            "id"=>"3",
            "name"=>"Business",
            "description"=>"",
            "price"=>"314000",
            "type"=>"Monthly"
        ),
        array(
            "id"=>"4",
            "name"=>"Professional",
            "description"=>"",
            "price"=>"899000",
            "type"=>"Monthly"
        ),
        array(
            "id"=>"2",
            "name"=>"Lite",
            "description"=>"",
            "price"=>"1428000",
            "type"=>"Yearly"
        ),
        array(
            "id"=>"3",
            "name"=>"Business",
            "description"=>"",
            "price"=>"2868000",
            "type"=>"Yearly"
        ),
        array(
            "id"=>"4",
            "name"=>"Professional",
            "description"=>"",
            "price"=>"8988000",
            "type"=>"Yearly"
        )
    );
    $success =1;
    $status =200;
    $msg = "Success";
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "packages"=>$res]);
?>