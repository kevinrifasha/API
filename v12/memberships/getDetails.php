<?php    
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require_once("./../membershipModels/membershipManager.php");
require_once("./../userModels/userManager.php");
require_once("./../masterModels/masterManager.php");
require_once("./../transactionModels/transactionManager.php");


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
    $MembershipManager = new MembershipManager($db);
    $UserManager = new UserManager($db);
    $MasterManager = new MasterManager($db);
    $TransactionManager = new TransactionManager($db);
    $tokens = $tokenizer->validate($token);
    $res = array();

    $masterId = $_GET['masterId'];
    $phone = $_GET['phone'];
    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
        $status = $tokens['status'];
        $msg = $tokens['msg']; 
        $success = 0; 
    }else{
        
        if(isset($masterId) && !empty($masterId) && isset($phone) && !empty($phone) ){
            $user = $UserManager->getUser($phone);
            if($user!=false){
                $master = $MasterManager->getMasterById($masterId);
                if($master!=false){
                    $membership = $MembershipManager->getMembership($phone,$masterId);
                    if($membership!=false){
                        $res['name']=$user->getName();
                        $res['point']=$membership->getPoint();
                        $transaction = $TransactionManager->getByPhoneAndMasterId($phone, $masterId);
                        if($transaction!=false){
                            $res['transaction'] = $transaction;
                        }else{
                            $res['transaction'] = array();
                        }
                        $success = 1;
                        $msg = "Success";
                        $status=200;
                    }else{
                        $success = 0;
                        $msg = "Data Not Found";
                        $status=204;
                    }
                }else{
                    $success = 0;
                    $msg = "Master Not Registered";
                    $status=400;
                }
            }else{
                $success = 0;
                $msg = "User Not Registered";
                $status=400;
            }
            
        }else{
            
            $success=0;
            $msg="Missing require field's";
            $status = 400;
        }
    }
    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "membership"=>$res]);

    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;
 ?>
 