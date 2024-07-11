<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
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
    
    if(isset($_GET['id']) && !empty($_GET['id'])){
        $manager = new RawMaterialManager($db);
        $id = $_GET['id'];
        
        $raw = $manager->getById($id);
        if($raw!=false){
            $rawd = $raw->getDetails();
            $metricManager = new MetricManager($db);
            $metric = $metricManager->getById($rawd['id_metric']);
            if($metric!=false){
                $dataM = $metric->getDetails();
                $rawd['metricName'] = $dataM['name'];
            }else{
                $rawd['metricName'] = "wrong";
            }
            $RMSmanager = new RawMaterialStockManager($db);
            $rawMaterialStocks = $RMSmanager->getByRawId($rawd['id']);
            $stock=0;
            $idm=0;
            $rawd['stockDetails'] = array();
            if($rawMaterialStocks!=false){
                $arrRms = array();
                foreach ($rawMaterialStocks as $valueRMS) {
                    $rms = $valueRMS->getDetails();
                    $metric = $metricManager->getById($rms['id_metric']);
                    if($metric!=false){
                        $dataM = $metric->getDetails();
                        $rms['metricName'] = $dataM['name'];
                    }else{
                        $rms['metricName'] = "Wrong";
                    }
                    array_push($arrRms,$rms);
                }
                $rawd['stockDetails'] = $arrRms;
            }
            $success = 1;
            $msg = "Success";
            $status = 200;
        }else{
            
            $success = 0;
            $msg = "Data Not Found";
            $status = 204;
            
        }
            

    }else{
        
        $success = 0;
        $msg = "Missing Required Field";
        $status = 400;
    
    }

}
    
        
$signupJson = json_encode(["msg"=>$msg, "status"=>$status,"success"=>$success,"rawMaterialsStock"=>$rawd['stockDetails']]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;

?>