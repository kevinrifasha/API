<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../tokenModels/tokenManager.php"); 
require_once("../../connection.php");
require '../../../db_connection.php';
require_once '../../../includes/CalculateFunctions.php';


$cf = new CalculateFunction();

$id = $_GET['id'];
$dateTo = $_GET['dateTo'];
$dateFrom = $_GET['dateFrom'];
$res = array();
$resQ = array();
$tot = [];

$newDateFormat = 0;

if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
    $dateTo = str_replace("%20"," ",$dateTo);
    $dateFrom = str_replace("%20"," ",$dateFrom);
    $newDateFormat = 1;
}

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
$values = array();
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    
    
    if($newDateFormat == 1){
        $query = "SELECT SUM(count) as count FROM ( SELECT COUNT(id) AS count FROM transaksi WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
        $dateFromStr = str_replace("-","", $dateFrom);
        $dateToStr = str_replace("-","", $dateTo);
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(
                ($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])
                ){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(id) AS count FROM `$transactions` WHERE `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND (`$transactions`.status=2 OR `$transactions`.status=1 ) AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $query .= ") AS tmp";
        
        $trx = mysqli_query($db_conn, $query);
        
        $query = "SELECT SUM(count) AS count, SUM(count2) AS count2 FROM ( SELECT SUM(detail_transaksi.qty) AS count, COUNT(DISTINCT transaksi.id) AS count2 FROM transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
        $dateFromStr = str_replace("-","", $dateFrom);
        $dateToStr = str_replace("-","", $dateTo);
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(
                ($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])
                ){
                $query .= "UNION ALL " ;
                $query .= "SELECT SUM(`$detail_transactions`.qty) AS count, COUNT(DISTINCT `$transactions`.id) AS count2 FROM `$transactions` JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.id_transaksi JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND (`$transactions`.status=2 OR `$transactions`.status=1 ) AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id " ;
            }
        }
        $query .= ") AS tmp";
        $sm = mysqli_query($db_conn, $query);
        
        $query = "SELECT id, id_transaksi, id_menu, qty FROM ( SELECT dt.id, dt.id_transaksi, dt.id_menu, dt.qty FROM detail_transaksi dt WHERE dt.deleted_at IS NULL AND dt.is_smart_waiter=1 GROUP BY dt.id_menu ";
        $dateFromStr = str_replace("-","", $dateFrom);
        $dateToStr = str_replace("-","", $dateTo);
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(
                ($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])
                ){
                $query .= "UNION ALL " ;
                $query .= "SELECT dt.id, dt.id_transaksi, dt.id_menu, dt.qty FROM `$detail_transactions` dt WHERE dt.deleted_at IS NULL AND dt.is_smart_waiter=1 GROUP BY dt.id_menu " ;
            }
        }
        $query .= ") AS tmp";
        $data = mysqli_query($db_conn, $query);
        
        $query = "SELECT SUM(count) AS count FROM ( SELECT SUM(detail_transaksi.qty) AS count FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
        $dateFromStr = str_replace("-","", $dateFrom);
        $dateToStr = str_replace("-","", $dateTo);
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(
                ($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])
                ){
                $query .= "UNION ALL " ;
                $query .= "SELECT SUM(`$detail_transactions`.qty) AS count FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND (`$transactions`.status=2 OR `$transactions`.status=1 ) AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id  " ;
            }
        }
        $query .= ") AS tmp";
        $io = mysqli_query($db_conn, $query);
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
            $status=401;
            $msg="Not Found";
        }
    } 
    else 
    {
        $query = "SELECT SUM(count) as count FROM ( SELECT COUNT(id) AS count FROM transaksi WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
        $dateFromStr = str_replace("-","", $dateFrom);
        $dateToStr = str_replace("-","", $dateTo);
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(
                ($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])
                ){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(id) AS count FROM `$transactions` WHERE `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND (`$transactions`.status=2 OR `$transactions`.status=1 ) AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $query .= ") AS tmp";
        
        $trx = mysqli_query($db_conn, $query);
        
        $query = "SELECT SUM(count) AS count, SUM(count2) AS count2 FROM ( SELECT SUM(detail_transaksi.qty) AS count, COUNT(DISTINCT transaksi.id) AS count2 FROM transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
        $dateFromStr = str_replace("-","", $dateFrom);
        $dateToStr = str_replace("-","", $dateTo);
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(
                ($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])
                ){
                $query .= "UNION ALL " ;
                $query .= "SELECT SUM(`$detail_transactions`.qty) AS count, COUNT(DISTINCT `$transactions`.id) AS count2 FROM `$transactions` JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.id_transaksi JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND (`$transactions`.status=2 OR `$transactions`.status=1 ) AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id " ;
            }
        }
        $query .= ") AS tmp";
        $sm = mysqli_query($db_conn, $query);
        
        $query = "SELECT id, id_transaksi, id_menu, qty FROM ( SELECT dt.id, dt.id_transaksi, dt.id_menu, dt.qty FROM detail_transaksi dt WHERE dt.deleted_at IS NULL AND dt.is_smart_waiter=1 GROUP BY dt.id_menu ";
        $dateFromStr = str_replace("-","", $dateFrom);
        $dateToStr = str_replace("-","", $dateTo);
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(
                ($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])
                ){
                $query .= "UNION ALL " ;
                $query .= "SELECT dt.id, dt.id_transaksi, dt.id_menu, dt.qty FROM `$detail_transactions` dt WHERE dt.deleted_at IS NULL AND dt.is_smart_waiter=1 GROUP BY dt.id_menu " ;
            }
        }
        $query .= ") AS tmp";
        $data = mysqli_query($db_conn, $query);
        
        $query = "SELECT SUM(count) AS count FROM ( SELECT SUM(detail_transaksi.qty) AS count FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
        $dateFromStr = str_replace("-","", $dateFrom);
        $dateToStr = str_replace("-","", $dateTo);
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(
                ($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])
                ){
                $query .= "UNION ALL " ;
                $query .= "SELECT SUM(`$detail_transactions`.qty) AS count FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND (`$transactions`.status=2 OR `$transactions`.status=1 ) AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id  " ;
            }
        }
        $query .= ") AS tmp";
        $io = mysqli_query($db_conn, $query);
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
            $status=401;
            $msg="Not Found";
        }
    }


}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$res, "total_transactions"=>$totalTrx, "total_sm"=>$totalSM, "trx_with_sm"=>$totalTrxWithSM, "total_io"=>$totalIO]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
