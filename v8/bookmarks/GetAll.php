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
$today = strtolower(date("l"));
$data = mysqli_query($db_conn, "SELECT b.id AS bID, b.partner_id, p.id, p.name, p.img_map, p.desc_map AS description, p.address, p.is_open, p.is_temporary_close, CASE WHEN oh.id IS NULL THEN p.jam_buka WHEN day.today = 'monday' THEN oh.monday_open WHEN day.today = 'tuesday' THEN oh.tuesday_open WHEN day.today = 'wednesday' THEN oh.wednesday_open WHEN day.today = 'thursday' THEN oh.thursday_open WHEN day.today = 'friday' THEN oh.friday_open WHEN day.today = 'saturday' THEN oh.saturday_open WHEN day.today = 'sunday' THEN oh.sunday_open ELSE p.jam_buka END jam_buka, CASE WHEN oh.id IS NULL THEN p.jam_tutup WHEN day.today = 'monday' THEN oh.monday_closed WHEN day.today = 'tuesday' THEN oh.tuesday_closed WHEN day.today = 'wednesday' THEN oh.wednesday_closed WHEN day.today = 'thursday' THEN oh.thursday_closed WHEN day.today = 'friday' THEN oh.friday_closed WHEN day.today = 'saturday' THEN oh.saturday_closed WHEN day.today = 'sunday' THEN oh.sunday_closed ELSE p.jam_buka END jam_tutup FROM bookmarks b JOIN partner p ON b.partner_id = p.id LEFT JOIN partner_opening_hours AS oh ON p.id = oh.partner_id CROSS JOIN (SELECT '$today' as today) AS day WHERE b.phone = '$token->phone' AND b.deleted_at IS NULL ORDER BY b.created_at DESC");
$res = mysqli_fetch_all($data, MYSQLI_ASSOC);
$success =1;
$status = 200;
$msg = "Data ditemukan";
// if(mysqli_num_rows($res)>0){
//     $success =1;
// }else{
//     $success =0;
//     $msg="Data tidak ditemukan";
// }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "bookmarks"=>$res]);