<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../partnerModels/partnerManager.php");
require_once("./../recipeModels/recipeManager.php");
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
$tokenDcrpt = json_decode($tokenizer->stringEncryption('decrypt',$token));
    $res = array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
}else{

    $manager = new PartnerManager($db);
    $tokenDcrpt->partnerID = $_GET['partnerId'];
    $master = $manager->getPartnerDetails($tokenDcrpt->partnerID);
    if($master!= false){
        $partnerID = $_GET['partnerId'];
        $recipeManage = new RecipeManager($db);
        $recipes = $recipeManage->getByPartnerIDALL($tokenDcrpt->partnerID);
        if($recipes!=false){

            $res = $recipes;
            $success = 1;
            $msg = "Success";
            $status = 200;

        }else{

            $success = 0;
            $msg = "Data Not Found";
            $status = 400;
        }
    }else{

        $success = 0;
        $msg = "Data Not Registerd";
        $status = 400;

    }

}


echo json_encode(["msg"=>$msg, "status"=>$status, "success"=>$success, "recipes"=>$res]);


 ?>
