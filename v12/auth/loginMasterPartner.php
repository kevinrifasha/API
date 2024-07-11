<?php    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../partnerModels/partnerManager.php"); 
require_once("./../masterModels/masterManager.php"); 


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
$today = date("Y-m-d H:i:s");
$db = connectBase();
$tokenizer = new TokenManager($db);
$json = file_get_contents('php://input');
$obj = json_decode($json,true);

$success=0;
$email = $obj['email'];
$password = $obj['password'];

    if (!empty($email) || !empty($password)) { 
        //validate email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {             
            $manager = new MasterManager($db);
            $password = md5($password);
            $master = $manager->login($email,$password);
                
            if($master==false){
                $manager = new PartnerManager($db);
                $partner = $manager->login($email,$password);
                        
                if($partner!=false){
                    $detail = $partner->getDetails();
                    $detail['role']="partner";
                    $msg = 'Logged'; 
                    $success=1;
                    $tkn = json_encode(['id'=>$detail['id'], 'role'=>'partner', 'created_at'=>$today, 'expired'=>3600000000]);
                }else{
                    $msg = 'credentials doesnt match';
                }
                
            }else{
                $detail = $master->getDetails($email,$password);
                $detail['role']="master";
                $tkn = json_encode(['id'=>$detail['id'], 'role'=>'master', 'created_at'=>$today, 'expired'=>3600000000]);
                if($master!=false){
                    $success=1;
                }
                $msg = 'Logged'; 
            }
            
            $encryptT = $tokenizer->stringEncryption('encrypt', $tkn);
            
        }else
        $msg = "Invalid email format"; 
    }else
        $msg = 'field(s) must not be empty' ;

    
        
$signupJson = json_encode(["msg"=>$msg, "detail"=>$detail, "success"=>$success, 'token'=>$encryptT]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;

 ?>