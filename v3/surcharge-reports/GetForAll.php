<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';
require_once '../../includes/CalculateFunctions.php';

$cf = new CalculateFunction();
$vals = [];

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
$idMaster = $token->masterID;
$values = array();

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $id = $_GET['id'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    
    $data = [];
    
    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    if($newDateFormat == 1){
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $id = $partner['partner_id'];
                
                $vals = $cf->getBySurchargeWithHour($id, $dateFrom, $dateTo);
                
                $partner['surcharges'] = $vals;
                
                if(count($vals) > 0) {
                    $totalValue = 0;
                    $totalSurcharge = 0;
                    
                    foreach($vals as $val) {
                        $totalValue += $val['value'];
                        $totalSurcharge += $val['surcharge'];
                    }
                    
                    $partner['totalValue'] = $totalValue;
                    $partner['totalSurcharge'] = $totalSurcharge;
                    $partner['total'] = $totalValue - $totalSurcharge;
                    
                    array_push($data, $partner);
                }
            }
            
            $success=1;
            $status=200;
            $msg="Success";
        } else {
            $success=0;
            $status=203;
            $msg="Data not found";
        }
    } 
    else 
    {
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $id = $partner['partner_id'];
                
                $vals = $cf->getBySurcharge($id, $dateFrom, $dateTo);
                
                $partner['surcharges'] = $vals;
                
                if(count($vals) > 0) {
                    $totalValue = 0;
                    $totalSurcharge = 0;
                    
                    foreach($vals as $val) {
                        $totalValue += $val['value'];
                        $totalSurcharge += $val['surcharge'];
                    }
                    
                    $partner['totalValue'] = $totalValue;
                    $partner['totalSurcharge'] = $totalSurcharge;
                    $partner['total'] = $totalValue - $totalSurcharge;
                    
                    array_push($data, $partner);
                }
            }
            
            $success=1;
            $status=200;
            $msg="Success";
        } else {
            $success=0;
            $status=203;
            $msg="Data not found";
        }
    }

 
}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data]);  

echo $signupJson;
