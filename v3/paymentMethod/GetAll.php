<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';

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
$token = '';
    
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$arr = array();
$arr[0]['id']='0';
$arr[0]['name']='SEMUA';
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $partnerID = $_GET['partnerID'];
    $q = mysqli_query($db_conn, "SELECT (CASE WHEN partner.ovo_active='1' THEN payment_method.nama END )AS name, (CASE WHEN partner.ovo_active='1' THEN payment_method.id END ) AS id FROM partner, payment_method WHERE partner.id='$partnerID' AND payment_method.id='1' UNION ALL
    SELECT (CASE WHEN partner.gopay_active='1' THEN payment_method.nama END )AS name, (CASE WHEN partner.dana_active='1' THEN payment_method.id END ) AS id FROM partner, payment_method WHERE partner.id='$partnerID' AND payment_method.id='2' UNION ALL
    SELECT (CASE WHEN partner.dana_active='1' THEN payment_method.nama END )AS name, (CASE WHEN partner.dana_active='1' THEN payment_method.id END ) AS id FROM partner, payment_method WHERE partner.id='$partnerID' AND payment_method.id='3' UNION ALL
    SELECT (CASE WHEN partner.linkaja_active='1' THEN payment_method.nama END )AS name, (CASE WHEN partner.linkaja_active='1' THEN payment_method.id END ) AS id FROM partner, payment_method WHERE partner.id='$partnerID' AND payment_method.id='4' UNION ALL
    SELECT payment_method.nama AS name, payment_method.id FROM payment_method WHERE payment_method.id='5' UNION ALL
    SELECT (CASE WHEN partner.cc_active='1' THEN payment_method.nama END )AS name, (CASE WHEN partner.cc_active='1' THEN payment_method.id END ) AS id FROM partner, payment_method WHERE partner.id='$partnerID' AND payment_method.id='7' UNION ALL
    SELECT (CASE WHEN partner.debit_active='1' THEN payment_method.nama END )AS name, (CASE WHEN partner.debit_active='1' THEN payment_method.id END ) AS id FROM partner, payment_method WHERE partner.id='$partnerID' AND payment_method.id='8' UNION ALL
    SELECT (CASE WHEN partner.qris_active='1' THEN payment_method.nama END )AS name, (CASE WHEN partner.qris_active='1' THEN payment_method.id END ) AS id FROM partner, payment_method WHERE partner.id='$partnerID' AND payment_method.id='9' UNION ALL
    SELECT (CASE WHEN partner.qris_active='1' THEN payment_method.nama END )AS name, (CASE WHEN partner.qris_active='1' THEN payment_method.id END ) AS id FROM partner, payment_method WHERE partner.id='$partnerID' AND payment_method.id='14' UNION ALL
    SELECT (CASE WHEN partner.shopeepay_active='1' THEN payment_method.nama END )AS name, (CASE WHEN partner.qris_active='1' THEN payment_method.id END ) AS id FROM partner, payment_method WHERE partner.id='$partnerID' AND payment_method.id='10'");
    
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        foreach($res as $r){
            if($r['id']!=null){
                array_push($arr, $r);
            }
        }
        $success =1;
        $status =200;
        $msg = "Success";
    } else { 
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "type"=>$type,"paymentMethodPartner"=>$arr]);
?>