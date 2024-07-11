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
require_once("./../partnerModels/partnerManager.php");


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
    $PartnerManager = new PartnerManager($db);
    $tokens = $tokenizer->validate($token);
    $res = array();

    $partnerId = $_GET['partnerId'];

    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
        $status = $tokens['status'];
        $msg = $tokens['msg']; 
        $success = 0; 
    }else{
        
        if(isset($partnerId) && !empty($partnerId)){
            $partner = $PartnerManager->getPartnerDetails($partnerId);
            if($partner!=false){
                $memberships = $MembershipManager->getByMasterId($partner->getId_master());
                if($memberships!=false){
                    foreach($memberships as $membership){
                        $data['id'] = $membership->getId();
                        $data['point'] = $membership->getPoint();
                        $data['userPhone'] = $membership->getUser_phone();
                        $user = $UserManager->getUser($membership->getUser_phone());
                        if($user!=false){
                            $data['userName'] = $user->getName();
                            
                        }else{
                            $data['userName'] = "Belum Daftar";

                        }
                        array_push($res, $data);
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
                $msg = "Data Not Found";
                $status=204;
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
 