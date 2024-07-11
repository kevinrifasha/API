<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

//init var
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
$tokenizer = new Token();
$token = '';
$res = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $now = date('Y-m-d');
    $validate = mysqli_query($db_conn, "SELECT p.id, p.subscription_status, p.trial_until, p.subscription_until, sp.name AS subsName FROM partner p JOIN subscription_packages sp ON p.primary_subscription_id=sp.id WHERE p.id='$token->id_partner'");
    if(mysqli_num_rows($validate)>0){
        $success =1;
        $status=200;
        $msg="Data ditemukan";
        while($row = mysqli_fetch_assoc($validate)){
            $subsStatus = $row['subscription_status'];
            $trialUntil = $row['trial_until'];
            $subscriptionUntil = $row['subscription_until'];
            $subsName = $row['subsName'];
            $shouldUpgrade=0;
            if($subsStatus=="Trial"){
                if(date('Y-m-d', strtotime($trialUntil))<$now){
                    $shouldUpgrade=1;
                    $msg="Masa trial anda sudah habis. Mohon pilih paket berlangganan";
                }else{
                    $shouldUpgrade=0;
                }
            }else if($subsStatus=="Subscribed"){
                if(date('Y-m-d',strtotime($subscriptionUntil))<$now || $subscriptionUntil==null){
                    $shouldUpgrade=1;
                    $msg="Masa berlangganan anda sudah habis. Mohon perpanjang paket berlangganan";
                }
            }else if($subsStatus=="Expired"){
                $shouldUpgrade=1;
                $msg="Masa berlangganan anda sudah expired. Mohon pilih paket berlangganan";
            }
        }
        }else{
        $success =0;
        $status =204;
        $msg = "Data tidak ditemukan";
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "trialUntil"=>$trialUntil,"subscriptionUntil"=>$subscriptionUntil,"shouldUpgrade"=>$shouldUpgrade, "subscriptionStatus"=>$subsStatus,"subsName"=>$subsName]);