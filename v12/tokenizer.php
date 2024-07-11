<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Content-Type: application/x-www-form-urlencoded");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../db_connection.php';

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

$authorization = "";
$randomString = '';
$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
$today = time();
$boolValidate = false;


foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $authorization=$value;
    }
}
if($authorization=="Basic aWthX3VucGFyOjIwMjAtY29kZW9udG9w"){
    $boolValidate=true;
}


//get params
if($boolValidate==true){
    parse_str(file_get_contents("php://input"), $data);
    if($data['grant_type']=="secret_credentials"){
        for ($i = 0; $i < 36; $i++) {
            $randomString .= $characters[rand(0, 35)];
        }
        $createToken = mysqli_query($db_conn, "INSERT INTO `token`(`token`, `expired`) VALUES ('$randomString', '3600')");
        
        if ($createToken) {
            echo '{"access_token":"'.$randomString.'","token_type":"Bearer","expires_in":3600}';
        }else{
            echo '{"msg":"Failed to get Token"}';
        }
    }else{
        echo '{"msg":"wrong grant_type"}';
    }
}else{
    echo '{"msg":"Wrong Authorization"}';
}
?>