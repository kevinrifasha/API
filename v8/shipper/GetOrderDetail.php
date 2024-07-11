<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();
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

    echo json_encode(["status"=>$status, "success"=>0, "data"=>$msg]);
}else{

    if((isset($token->masterID) && !empty($token->masterID)) || (isset($token->id_master) && !empty($token->id_master)) || (isset($token->phone) && !empty($token->phone))){
    $ch = curl_init();
    if(isset($_GET['order_id']) && !empty($_GET['order_id'])){
        curl_setopt($ch, CURLOPT_URL, 'https://'.$_ENV['SHIPPER_URL'].'/public/v1/orders/'.$_GET['order_id'].'?apiKey='.$_ENV['SHIPPER_API_KEY']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_POST, 1);

        //set headers
        $headers = array();
        $headers[] = 'Accept: application/json';
        //< adalah key server yang di ubah menggunakan base64>
        $headers[] = 'User-Agent: Shipper/';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        //execute
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            http_response_code(400);
            echo json_encode(["status"=>400, "success"=>0, "data"=>curl_error($ch)]);
        }else{
            //response json dari API midtrans di ubah menjadi array
            $result = json_decode($result);
            foreach ($result as $x => $value) {
                // echo gettype($value);
                if(gettype($value)=="array"){
                    foreach ($value as $val) {
                        $val = json_decode(json_encode($val));
                        foreach($val as $y => $v){
                            $arr[$y] = $v;
                        }
                    }
                }else{
                    $arr[$x] = $value;
                }
            }
        }
        if($arr['status']==="success"){
            http_response_code(200);
            echo json_encode(["status"=>200, "success"=>1, "data"=>$arr['data']]);
        }else{
            http_response_code(204);
        }
    }else{
        http_response_code(400);
        echo json_encode(["status"=>400, "success"=>0, "data"=>"Missing Required Field"]);
    }
    }else{
        http_response_code(403);
        echo json_encode(["status"=>403, "success"=>0, "data"=>"Wrong Token"]);
    }
}
?>