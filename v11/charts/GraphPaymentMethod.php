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
$idMaster = $token->id_master;
$res = array();
$success=0;
$msg = 'Failed';
$all = "0";

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $id = $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $id = $_GET['partnerID']; 
    }
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($all == "1") {
        $query = "SELECT pm.nama as label, COUNT(trx.id) AS count, ROUND(COUNT(trx.id) * 100 / t.total,2) AS value FROM transaksi AS trx JOIN payment_method AS pm ON trx.tipe_bayar = pm.id CROSS JOIN (SELECT COUNT(tx.id) as total FROM transaksi AS tx JOIN partner pt ON pt.id = tx.id_partner WHERE DATE(`tx`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND tx.deleted_at IS NULL AND tx.status in (1,2) AND pt.id_master='$idMaster') as t JOIN partner p ON p.id = trx.id_partner WHERE DATE(`trx`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND trx.deleted_at IS NULL AND trx.status in (1,2) AND p.id_master='$idMaster' GROUP BY trx.tipe_bayar";
    } else {
        $query = "SELECT pm.nama as label, COUNT(trx.id) AS count, ROUND(COUNT(trx.id) * 100 / t.total,2) AS value FROM transaksi AS trx JOIN payment_method AS pm ON trx.tipe_bayar = pm.id CROSS JOIN (SELECT COUNT(tx.id) as total FROM transaksi AS tx WHERE DATE(`tx`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND tx.deleted_at IS NULL AND tx.status in (1,2) AND tx.id_partner='$id') as t WHERE DATE(`trx`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND trx.deleted_at IS NULL AND trx.status in (1,2) AND trx.id_partner='$id' GROUP BY trx.tipe_bayar";
    }

    $q = mysqli_query($db_conn, $query);
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
http_response_code($status);
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$res]);  
echo $signupJson;