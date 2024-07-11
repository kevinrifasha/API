<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';

$headers = apache_request_headers();
$token = '';
    
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$res = array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $partner_id =$_GET['partner_id'];
    $q = mysqli_query($db_conn, "SELECT reservations.phone,`reservations`.`id`, `reservations`.`user_id`, `reservations`.`name`, email, `reservations`.`description`, `reservations`.`persons`, `reservations`.`minimum_transaction`, `reservations`.`booking_price`, `reservations`.`duration`, `reservations`.`duration_metric`, `reservations`.`reservation_time`, `reservations`.`end_time`, `reservations`.`status`, `for_reservation`.`meja_id`, meja.idmeja AS meja_name, for_reservation.`table_group_id`, `for_reservation`.`id` AS for_reservation_id, table_groups.name AS table_group_name, partner_notes FROM `reservations` LEFT JOIN `for_reservation` ON reservations.for_reservation_id=for_reservation.id LEFT JOIN meja ON meja.id=for_reservation.meja_id LEFT JOIN table_groups ON table_group_id=table_groups.id WHERE reservations.`partner_id`='$partner_id' AND `reservations`.deleted_at IS NULL;");
    $res = array();

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
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "roles"=>$res]);  

?>