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
$resAvail = array();

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
  $partner_id = $_GET['partner_id'];
  $date_start_reservation = $_GET['date_start_reservation'];
  $date_end_reservation = $_GET['date_end_reservation'];
  $capacity = $_GET['capacity'];

  $qAvail = mysqli_query($db_conn, "SELECT
	CASE
    	WHEN partner.max_people_reservation-SUM(reservations.capacity) IS NULL THEN partner.max_people_reservation
        ELSE partner.max_people_reservation-SUM(reservations.capacity)
     END AS left_capacity
FROM calendar LEFT JOIN reservations ON DATE(reservations.reservation_time)=calendar.str_date JOIN partner ON partner.id=$partner_id' WHERE reservations.partner_id=$partner_id' AND (reservations.status!='Pending' AND reservations.status!='Waiting_For_Approval') AND reservations.for_reservation_id='0' AND reservations.reservation_time<='$date_start_reservation' AND reservations.end_time>='$date_end_reservation'");

  $q = mysqli_query($db_conn, "SELECT reservations.phone,`reservations`.`id`, `reservations`.`user_id`, `reservations`.`name`, `reservations`.`description`, `reservations`.`capacity`, `reservations`.`minimum_transaction`, `reservations`.`booking_price`, `reservations`.`duration`, `reservations`.`duration_metric`, `reservations`.`reservation_time`, `reservations`.`end_time`, `reservations`.`status`, `for_reservation`. `meja_id`, meja.idmeja AS meja_name, for_reservation.`table_group_id`, table_groups.name AS table_group_name FROM `reservations` LEFT JOIN `for_reservation` ON reservations.for_reservation_id=for_reservation.id LEFT JOIN meja ON meja.id=for_reservation.meja_id LEFT JOIN table_groups ON table_group_id=table_groups.id WHERE for_reservation.partner_id='$partner_id' AND `for_reservation`.deleted_at IS NULL");
  if (mysqli_num_rows($q) > 0 || mysqli_num_rows($qAvail) > 0) {
    if (mysqli_num_rows($qAvail) > 0 ) {
      $resAvail = mysqli_fetch_all($qAvail, MYSQLI_ASSOC);
    }
    if (mysqli_num_rows($q) > 0 ) {
      $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
    }
      $success = 1;
      $msg = "Success";
      $status = 200;
  }else{
      $success = 0;
      $msg = "Data Not Found";
      $status = 204;
  }

}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "forReservations"=>$res, "dateCustomAvail"=>$resAvail]);