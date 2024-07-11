<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once '../../includes/CalculateFunctions.php';
require_once("../connection.php");
require '../../db_connection.php';

$id = $_GET['id'];
$dateTo = $_GET['dateTo'];
$dateFrom = $_GET['dateFrom'];

$newDateFormat = 0;

if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
    $dateTo = str_replace("%20"," ",$dateTo);
    $dateFrom = str_replace("%20"," ",$dateFrom);
    $newDateFormat = 1;
} else {
    $dateTo = $dateTo . " 00:00:00";
    $dateFrom = $dateFrom . " 23:59:59";
    $newDateFormat = 1;
}

$values = [];
$tot = [];

$cf = new CalculateFunction();
$headers = array();
$rx_http = '/\AHTTP_/';
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
            $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
    }
}
$token = '';

foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->masterID;
$values = array();
$all = "0";

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
    
}else{
    $values1 = [];
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }

    if($newDateFormat == 1){
      if($all != "1") {
          $idMaster = null;
          $values1 = $cf->getByHourWithHour($id, $dateFrom, $dateTo);
      } else {
          $values1 = $cf->getByHourMasterWithHour($idMaster, $dateFrom, $dateTo);
      }
    } 
    else 
    {
      if($all != "1") {
        $idMaster = null;
        $values1 = $cf->getByHour($id, $dateFrom, $dateTo);
      } else {
          $values1 = $cf->getByHourMaster($idMaster, $dateFrom, $dateT);
      }
    }

    $i = 0;
    foreach ($values1 as $value) {
        $values[$i]['label'] = $value['hour'];
        $values[$i]['value'] = $value['sales'];
        $i += 1;
    }
    $success = 1;
    $status = 200;
    $msg = "Success";

}

$signupJson = json_encode(["success" => $success, "status" => $status, "msg" => $msg, "data" => $values]);

echo $signupJson;
?>