<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
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
$all = "0";
    
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $tokenDecoded->masterID;
$value = array();
$success=0;
$msg = 'Failed';
$all = "0";

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $partnerID = $_GET['partnerID'];
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    
    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }
    
    $totalIncome = 0;
    $totalExpense = 0;
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }

    if($newDateFormat == 1){
      if($all == "1") {
        $addQuery1 = "sc.master_id='$idMaster'";
      } else {
          $addQuery1 = "sc.partner_id='$partnerID'";
      }
      
      $q = mysqli_query($db_conn, "SELECT sc.id, sc.raw_material_id, sc.menu_id, sc.metric_id, sc.qty, sc.qty_before, sc.notes, sc.created_by, e.nama as employeeName, m.name as metricName, sc.raw_material_stock_id, CASE WHEN sc.raw_material_id=0 THEN (SELECT nama FROM menu WHERE menu.id=sc.menu_id) ELSE (SELECT name FROM raw_material rm WHERE rm.id=sc.raw_material_id) END AS itemName, CASE WHEN sc.raw_material_id=0 THEN (select hpp FROM menu WHERE menu.id=sc.menu_id) ELSE (select unit_price FROM raw_material rm WHERE rm.id=sc.raw_material_id)END AS unitPrice, sc.created_at, sc.money_value FROM stock_changes sc JOIN employees e ON sc.created_by=e.id JOIN metric m ON sc.metric_id=m.id WHERE ". $addQuery1 ." AND sc.deleted_at IS NULL AND sc.created_at BETWEEN '$dateFrom' AND '$dateTo' ORDER BY sc.id DESC");
      
      if (mysqli_num_rows($q) > 0) {
          $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
          $i = 0;
          foreach($res as $x){
              if((double)$x['money_value']>0){
                  $totalIncome +=(double)$x['money_value'];
              }else{
                  $totalExpense +=(double)$x['money_value'];
              }
              $i++;
          }
          $totalAdjustment = $i;
          $success =1;
          $status =200;
          $msg = "Success";
      } else {
          $success =0;
          $status =204;
          $msg = "Data Not Found";
      }
    } 
    else 
    {
      if($all == "1") {
        $addQuery1 = "sc.master_id='$idMaster'";
      } else {
          $addQuery1 = "sc.partner_id='$partnerID'";
      }
      
      $q = mysqli_query($db_conn, "SELECT sc.id, sc.raw_material_id, sc.menu_id, sc.metric_id, sc.qty, sc.qty_before, sc.notes, sc.created_by, e.nama as employeeName, m.name as metricName, sc.raw_material_stock_id, CASE WHEN sc.raw_material_id=0 THEN (SELECT nama FROM menu WHERE menu.id=sc.menu_id) ELSE (SELECT name FROM raw_material rm WHERE rm.id=sc.raw_material_id) END AS itemName, CASE WHEN sc.raw_material_id=0 THEN (select hpp FROM menu WHERE menu.id=sc.menu_id) ELSE (select unit_price FROM raw_material rm WHERE rm.id=sc.raw_material_id)END AS unitPrice, sc.created_at, sc.money_value FROM stock_changes sc JOIN employees e ON sc.created_by=e.id JOIN metric m ON sc.metric_id=m.id WHERE ". $addQuery1 ." AND sc.deleted_at IS NULL AND DATE(sc.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY sc.id DESC");
      
      if (mysqli_num_rows($q) > 0) {
          $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
          $i = 0;
          foreach($res as $x){
              if((double)$x['money_value']>0){
                  $totalIncome +=(double)$x['money_value'];
              }else{
                  $totalExpense +=(double)$x['money_value'];
              }
              $i++;
          }
          $totalAdjustment = $i;
          $success =1;
          $status =200;
          $msg = "Success";
      } else {
          $success =0;
          $status =204;
          $msg = "Data Not Found";
      }
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "stockChanges"=>$res, "adjustments"=>$totalAdjustment, "income"=>$totalIncome, "expense"=>$totalExpense]);
?>