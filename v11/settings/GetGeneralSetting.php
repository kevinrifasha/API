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
    $q = mysqli_query($db_conn, "SELECT name, address, phone, tax, service, restaurant_number, delivery_fee, latitude, longitude, img_map, desc_map, is_delivery, is_takeaway, is_open, jam_buka, jam_tutup, wifi_ssid, wifi_password, hide_charge, created_at, desc_map as description, ovo_active, dana_active,hide_charge, linkaja_active, debit_active, cc_active, qris_active, gopay_active, is_average_cogs, shipper_location, is_bluetooth, shopeepay_active, is_temporary_close, charge_ur_shipper,  charge_ur, is_table_management, open_bill,delivery_status_tracking, grab_active, go_send_active, is_rounding, rounding_digits, rounding_down_below, is_temporary_qr FROM `partner` WHERE id='$token->id_partner'");
    $q1 = mysqli_query($db_conn, "SELECT psa.id, psa.subcategory_id, psa.category_id AS partner_type_id, ps.name AS subcategory_name, pt.name AS partner_type_name FROM partner_subcategory_assignments psa LEFT JOIN partner_subcategories ps ON ps.id = psa.subcategory_id LEFT JOIN partner_types pt ON pt.id = psa.category_id WHERE psa.partner_id = '$token->id_partner' AND psa.deleted_at IS NULL");

    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        if($res[0]['hide_charge']=="1"){
            $res[0]['charge_ur']="0";
            $res[0]['charge_ur_shipper']="0";
        }
        $res[0]['tax_value']=$res[0]['tax'];
        $res[0]['charge_ur']="0";
        $res[0]['partner_subcategory']=$res1;
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "setting"=>$res]);
?>