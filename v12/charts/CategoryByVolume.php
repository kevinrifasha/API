<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
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
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->masterID;
$value = array();
$sorted1 = array();
$sorted2 = array();
$success=0;
$msg = 'Failed';
$all = "0";

$id = $_GET['id'];
$dateFrom=$_GET['dateFrom'];
$dateTo=$_GET['dateTo'];

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

$values = array();
$values2 = array();
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);

    $qDetail = "";
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }

    if($newDateFormat == 1){
      if($all != "1") {
          $idMaster = null;
          $qDetail = "SELECT SUM(detail_transaksi.qty) AS qty, categories.name AS nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category WHERE transaksi.id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY categories.id ORDER BY qty DESC";
      } else {
          $qDetail = "SELECT SUM(detail_transaksi.qty) AS qty, categories.name AS nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY categories.name ORDER BY qty DESC";
          
      }
      
      $detail = mysqli_query($db_conn, $qDetail);
          while($row=mysqli_fetch_assoc($detail)){
              $namaMenu = $row['nama'];
              $qty = (int) $row['qty'];
              array_push($values, array("name" => "$namaMenu", "value" => $qty));
          }
  
      
      $queryTrans = "SELECT table_name FROM information_schema.tables
      WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
      $transaksi = mysqli_query($db_conn, $queryTrans);
          while($row=mysqli_fetch_assoc($transaksi)){
              $table_name = explode("_",$row['table_name']);
              $transactions = "transactions_".$table_name[1]."_".$table_name[2];
              $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
              if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                  $sqlGetSales = mysqli_query($db_conn, "SELECT SUM(`$detail_transactions`.qty) AS qty, categories.name AS nama FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN categories ON categories.id=menu.id_category WHERE `$transactions`.id_partner='$id' AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.deleted_at IS NULL AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY categories.id ORDER BY qty DESC");
                  if(mysqli_num_rows($sqlGetSales) > 0) {
                      while($row2=mysqli_fetch_assoc($sqlGetSales)){
                          $namaMenu = $row2['nama'];
                          $qty =(int) $row2['qty'];
                          $add =true;
                          $arI = 0;
                          foreach($values as $ar){
                              if($ar['name']==$namaMenu2){
                                  $add = false;
                                  $values[$arI]['sales']+=$qty;
                              }
                              $arI +=1;
                          }
                          if($add==true){
                              array_push($values, array("name" => "$namaMenu", "value" => $qty));
                          }
                      }
                  }
  
              }
          }
  
          $sorted = array_column($values, 'value');
      $i=0;
      array_multisort($sorted, SORT_DESC, $values);
      foreach($values as $sr){
          if($i<5){
              $sorted1[$i] = $sr;
          }
          $i+=1;
      }
      
      if($all == "1") {
          $qDetail = "SELECT SUM(detail_transaksi.harga) AS qty, categories.name AS nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY categories.name ORDER BY qty DESC";
      } else {
          $qDetail = "SELECT SUM(detail_transaksi.harga) AS qty, categories.name AS nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category WHERE transaksi.id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY categories.id ORDER BY qty DESC";
      }
  
      $detail2 = mysqli_query($db_conn, $qDetail);
       
          while($row2=mysqli_fetch_assoc($detail2)){
              $namaMenu2 = $row2['nama'];
              $qty2 = (int)$row2['qty'];
              array_push($values2, array("name" => "$namaMenu2", "value" => $qty2));
          }
      
      $queryTrans = "SELECT table_name FROM information_schema.tables
      WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
      $transaksi = mysqli_query($db_conn, $queryTrans);
          while($row=mysqli_fetch_assoc($transaksi)){
              $table_name = explode("_",$row['table_name']);
              $transactions = "transactions_".$table_name[1]."_".$table_name[2];
              $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
              if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
  
                  $sqlGetSales = mysqli_query($db_conn, "SELECT SUM(`$detail_transactions`.harga) AS qty, categories.name AS nama FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN categories ON categories.id=menu.id_category WHERE `$transactions`.id_partner='$id' AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.deleted_at IS NULL AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY categories.id ORDER BY qty DESC ");
                  if(mysqli_num_rows($sqlGetSales) > 0) {
                      while($row2=mysqli_fetch_assoc($sqlGetSales)){
                          $namaMenu2 = $row2['nama'];
                          $qty2 =(int) $row2['qty'];
                          $add =true;
                          $arI = 0;
                          foreach($values2 as $ar){
                              if($ar['name']==$namaMenu2){
                                  $add = false;
                                  $values2[$arI]['sales']+=$qty2;
                              }
                              $arI +=1;
                          }
                          if($add==true){
                              array_push($values2, array("name" => "$namaMenu2", "value" => $qty2));
                          }
                      }
                  }
  
              }
          }
          $sorted = array_column($values2, 'value');
      $i=0;
      array_multisort($sorted, SORT_DESC, $values2);
      foreach($values2 as $sr){
          if($i<5){
              $sorted2[$i] = $sr;
          }
          $i+=1;
      }
  
      $status=200;
      $success=1;
      $msg="Success";

    } 
    else 
    {
      if($all !== "1") {
        $idMaster = null;
        $qDetail = "SELECT SUM(detail_transaksi.qty) AS qty, categories.name AS nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category WHERE transaksi.id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY categories.id ORDER BY qty DESC";
      } else {
          $qDetail = "SELECT SUM(detail_transaksi.qty) AS qty, categories.name AS nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY categories.name ORDER BY qty DESC";
          
      }
      
      $detail = mysqli_query($db_conn, $qDetail);
          while($row=mysqli_fetch_assoc($detail)){
              $namaMenu = $row['nama'];
              $qty = (int) $row['qty'];
              array_push($values, array("name" => "$namaMenu", "value" => $qty));
      }

      
      $queryTrans = "SELECT table_name FROM information_schema.tables
      WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
      $transaksi = mysqli_query($db_conn, $queryTrans);
          while($row=mysqli_fetch_assoc($transaksi)){
              $table_name = explode("_",$row['table_name']);
              $transactions = "transactions_".$table_name[1]."_".$table_name[2];
              $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
              if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                  $sqlGetSales = mysqli_query($db_conn, "SELECT SUM(`$detail_transactions`.qty) AS qty, categories.name AS nama FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN categories ON categories.id=menu.id_category WHERE `$transactions`.id_partner='$id' AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY categories.id ORDER BY qty DESC");
                  if(mysqli_num_rows($sqlGetSales) > 0) {
                      while($row2=mysqli_fetch_assoc($sqlGetSales)){
                          $namaMenu = $row2['nama'];
                          $qty =(int) $row2['qty'];
                          $add =true;
                          $arI = 0;
                          foreach($values as $ar){
                              if($ar['name']==$namaMenu2){
                                  $add = false;
                                  $values[$arI]['sales']+=$qty;
                              }
                              $arI +=1;
                          }
                          if($add==true){
                              array_push($values, array("name" => "$namaMenu", "value" => $qty));
                          }
                      }
                  }

              }
          }

      $sorted = array_column($values, 'value');
      $i=0;
      array_multisort($sorted, SORT_DESC, $values);
      foreach($values as $sr){
          if($i<5){
              $sorted1[$i] = $sr;
          }
          $i+=1;
      }
      
      if($all == "1") {
          $qDetail = "SELECT SUM(detail_transaksi.harga) AS qty, categories.name AS nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '430' AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY categories.name ORDER BY qty DESC";
      } else {
          $qDetail = "SELECT SUM(detail_transaksi.harga) AS qty, categories.name AS nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category WHERE transaksi.id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY categories.id ORDER BY qty DESC";
      }

      $detail2 = mysqli_query($db_conn, $qDetail);
      
          while($row2=mysqli_fetch_assoc($detail2)){
              $namaMenu2 = $row2['nama'];
              $qty2 = (int)$row2['qty'];
              array_push($values2, array("name" => "$namaMenu2", "value" => $qty2));
          }
      
      $queryTrans = "SELECT table_name FROM information_schema.tables
      WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
      $transaksi = mysqli_query($db_conn, $queryTrans);
          while($row=mysqli_fetch_assoc($transaksi)){
              $table_name = explode("_",$row['table_name']);
              $transactions = "transactions_".$table_name[1]."_".$table_name[2];
              $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
              if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){

                  $sqlGetSales = mysqli_query($db_conn, "SELECT SUM(`$detail_transactions`.harga) AS qty, categories.name AS nama FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN categories ON categories.id=menu.id_category WHERE `$transactions`.id_partner='$id' AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY categories.id ORDER BY qty DESC ");
                  if(mysqli_num_rows($sqlGetSales) > 0) {
                      while($row2=mysqli_fetch_assoc($sqlGetSales)){
                          $namaMenu2 = $row2['nama'];
                          $qty2 =(int) $row2['qty'];
                          $add =true;
                          $arI = 0;
                          foreach($values2 as $ar){
                              if($ar['name']==$namaMenu2){
                                  $add = false;
                                  $values2[$arI]['sales']+=$qty2;
                              }
                              $arI +=1;
                          }
                          if($add==true){
                              array_push($values2, array("name" => "$namaMenu2", "value" => $qty2));
                          }
                      }
                  }

              }
          }
          $sorted = array_column($values2, 'value');
      $i=0;
      array_multisort($sorted, SORT_DESC, $values2);
      foreach($values2 as $sr){
          if($i<5){
              $sorted2[$i] = $sr;
          }
          $i+=1;
      }

      $status=200;
      $success=1;
      $msg="Success";
    }


}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$sorted1, "data2"=>$sorted2]);  

echo $signupJson;
?>