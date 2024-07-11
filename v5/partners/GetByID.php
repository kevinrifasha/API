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
$headers = apache_request_headers();
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
$bookmark = "0";
$maxDiscount = 0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $id = $_GET['id'];
    $today = strtolower(date("l"));
    $q = mysqli_query($db_conn, "SELECT partner.`id`, partner.`partner_type`, partner.`name`, partner.`address`, partner.`phone`, partner.`status`, partner.`tax`, partner.`service`, partner.`restaurant_number`, partner.`delivery_fee`, partner.`ovo_active`, partner.`dana_active`, partner.`linkaja_active`, partner.gopay_active, partner.`cc_active`, partner.`debit_active`, partner.`qris_active`,partner.`qris_ur_active`, partner.`shopeepay_active`, partner.`id_master`, partner.`longitude`, partner.`latitude`, partner.`img_map`, partner.`desc_map`, partner.`is_delivery`, partner.`is_takeaway`, partner.`is_open`, partner.`wifi_ssid`, partner.`wifi_password`, partner.`is_booked`, partner.`booked_before`, partner.`created_at`, partner.`hide_charge`, partner.`url`, partner.`shipper_location`, partner.`go_send_active`, partner.`grab_active`, partner.`is_dine_in`, partner.`is_preorder`, partner.`open_bill`, partner.is_temporary_close, partner.is_queue_tracking, partner.is_foodcourt, partner.is_reservation, partner.is_special_reservation, partner.cash_active, partner.allow_override_stock, partner.is_rounding, partner.rounding_digits, partner.rounding_down_below, CASE WHEN partner.fc_parent_id = 0 THEN partner.is_centralized WHEN partner.fc_parent_id != 0 THEN parent.is_centralized ELSE 0 END AS is_centralized, day.today, CASE WHEN oh.id IS NULL THEN partner.jam_buka WHEN day.today = 'monday' THEN oh.monday_open WHEN day.today = 'tuesday' THEN oh.tuesday_open WHEN day.today = 'wednesday' THEN oh.wednesday_open WHEN day.today = 'thursday' THEN oh.thursday_open WHEN day.today = 'friday' THEN oh.friday_open WHEN day.today = 'saturday' THEN oh.saturday_open WHEN day.today = 'sunday' THEN oh.sunday_open ELSE partner.jam_buka END jam_buka, CASE WHEN oh.id IS NULL THEN partner.jam_tutup WHEN day.today = 'monday' THEN oh.monday_closed WHEN day.today = 'tuesday' THEN oh.tuesday_closed WHEN day.today = 'wednesday' THEN oh.wednesday_closed WHEN day.today = 'thursday' THEN oh.thursday_closed WHEN day.today = 'friday' THEN oh.friday_closed WHEN day.today = 'saturday' THEN oh.saturday_closed WHEN day.today = 'sunday' THEN oh.sunday_closed ELSE partner.jam_buka END jam_tutup FROM `partner` LEFT JOIN partner parent ON parent.id = partner.fc_parent_id LEFT JOIN partner_opening_hours AS oh ON partner.id = oh.partner_id CROSS JOIN (SELECT '$today' as today) AS day WHERE partner.id = '$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "partners"=>$res]);
