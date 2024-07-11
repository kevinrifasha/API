<?php    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../connection.php");
require_once("./../../menuModels/menuManager.php"); 
require_once("./../../tokenModels/tokenManager.php"); 
require_once("./../../recipeModels/recipeManager.php"); 
require_once("./../../menusVariantGroupsModels/menusVariantGroupsManager.php"); 
require '../../../db_connection.php';


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
    
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    $MenuManager = new MenuManager($db);
    if(isset($obj['id']) && !empty($obj['id'])){

        $menu = $MenuManager->getById($obj['id']);
        $id=$obj['id'];
        $is_suggestions=$obj['is_suggestions'];
        if($menu!=false){
            
            $insert = mysqli_query($db_conn,"UPDATE `menu` SET `is_suggestions`='$is_suggestions' WHERE `id`='$id'");
            if($insert){
                
                if(isset($obj['oldID']) && !empty($obj['oldID'])){
                    $oi = $obj['oldID'];
                    $insert = mysqli_query($db_conn,"UPDATE `menu` SET `is_suggestions`='0' WHERE `id`='$oi'");
                }
                $msg = "Berhasil mengubah data";
                $success = 1;
                $status=200;
            }else{
                $msg = "Gagal mengubah data";
                $success = 0;
                $status=204;
            }
        }else{
            
            $success = 0;
            $msg = "Data Not Registered";
            $status = 400;    
            
        }
    }else{

        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;   

    }

}  
        
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg ]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
?>