<?php    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../membershipModels/membershipManager.php"); 

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
$MembershipMannager = new MembershipManager($db);
$tokens = $tokenizer->validate($token);
$success=0;
$msg = 'Failed'; 

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg']; 

}else{
    
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    if(
        isset($obj['phone'])
        && !empty($obj['phone'])
        && isset($obj['masterId'])
        && !empty($obj['masterId'])
    ){
        $registered = $MembershipMannager->getMembership($obj['phone'],$obj['masterId']);

        if($registered==false){

            $membership = new Membership(array("id"=>0, "user_phone"=>$obj['phone'], "master_id"=>$obj['masterId'],"point"=>$obj['point']));
            $add = $MembershipMannager->add($membership);

            if($add!=false){
                $status = 200;
                $success=1;
                $msg="Success";
            }else{
                $status = 204;
                $success=0;
                $msg="Failed";
            }

        }else{
            $status = 400;
            $success = 0;
            $msg = "User Phone Already Registered On This Master";
        }

    }else{
        $status = 400;
        $success = 0;
        $msg = "Missing Required Fields";
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
 