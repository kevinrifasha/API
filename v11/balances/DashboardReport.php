<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once '../../includes/CalculateFunctions.php';

$cf = new CalculateFunction();

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
$subtotal = 0;
$diskon_spesial = 0;
$employee_discount = 0;
$diskon_voucher = 0;
$service = 0;
$tax = 0;
$total = 0;
$todayNumTrx = 0;
$todaySales = 0;
$yesterdayNumTrx = 0;
$yesterdaySales = 0;
$program_discount = 0;
$charge_ur = 0;
$delivery_fee_resto = 0;
$all = "0";

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $id= $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $id = $_GET['partnerID'];
    }
    $dateFrom = date("Y-m-d");
    $dateTo = date("Y-m-d");
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($all == "1") {
        $resToday = $cf->getSubTotalMaster($idMaster, $dateFrom, $dateTo);
    } else {
        $resToday = $cf->getSubTotal($id, $dateFrom, $dateTo);
    }

    if ($resToday) {
        // $today = date("Y-m-d");
        // $resToday = $cf->getSubTotal($id, $today, $today);

        $yesterday = date('Y-m-d',strtotime("-1 days"));
         if($all == "1") {
            $resYesterday = $cf->getSubTotalMaster($idMaster, $yesterday, $yesterday);
        } else {
            $resYesterday = $cf->getSubTotal($id, $yesterday, $yesterday);
        }
        $todayNumTrx = 0;
        if((int) $resToday['count'] > 0){
            $todayNumTrx = (int) $resToday['count'] -1;
        }
        $todaySales = $resToday['total']+$resToday['rounding'];
        $yesterdayNumTrx = 0;
        if((int) $resYesterday['count'] > 0){
            $yesterdayNumTrx = (int) $resYesterday['count']-1;
        }
        $yesterdaySales = $resYesterday['total']+$resYesterday['rounding'];
        $success =1;
        $status =200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "todaySales"=>$todaySales, "todayNumberTrx"=>$todayNumTrx, "yesterdaySales"=>$yesterdaySales, "yesterdayNumberTrx"=>$yesterdayNumTrx]);
?>