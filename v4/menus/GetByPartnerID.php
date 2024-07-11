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
    if($_GET['partner_type'] == 7){
        $page = $_GET['page'];
        $load = $_GET['load'];
        $finish = $load * $page;
        $start = $finish - $load;    
        $q = mysqli_query($db_conn, "SELECT m.id, m.nama, m.harga, m.sku, m.Deskripsi as deskripsi, c.name as category, m.img_data, m.thumbnail, m.enabled, m.stock, m.harga_diskon, m.is_variant, m.is_recommended, m.is_recipe, m.id_category, m.hpp, m.is_multiple_price, m.show_in_sf, m.show_in_waiter FROM menu m LEFT JOIN categories c ON m.id_category = c.id WHERE m.id_partner='$token->id_partner' AND m.deleted_at IS NULL LIMIT $start,$load");
    } else {
        $q = mysqli_query($db_conn, "SELECT m.id, m.nama, m.harga, m.sku, m.Deskripsi as deskripsi, c.name as category, m.img_data, m.thumbnail, m.enabled, m.stock, m.harga_diskon, m.is_variant, m.is_recommended, m.is_recipe, m.id_category, m.hpp, m.is_multiple_price, m.show_in_sf, m.show_in_waiter FROM menu m LEFT JOIN categories c ON m.id_category = c.id WHERE m.id_partner='$token->id_partner' AND m.deleted_at IS NULL");
    }
    
    $res1 = [];
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $i = 0;
        foreach ($res as $value) {
            $res1[$i] = $value; 
            $menuID = $value['id'];
        $getSurchargePrice = mysqli_query($db_conn, "SELECT id, surcharge_id, price FROM menu_surcharge_types WHERE deleted_at IS NULL and partner_id='$token->id_partner' AND menu_id='$menuID'");
        $sp = mysqli_fetch_all($getSurchargePrice, MYSQLI_ASSOC);
        
        if($value['is_multiple_price'] == "0" || $value['is_multiple_price'] == 0){
            $res1[$i]['surcharges'] = array();
        } else {
            $res1[$i]['surcharges'] = $sp; 
        }
        
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
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "menus"=>$res1]);
?>