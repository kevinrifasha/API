<?php    
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../partnerModels/partnerManager.php");
require_once("./../categoryModels/categoryManager.php");

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
    // $tokens = $tokenizer->validate($token);
    $tokens = $tokenizer->validate($token);
    $tokenDcrpt = json_decode($tokenizer->stringEncryption('decrypt',$token));

    $status=200;
    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
        $status = $tokens['status'];
        $msg = $tokens['msg']; 
        $success = 0; 
    }else{
        $Tmanager = new PartnerManager($db);
        // if(isset($partnerId) && !empty($partnerId)){
        $pId = $_GET['partner_id'];
            $partner = $Tmanager->getPartnerDetails($pId);
            $Cmanager = new CategoryManager($db);
            if($partner!=false){
                $categories = $Cmanager->getByMasterIdDD($partner->getId_master());
                $res = array();
                if($categories!=false ){
                    
                    foreach($categories as $category){
                        $data = $category->getDetails();
                        array_push($res,$data);
                    }
                    
                    if(count($categories)>0){
                        $success = 1;
                        $msg = "Success";
                        $status = 200;
                    }else{
                        $success = 0;
                        $msg = "Data Not Found";
                        $status = 204;
                    }
                    
                }else{

                    $success = 0;
                    $msg = "Data Not Registered";
                    $status = 204;    
                }
            }else{

                $success = 0;
                $msg = "Data Not Registered";
                $status = 400;    
            }

            
        // }else{

        //     $success = 0;
        //     $msg = "Missing Mandatory Field";
        //     $status = 400;    

        // }

    }
    $signupJson = json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "categories"=>$res]);

    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;
 ?>
 
