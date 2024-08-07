<?php    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../employeeModels/employeeManager.php"); 
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

    if(isset($obj['id']) && !empty($obj['id'])){

        $employeeManager = new EmployeeManager($db);
        $employee = $employeeManager->getById($obj['id']);

        if($employee!=false){
            $deleted = $employeeManager->delete($employee);
            if($deleted==true){
                $success=1;
                $msg="Successs";
                $status=200;
            }else{
                $success=0;
                $msg="Failed";
                $status=503;
            } 
        }else{
            $success=0;
            $msg="Data Not Found";
            $status=204;
        }
        
    }else{
        $msg = "Missing require field";
        $success=0;
        $status=400;
    }

}
        
$signupJson = json_encode(["msg"=>$msg, "success"=>$success, "status"=>$status]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;

 ?>
 