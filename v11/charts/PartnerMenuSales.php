<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');
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


$tokenizer = new Token();
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';

$id =$token->id_partner;
$dateFrom=$_GET['dateFrom'];
$dateTo=$_GET['dateTo'];

$values = array();
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    
  $bestSeller = array();
  $namaMenu = "";
  $qty=0;
  
  $dateFromStr = str_replace("-","", $dateFrom);
  $dateToStr = str_replace("-","", $dateTo);
  $query = "SELECT SUM(qty) qty, nama, menu_id FROM ( SELECT SUM(detail_transaksi.qty) AS qty, menu.nama, menu.id AS menu_id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu  WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY menu_id ";
  
  $queryTrans = "SELECT table_name FROM information_schema.tables
  WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
  $transaksi = mysqli_query($db_conn, $queryTrans);
  while($row=mysqli_fetch_assoc($transaksi)){
      $table_name = explode("_",$row['table_name']);
      $transactions = "transactions_".$table_name[1]."_".$table_name[2];
      $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
      if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
          $query .= "UNION ALL " ;
          $query .= "SELECT SUM(`$detail_transactions`.qty) AS qty, menu.nama, menu.id AS menu_id FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu  WHERE `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1 AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY menu_id ";
      }
  }
  $query .= " ) AS tmp GROUP BY menu_id ORDER BY qty DESC";
  $detail = mysqli_query($db_conn, $query);
  while($row=mysqli_fetch_assoc($detail)){
      $namaMenu = $row['nama'];
      $qty = $row['qty'];
      array_push($bestSeller, array("name" => "$namaMenu", "value" => $qty));
  }
  $values=$bestSeller;

    $status=200;
    $success=1;
    $msg="Success";
}
http_response_code($status);
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$values]);  
// Echo the message.
echo $signupJson;
?>