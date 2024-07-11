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
$token   = '';
$res     = array();
$success = 0;

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status  = $tokenValidate['status'];
    $msg     = $tokenValidate['msg'];
    $success = 0;
}else{
    if(isset($_GET['type']) && !empty($_GET['type'])){
        $type       = $_GET['type'];
        $operator   = $_GET['operator'];
        $username   = "085155053040";
        $apiKey     = "7296075190d9d5f7";
        $signature  = md5($username.$apiKey.'pl');
        $json = array(
            "commands"=> "pricelist",
            "username"=> $username,
            "sign"    => $signature,
        );
        $json = json_encode($json);
        $url = "https://testprepaid.mobilepulsa.net/v1/legacy/index/".$type."/".$operator;
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        if(curl_errno($ch)){
            $msg = 'Request Error:' . curl_error($ch);
        }
        curl_close($ch);
        $response   = json_decode($data);
        if(!empty($response->data->rc)){
            $status     = intval($response->data->rc);
            $msg        = $response->data->message;
            $success    = intval($response->data->status);
        } else {
            $status     = 200;
            $msg        = "Berhasil";
            $success    = 1;
            $res        = $response->data;
        }
    } else {
        $status = 400;
        $msg    = "Missing Required Field";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "data"=>$res]);
?>
