<?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: access");
// header("Access-Control-Allow-Methods: GET");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// require_once("../../tokenModels/tokenManager.php"); 
// require_once("../../connection.php");
// require '../../../db_connection.php';
// require  __DIR__ . '/../../../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
// $dotenv->load();

// $headers = array();
//     $rx_http = '/\AHTTP_/';
//     foreach($_SERVER as $key => $val) {
//       if( preg_match($rx_http, $key) ) {
//         $arh_key = preg_replace($rx_http, '', $key);
//         $rx_matches = array();
//         // do some nasty string manipulations to restore the original letter case
//         // this should work in most cases
//         $rx_matches = explode('_', $arh_key);
//         if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
//           foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
//           $arh_key = implode('-', $rx_matches);
//         }
//         $headers[$arh_key] = $val;
//       }
//     }
// $token = '';
    
// foreach ($headers as $header => $value) {
//     if($header=="Authorization" || $header=="AUTHORIZATION"){
//         $token=substr($value,7);
//     }
// }

// $db = connectBase();
// $tokenizer = new TokenManager($db);
// $tokens = $tokenizer->validate($token);
// $tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
// $value = array();
// $success=0;
// $msg = 'Failed';
// $countTrx = 0;
// $countUser = 0;
// $countQty = 0;
// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
//     $status = $tokens['status'];
//     $msg = $tokens['msg']; 
//     $success = 0;
    
// }else{
//     $id = $_GET['id'];
//     $dateTo = $_GET['dateTo'];
//     $dateFrom = $_GET['dateFrom'];
//     $dateFromStr = str_replace("-","", $dateFrom);
//     $dateToStr = str_replace("-","", $dateTo);
    
    
    

//     $query = "SELECT COUNT(DISTINCT transaksi.id) AS trx FROM `transaksi` JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
//     WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND  DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";

//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= "UNION ALL " ;
//             $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS trx FROM `$transactions` JOIN   `$detail_transactions` ON   `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=  `$detail_transactions`.id_menu
//             WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND  DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
//         }
//     }
//     $sql = mysqli_query($db_conn, $query);

//     $query =  "SELECT COUNT(DISTINCT transaksi.phone) AS user FROM `transaksi` JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
//     WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND  DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= "UNION ALL " ;
//             $query .= "SELECT COUNT(DISTINCT `$transactions`.phone) AS user FROM `$transactions` JOIN   $detail_transactions ON   $detail_transactions.id_transaksi=`$detail_transactions`.id JOIN menu ON menu.id=  $detail_transactions.id_menu
//             WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND  DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
//         }
//     }
//     $sql1 = mysqli_query($db_conn, $query);

//     $query =  "SELECT SUM(detail_transaksi.qty) AS qty FROM `transaksi` 
//     JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= "UNION ALL " ;
//             $query .= "SELECT SUM(`$detail_transactions`.qty) AS qty FROM `$transactions`
//             JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
//         }
//     }
//     $sql2 = mysqli_query($db_conn, $query);
//     if(mysqli_num_rows($sql2) > 0) {
//         $data2 = mysqli_fetch_all($sql2, MYSQLI_ASSOC);
//         foreach ($data2 as $value) {
//             $countQty += (int) $value['qty'];
//         }
//     }
//     if(mysqli_num_rows($sql1) > 0) {
//         $data1 = mysqli_fetch_all($sql1, MYSQLI_ASSOC);
//         foreach ($data1 as $value) {
//             $countUser += (int) $value['user'];
//         }
//     }
//     if(mysqli_num_rows($sql) > 0) {
//         $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
//         foreach ($data as $value) {
//             $countTrx +=(int) $value['trx'];
//         }
//     }
//     $success = 1;
//     $status = 200;
//     $msg = "Success";
    
// }
// if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
//         http_response_code(200);
//     }else{
//         http_response_code($status);
//     }
// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "qty"=>$countQty, "user"=>$countUser, "trx"=>$countTrx]);

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
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
$countTrx = 0;
$countUser = 0;
$countQty = 0;
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $id = $_GET['id'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);
    
    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }
    
    if($newDateFormat == 1)
    {
        $query = "SELECT COUNT(DISTINCT transaksi.id) AS trx FROM `transaksi` JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND  transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
    
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS trx FROM `$transactions` JOIN   `$detail_transactions` ON   `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=  `$detail_transactions`.id_menu
                WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND  `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $sql = mysqli_query($db_conn, $query);
    
        $query =  "SELECT COUNT(DISTINCT transaksi.phone) AS user FROM `transaksi` JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND  transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.phone) AS user FROM `$transactions` JOIN   $detail_transactions ON   $detail_transactions.id_transaksi=`$detail_transactions`.id JOIN menu ON menu.id=  $detail_transactions.id_menu
                WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND  `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $sql1 = mysqli_query($db_conn, $query);
    
        $query =  "SELECT SUM(detail_transaksi.qty) AS qty FROM `transaksi` 
        JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT SUM(`$detail_transactions`.qty) AS qty FROM `$transactions`
                JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $sql2 = mysqli_query($db_conn, $query);
        if(mysqli_num_rows($sql2) > 0) {
            $data2 = mysqli_fetch_all($sql2, MYSQLI_ASSOC);
            foreach ($data2 as $value) {
                $countQty += (int) $value['qty'];
            }
        }
        if(mysqli_num_rows($sql1) > 0) {
            $data1 = mysqli_fetch_all($sql1, MYSQLI_ASSOC);
            foreach ($data1 as $value) {
                $countUser += (int) $value['user'];
            }
        }
        if(mysqli_num_rows($sql) > 0) {
            $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
            foreach ($data as $value) {
                $countTrx +=(int) $value['trx'];
            }
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    }

    else
    {
        $query = "SELECT COUNT(DISTINCT transaksi.id) AS trx FROM `transaksi` JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND  DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
    
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS trx FROM `$transactions` JOIN   `$detail_transactions` ON   `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=  `$detail_transactions`.id_menu
                WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND  DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $sql = mysqli_query($db_conn, $query);
    
        $query =  "SELECT COUNT(DISTINCT transaksi.phone) AS user FROM `transaksi` JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND  DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.phone) AS user FROM `$transactions` JOIN   $detail_transactions ON   $detail_transactions.id_transaksi=`$detail_transactions`.id JOIN menu ON menu.id=  $detail_transactions.id_menu
                WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND  DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $sql1 = mysqli_query($db_conn, $query);
    
        $query =  "SELECT SUM(detail_transaksi.qty) AS qty FROM `transaksi` 
        JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT SUM(`$detail_transactions`.qty) AS qty FROM `$transactions`
                JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $sql2 = mysqli_query($db_conn, $query);
        if(mysqli_num_rows($sql2) > 0) {
            $data2 = mysqli_fetch_all($sql2, MYSQLI_ASSOC);
            foreach ($data2 as $value) {
                $countQty += (int) $value['qty'];
            }
        }
        if(mysqli_num_rows($sql1) > 0) {
            $data1 = mysqli_fetch_all($sql1, MYSQLI_ASSOC);
            foreach ($data1 as $value) {
                $countUser += (int) $value['user'];
            }
        }
        if(mysqli_num_rows($sql) > 0) {
            $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
            foreach ($data as $value) {
                $countTrx +=(int) $value['trx'];
            }
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
        
    }
    
    
}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "qty"=>$countQty, "user"=>$countUser, "trx"=>$countTrx]);  

?>