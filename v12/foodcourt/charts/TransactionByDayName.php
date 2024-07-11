<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../tokenModels/tokenManager.php"); 
require_once '../../../includes/CalculateFunctions.php';
require_once("../../connection.php");
require '../../../db_connection.php';

$array = array();
$array1 = array();
$id = $_GET['id'];
$dateTo = $_GET['dateTo'];
$dateFrom = $_GET['dateFrom'];

$newDateFormat = 0;

if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
    $dateTo = str_replace("%20"," ",$dateTo);
    $dateFrom = str_replace("%20"," ",$dateFrom);
    $newDateFormat = 1;
}

$values = [];
$tot = [];

$cf = new CalculateFunction();
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
$values = array();
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{

  if($newDateFormat == 1){
    $value = $cf->getByDayWithHour($id, $dateFrom, $dateTo);
    for ($i=0; $i < 7; $i++) { 
        $array[$i]['value']=0;
        if($i==0){
            $array[$i]['label']="Senin";
        }else if($i==1){
            $array[$i]['label']="Selasa";
        }else if($i==2){
            $array[$i]['label']="Rabu";
        }else if($i==3){
            $array[$i]['label']="Kamis";
        }else if($i==4){
            $array[$i]['label']="Jumat";
        }else if($i==5){
            $array[$i]['label']="Sabtu";
        }else if($i==6){
            $array[$i]['label']="Minggu";
        }
    }
    foreach ($value as $val) {
        if($val['day']=="Monday"){
            $array[0]['value']=$val['sales'];
        }else if($val['day']=="Tuesday"){
            $array[1]['value']=$val['sales'];
        }else if($val['day']=="Wednesday"){
            $array[2]['value']=$val['sales'];
        }else if($val['day']=="Thursday"){
            $array[3]['value']=$val['sales'];
        }else if($val['day']=="Friday"){
            $array[4]['value']=$val['sales'];
        }else if($val['day']=="Saturday"){
            $array[5]['value']=$val['sales'];
        }else if($val['day']=="Sunday"){
            $array[6]['value']=$val['sales'];
        }
    }
    $success=1;
    $status=200;
    $msg="Success";
  } 
  else 
  {
    $value = $cf->getByDay($id, $dateFrom, $dateTo);
    for ($i=0; $i < 7; $i++) { 
        $array[$i]['value']=0;
        if($i==0){
            $array[$i]['label']="Senin";
        }else if($i==1){
            $array[$i]['label']="Selasa";
        }else if($i==2){
            $array[$i]['label']="Rabu";
        }else if($i==3){
            $array[$i]['label']="Kamis";
        }else if($i==4){
            $array[$i]['label']="Jumat";
        }else if($i==5){
            $array[$i]['label']="Sabtu";
        }else if($i==6){
            $array[$i]['label']="Minggu";
        }
    }
    foreach ($value as $val) {
        if($val['day']=="Monday"){
            $array[0]['value']=$val['sales'];
        }else if($val['day']=="Tuesday"){
            $array[1]['value']=$val['sales'];
        }else if($val['day']=="Wednesday"){
            $array[2]['value']=$val['sales'];
        }else if($val['day']=="Thursday"){
            $array[3]['value']=$val['sales'];
        }else if($val['day']=="Friday"){
            $array[4]['value']=$val['sales'];
        }else if($val['day']=="Saturday"){
            $array[5]['value']=$val['sales'];
        }else if($val['day']=="Sunday"){
            $array[6]['value']=$val['sales'];
        }
    }
    $success=1;
    $status=200;
    $msg="Success";
  }
    
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$array]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
?>