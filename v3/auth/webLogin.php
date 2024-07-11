<?php    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../partnerModels/partnerManager.php"); 
require_once("./../masterModels/masterManager.php"); 
require_once("./../employeeModels/employeeManager.php"); 
require_once("./../tokenModels/tokenManager.php"); 

$today = date("Y-m-d H:i:s");
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

    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    $success=0;
    $email = $obj['email'];
    $password = $obj['password'];
    $res = array();
    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
        $signupMsg = $tokens['msg']; 
    }else{
        //validate fields (must not be empty)
        if (!empty($email) || !empty($password)) { 
                //validate email format
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) { 
                    
                    $employeeManager = new EmployeeManager($db);
                    $employee = $employeeManager->loginEmail($email,md5($password));
                    $partnerManager = new PartnerManager($db);
                    $partner = $partnerManager->getPartnerDetails($employee->getId_partner());
                    if($employee->getRole_id()=='1'){
                        $masterManager = new MasterManager($db);
                        $master = $masterManager->getMasterDetails($employee->getId_master());
                        $res = $master->getDetails();
                        $res['role']="master";
                        $res['emp_phone']=$employee->getPhone();
                    }else{
                        $res = $partner->getDetails();
                        $res['emp_phone']=$employee->getPhone();
                        $res['role']="partner";
                    }

                    if($employee==false){
                        $success=0;
                        $signupMsg="failed";
                    }else{
                        $success=1;
                        $signupMsg="Success";
                    }
                    
                }
        }else
            $signupMsg = 'field(s) must not be empty' ;
    }
        
    $signupJson = json_encode(["msg"=>$signupMsg, "detail"=>$res, "success"=>$success, "emp_phone"=>$res['emp_phone']]);  
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;

 ?>
 