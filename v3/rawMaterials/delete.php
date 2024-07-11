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
require_once("./../rawMaterialStockModels/rawMaterialStockManager.php"); 
require_once("./../recipeModels/recipeManager.php"); 
require_once("./../menuModels/menuManager.php");

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
$raw_material_id = $obj['id'];

$success=0;
$msg = "failed";

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $msg = $tokens['msg']; 
    $success = 0; 
}else{

    $rawManager = new RawMaterialManager($db);
    $rawManagerStock = new RawMaterialStockManager($db);
    $rawMaterial = $rawManager->getById($raw_material_id);
    
    if($rawMaterial!=false){
        
        $rm = $rawManager->delete($rawMaterial);
        $rawMaterialS = $rawManagerStock->getByRawId($obj['id']);
        
        if($rawMaterialS!=false) {
            
            $rawManagerStock->deleteByRawId($obj['id']);
            $recipeManager = new RecipeManager($db);
            $registered = $recipeManager->getByRawID($obj['id']);
            
            foreach($registered as $rgstrd) {
                $details = $rgstrd->getDetails();
                $deleted = $recipeManager->delete($details['id']);
                $id_menu=$details['id_menu'];

                $recipeManager = new RecipeManager($db);
                $recipes = $recipeManager->getByMenuId($id_menu);
                if(count($recipes)>0){

                }else{
                    $MenuManager = new MenuManager($db);
                        $menu = $MenuManager->getById($id_menu);
                        if($menu!=false){
                            $menu->setIs_recipe(0);
                            $update = $MenuManager->update($menu);
                        }
                }
            }
            
            // ubah cogs
            $sqlVariant = mysqli_query($db_conn, "SELECT id_variant, id_menu, sfg_id, qty FROM `recipe` WHERE id_raw = '$raw_material_id' ORDER BY `id` DESC");
                        
            if(mysqli_num_rows($sqlVariant) > 0) {
                
                $data = mysqli_fetch_all($sqlVariant, MYSQLI_ASSOC);
                            
                foreach($data as $val) {
                    $variant_id = $val['id_variant'];
                    $id_menu = $val['id_menu'];
                    $id_sfg = $val['sfg_id'];
                    $newCogsVariant = 0;
                    $newCogsMenu = 0;
                    $newUnitPrice = 0;
                                
                    if ($variant_id != 0) {
                        // variant
                        $sqlVariantRecipe = mysqli_query($db_conn, "SELECT id_raw, qty FROM `recipe` WHERE id_variant = '$variant_id' AND deleted_at IS NULL ORDER BY `id` DESC");
                        
                        // check kalo misalnya resep di variant cuman bahan baku yang dihapus, otomatis dia udah tidak punya resep lagi, maka ubah is_recipe, stock, cogs jadi 0
                        if(mysqli_num_rows($sqlVariantRecipe) == 0) {
                            // ubah is_recipe, stock, cogs jadi 0 disini
                            $sqlUpdateVariant = mysqli_query($db_conn, "UPDATE variant SET is_recipe='0', stock='0', cogs='0' WHERE id ='$variant_id' AND deleted_at IS NULL");
                            
                        } else {
                            $dataVariant = mysqli_fetch_all($sqlVariantRecipe, MYSQLI_ASSOC);
                            foreach($dataVariant as $item) {
                                $raw_id = $item['id_raw'];
                                $qty_var = $item['qty'];
                                                
                                $sqlPrice = mysqli_query($db_conn, "SELECT unit_price FROM raw_material WHERE id = '$raw_id' AND deleted_at IS NULL");
                                $dataPrice = mysqli_fetch_all($sqlPrice, MYSQLI_ASSOC);
                                $price_raw = (double)$dataPrice[0]['unit_price'];
                                                
                                $newCogsVariant += $price_raw * $qty_var ;
                            }
                            
                            // update cogs variant
                            $sqlUpdateCOGSVariant = mysqli_query($db_conn, "UPDATE variant SET cogs = '$newCogsVariant' WHERE id = '$variant_id' AND deleted_at IS NULL");
                        }
                                        
                        
                    } else if ($id_menu != 0) {
                        // menu
                        $sqlMenuRecipe = mysqli_query($db_conn, "SELECT id_raw, qty FROM `recipe` WHERE id_menu = '$id_menu' AND deleted_at IS NULL ORDER BY `id` DESC");
                        
                        // check kalo misalnya resep di menu cuman bahan baku yang dihapus, otomatis dia udah tidak punya resep lagi, maka ubah is_recipe, stock, hpp jadi 0
                        
                        if(mysqli_num_rows($sqlMenuRecipe) == 0) {
                            // ubah is_recipe, stock, cogs jadi 0 disini
                            $sqlUpdateMenu = mysqli_query($db_conn, "UPDATE menu SET is_recipe='0', stock='0', hpp='0' WHERE id ='$id_menu' AND deleted_at IS NULL");
                            
                        } else {
                            $fetchMenuRecipe = mysqli_fetch_all($sqlMenuRecipe, MYSQLI_ASSOC);
                            foreach($fetchMenuRecipe as $item) {
                                $raw_id = $item['id_raw'];
                                $qty_var = $item['qty'];
                                                
                                $sqlPrice = mysqli_query($db_conn, "SELECT unit_price FROM raw_material WHERE id = '$raw_id' AND deleted_at IS NULL");
                                $dataPrice = mysqli_fetch_all($sqlPrice, MYSQLI_ASSOC);
                                $price_raw = (double)$dataPrice[0]['unit_price'];
                                                
                                $newCogsMenu += $price_raw * $qty_var ;
                            }
                            
                            // update cogs menu
                            $sqlUpdateCOGSMenu = mysqli_query($db_conn, "UPDATE menu SET hpp = '$newCogsMenu' WHERE id = '$id_menu' AND deleted_at IS NULL");
                        }
                        
                    } else {
                        
                        // Bahan Setengah Jadi
                        $sqlSemiFinished = mysqli_query($db_conn, "SELECT id_raw, qty FROM `recipe` WHERE sfg_id = '$id_sfg' AND deleted_at IS NULL ORDER BY `id` DESC");
                        
                        // check kalo misalnya resep di sfg cuman bahan baku yang dihapus, otomatis dia udah tidak punya resep lagi, maka unit_price jadi 0
                        if(mysqli_num_rows($sqlSemiFinished) == 0) {
                            
                            // ubah unit_price disini
                            $sqlUpdateSfg = mysqli_query($db_conn, "UPDATE raw_material SET unit_price='0' WHERE id ='$id_sfg' AND deleted_at IS NULL");
            
                        } else {
                            $fetchSemiFinished = mysqli_fetch_all($sqlSemiFinished, MYSQLI_ASSOC);
                            foreach($fetchSemiFinished as $item) {
                                $raw_id = $item['id_raw'];
                                $qty_var = $item['qty'];
                                                
                                $sqlPrice = mysqli_query($db_conn, "SELECT unit_price FROM raw_material WHERE id = '$raw_id' AND deleted_at IS NULL");
                                $dataPrice = mysqli_fetch_all($sqlPrice, MYSQLI_ASSOC);
                                $price_raw = (double)$dataPrice[0]['unit_price'];
                                                
                                $newUnitPrice += $price_raw * $qty_var ;
                            }
                            
                            // update cogs bahan setengah jadi
                            $sqlUpdateUnitPrice = mysqli_query($db_conn, "UPDATE raw_material SET unit_price = '$newUnitPrice' WHERE id = '$id_sfg' AND deleted_at IS NULL");
                        }
                        
                    }
                }
            }
            // ubah cogs end
            
        }

        if($rm!=false){
            $success=1;
            $msg = "Success";
            $status=200;
        }else{
            $success=0;
            $msg = "Failed To Delete";
            $status=503;
        }
    }else{
        $success=0;
        $status=400;
        $msg = "Data not registered";

    }
}
        
$signupJson = json_encode(["success"=>$success, "msg"=>$msg]);  

echo $signupJson;

 ?>
 