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
$signupMsg = 'Failed';
// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
//     $status = $tokens['status'];
//     $signupMsg = $tokens['msg'];
// }else{
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    if(isset($obj['name']) && !empty($obj['name'])){
        if(isset($obj['Deskripsi']) && !empty($obj['Deskripsi'])){
        }else{
            $obj['Deskripsi']="-";
        }
        $partner_id = $obj['partnerId'];
        $name = $obj['name'];
        $price = $obj['price'];
        $cogs = $obj['cogs'];
        $image = $obj['image'];
        $thumbnail = $obj['thumbnail'];
        $category = $obj['category'];
        $insert = mysqli_query($db_conn,"INSERT INTO `menu` SET `id_partner`='$partner_id',`nama`='{$name}',`harga`='{$price}',`Deskripsi`='-',`category`='-',`id_category`='{$category}',`img_data`='{$image}',`enabled`='1',`stock`='0',`hpp`='{$cogs}',`harga_diskon`='0',`is_variant`='0',`is_recommended`='0',`is_recipe`='0', `thumbnail`='$thumbnail' ");
        $iid = mysqli_insert_id($db_conn);
        $movement = mysqli_query($db_conn, "INSERT INTO stock_movements SET partner_id='$partner_id', menu_id='$iid', metric_id='6', type=0, initial='0', remaining='0'");
        if($insert){
            $success=1;
            $signupMsg="Success";
            $status=200;
        }else{
            $success=0;
            $signupMsg="Failed";
            $status=503;
        }
    }else{
        $success=0;
        $signupMsg="Missing require filed's";
        $status=400;
    }
// }


    $signupJson = json_encode(["msg"=>$signupMsg, "status"=>$status,"success"=>$success]);
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;

 ?>
