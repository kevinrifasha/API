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
$arr = array();
$arrRes = array();

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
    $partner_id = $token->id_partner;
    if($_GET['partner_type'] == 7){
        $page = $_GET['page'];
        $load = $_GET['load'];
        $finish = $load * $page;
        $start = $finish - $load;  
        $q = mysqli_query($db_conn, "SELECT m.nama AS label, m.id, m.id AS value, m.hpp AS cogs FROM menu m JOIN categories c ON m.id_category=c.id WHERE m.id_partner='$partner_id' AND m.is_recipe=0 AND m.deleted_at IS NULL LIMIT $start,$load");
        $q1 = mysqli_query($db_conn, "SELECT r.id as value ,r.id, r.name as label, r.unit_price as cogs, m.id as metric_id, m.name as metric_name FROM `raw_material` r JOIN metric m ON r.id_metric = m.id  WHERE r.id_partner='$partner_id' AND r.deleted_at IS NULL LIMIT $start,$load");
    } else {
        $q = mysqli_query($db_conn, "SELECT m.nama AS label, m.id, m.id AS value, m.hpp AS cogs FROM menu m JOIN categories c ON m.id_category=c.id WHERE m.id_partner='$partner_id' AND m.is_recipe=0 AND m.deleted_at IS NULL");
        $q1 = mysqli_query($db_conn, "SELECT r.id as value ,r.id, r.name as label, r.unit_price as cogs, m.id as metric_id, m.name as metric_name FROM `raw_material` r JOIN metric m ON r.id_metric = m.id  WHERE r.id_partner='$partner_id' AND r.deleted_at IS NULL");
    }
    if (mysqli_num_rows($q) > 0 || mysqli_num_rows($q1) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        $i = 0;
        foreach ($res as $r) {
            $arr[$i] = $r;
            $arr[$i]['name']='items';
            $arr[$i]['type']='menu';
            $arr[$i]['target']=$arr[$i];
            $i+=1;
        }
        foreach ($res1 as $r) {
            $arr[$i] = $r;
            $arr[$i]['name']='items';
            $arr[$i]['type']='raw';
            $arr[$i]['target']=$arr[$i];
            $i+=1;
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
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "purchaseOrders"=>$arr]);
?>