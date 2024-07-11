<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../variantGroupModels/variantGroupManager.php"); 
require_once("./../variantModels/variantManager.php"); 
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../recipeModels/recipeManager.php"); 

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
$tokenDcrpt = json_decode($tokenizer->stringEncryption('decrypt',$token));
$success=0;
$msg = 'Failed'; 

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];

    $msg = $tokens['msg']; 

}else{
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    $name = mysqli_real_escape_string($db_conn, $obj['name']);
    $partnerID = $obj['partnerID'];
    $masterID = $obj['masterID'];
    
    // $variantGroup = new VariantGroup(array("id_menu"=>0,"id_master"=>$tokenDcrpt->masterID,"name"=>$name,"type"=>$obj['type']));
    $variantGroup = new VariantGroup(array("id_menu"=>0,"id_master"=>$masterID,"partner_id"=>$partnerID,"name"=>$name,"type"=>$obj['type']));

    $VariantGroupManager = new VariantGroupManager($db);
    $insert = $VariantGroupManager->add($variantGroup);
    if($insert!=false){
        foreach ($obj['variants'] as $value) {

            $VariantManager = new VariantManager($db);
            $valName = mysqli_real_escape_string($db_conn, $value['name']);
            $variant = new variant(array("id_variant_group"=>$insert,"name"=>$valName,"price"=>$value['price'],"stock"=>$value['stock'],"is_recipe"=>$value['is_recipe']));
            $add1 = $VariantManager->add($variant);
            
            $query = "SELECT id FROM `variant` ORDER BY `id` DESC LIMIT 1";
            $sql = mysqli_query($db_conn, $query);
            $getLastData = mysqli_fetch_all($sql, MYSQLI_ASSOC);
            $lastID = $getLastData[0]['id'];
            
            if($value['is_recipe']=='1' && $add1!=false || $value['is_recipe']==1 && $add1!=false){
                $cogs = 0;
                foreach ($value['recipes'] as $value) {
                    $recipeManager = new RecipeManager($db);
                    $recipes = new Recipe(array("id_menu"=>0,"id_raw"=>$value['id_raw'],"qty"=>$value['qty'],"id_metric"=>$value['id_metric'],"id_variant"=>$add1));
                    $add = $recipeManager->add($recipes);
                    $cogs += ((int)$value['qty'] * $value['price_raw'] );
                }
                
                // update cogsnya disini
                $queryUpdate = "UPDATE `variant` SET `cogs`='$cogs' WHERE id = '$lastID'";
                $updateCOGS = mysqli_query($db_conn, $queryUpdate);
            }

        }

    }

    if($insert==true){
        $success=1;
        $msg="Success";
        $status=200;
    }else{
        $success=0;
        $msg="Failed";
        $status=204;
    }
}
    
        
$signupJson = json_encode(["msg"=>$msg, "status"=>$status,"success"=>$success, "lastCOGS"=>$cogs]);  
echo $signupJson;

?>