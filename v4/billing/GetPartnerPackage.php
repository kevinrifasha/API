<?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: access");
// header("Access-Control-Allow-Methods: GET");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// require_once("./../tokenModels/tokenManager.php");
// require_once("../connection.php");
// require '../../db_connection.php';

// $headers = array();
//     $rx_http = '/\AHTTP_/';
//     foreach($_SERVER as $key => $val) {
//       if( preg_match($rx_http, $key) ) {
//         $arh_key = preg_replace($rx_http, '', $key);
//         $rx_matches = array();
//         // do some nasty string manipulations to restore the original letter case
//         // this should work in most cases
//         $rx_matches = explode('_', $arh_key);
//         if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
//           foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
//           $arh_key = implode('-', $rx_matches);
//         }
//         $headers[$arh_key] = $val;
//       }
//     }
// $token = '';

// foreach ($headers as $header => $value) {
//     if($header=="Authorization" || $header=="AUTHORIZATION"){
//         $token=substr($value,7);
//     }
// }

// $db = connectBase();
// $tokenizer = new TokenManager($db);
// $tokens = $tokenizer->validate($token);
// $token = json_decode($tokenizer->stringEncryption('decrypt',$token));
// $partnerID = $token->partnerID;
// $value = array();
// $success=0;
// $msg = 'Failed';
// $res = [];

// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

//     $status = $tokens['status'];
//     $msg = $tokens['msg'];
//     $success = 0;

// }else{
//     $qMain = "SELECT sp.name, sp.id, sp.price, sp.type, p.subscription_paid_date AS paid_date, p.subscription_until AS expired_date FROM partner p JOIN subscription_packages sp ON sp.id = p.primary_subscription_id WHERE p.id = '$partnerID' AND sp.deleted_at IS NULL";
//     $sqlMain = mysqli_query($db_conn, $qMain);
    
//     if(mysqli_num_rows($sqlMain)>0){
//         $fetchMainPackage = mysqli_fetch_all($sqlMain, MYSQLI_ASSOC);
//         $mainPackage = $fetchMainPackage[0];
//         $mainPackage['price'] = (double)$mainPackage['price'];
//         $res['main'] = $mainPackage;
//         $res['addons'] = [];
        
//         // get addon
//         $qAddons = "SELECT psh.partner_id, psh.package_id, psh.expired_date, sp.name, sp.id, sp.price, p.subscription_paid_date AS paid_date FROM partner_subscription_history psh JOIN subscription_packages sp ON sp.id=psh.package_id JOIN partner p ON p.id=psh.partner_id WHERE psh.partner_id='$partnerID' AND sp.is_addon = 1 AND psh.deleted_at IS NULL";
//         $sqlAddons = mysqli_query($db_conn, $qAddons);
//         $fetchAddons = mysqli_fetch_all($sqlAddons, MYSQLI_ASSOC);
//         $dataAddons = [];
//         foreach($fetchAddons as $val) {
//             $val['price'] = (double)$val['price'];
//             array_push($dataAddons, $val);
//         }
//         $res['addons'] = $dataAddons;
//         // get addon end
        
//         $success=1;
//         $status=200;
//         $msg="Success";
//     }else{
//         $success =0;
//         $status =204;
//         $msg = "Data Not Found";
//     }
// }
// echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "package"=>$res]);

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

$partnerID = $token->id_partner;
$value = array();
$success=0;
$msg = 'Failed';
$res = [];

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $qMain = "SELECT sp.name, sp.id, sp.price, sp.type, p.subscription_paid_date AS paid_date, p.subscription_until AS expired_date FROM partner p JOIN subscription_packages sp ON sp.id = p.primary_subscription_id WHERE p.id = '$partnerID' AND sp.deleted_at IS NULL";
    $sqlMain = mysqli_query($db_conn, $qMain);
    
    if(mysqli_num_rows($sqlMain)>0){
        $fetchMainPackage = mysqli_fetch_all($sqlMain, MYSQLI_ASSOC);
        $mainPackage = $fetchMainPackage[0];
        $mainPackage['price'] = (double)$mainPackage['price'];
        $res['main'] = $mainPackage;
        $res['addons'] = [];
        
        // get addon
        $qAddons = "SELECT psh.partner_id, psh.package_id, psh.expired_date, sp.name, sp.id, sp.price, p.subscription_paid_date AS paid_date FROM partner_subscription_history psh JOIN subscription_packages sp ON sp.id=psh.package_id JOIN partner p ON p.id=psh.partner_id WHERE psh.partner_id='$partnerID' AND sp.is_addon = 1 AND psh.deleted_at IS NULL";
        $sqlAddons = mysqli_query($db_conn, $qAddons);
        $fetchAddons = mysqli_fetch_all($sqlAddons, MYSQLI_ASSOC);
        $dataAddons = [];
        foreach($fetchAddons as $val) {
            $val['price'] = (double)$val['price'];
            array_push($dataAddons, $val);
        }
        $res['addons'] = $dataAddons;
        // get addon end
        
        $success=1;
        $status=200;
        $msg="Success";
    }else{
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "package"=>$res]);
