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
$tokenDcrpt = json_decode($tokenizer->stringEncryption('decrypt',$token));
$success=0;
$signupMsg = 'Failed'; 
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $signupMsg = $tokens['msg']; 
}else{
    
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);

    // if(
    //     isset($obj['nama']) && !empty($obj['nama'])
    //     && isset($obj['phone']) && !empty($obj['phone'])
    //     && isset($obj['email']) && !empty($obj['email'])
    // ){

        if(isset($obj['id']) && !empty($obj['id'])){
            $tokenDcrpt->id=$obj['id'];
        }

        $employeeManager = new EmployeeManager($db);
        $employee = $employeeManager->getById($tokenDcrpt->id);
        if($employee!=false){
            
            
            if(isset($obj['nik'])){
                $employee->setNik($obj['nik']);
            }
            
            if(isset($obj['nama']) && !empty($obj['nama'])){
                $employee->setNama(str_replace("'","''",$obj['nama']));
            }
            
            if(isset($obj['gender']) ){
                $employee->setGender($obj['gender']);
            }
            
            if(isset($obj['phone']) && !empty($obj['phone'])){
                $employee->setPhone($obj['phone']);
            }
            
            if(isset($obj['email']) && !empty($obj['email'])){
                $employee->setEmail($obj['email']);
            }
            
            if(isset($obj['id_master']) && !empty($obj['id_master'])){
                $employee->setId_master($obj['id_master']);
                
            }
            
            if(isset($obj['id_partner']) && !empty($obj['id_partner'])){
                $employee->setId_partner($obj['id_partner']);
                
            }
            
            if(isset($obj['role_id']) && !empty($obj['role_id'])){
                $employee->setRole_id($obj['role_id']);
            }

            if(isset($obj['pattern_id']) && !empty($obj['pattern_id'])){
                $employee->setPattern_id($obj['pattern_id']);
            }
            
            if(isset($obj['show_as_server']) ){
                $employee->setShow_as_server($obj['show_as_server']);
            }

            if(isset($obj['pin']) && !empty($obj['pin'])){
                $employee->setPin(md5($obj['pin']));
            }
            
            if(isset($obj['newPin']) && !empty($obj['newPin'])){
                if(isset($obj['oldPin']) && !empty($obj['oldPin'])){
                    if($employee->getPin()==md5($obj['oldPin'])){
                        $employee->setPin(md5($obj['newPin']));
                    }else{
                        $signupMsg = "Wrong Old PIN";
                        $status = 400;
                    }
                }
            }
            
            $update = $employeeManager->update($employee);

            if($update!=false && $signupMsg!="Wrong Old PIN"){
                $success=1;
                $signupMsg="success";
                $status=200;
            }else if($signupMsg=="Wrong Old PIN"){
                $success=0;
                $status=503;
            }else{
                $success=0;
                $status=503;
                $signupMsg="failed";
            }
        }else{
            $success=0;
            $signupMsg="Data Not Found";
            $status=204;
        }
    // }else{
    //     $success=0;
    //     $signupMsg="Missing Require Fields";
    //     $status=503;
    // }
}
    
    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$signupMsg]);  
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;

 ?>
 