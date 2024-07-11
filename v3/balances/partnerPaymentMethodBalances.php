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
$data=array();
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->masterID;
$values = array();
$totals = [];
$mdrTax=0;
$all = "0";

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $id = $_GET['id'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    
    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    if($all !== "1") {
        $idMaster = null;
    }
    $mdrTax=0;
    
    
    if($newDateFormat == 1){
        $vals = $cf->getGroupPaymentMethodWithHour($id, $dateFrom, $dateTo, $idMaster);
    }else{
        $vals = $cf->getGroupPaymentMethod($id, $dateFrom, $dateTo, $idMaster);
    }
    
    $getMDRTax = mysqli_query($db_conn, "SELECT value FROM settings WHERE id=24");
    while($row=mysqli_fetch_assoc($getMDRTax)){
      $mdrTax = (int)$row['value'];
    }
    $i=0;
    $totalIncome = 0;
    $totalMDR = 0;
    $totalTax = 0;
    $totalValue = 0;
    foreach($vals as $x){
        $data[$i]=$x;
        $intType = (int)$x['tipe'];
        if($intType==1||$intType==3||$intType==4||$intType==10){
            $data[$i]['mdr']=1.5;
            $data[$i]['tax']=$mdrTax;
        }else if($intType==2){
            $data[$i]['mdr']=2;
            $data[$i]['tax']=$mdrTax;
        }else{
            $data[$i]['mdr']=0;
            $data[$i]['tax']=0;
        }
        $data[$i]['mdr_rupiah']=floor((int)$data[$i]['value']*$data[$i]['mdr']/100);
        $data[$i]['tax_rupiah']=floor((int)$data[$i]['mdr_rupiah']*$data[$i]['tax']/100);
        $data[$i]['income']= $data[$i]['value']-$data[$i]['mdr_rupiah']-$data[$i]['tax_rupiah'];
        $totalIncome += (int)$data[$i]['income'];
        $totalMDR += (int)$data[$i]['mdr_rupiah'];
        $totalTax += (int)$data[$i]['tax_rupiah'];
        $totalValue += (int)$data[$i]['value'];
        $i++;
    }
    $totals['total_income']=$totalIncome;
    $totals['total_mdr']=$totalMDR;
    $totals['total_tax']=$totalTax;
    $totals['total_value']=$totalValue;
    $success=1;
    $status=200;
    $msg="Success";
}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data, "totals"=>$totals, "mdrTax"=>$mdrTax]);

echo $signupJson;
