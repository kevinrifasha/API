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

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $id = $token->id_partner;
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    $res=array();
    
    
        $query = "SELECT SUM(count) count, hour FROM ( SELECT COUNT(transaksi.id) AS count,HOUR(transaksi.jam) AS hour
    FROM transaksi
    WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.status<=2 AND transaksi.status>=1 GROUP BY hour ";
    $query .= " ) AS tmp GROUP BY hour ";
    $hourlyTransaction = mysqli_query($db_conn, $query);

    if (mysqli_num_rows($hourlyTransaction) > 0) {
        $ht = mysqli_fetch_all($hourlyTransaction, MYSQLI_ASSOC);
       
        $status = 200;
        $success=1;
        $msg="Success";
    } else {
        $res['hourlyTransaction']=[];
        $status = 204;
        $success=0;
        $msg="Data tidak ditemukan";
    }
   

    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "hourlyTransaction"=>$ht]);
