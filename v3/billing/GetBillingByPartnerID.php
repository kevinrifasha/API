<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';

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
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$partnerID = $token->partnerID;
$value = array();
$success=0;
$msg = 'Failed';
$res = [];
// $cycle = $_POST['cycle'];
$json = file_get_contents('php://input');
$obj = json_decode($json,true);
$main = $obj['main'];
$addons = $obj['addons'];
$cycle = $obj['cycle'];
$total = 0;

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $mainName = $main['name'];
    $qMain = "SELECT sp.name, sp.id, sp.price, sp.type FROM partner p JOIN subscription_packages sp ON sp.name = '$mainName' WHERE p.id = '$partnerID' AND type = '$cycle' AND sp.deleted_at IS NULL";
    $sqlMain = mysqli_query($db_conn, $qMain);
    
    if(mysqli_num_rows($sqlMain)>0){
        $fetchMainPackage = mysqli_fetch_all($sqlMain, MYSQLI_ASSOC);
        $mainPackage = $fetchMainPackage[0];
        $mainPackage['price'] = (double)$mainPackage['price'];
        $total += $mainPackage['price'];
        $res['main'] = $mainPackage;
        $res['addons'] = [];
        
        // get addon
        $addonsName = "";
        $dataAddons = [];
        if(count($addons) > 0) {
            foreach($addons as $val){
                if($addonsName == "") {
                    $addonsName .= $val['name'];
                } else {
                    $addonsName .= "," . $val['name'];
                }
            }       
            $qAddons = "SELECT sp.name, sp.id, sp.price, sp.type FROM partner p JOIN subscription_packages sp ON sp.name IN ('$addonsName') WHERE p.id = '$partnerID' AND type = '$cycle' AND sp.deleted_at IS NULL";
            $sqlAddons = mysqli_query($db_conn, $qAddons);
            $fetchAddons = mysqli_fetch_all($sqlAddons, MYSQLI_ASSOC);
           
            foreach($fetchAddons as $val) {
                $val['price'] = (double)$val['price'];
                $total += $val['price'];
                array_push($dataAddons, $val);
            }
        }
        $res['addons'] = $dataAddons;
        $res['total'] = $total;
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
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "billing"=>$res]);
?>