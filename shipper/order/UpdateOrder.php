
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

require '../../db_connection.php';
require_once("../../v3/tokenModels/tokenManager.php"); 
require_once("../../v3/connection.php");

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
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
if((isset($tokenDecoded->masterID) && !empty($tokenDecoded->masterID)) || (isset($tokenDecoded->id_master) && !empty($tokenDecoded->id_master)) || (isset($tokenDecoded->phone) && !empty($tokenDecoded->phone))){
$ch = curl_init();
$json = file_get_contents('php://input');
$obj = json_decode($json,true);
if(
    isset($obj['order_id']) && !empty($obj['order_id'])
    && isset($obj['weight']) && !empty($obj['weight'])
    && isset($obj['height']) && !empty($obj['height'])
    && isset($obj['length']) && !empty($obj['length'])
    && isset($obj['width']) && !empty($obj['width'])
    ){

        $url = 'https://'.$_ENV['SHIPPER_URL'].'/public/v1/orders/'.$obj['order_id'].'?apiKey='.$_ENV['SHIPPER_API_KEY'];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS,"l=".$obj['length']."&w=".$obj['width']."&h=".$obj['height']."&wt=".$obj['weight']);
        
        $headers = array();
        $headers[] = 'Accept: application/x-www-form-urlencoded';
        //< adalah key server yang di ubah menggunakan base64>
        $headers[] = 'User-Agent: Shipper/';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        //execute
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            http_response_code(400);
            echo json_encode(["status"=>400, "success"=>1, "data"=>curl_error($ch)]);
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
            http_response_code(400);
            echo json_encode(["status"=>400, "success"=>0, "data"=>$arr['data']]);
        }
    }else{
        http_response_code(400);
        echo json_encode(["status"=>400, "success"=>0, "data"=>"Missing Required Field"]);
    }
}else{
    http_response_code(403);
    echo json_encode(["status"=>403, "success"=>0, "data"=>"Wrong Token"]);
}
?>