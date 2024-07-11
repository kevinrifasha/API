<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
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
$test = '';

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

    $json = file_get_contents('php://input');
    $data = json_decode($json,true);
    $id = $token->id_partner;

    if(
        isset($data['name']) && !empty($data['name'])
        && isset($data['phone']) && !empty($data['phone'])
    ){
        $name = mysqli_real_escape_string($db_conn, $data['name']);
        $phone = mysqli_real_escape_string($db_conn, $data['phone']);
        $description = mysqli_real_escape_string($db_conn, $data['description']);
        $address = mysqli_real_escape_string($db_conn, $data['address']);
        $ssid = mysqli_real_escape_string($db_conn,$data['ssid']);
        $password = mysqli_real_escape_string($db_conn,$data['password']);
        $open_hour = $data['open_hour'];
        $close_hour = $data['close_hour'];
        $service = $data['service'];
        $shipper_location = $data['shipper_location'];
        $longitude = $data['longitude'];
        $latitude = $data['latitude'];
        $tax = $data['tax'];
        $is_delivery = $data['is_delivery'];
        $is_takeaway = $data['is_takeaway'];
        $hide_charge = $data['hide_charge'];
        $ovo_active = $data['ovo_active'];
        $dana_active = $data['dana_active'];
        $linkaja_active = $data['linkaja_active'];
        $debit_active = $data['debit_active'];
        $cc_active = $data['cc_active'];
        $qris_active = $data['qris_active'];
        $shopeepay_active = $data['shopeepay_active'];
        $is_average_cogs = $data['is_average_cogs'];
        $is_bluetooth = $data['is_bluetooth'];
        $is_table_management = $data['is_table_management'];
        $delivery_status_tracking = $data['delivery_status_tracking'];
        $img_map = $data['img_map'];
        $open_bill = $data['open_bill'];
        $grab_active = $data['grab_active'];
        $go_send_active = $data['go_send_active'];
        $is_temporary_qr = $data['is_temporary_qr'];
        $is_table_required=$data['is_table_required'];
        
        $membership_point = "";
        if(isset($data['membership_point'])){
            $membership_point = $data['membership_point'];
        }
        
        $membership_point_multiplier = "";
        $qMembership = "";
        if(isset($data['membership_point_multiplier'])){
            $membership_point_multiplier = $data['membership_point_multiplier'];
            $qMembership =  ", membership_point='$membership_point', membership_point_multiplier='$membership_point_multiplier'";
        }
        
        $is_rounding = "";
        $rounding_digits = "";
        $rounding_down_below = "";
        
        if(
            isset($data['is_rounding'])
            && isset($data['rounding_digits'])
            && isset($data['rounding_down_below'])
        ){
            $is_rounding = $data['is_rounding'];
            $rounding_digits = $data['rounding_digits'];
            $rounding_down_below = $data['rounding_down_below'];
            $updateUser = mysqli_query($db_conn, "UPDATE partner SET name='$name', phone='$phone', desc_map='$description', address='$address', wifi_ssid='$ssid', wifi_password='$password', jam_buka='$open_hour', jam_tutup='$close_hour', service='$service', shipper_location='$shipper_location', longitude='$longitude', latitude='$latitude', tax='$tax', is_delivery='$is_delivery', is_takeaway='$is_takeaway',is_table_required='$is_table_required',hide_charge='$hide_charge', ovo_active='$ovo_active', dana_active='$dana_active', linkaja_active='$linkaja_active', debit_active='$debit_active', cc_active='$cc_active', qris_active='$qris_active', is_average_cogs='$is_average_cogs', `is_bluetooth`='$is_bluetooth', `shopeepay_active`='$shopeepay_active', `img_map`='$img_map', `is_table_management`='$is_table_management', `open_bill`='$open_bill', `delivery_status_tracking`='$delivery_status_tracking', grab_active='$grab_active', go_send_active='$go_send_active', is_rounding='$is_rounding',is_temporary_qr='$is_temporary_qr', rounding_digits='$rounding_digits', rounding_down_below='$rounding_down_below'" . $qMembership .  " WHERE id='$id'");

            if(
                isset($data['membership_point'])
                && isset($data['membership_point_multiplier'])
            ){
                $updateMaster = mysqli_query($db_conn, "UPDATE master SET harga_point='$membership_point_multiplier', point_pay='$membership_point WHERE id=$masterID'");   
            }
            
            if ($updateUser) {
                $success =1;
                $status =200;
                $msg = "Success";
                   $result=mysqli_query($db_conn,"SELECT `is_table_required` from partner WHERE id='$id'");
                if($result){
                    if(mysqli_num_rows($result)>0){
                        $row=mysqli_fetch_assoc($result);
                        $show_is_table_required=$row['is_table_required'];
                        $msgData="{$show_is_table_required}";
                    }
                }
            } else {
                $success =0;
                $status =204;
                $msg = "Data Not Found";
            }
        } else {
            $updateUser = mysqli_query($db_conn, "UPDATE partner SET name='$name', phone='$phone', desc_map='$description', address='$address', wifi_ssid='$ssid', wifi_password='$password', jam_buka='$open_hour', jam_tutup='$close_hour', service='$service', shipper_location='$shipper_location', longitude='$longitude', latitude='$latitude', tax='$tax',is_table_required='$is_table_required',is_delivery='$is_delivery', is_takeaway='$is_takeaway', hide_charge='$hide_charge', ovo_active='$ovo_active', dana_active='$dana_active', linkaja_active='$linkaja_active', debit_active='$debit_active', cc_active='$cc_active', qris_active='$qris_active', is_average_cogs='$is_average_cogs', `is_bluetooth`='$is_bluetooth', `shopeepay_active`='$shopeepay_active', `img_map`='$img_map', `is_table_management`='$is_table_management', `open_bill`='$open_bill', `delivery_status_tracking`='$delivery_status_tracking', grab_active='$grab_active', go_send_active='$go_send_active', membership_point='$membership_point',is_temporary_qr='$is_temporary_qr', membership_point_multiplier='$membership_point_multiplier' WHERE id='$id'");
            
            if(
                isset($data['membership_point'])
                && isset($data['membership_point_multiplier'])
            ){
                $updateMaster = mysqli_query($db_conn, "UPDATE master SET harga_point='$membership_point_multiplier', point_pay='$membership_point WHERE id=$masterID'");   
            }
            
            if ($updateUser) {
                $success =1;
                $status =200;
                // $msg = "Success";
                $result=mysqli_query($db_conn,"SELECT `is_table_required` from partner WHERE id='$id'");
                if($result){
                    if(mysqli_num_rows($result)>0){
                        $row=mysqli_fetch_assoc($result);
                        $show_is_table_required=$row['is_table_required'];
                        $msg="Success  table required={$show_is_table_required}";
                    }
                }
            } else {
                $success =0;
                $status =204;
                $msg = "Data Not Found";
            }
        }
    }else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field's";
    }

}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg,"table required"=>$msgData]);
?>

