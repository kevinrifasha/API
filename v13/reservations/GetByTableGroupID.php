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
    $partnerID = $_GET['partnerID'];
    $tgID = $_GET['tableGroupID'];
    $date = $_GET['date'];
    $i=0;
    $res=[];
    $date = date('Y-m-d', strtotime($date));
    $sql = mysqli_query($db_conn, "SELECT 
      fr.id, 
      m.idmeja,
      fr.meja_id, 
      fr.table_group_id, 
      fr.name, 
      fr.description, 
      fr.capacity, 
      fr.minimum_transaction, 
      fr.booking_price, 
      fr.duration, 
      fr.duration_metric, 
      fr.image, 
      CASE WHEN (
        DATE(r.reservation_time) = '$date'
      ) THEN '1' WHEN m.is_seated=1 AND '$date'=DATE(NOW()) THEN '1' ELSE '0' END AS reservationStatus 
    FROM 
      for_reservation fr 
      LEFT OUTER JOIN reservations r ON r.for_reservation_id = fr.id 
      AND r.partner_id = '$partnerID' 
      AND DATE(r.reservation_time) = '$date' 
      AND r.deleted_at IS NULL 
      JOIN meja m ON m.id=fr.meja_id
      LEFT JOIN table_group_details tgd ON tgd.table_id=m.id
      LEFT JOIN table_groups tg ON tg.id = tgd.table_group_id 
    WHERE 
      fr.partner_id = '$partnerID' 
      AND fr.deleted_at IS NULL
      AND tg.id='$tgID'");
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        foreach($data as $x){
            $id = $x['id'];
            $res[$i]=$x;
            $getPrices = mysqli_query($db_conn, "SELECT id, price, date_from, date_to FROM booking_table_price WHERE deleted_at IS NULL AND for_reservation_id='$id' AND '$date' BETWEEN date_from AND date_to");
            if(mysqli_num_rows($getPrices)>0){
                $prices = mysqli_fetch_all($getPrices, MYSQLI_ASSOC);
                $res[$i]['minimum_transaction']=$prices[0]['price'];
                
            }else{
               
            }
            $i++;
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }

}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "areas"=>$res]);