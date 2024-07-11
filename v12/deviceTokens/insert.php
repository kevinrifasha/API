<?php    
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../deviceTokenModels/deviceTokenManager.php");


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
$today = date("Y-m-d H:i:s");


$data = json_decode(json_encode($_POST));
$res=array();

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0; 
}else{

    if(isset($data->partnerId) && !empty($data->partnerId) && isset($data->token) && !empty($data->token)){
        
        if(isset($data->old_token) && !empty($data->old_token)){

            $DTmanager = new DeviceTokenManager($db);
            $res = $DTmanager->getByToken($data->old_token);
            
            $msg = "Nothing's Value updated"; 
            $success = 0;
            
            if($res!=false){
                $deviceToken = new DeviceToken($res);
                $deviceToken->setTokens($data->token);
                $deviceToken->setUpdated_at($today);
                $add = $DTmanager->updatePartnerTokens($deviceToken);
                if($add==true){
                    $msg = "Success"; 
                    $success = 1; 
                }
            }
            
        }else{

            $DTmanager = new DeviceTokenManager($db);
            $res = $DTmanager->getByToken($data->token);

            $msg = "Nothing's Value updated"; 
            $success = 0;
            
            if($res==false){

                $deviceToken = new DeviceToken(array( "id_partner" => $data->partnerId, "tokens" =>$data->token, "created_at"=> $today));
                $add = $DTmanager->insertPartnerTokens($deviceToken);
                if($add==true){

                    $msg = "Success"; 
                    $success = 1; 
                    
                }

            }

        }


    }else{
        $msg = "Missing Value"; 
        $success = 0; 
    }

}


        
$signupJson = json_encode(["success"=>$success, "msg"=>$msg]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;

 ?>
 