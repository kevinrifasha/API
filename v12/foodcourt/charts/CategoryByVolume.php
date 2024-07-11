<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../tokenModels/tokenManager.php"); 
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
$sorted1 = array();
$sorted2 = array();
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

$values = array();
$values2 = array();
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);
    
    if($newDateFormat == 1){
      $query ="SELECT SUM(qty) AS value, nama AS name, id FROM ( SELECT SUM(detail_transaksi.qty) AS qty, categories.name AS nama, categories.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category WHERE menu.id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
      $queryTrans = "SELECT table_name FROM information_schema.tables
      WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
      $transaksi = mysqli_query($db_conn, $queryTrans);
      while($row=mysqli_fetch_assoc($transaksi)){
          $table_name = explode("_",$row['table_name']);
          $transactions = "transactions_".$table_name[1]."_".$table_name[2];
          $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
          if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
              $query .= " UNION ALL ";
              $query .= " SELECT SUM(`$detail_transactions`.qty) AS qty, categories.name AS nama, categories.id FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN categories ON categories.id=menu.id_category WHERE menu.id_partner='$id' AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.deleted_at IS NULL AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
          }
      }
      $query .=  ") AS tmp GROUP BY id ORDER BY qty DESC";
      $trxQ = mysqli_query($db_conn, $query);
          
      if(mysqli_num_rows($trxQ) > 0) {
          $sorted1 = mysqli_fetch_all($trxQ, MYSQLI_ASSOC);
      }
      

      
      $query ="SELECT SUM(qty) AS value, nama AS name, id FROM ( SELECT SUM(detail_transaksi.harga) AS qty, categories.name AS nama, categories.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category WHERE menu.id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
      $queryTrans = "SELECT table_name FROM information_schema.tables
      WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
      $transaksi = mysqli_query($db_conn, $queryTrans);
      while($row=mysqli_fetch_assoc($transaksi)){
          $table_name = explode("_",$row['table_name']);
          $transactions = "transactions_".$table_name[1]."_".$table_name[2];
          $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
          if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
              $query .= " UNION ALL ";
              $query .= " SELECT SUM(`$detail_transactions`.harga) AS qty, categories.name AS nama, categories.id FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN categories ON categories.id=menu.id_category WHERE menu.id_partner='$id' AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.deleted_at IS NULL AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
          }
      }
      $query .=  ") AS tmp GROUP BY id ORDER BY qty DESC";
      $trxQ = mysqli_query($db_conn, $query);
          
      if(mysqli_num_rows($trxQ) > 0) {
          $sorted2 = mysqli_fetch_all($trxQ, MYSQLI_ASSOC);
      }
      $status=200;
      $success=1;
      $msg="Success";
    } 
    else 
    {
      $query ="SELECT SUM(qty) AS value, nama AS name, id FROM ( SELECT SUM(detail_transaksi.qty) AS qty, categories.name AS nama, categories.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category WHERE menu.id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
      $queryTrans = "SELECT table_name FROM information_schema.tables
      WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
      $transaksi = mysqli_query($db_conn, $queryTrans);
      while($row=mysqli_fetch_assoc($transaksi)){
          $table_name = explode("_",$row['table_name']);
          $transactions = "transactions_".$table_name[1]."_".$table_name[2];
          $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
          if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
              $query .= " UNION ALL ";
              $query .= " SELECT SUM(`$detail_transactions`.qty) AS qty, categories.name AS nama, categories.id FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN categories ON categories.id=menu.id_category WHERE menu.id_partner='$id' AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
          }
      }
      $query .=  ") AS tmp GROUP BY id ORDER BY qty DESC";
      $trxQ = mysqli_query($db_conn, $query);
          
      if(mysqli_num_rows($trxQ) > 0) {
          $sorted1 = mysqli_fetch_all($trxQ, MYSQLI_ASSOC);
      }
      

      
      $query ="SELECT SUM(qty) AS value, nama AS name, id FROM ( SELECT SUM(detail_transaksi.harga) AS qty, categories.name AS nama, categories.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category WHERE menu.id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
      $queryTrans = "SELECT table_name FROM information_schema.tables
      WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
      $transaksi = mysqli_query($db_conn, $queryTrans);
      while($row=mysqli_fetch_assoc($transaksi)){
          $table_name = explode("_",$row['table_name']);
          $transactions = "transactions_".$table_name[1]."_".$table_name[2];
          $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
          if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
              $query .= " UNION ALL ";
              $query .= " SELECT SUM(`$detail_transactions`.harga) AS qty, categories.name AS nama, categories.id FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN categories ON categories.id=menu.id_category WHERE menu.id_partner='$id' AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
          }
      }
      $query .=  ") AS tmp GROUP BY id ORDER BY qty DESC";
      $trxQ = mysqli_query($db_conn, $query);
          
      if(mysqli_num_rows($trxQ) > 0) {
          $sorted2 = mysqli_fetch_all($trxQ, MYSQLI_ASSOC);
      }
      $status=200;
      $success=1;
      $msg="Success";
    }
}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$sorted1, "data2"=>$sorted2]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
?>