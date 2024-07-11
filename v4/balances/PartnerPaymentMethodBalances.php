<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once '../../includes/CalculateFunctions.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$cf = new CalculateFunction();
$values = [];
$tot = [];

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

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;
$data = array();
$totals = array();
$all = "0";

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    http_response_code($status);
    $success = 0;

}else{
    $id = $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $id = $_GET['partnerID']; 
    }
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    $mdrTax=0;
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($all !== "1") {
        $idMaster = null;
    }
    
    $vals = $cf->getGroupPaymentMethod($id, $dateFrom, $dateTo, $idMaster);
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
        $data[$i]['mdr_rupiah']=ceil((int)$data[$i]['value']*$data[$i]['mdr']/100);
        $data[$i]['tax_rupiah']=ceil((int)$data[$i]['mdr_rupiah']*$data[$i]['tax']/100);
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

http_response_code($status);
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data, "totals"=>$totals]);
// Echo the message.
echo $signupJson;
