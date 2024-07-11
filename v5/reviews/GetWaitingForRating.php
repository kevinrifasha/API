<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

//init var
$headers = array();
    $rx_http = '/\AHTTP_/';
    foreach($_SERVER as $key => $val) {
      if( preg_match($rx_http, $key) ) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
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
    $period= date("Y-m-d",strtotime("-3 months"));
    $query = "SELECT t.created_at, p.name, p.img_map, t.id AS transaksiID, p.id AS partnerID FROM transaksi t JOIN partner p ON t.id_partner = p.id WHERE t.phone='$token->phone' AND t.deleted_at IS NULL AND t.rated=0 AND t.status=2 AND DATE(t.jam)>'$period' ORDER BY t.jam DESC";
    $data = mysqli_query($db_conn, $query);
    $res = mysqli_fetch_all($data, MYSQLI_ASSOC);
    $status = 200;
    $msg = "Success";
    $success = 1;

}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "waiting"=>$res]);