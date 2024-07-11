<?php
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

    // POST DATA
    $json = file_get_contents('php://input');
    $data = json_decode($json,true);
    if(isset($data['id'])
        && isset($data['id_metric'])
        && isset($data['id_metric_price'])
        && isset($data['name'])
        && isset($data['reminder_allert'])
        && isset($data['unit_price'])
        && !empty($data['id'])
        && !empty($data['id_metric'])
        && !empty($data['id_metric_price'])
        && !empty($data['name'])
        && !empty($data['reminder_allert'])
        && !empty($data['unit_price'])){
            $yield=100;
            $id = $data['id'];
            $id_metric = $data['id_metric'];
            $id_metric_price = $data['id_metric_price'];
            $name = $data['name'];
            $reminder_allert = $data['reminder_allert'];
            $unit_price = $data['unit_price'];
            $yield = $data['yield'];
            $name = mysqli_real_escape_string($db_conn, $name);
            $insert = mysqli_query($db_conn,"UPDATE `raw_material` SET id_metric='$id_metric', name='$name', reminder_allert='$reminder_allert', unit_price='$unit_price', id_metric_price='$id_metric_price', yield='$yield' WHERE id='$id'");
            if($insert){
                
                // update cogs variant disini
                $id_raw_material = $id;
                $sqlVariant = mysqli_query($db_conn, "SELECT id_variant, qty FROM `recipe` WHERE id_raw = '$id_raw_material' AND deleted_at IS NULL ORDER BY `id` DESC");
                    
                if(mysqli_num_rows($sqlVariant) > 0) {
                    $data = mysqli_fetch_all($sqlVariant, MYSQLI_ASSOC);
                                
                    foreach($data as $val) {
                        $variant_id = $val['id_variant'];
                        // $qty_var = (int)$val['qty'];
                        $newCogs = 0;
                                    
                        $sqlVariantRecipe = mysqli_query($db_conn, "SELECT id_raw, qty FROM `recipe` WHERE id_variant = '$variant_id' AND deleted_at IS NULL ORDER BY `id` DESC");
                        $dataVariant = mysqli_fetch_all($sqlVariantRecipe, MYSQLI_ASSOC);
                        foreach($dataVariant as $item) {
                            $raw_id = $item['id_raw'];
                            $qty_var = (int)$item['qty'];
                                        
                            $sqlPrice = mysqli_query($db_conn, "SELECT unit_price FROM raw_material WHERE id = '$raw_id' AND deleted_at IS NULL");
                            $dataPrice = mysqli_fetch_all($sqlPrice, MYSQLI_ASSOC);
                            $price_raw = (double)$dataPrice[0]['unit_price'];
                                        
                            $newCogs += $price_raw *$qty_var ;
                        }
                                    
                        // update cogs variant
                        $sqlUpdateCOGS = mysqli_query($db_conn, "UPDATE variant SET cogs = '$newCogs' WHERE id = '$variant_id' AND deleted_at IS NULL");
                        }
                }
                // update cogs variant disini end
                
                $success =1;
                $status =200;
                $msg = "Success";
            }else{
                $success =0;
                $status =204;
                $msg = "Failed";
            }
    }else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>