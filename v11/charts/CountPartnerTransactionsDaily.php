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

$dateFrom = $_GET['dateFrom'];
$dateTo = $_GET['dateTo'];

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
    $query="SELECT SUM(counted) counted, DayOnly, YearOnly, date FROM ( SELECT count(transaksi.total) AS counted, 
    CASE WHEN order_status_trackings.created_at IS NULL THEN DAY(transaksi.jam) ELSE DAY(DATE_ADD(order_status_trackings.created_at, INTERVAL 7 HOUR)) END AS DayOnly, 
    CASE WHEN order_status_trackings.created_at IS NULL THEN YEAR(transaksi.jam) ELSE YEAR(DATE_ADD(order_status_trackings.created_at, INTERVAL 7 HOUR)) END AS YearOnly, 
    CASE WHEN order_status_trackings.created_at IS NULL THEN DATE(transaksi.jam) ELSE DATE(DATE_ADD(order_status_trackings.created_at, INTERVAL 7 HOUR)) END AS date
    FROM transaksi LEFT JOIN order_status_trackings ON order_status_trackings.transaction_id=transaksi.id
    WHERE transaksi.id_partner='$token->id_partner' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY date ";
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
            $query .= "UNION ALL " ;
            $query .= "SELECT count(`$transactions`.total) AS counted, 
            CASE WHEN order_status_trackings.created_at IS NULL THEN DAY(`$transactions`.jam) ELSE DAY(DATE_ADD(order_status_trackings.created_at, INTERVAL 7 HOUR)) END AS DayOnly, 
            CASE WHEN order_status_trackings.created_at IS NULL THEN YEAR(`$transactions`.jam) ELSE YEAR(DATE_ADD(order_status_trackings.created_at, INTERVAL 7 HOUR)) END AS YearOnly, 
            CASE WHEN order_status_trackings.created_at IS NULL THEN DATE(`$transactions`.jam) ELSE DATE(DATE_ADD(order_status_trackings.created_at, INTERVAL 7 HOUR)) END AS date
            FROM `$transactions` LEFT JOIN order_status_trackings ON order_status_trackings.transaction_id=`$transactions`.id
            WHERE `$transactions`.id_partner='$token->id_partner' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1 AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY date ";
        }
    }
    $query .= " ) AS tmp GROUP BY date";
    $transaksi = mysqli_query($db_conn, $query);
    
    while ($row = mysqli_fetch_assoc($transaksi)) {
        $timestamp = strtotime($row['date']);
        $row['date'] = date("d-M-Y", $timestamp);
        array_push($value, array("date" => $row['date'], "value" => $row['counted']));
    }

    if(count($transaksi)>0){
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
    
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$value]);  
http_response_code($status);
echo $signupJson;
?>