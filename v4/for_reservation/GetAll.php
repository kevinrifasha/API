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

    $is_reservation_area = 0;
    
    if(isset($_GET["is_reservation_area"])){
        $is_reservation_area = $_GET["is_reservation_area"];
    }
    $partner_id=$token->id_partner;
    $i=0;
    
    $sql = mysqli_query($db_conn,
        "SELECT * FROM (SELECT for_reservation.`id`, `meja_id`, meja.idmeja AS meja_name,`table_group_id`, table_groups.name AS table_group_name, for_reservation.`name`, `description`, `image`,`capacity`, `minimum_transaction`, `booking_price`, `duration`, `duration_metric`, for_reservation.`created_at` FROM `for_reservation` LEFT JOIN meja ON meja.id=for_reservation.meja_id LEFT JOIN table_groups ON table_group_id=table_groups.id WHERE for_reservation.`partner_id`='$partner_id' AND for_reservation.`deleted_at` IS NULL AND meja_id IS NOT NULL AND meja_id != 0

        UNION ALL

        SELECT for_reservation.`id`, meja.id AS `meja_id`, meja.idmeja AS meja_name, for_reservation.`table_group_id`, table_groups.name AS table_group_name, for_reservation.`name`, `description`, `image`,`capacity`, `minimum_transaction`, `booking_price`, `duration`, `duration_metric`, for_reservation.`created_at` FROM `for_reservation` LEFT JOIN table_group_details tgd ON tgd.table_group_id=for_reservation.table_group_id LEFT JOIN meja ON meja.id=tgd.table_id LEFT JOIN table_groups ON table_groups.id = for_reservation.table_group_id WHERE for_reservation.`partner_id`='$partner_id' AND for_reservation.`deleted_at` IS NULL AND for_reservation.table_group_id IS NOT NULL AND for_reservation.table_group_id != 0) fr GROUP BY fr.meja_id;"
    );
    
    if(isset($_GET["is_reservation_area"]) && ($is_reservation_area == 1 || $is_reservation_area == "1")){
        $sql = mysqli_query($db_conn, "SELECT for_reservation.`id`, `meja_id`, meja.idmeja AS meja_name,`table_group_id`, table_groups.name AS table_group_name, for_reservation.`name`, `description`, `image`,`capacity`, `minimum_transaction`, `booking_price`, `duration`, `duration_metric`, for_reservation.`created_at` FROM `for_reservation` LEFT JOIN meja ON meja.id=for_reservation.meja_id LEFT JOIN table_groups ON table_group_id=table_groups.id WHERE for_reservation.`partner_id`='$partner_id' AND for_reservation.`deleted_at` IS NULL");
    }
    
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
    
        
$signupJson = json_encode(["msg"=>$msg, "status"=>$status, "success"=>$success, "forReservations"=>$res, "is_reservation_area"=>$is_reservation_area]);  
http_response_code($status);
echo $signupJson;

 ?>
 