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
require  __DIR__ . '/../../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
$dotenv->load();

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
$value = array();
$success=0;
$msg = 'Failed';

$id = $_GET['id'];
$dateFrom=$_GET['dateFrom'];
$dateTo=$_GET['dateTo'];

$newDateFormat = 0;

if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
    $dateTo = str_replace("%20"," ",$dateTo);
    $dateFrom = str_replace("%20"," ",$dateFrom);
    $newDateFormat = 1;
}

$sorted1 = array();

$data = array();
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $namaMenu = "";
    if($newDateFormat == 1){
      $query = "SELECT SUM(qty) as value,SUM(qty) as qty, nama as name, id FROM ( SELECT SUM(detail_transaksi.qty) AS qty, menu.nama, menu.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu  WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY menu.id, detail_transaksi.id_transaksi ";

      $dateFromStr = str_replace("-","", $dateFrom);
      $dateToStr = str_replace("-","", $dateTo);
      $queryTrans = "SELECT table_name FROM information_schema.tables
      WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
      $transaksi = mysqli_query($db_conn, $queryTrans);
      while($row=mysqli_fetch_assoc($transaksi)){
          $table_name = explode("_",$row['table_name']);
          $transactions = "transactions_".$table_name[1]."_".$table_name[2];
          $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
          if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
              $query .= " UNION ALL ";
              $query .= " SELECT SUM(`$detail_transactions`.qty) AS qty, menu.nama, menu.id FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu  WHERE `menu`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY menu.id, `$detail_transactions`.id_transaksi ";
          }
      }
      $query .= ") AS tmp GROUP BY id ORDER BY qty DESC LIMIT 5 ";
      $sqlGetSales = mysqli_query($db_conn, $query);
      if(mysqli_num_rows($sqlGetSales) > 0) {
          $data = mysqli_fetch_all($sqlGetSales, MYSQLI_ASSOC);
      }
      $status=200;
      $success=1;
      $msg="Success";
    } 
    else 
    {
      $query = "SELECT SUM(qty) as value,SUM(qty) as qty, nama as name, id FROM ( SELECT SUM(detail_transaksi.qty) AS qty, menu.nama, menu.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu  WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY menu.id, detail_transaksi.id_transaksi ";
  
      $dateFromStr = str_replace("-","", $dateFrom);
      $dateToStr = str_replace("-","", $dateTo);
      $queryTrans = "SELECT table_name FROM information_schema.tables
      WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
      $transaksi = mysqli_query($db_conn, $queryTrans);
      while($row=mysqli_fetch_assoc($transaksi)){
          $table_name = explode("_",$row['table_name']);
          $transactions = "transactions_".$table_name[1]."_".$table_name[2];
          $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
          if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
              $query .= " UNION ALL ";
              $query .= " SELECT SUM(`$detail_transactions`.qty) AS qty, menu.nama, menu.id FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu  WHERE `menu`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1 AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY menu.id, `$detail_transactions`.id_transaksi ";
          }
      }
      $query .= ") AS tmp GROUP BY id ORDER BY qty DESC LIMIT 5 ";
      $sqlGetSales = mysqli_query($db_conn, $query);
      if(mysqli_num_rows($sqlGetSales) > 0) {
          $data = mysqli_fetch_all($sqlGetSales, MYSQLI_ASSOC);
      }
      $status=200;
      $success=1;
      $msg="Success";
    }
    
}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
?>