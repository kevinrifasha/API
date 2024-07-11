<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
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
    $id = $token->id;
    $keyword = $_GET['keyword'];
    $extraQuery = "AND (CONVERT(`p`.`id` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`fc_parent_id` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`stall_id` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_testing` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`name` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`address` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`phone` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`email` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`status` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`saldo_ewallet` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`tax` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`service` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`restaurant_number` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`delivery_fee` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`id_master` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`device_token` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`longitude` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`latitude` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`img_map` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`thumbnail` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`desc_map` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_foodcourt` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_centralized` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_delivery` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_takeaway` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_dine_in` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_preorder` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_open` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_temporary_close` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_attendance` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`jam_buka` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`jam_tutup` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`wifi_ssid` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`wifi_password` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`ip_checker` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`ip_receipt` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_booked` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`booked_before` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`hide_charge` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`charge_ur` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`charge_ur_shipper` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`ovo_active` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`gopay_active` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`dana_active` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`linkaja_active` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`cash_active` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`cc_active` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`debit_active` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`qris_active` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`shopeepay_active` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`partner_type` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`url` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`open_bill` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_average_cogs` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`shipper_location` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`max_delivery_distance` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`grab_active` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`go_send_active` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_bluetooth` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_pos` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_email_report` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_table_management` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_reservation` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_helper` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`reservation_notes` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`delivery_status_tracking` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`max_people_reservation` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`max_days_reservation` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`ewallet_charge` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`transaction_charge` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`delivery_charge` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`subscription_status` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`trial_until` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`primary_subscription_id` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`subscription_until` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_ar` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`track_server` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`is_dp` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`logo` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`open_close_table` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`referral` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`created_at` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`updated_at` USING utf8) LIKE '%$keyword%' OR CONVERT(`p`.`deleted_at` USING utf8) LIKE '%$keyword%')";
    if($token->roleID=="5"){
        $query="SELECT p.id, p.name, p.address, DATE(p.created_at) AS joinDate, '-' AS salesName FROM partner p WHERE p.referral='$id' AND p.deleted_at IS NULL AND p.status=1 ".$extraQuery;
    }else{
        $query="SELECT p.id, p.name, p.address, IFNULL(s.name,'-') AS salesName, DATE(p.created_at) AS joinDate FROM partner p LEFT JOIN sa_users s ON p.referral=s.id WHERE p.deleted_at IS NULL AND DATE(p.created_at)>'2022-07-01' AND p.id NOT IN ('000310','000276','000262','000343','000313', '000309', '000343', '000314', '000295', '000266','000241','000444','000443','000171','000162','000336','000231','000202','000401','000701') ".$extraQuery;
    }
    $sql = mysqli_query($db_conn, $query);

    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data]);
