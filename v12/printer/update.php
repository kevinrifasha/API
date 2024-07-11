<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../printerModels/printerManager.php"); 
require_once("./../tokenModels/tokenManager.php"); 
        
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
    if(
        isset($obj['printerId']) && !empty($obj['printerId'])
    ){
        $printerManager = new PrinterManager($db);
        $id = $obj['printerId'];
        $printer = $printerManager->getById($id);
        
        if($printer!=false){
            
            if(isset($obj['ip']) && !empty($obj['ip'])){
                $printer->setIp($obj['ip']);    
            }

            if(isset($obj['name']) && !empty($obj['name'])){
                $printer->setName(str_replace("'","''",$obj['name']));    
            }

            if(isset($obj['macAdress']) && !empty($obj['macAdress'])){
                $printer->setMacAdress($obj['macAdress']);    
            }

            if(isset($obj['isReceipt']) && !empty($obj['isReceipt'])){
                $printer->setIsReceipt($obj['isReceipt']);    
            }

            if(isset($obj['isFullChecker']) && !empty($obj['isFullChecker'])){
                $printer->setIsFullChecker($obj['isFullChecker']);    
            }

            if(isset($obj['isCategoryChecker']) && !empty($obj['isCategoryChecker'])){
                $printer->setIsCategoryChecker($obj['isCategoryChecker']);    
            }

            $update = $printerManager->update($printer);
            if($update!=false){
                $msg = "Success";
                $success = 1;
                $status = 200;
            }else{
                $msg = "Failed";
                $success = 1;
                $status = 204;
            }
            
        }else{
            $success=0;
            $msg="Data Not Found";
            $status = 400;
        }

    }

}
        
$signupJson = json_encode(["msg"=>$msg, "status"=>$status,"success"=>$success,"status"=>$status]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
    
?>
     