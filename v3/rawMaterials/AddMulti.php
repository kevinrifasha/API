<?php    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../rawMaterialModels/rawMaterialManager.php"); 
require_once("./../rawMaterialStockModels/rawMaterialStockManager.php"); 

// $headers = array();
//     $rx_http = '/\AHTTP_/';
//     foreach($_SERVER as $key => $val) {
//       if( preg_match($rx_http, $key) ) {
//         $arh_key = preg_replace($rx_http, '', $key);
//         $rx_matches = array();
//         // do some nasty string manipulations to restore the original letter case
//         // this should work in most cases
//         $rx_matches = explode('_', $arh_key);
//         if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
//           foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
//           $arh_key = implode('-', $rx_matches);
//         }
//         $headers[$arh_key] = $val;
//       }
//     }
// $token = '';

// foreach ($headers as $header => $value) {
//     if($header=="Authorization" || $header=="AUTHORIZATION"){
//         $token=substr($value,7);
//     }
// }

$db = connectBase();
$tokenizer = new TokenManager($db);
// $tokens = $tokenizer->validate($token);
$today = date("Y-m-d H:i:s");

$json = file_get_contents('php://input');
$obj = json_decode($json,true);
$res=array();

$success=0;
$msg = "failed";
// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
//     $status = $tokens['status'];
//     $msg = $tokens['msg']; 
//     $success = 0;

// }else{

    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    
    $id_master = $obj["id_master"];
    $id_partner = $obj["id_partner"];
    $data = $obj['raws']; 

    foreach($data as $v){

        $rawManager = new RawMaterialManager($db);
        $rawMaterial = new RawMaterial(array("id_master"=>$id_master,"id_partner"=>$id_partner,"name"=>str_replace("'","''",$v["name"]),"reminder_allert"=>$v["reminderAlertInsert"],"id_metric"=>$v["metricReminderIDInsert"],"unit_price"=>0, "id_metric_price"=>$v['metricIDInsert']));
        $add = $rawManager->add($rawMaterial);
        
        if($add!=false){
            $rawManagerS = new RawMaterialStockManager($db);
            $rawMaterialS = new RawMaterialStock(array("id_raw_material"=>$add,"id_metric"=>$v["metricIDInsert"],"stock"=>$v['stock'],"exp_date"=>$v['expired']));
            $add = $rawManagerS->insertInit($rawMaterialS);
        }else{
            // $success=0;
            // $msg = "Failed";
            // $status = 400;
        }
    }
    $success=1;
    $msg = "Success";
    $status = 200;

// }
        
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
?>