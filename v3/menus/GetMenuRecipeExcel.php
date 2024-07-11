<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../tokenModels/tokenManager.php");
require_once("./../menuModels/menuManager.php");
require_once("./../categoryModels/categoryManager.php");

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
    $res = [];

    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
        $status = $tokens['status'];
        $signupMsg = $tokens['msg'];
        $success = 0;
    }else{
        $partnerId = $_GET['partnerId'];
        
        $query = "SELECT menu.id FROM `menu` WHERE id_partner = '$partnerId' AND is_recipe = '1' AND deleted_at IS NULL ORDER BY id DESC";
        $sql = mysqli_query($db_conn, $query);
        
        if(mysqli_num_rows($sql) > 0) {
            $dataGet = mysqli_fetch_all($sql, MYSQLI_ASSOC);
            
            foreach ($dataGet as $val) {
                $menuID = $val['id'];
                
                $sqlRawMaterial = mysqli_query($db_conn, "SELECT r.id_raw, rm.name FROM `recipe` r JOIN `raw_material` rm ON rm.id = r.id_raw WHERE id_menu = '$menuID' ORDER BY r.id DESC;");
                
                $dataRaw = mysqli_fetch_all($sqlRawMaterial, MYSQLI_ASSOC);
                
                $raws = array();
                
                if(count($dataRaw) > 0) {
                    foreach($dataRaw as $data) {
                        $rawName = $data['name'];
                        array_push($raws, $rawName);
                    }
                    $val['rawMaterials'] = implode(", ", $raws);
                } else {
                    $val['rawMaterials'] = "";
                }
                
                array_push($res, $val);
            }
            
            $success = 1;
            $signupMsg = "Success";
            $status=200;
        } else {
            $success = 0;
            $signupMsg = "Data not found";
            $status=400;
        }
        
        
    }
    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$signupMsg, "menus"=>$res]);

    echo $signupJson;
 ?>
