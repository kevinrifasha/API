<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../tokenModels/tokenManager.php");
require_once("./../rawMaterialModels/rawMaterialManager.php");

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
$today = date("Y-m-d H:i:s");


$json = file_get_contents('php://input');
$obj = json_decode($json,true);
$res=array();

$success=0;
$msg = "failed";
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
}else{
    
    $id_raw_material = $obj['id'];
    $rawManager = new RawMaterialManager($db);
    $rawMaterial = $rawManager->getById($id_raw_material);

    $name = mysqli_real_escape_string($db_conn, $obj['name']);
    $rawMaterial->setName($name);
    $rawMaterial->setReminder_allert($obj['reminder_allert']);
    $rawMaterial->setId_metric($obj['id_metric']);
    $rawMaterial->setUnit_price($obj['unit_price']);
    $rawMaterial->setId_metric_price($obj['id_metric_price']);
    $rawMaterial->setCategory_id($obj['categoryID']);
    $rawMaterial->setYieldRM($obj['yield']);
    $update = $rawManager->update($rawMaterial);
    
    if($update!=false){
        
        // update cogs variant disini
        $sqlVariant = mysqli_query($db_conn, "SELECT id_variant, qty FROM `recipe` WHERE id_raw = '$id_raw_material' AND deleted_at IS NULL ORDER BY `id` DESC");
                    
                if(mysqli_num_rows($sqlVariant) > 0) {
                    $data = mysqli_fetch_all($sqlVariant, MYSQLI_ASSOC);
                        
                    foreach($data as $val) {
                        $variant_id = $val['id_variant'];
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
        
        $success=1;
        $msg = "Success";
        $status = 200;
    }else{
        $success=0;
        $msg = "Failed";
        $status = 400;
    }
}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);

echo $signupJson;

 ?>
