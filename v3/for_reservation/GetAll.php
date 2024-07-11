<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';

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
$data=array();
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $partner_id = $_GET['partner_id'];
    $i=0;
    $res=[];
    $sql = mysqli_query($db_conn, "SELECT for_reservation.`id`, `meja_id`, meja.idmeja AS meja_name,`table_group_id`, table_groups.name AS table_group_name, for_reservation.`name`, `description`, `image`,`capacity`, `minimum_transaction`, `booking_price`, `duration`, `duration_metric`, for_reservation.`created_at` FROM `for_reservation` LEFT JOIN meja ON meja.id=for_reservation.meja_id LEFT JOIN table_groups ON table_group_id=table_groups.id WHERE for_reservation.`partner_id`='$partner_id' AND for_reservation.`deleted_at` IS NULL");
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        foreach($data as $x){
            $id = $x['id'];
            $res[$i]=$x;
            $getPrices = mysqli_query($db_conn, "SELECT id, price, date_from, date_to FROM booking_table_price WHERE deleted_at IS NULL AND for_reservation_id='$id'");
            if(mysqli_num_rows($getPrices)>0){
                $prices = mysqli_fetch_all($getPrices, MYSQLI_ASSOC);
                $res[$i]['prices']=$prices;
            }else{
                $res[$i]['prices']=[];
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

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "forReservations"=>$res]);  

?>