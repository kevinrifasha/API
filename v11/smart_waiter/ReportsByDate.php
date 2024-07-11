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

$res = array();
$resQ = array();
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
$res = array();
    
//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$res=array();
$totalTrx=0;
$totalSM=0;
$totalTrxWithSM=0;
$totalIO=0;
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;
$all = "0";

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    $id = $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $id = $_GET['partnerID']; 
    }
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($all == "1") {
        $addQuery1 = "p.id_master='$idMaster'";
        $addQuery2 = "JOIN partner p ON p.id = t.id_partner";
    } else {
        $addQuery1 = "t.id_partner='$id'";
        $addQuery2 = "";
    }
    
    $trx = mysqli_query($db_conn, "SELECT COUNT(t.id) AS count FROM transaksi t ". $addQuery2 ." WHERE ". $addQuery1 ." AND t.deleted_at IS NULL AND (t.status=2 OR t.status=1 ) AND DATE(t.paid_date) BETWEEN '$dateFrom' AND '$dateTo'");
    
    $sm = mysqli_query($db_conn, "SELECT SUM(detail_transaksi.qty) AS count, COUNT(DISTINCT t.id) AS count2 FROM detail_transaksi JOIN transaksi t ON detail_transaksi.id_transaksi=t.id ". $addQuery2 ." WHERE detail_transaksi.is_smart_waiter=1 AND ". $addQuery1 ." AND t.deleted_at IS NULL AND (t.status=2 OR t.status=1 ) AND DATE(t.paid_date) BETWEEN '$dateFrom' AND '$dateTo'");
    
    $data = mysqli_query($db_conn, "SELECT SUM(dt.qty) AS sum, m.nama AS name FROM detail_transaksi dt JOIN menu m ON m.id=dt.id_menu JOIN transaksi t ON t.id=dt.id_transaksi ". $addQuery2 ." WHERE dt.deleted_at IS NULL AND dt.is_smart_waiter=1 AND ". $addQuery1 ." AND t.deleted_at IS NULL AND (t.status=2 OR t.status=1 ) AND DATE(t.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY dt.id_menu ORDER BY sum DESC");
    
    $io = mysqli_query($db_conn, "SELECT SUM(detail_transaksi.qty) AS count FROM detail_transaksi JOIN transaksi t ON detail_transaksi.id_transaksi=t.id ". $addQuery2 ." WHERE ". $addQuery1 ." AND t.deleted_at IS NULL AND (t.status=2 OR t.status=1 ) AND DATE(t.paid_date) BETWEEN '$dateFrom' AND '$dateTo'");
    
    if (mysqli_num_rows($data) > 0) {       
        $res = mysqli_fetch_all($data, MYSQLI_ASSOC);
        $trxCount = mysqli_fetch_all($trx, MYSQLI_ASSOC);
        $ioCount = mysqli_fetch_all($io, MYSQLI_ASSOC);
        $smCount = mysqli_fetch_all($sm, MYSQLI_ASSOC);
        $totalTrx = (int)$trxCount[0]['count'];
        $totalSM = (int)$smCount[0]['count'];
        $totalTrxWithSM = (int)$smCount[0]['count2'];
        $totalIO = (int)$ioCount[0]['count'];
        // $res['hpp']=(int)$resQ[0]['hpp'];
        // $res['gross_profit'] = $res['gross_profit'] - $res['hpp'];
        // $res['gross_profit_afterservice']=$res['gross_profit']-$res['service'];
        // $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
        $success=1;
        $status=200;
        $msg="Success";
    }else{
        $success=0;
        $status=204;
        $msg="Not Found";
    }
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$res, "total_transactions"=>$totalTrx, "total_sm"=>$totalSM, "trx_with_sm"=>$totalTrxWithSM, "total_io"=>$totalIO]);  
http_response_code($status);
echo $signupJson;
