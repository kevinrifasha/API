<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require_once("./../partnerModels/partnerManager.php");
require_once("./../categoryModels/categoryManager.php");

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
    $db = connectBase();
    $tokenizer = new TokenManager($db);
    // $tokens = $tokenizer->validate($token);
    $tokens = $tokenizer->validateCreate($token);

    $status=200;
    // if( $tokens['status']=='403' || $tokens['success']==403){
    //     $status = $tokens['status'];
    //     $signupMsg = $tokens['msg'];
    //     $success = 0;
    //     $tkn="";
    // }else{
    //     $tkn = $tokenizer->reCreate($token);
    //     $success = 1;
    //     $signupMsg = "Success";
    // }
    
    if($tokens['status'] == 200 || $tokens['status'] == "200" || $tokens['status'] == 401 || $tokens['status'] == "401") {
        $tkn = $tokenizer->reCreate($token);
        $success = 1;
        $signupMsg = "Success";
    } else {
        $status = $tokens['status'];
        $signupMsg = $tokens['msg'];
        $success = 0;
        $tkn="";
    }
    
    $signupJson = json_encode(["success"=>$success, "msg"=>$signupMsg, "status"=>$status, "token"=>$tkn]);

    // http_response_code($status);
    echo $signupJson;
 ?>

