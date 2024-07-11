<?php    
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
require_once("../connection.php");
require_once("./../partnerModels/partnerManager.php"); 
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

$db = connectBase();
$tokenizer = new TokenManager($db);

    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);

    $email = $obj['email'];
    $password = $obj['password'];

    
    //validate fields (must not be empty)
    if (!empty($email) || !empty($password)) { 
                //validate email format
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) { 
                    
                    $manager = new PartnerManager($db);
                    $password = md5($password);
                    $partner = $manager->login($email,$password);
                    if($partner!==false){
                        $partner = $partner->getDetails();
                        $tkn = json_encode(['id'=>$partner['id'], 'role'=>'partner', 'created_at'=>$today, 'expired'=>3600]);
                        $encryptT = $tokenizer->stringEncryption('encrypt', $tkn);
                        $msg = 'Logged'; 
                    }else{
                        $msg = 'Data Not Found'; 
                    }
                    
                }else
                    $msg = "Invalid email format"; 
        }else
            $msg = 'field(s) must not be empty' ;
    
        
    $signupJson = json_encode(["msg"=>$msg, "partner"=>$partner, 'token'=>$encryptT]);  
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;

 ?>
 