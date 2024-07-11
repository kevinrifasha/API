<?php    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
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
}else{
    
    $partner_id=$token->id_partner;
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    $q = mysqli_query($db_conn, "SELECT r.phone,r.`id`, r.`user_id`, r.`name`, r.`description`, r.`persons`, fr.`minimum_transaction`, fr.`booking_price`, r.`duration`, r.`duration_metric`, r.`reservation_time`, r.`end_time`, r.`status`, r.`email`, r.`partner_notes`, IFNULL(m.`idmeja`,'-') AS tableName, IFNULL(m.`id`,'-') AS tableId FROM reservations r LEFT JOIN for_reservation fr ON fr.id=r.for_reservation_id LEFT JOIN table_group_details tgd ON tgd.table_group_id = fr.table_group_id LEFT join meja m ON m.id = r.table_id WHERE r.`partner_id`='$partner_id' AND r.deleted_at IS NULL AND DATE(reservation_time) BETWEEN '$dateFrom' AND '$dateTo' AND r.deleted_at IS NULL AND fr.deleted_at IS NULL GROUP BY r.id");
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
    
        
$signupJson = json_encode(["msg"=>$msg, "status"=>$status, "success"=>$success, "reservations"=>$res]);  
http_response_code($status);
echo $signupJson;

 ?>
 