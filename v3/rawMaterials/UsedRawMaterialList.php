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
require_once("./../metricModels/metricManager.php");
require_once("./../rawMaterialStockModels/rawMaterialStockManager.php");
require_once("./../metricConvertModels/metricConvertManager.php");

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
$res = array();
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
    if(isset($_GET['rawID']) && !empty($_GET['rawID'])){
        
        $raw_id = $_GET['rawID'];
        $sqlRecipe = mysqli_query($db_conn, "SELECT id_menu, id_variant, sfg_id FROM recipe WHERE id_raw = '$raw_id' AND deleted_at IS NULL");
        
        $menuList = "";
        $variantList = "";
        $semiFinishedList = "";
        
        if(mysqli_num_rows($sqlRecipe)) {
            $fetchRecipe = mysqli_fetch_all($sqlRecipe, MYSQLI_ASSOC);
            
            foreach($fetchRecipe as $val) {
                $id_menu = $val['id_menu'];
                $id_variant = $val['id_variant'];
                $id_sfg = $val['sfg_id'];
                
                if($id_menu != 0) {
                    $sqlMenu = mysqli_query($db_conn, "SELECT nama FROM menu WHERE id = '$id_menu' AND deleted_at IS NULL");
                    $fetchMenu = mysqli_fetch_all($sqlMenu, MYSQLI_ASSOC);
                    $menuName = $fetchMenu[0]['nama'];
                    
                    if($menuList == "") {
                        $menuList .= $menuName;
                    } else {
                        $menuList .= ", " . $menuName;
                    }
                } else if ($id_variant != 0) {
                    $sqlVariant = mysqli_query($db_conn, "SELECT name FROM variant WHERE id = '$id_variant' AND deleted_at IS NULL");
                    $fetchVariant = mysqli_fetch_all($sqlVariant, MYSQLI_ASSOC);
                    $variantName = $fetchVariant[0]['name'];
                    
                    if($variantList == "") {
                        $variantList .= $variantName;
                    } else {
                        $variantList .= ", " . $variantName;
                    }
                } else {
                    $sqlSemiFinished = mysqli_query($db_conn, "SELECT name FROM raw_material WHERE level = '1' AND id = '$id_sfg' AND deleted_at IS NULL");
                    $fetchSemiFinished = mysqli_fetch_all($sqlSemiFinished, MYSQLI_ASSOC);
                    $semiFinishedName = $fetchSemiFinished[0]['name'];
                    
                    if($semiFinishedList == "") {
                        $semiFinishedList .= $semiFinishedName;
                    } else {
                        $semiFinishedList .= ", " . $semiFinishedName;
                    }
                }
            }
        }
        
        $res['menuList'] = $menuList;
        $res['variantList'] = $variantList;
        $res['semiFinishedList'] = $semiFinishedList;
        
        if(strlen($menuList) == 0 && strlen($variantList) == 0 && strlen($semiFinishedList) == 0) {
            $success = 0;
            $msg = "Data not found";
            $status = 203;
        } else {
            
            $sqlRaw = mysqli_query($db_conn, "SELECT name FROM raw_material WHERE id = '$raw_id' AND deleted_at IS NULL");
            $fetchRaw = mysqli_fetch_all($sqlRaw, MYSQLI_ASSOC);
            $rawName = $fetchRaw[0]['name'];
            $res['rawName'] = $rawName;
            
            $success = 1;
            $msg = "Success";
            $status = 200;
        }
        
    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;
    }
}

echo json_encode(["msg"=>$msg, "status"=>$status,"success"=>$success,"list"=>$res]);

?>