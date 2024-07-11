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
    $EmployeeManager = new EmployeeManager($db);


        if(!empty($obj['nik'])){
            $cek = $EmployeeManager->getByNik($obj['nik']);
        }else{
            $cek = false;
        }
        if($cek==false){
        
            if(isset($obj['phone']) && !empty($obj['phone'])){
                $cek = $EmployeeManager->getByPhone($obj['phone']);
            }
            
            if($cek==false){
            
                $cek = $EmployeeManager->getByEmail($obj['email']);
                if($cek==false){
                    
                    $employee = new Employee(array("nik"=>$obj['nik'],"nama"=>str_replace("'","''",$obj['nama']),"gender"=>$obj['gender'],"phone"=>$obj['phone'],"email"=>$obj['email'],"pin"=>md5($obj['pin']),"id_master"=>$obj['id_master'],"id_partner"=>$obj['id_partner'],"role_id"=>$obj['role_id'],"pattern_id"=>$obj['pattern_id'],"show_as_server"=>$obj['show_as_server'],
                        "organization" => 'Natta'));
                    $insert = $EmployeeManager->add($employee);
                    
                    if($insert==true){
                        $success=1;
                        $msg="Success";
                        $status = 200;
                    }else{
                        $success=0;
                        $msg=$insert;
                        $status = 201;
                    }
                    
                }else{
                    
                    $success=0;
                    $msg="Email sudah terdaftar!";
                    $status=201;

                }
                
            }else{
                
                $success=0;
                $msg="Nomor telepon sudah terdaftar!";
                $status=201;
                
            }
            
        }else{
            
            $success=0;
            $msg="NIK Already Registered";
            $status=201;
            
        }
    
}
    
        
$signupJson = json_encode(["msg"=>$msg, "success"=>$success,"status"=>$status]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;

 ?>
 