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
    $user_id=$token->id;
    $q = mysqli_query($db_conn, "SELECT r.phone,r.`id`, r.`user_id`, r.`name`, r.`description`, r.email, r.`persons`, r.`reservation_time`, r.`status`, p.name AS partnerName, p.phone, p.address, p.img_map, IFNULL(m.idMeja,'-') AS tableName, r.booking_price, r.minimum_transaction FROM reservations r JOIN partner p ON r.partner_id=p.id LEFT JOIN meja m ON m.id=r.table_id WHERE r.`user_id`='$user_id' AND r.deleted_at IS NULL AND p.organization='Natta' AND r.status IN('Finished','Canceled_By_User','Canceled_By_Partner','Canceled_By_UR','User_Not_Come') ORDER BY r.created_at DESC");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $success = 1;
        $msg = "Success";
        $status = 200;
    }else{
        $success = 0;
        $msg = "Data Not Found";
        $status = 204;
    }

}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "reservations"=>$res]);