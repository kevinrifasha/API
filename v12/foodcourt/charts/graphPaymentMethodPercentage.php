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

$cf = new CalculateFunction();
$res = array();
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$value = array();
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $id = $_GET['id'];
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    $res=array();
    $status = 200;
    $success=1;
    $msg="Success";

    $newDateFormat = 0;

  if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
      $dateTo = str_replace("%20"," ",$dateTo);
      $dateFrom = str_replace("%20"," ",$dateFrom);
      $newDateFormat = 1;
  }

  if($newDateFormat == 1){
    $query = "SELECT SUM(counted) AS counted, payment_method, tipe_bayar FROM( SELECT COUNT(DISTINCT transaksi.id) AS counted, payment_method.nama AS payment_method, transaksi.tipe_bayar FROM transaksi JOIN payment_method ON payment_method.id=transaksi.tipe_bayar JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON detail_transaksi.id_menu=menu.id WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.status IN (2,1) GROUP BY transaksi.id ";
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
            $query .= " UNION ALL " ;
            $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS counted, payment_method.nama AS payment_method, `$transactions`.tipe_bayar FROM `$transactions` JOIN payment_method ON payment_method.id=`$transactions`.tipe_bayar JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON `$detail_transactions`.id_menu=menu.id WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.status IN (2,1) GROUP BY `$transactions`.id  ";
        }
    }
    $query .= ") AS temp GROUP BY payment_method ORDER BY counted DESC" ;
    $transaksi = mysqli_query($db_conn, $query);
    $values = array();

    $total = 0;
    $ovo = 0;
    $gopay = 0;
    $dana = 0;
    $linkaja = 0;
    $tunai = 0;
    $sakuku = 0;
    $credit = 0;
    $debit = 0;
    $qris = 0;
    $shopee = 0;
    while ($row = mysqli_fetch_assoc($transaksi)) {
        $total += (int) $row['counted'];
        array_push($values, array("label" => $row['payment_method'], "value1" => $row['counted']));
    }
    $i=0;
    foreach ($values as $value) {
        $values[$i]['value']=round(($value['value1']/$total)*100);
        if($value['label']=='OVO'){
            $values[$i]['color']="#c128c9";
        }else if($value['label']=='DANA'){
            $values[$i]['color']="#00D8FF";
        }else if($value['label']=='LINKAJA'){
            $values[$i]['color']="#DD1B16";
        }else if($value['label']=='TUNAI'){
            $values[$i]['color']="#11f215";
        }else if($value['label']=='CREDIT CARD'){
            $values[$i]['color']="#1ca3a1";
        }else if($value['label']=='DEBIT CARD'){
            $values[$i]['color']="#415ff2";
        }else if($value['label']=='QRIS'){
            $values[$i]['color']="#e9f241";
        }else if($value['label']=='SHOPEEPAY'){
            $values[$i]['color']="#f59342";
        }
        $i+=1;
    }
    $res['paymentMethodPercentage']=$values;
  } 
  else 
  {
    $query = "SELECT SUM(counted) AS counted, payment_method, tipe_bayar FROM( SELECT COUNT(DISTINCT transaksi.id) AS counted, payment_method.nama AS payment_method, transaksi.tipe_bayar FROM transaksi JOIN payment_method ON payment_method.id=transaksi.tipe_bayar JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON detail_transaksi.id_menu=menu.id WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.status IN (2,1) GROUP BY transaksi.id ";
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
            $query .= " UNION ALL " ;
            $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS counted, payment_method.nama AS payment_method, `$transactions`.tipe_bayar FROM `$transactions` JOIN payment_method ON payment_method.id=`$transactions`.tipe_bayar JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON `$detail_transactions`.id_menu=menu.id WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.status IN (2,1) GROUP BY `$transactions`.id  ";
        }
    }
    $query .= ") AS temp GROUP BY payment_method ORDER BY counted DESC" ;
    $transaksi = mysqli_query($db_conn, $query);
    $values = array();

    $total = 0;
    $ovo = 0;
    $gopay = 0;
    $dana = 0;
    $linkaja = 0;
    $tunai = 0;
    $sakuku = 0;
    $credit = 0;
    $debit = 0;
    $qris = 0;
    $shopee = 0;
    while ($row = mysqli_fetch_assoc($transaksi)) {
        $total += (int) $row['counted'];
        array_push($values, array("label" => $row['payment_method'], "value1" => $row['counted']));
    }
    $i=0;
    foreach ($values as $value) {
        $values[$i]['value']=round(($value['value1']/$total)*100);
        if($value['label']=='OVO'){
            $values[$i]['color']="#c128c9";
        }else if($value['label']=='DANA'){
            $values[$i]['color']="#00D8FF";
        }else if($value['label']=='LINKAJA'){
            $values[$i]['color']="#DD1B16";
        }else if($value['label']=='TUNAI'){
            $values[$i]['color']="#11f215";
        }else if($value['label']=='CREDIT CARD'){
            $values[$i]['color']="#1ca3a1";
        }else if($value['label']=='DEBIT CARD'){
            $values[$i]['color']="#415ff2";
        }else if($value['label']=='QRIS'){
            $values[$i]['color']="#e9f241";
        }else if($value['label']=='SHOPEEPAY'){
            $values[$i]['color']="#f59342";
        }
        $i+=1;
    }
    $res['paymentMethodPercentage']=$values;
  }

}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$res]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;