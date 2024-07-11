<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../menuModels/menuManager.php");
require_once("./../tokenModels/tokenManager.php");
require_once("./../recipeModels/recipeManager.php");
require_once("./../menusVariantGroupsModels/menusVariantGroupsManager.php");
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
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];

}else{
    $obj = json_decode(file_get_contents('php://input'));
    if (
        isset($obj->category_id) &&
        !empty($obj->category_id) &&
        isset($obj->percentage) &&
        !empty($obj->percentage)
    ) {
        $implodeCat = implode(",",$obj->category_id);
        
        if($obj->calculation_type == "1"){
            $newMultiplier = (100 + (int) $obj->percentage) /100;
        } else {
            $newMultiplier = (100 - (int) $obj->percentage) /100;
        }
        
        $updatePrice = mysqli_query($db_conn, "UPDATE menu SET harga = harga * $newMultiplier WHERE id_category IN(" . $implodeCat . ")");
        
        if($updatePrice){
            $success = 1;
            $msg = "Berhasil Update Harga";
            $status = 200;
        } else {
            $success = 0;
            $msg = "Gagal Update Harga";
            $status = 204;
        }
        
    } else {
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 400;
    }

}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);
echo $signupJson;
