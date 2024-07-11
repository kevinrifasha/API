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
$resP = array();
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{


    $partner_id = $token->id_partner;
    $today = date("Y-m-d");
    $gtotal = $_GET['total']??0;
    $qP = mysqli_query($db_conn,"SELECT `id`, `master_program_id`, `master_id`, `partner_id`, `title`, `minimum_value`, `menus`, categories, `enabled`, `valid_from`, `qty_redeem`, `valid_until`, `discount_type`,`discount_percentage`,`minimum_value`-'$gtotal' AS need_extra,
    CASE WHEN `maximum_discount` IS NOT NULL THEN `maximum_discount` ELSE 0 END AS maximum_discount  
    ,
    CASE
        WHEN `minimum_value`-'$gtotal'>0 THEN 0
        ELSE 1
    END AS active, is_multiple, prerequisite_menu, prerequisite_category, start_hour, end_hour
    FROM `programs` WHERE `partner_id`='$partner_id' AND `deleted_at` IS NULL AND `enabled`='1' AND '$today' BETWEEN `valid_from` AND  `valid_until` AND is_sf_only=0 ORDER BY active DESC, need_extra DESC");
    if (mysqli_num_rows($qP) > 0) {
        $resP = mysqli_fetch_all($qP, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 203;
        $msg = "Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "programs"=>$resP]);
