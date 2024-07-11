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
require_once("./../roleModels/rolesManager.php"); 
require_once("./../tokenModels/tokenManager.php"); 
require '../../db_connection.php';


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
    $partnerId = $_GET['partnerId'];
    $res = array();
    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
        $status = $tokens['status'];
        $msg = $tokens['msg']; 
    }else{
        //validate fields (must not be empty)
        if (!empty($partnerId)) { 
            $employeeManager = new EmployeeManager($db);
            $employee = $employeeManager->getByPartnerId($partnerId);
            $res = array();
            
            if($employee!= false){
                foreach ($employee as $value) {
                    $rManager = new RolesManager($db);
                    $data = $value->getDetails();
                    $role = $rManager->getById($data['role_id']);
                    // var_dump($role);
                    if($role== false){
                        $data['role']['id']='0';
                        $data['role']['value']='0';
                        $data['role']['label']='Wrong';
                    }else{
                        $role = $role->getDetails();
                        $data['role']['id']=$role['id'];
                        $data['role']['value']=$role['id'];
                        $data['role']['label']=$role['name'];
                        $data['role']['is_owner']=$role['is_owner'];
                    }
                    $pattern_id = $data['pattern_id'];
                    $pattern = mysqli_query($db_conn, "SELECT id, name FROM `attendance_patterns` WHERE id='$pattern_id'");
                    if(mysqli_num_rows($pattern) > 0) {
                        $patterns = mysqli_fetch_all($pattern, MYSQLI_ASSOC);
                        $data['pattern']['id']=$patterns[0]['id'];
                        $data['pattern']['value']=$patterns[0]['id'];
                        $data['pattern']['label']=$patterns[0]['name'];    
                    }else{
                        $data['pattern']['id']='0';
                        $data['pattern']['value']='0';
                        $data['pattern']['label']='Wrong';    
                    }
                    $partner_id = $data['id_partner'];
                    $partner = mysqli_query($db_conn, "SELECT id, name, id_master FROM `partner` WHERE id='$partner_id' AND deleted_at IS NULL");
                    if(mysqli_num_rows($partner) > 0) {
                        $partners = mysqli_fetch_all($partner, MYSQLI_ASSOC);
                        $data['partner']['id']=$partners[0]['id'];
                        $data['partner']['value']=$partners[0]['id'];
                        $data['partner']['id_master']=$partners[0]['id_master'];
                        $data['partner']['label']=$partners[0]['name'];    
                    }else{
                        $data['partner']['id']='0';
                        $data['partner']['value']='0';
                        $data['partner']['id_master']='0';
                        $data['partner']['label']='Wrong';    
                    }
                    array_push($res,$data);
                }
                
                $success=1;
                $status=200;
                $msg="Success";
            }else{
                $msg="Data Not Found";
                $status=204;
            }
            
        }else{
            $msg = 'Missing Mandatory Field' ;
            $status=400;
        }
    
    }
        
    $signupJson = json_encode(["success"=>$success, "msg"=>$msg, "status"=>$status, "detail"=>$res]);  
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;

 ?>
 