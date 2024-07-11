<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once("../../v3/connection.php");
require_once("../../v3/variantGroupModels/variantGroupManager.php"); 
require_once("../../v3/variantModels/variantManager.php"); 
require_once("../../v3/recipeModels/recipeManager.php"); 

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
$db = connectBase();

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
    $obj = json_decode($json,true);
    $obj['name'] = mysqli_real_escape_string($db_conn, $obj['name']);

    $variantGroup = new VariantGroup(array("id_menu"=>0,"id_master"=>$token->id_master,"name"=>$obj['name'],"type"=>$obj['type'],"partner_id"=>$token->id_partner));
    $VariantGroupManager = new VariantGroupManager($db);
    $insert = $VariantGroupManager->add($variantGroup);
    if($insert!=false){
        foreach ($obj['variants'] as $value) {

            $VariantManager = new VariantManager($db);
            $variant = new variant(array("id_variant_group"=>$insert,"name"=>$value['name'],"price"=>$value['price'],"stock"=>$value['stock'],"is_recipe"=>$value['is_recipe']));
            $add1 = $VariantManager->add($variant);
            
            $query = "SELECT id FROM `variant` ORDER BY `id` DESC LIMIT 1";
            $sql = mysqli_query($db_conn, $query);
            $getLastData = mysqli_fetch_all($sql, MYSQLI_ASSOC);
            $lastID = $getLastData[0]['id'];
            
            if($value['is_recipe']=='1' && $add1!=false || $value['is_recipe']==1 && $add1!=false){
                $cogs = 0;
                foreach ($value['recipes'] as $value) {
                    $raw_id = $value['id_raw'];
                    $recipeManager = new RecipeManager($db);
                    $recipes = new Recipe(array("id_menu"=>0,"id_raw"=>$raw_id,"qty"=>$value['qty'],"id_metric"=>$value['id_metric'],"id_variant"=>$add1));
                    $add = $recipeManager->add($recipes);
                    
                    $sql = mysqli_query($db_conn, "SELECT unit_price FROM raw_material WHERE id = '$raw_id' AND deleted_at IS NULL");
                    $dataRaw = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $unitPrice = $dataRaw[0]['unit_price'];
                    
                    $cogs += ((int)$value['qty'] * $unitPrice);
                }
            }
            
            // update cogsnya disini
            $queryUpdate = "UPDATE `variant` SET `cogs`='$cogs' WHERE id = '$lastID'";
            $updateCOGS = mysqli_query($db_conn, $queryUpdate);

        }

    }

    if($insert!=false){
        $success=1;
        $msg="Success";
        $status=200;
    }else{
        $success=0;
        $msg="Failed";
        $status=204;
    }

}
// http_response_code($status);    
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]); 
    
?>
     