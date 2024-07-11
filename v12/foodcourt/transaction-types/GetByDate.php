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
//     $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty 
//     FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
//     WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND 
//     menu.id_partner='$id' AND takeaway=0 AND 
//     pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND 
//     (transaksi.status='1' OR transaksi.status='2' ) AND 
//     transaksi.deleted_at IS NULL GROUP BY transaksi.id ";
//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= " UNION ALL " ;
//             $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty 
//             FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
//             WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND 
//             menu.id_partner='$id' AND takeaway=0 AND 
//             pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND 
//             (`$transactions`.status='1' OR `$transactions`.status='2' ) AND 
//             `$transactions`.deleted_at IS NULL GROUP BY `$transactions`.id  ";
//         }
//     }
//     $query.=") AS tmp";
//     $sqlCountDineIn = mysqli_query($db_conn, $query);

    
//     $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
//     WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND 
//     menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2') GROUP BY transaksi.id ";
//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= "UNION ALL " ;
//             $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
//             WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND 
//             menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') GROUP BY `$transactions`.id ";
//         }
//     }
//     $query.=") AS tmp";
//     $sqlCountTakeaway = mysqli_query($db_conn, $query);
    
//     $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
//     WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND 
//     menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND DATE(jam) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.pre_order_id !=0 AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL GROUP BY transaksi.id ";
//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= "UNION ALL " ;
//             $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi= `$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
//             WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND 
//             menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND DATE(jam) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.pre_order_id !=0 AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL GROUP BY `$transactions`.id ";
//         }
//     }
//     $query.=") AS tmp";
//     $sqlCountPreorder = mysqli_query($db_conn, $query);
    
//     $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT d.id) AS qty FROM delivery d JOIN transaksi t ON t.id= d.transaksi_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=t.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND d.deleted_at IS NULL AND DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL GROUP BY t.id ";
//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= "UNION ALL " ;
//             $query .= "SELECT COUNT(DISTINCT d.id) AS qty FROM delivery d JOIN `$transactions` t ON t.id= d.transaksi_id JOIN   $detail_transactions ON   $detail_transactions.id_transaksi=t.id JOIN menu ON menu.id=  $detail_transactions.id_menu WHERE menu.id_partner='$id' AND d.deleted_at IS NULL AND DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL GROUP BY t.id ";
//         }
//     }
//     $query.=") AS tmp";
//     $sqlCountDelivery = mysqli_query($db_conn, $query);
    
//     if(mysqli_num_rows($sqlCountDineIn) > 0 || 
//     mysqli_num_rows($sqlCountTakeaway) > 0 || 
//     mysqli_num_rows($sqlCountPreorder) > 0 || 
//     mysqli_num_rows($sqlCountDelivery) > 0 ) {
//         $dineIn1 = mysqli_fetch_all($sqlCountDineIn, MYSQLI_ASSOC);
//         foreach ($dineIn1 as  $value) {
//             $dineIn[0]['dineIn']+=(int) $value['qty'];
//         }
        
//         $takeaway1 = mysqli_fetch_all($sqlCountTakeaway, MYSQLI_ASSOC);
//         foreach ($takeaway1 as  $value) {
//             $takeaway[0]['takeaway']+=(int) $value['qty'];
//         }
        
//         $preorder1 = mysqli_fetch_all($sqlCountPreorder, MYSQLI_ASSOC);
//         foreach ($preorder1 as  $value) {
//             $preorder[0]['preorder']+=(int) $value['qty'];
//         }
        
//         $delivery1 = mysqli_fetch_all($sqlCountDelivery, MYSQLI_ASSOC);
//         foreach ($delivery1 as  $value) {
//             $delivery[0]['delivery']+=(int) $value['qty'];
//             $dineIn[0]['dineIn']-=(int) $value['qty'];
//         }
//         $success = 1;
//         $status = 200;
//         $msg = "Success";
//     }else{
//         $success = 0;
//         $status = 204;
//         $msg = "Data Not Found";
//     }
    
// }
// if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
//         http_response_code(200);
//     }else{
//         http_response_code($status);
//     }
// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "dineIn"=>$dineIn, "takeaway"=>$takeaway, "preorder"=>$preorder, "delivery"=>$delivery]);  

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
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $id = $_GET['id'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];

    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);

    if($newDateFormat == 1)
    {
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty 
        FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
        menu.id_partner='$id' AND takeaway=0 AND 
        pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND 
        (transaksi.status='1' OR transaksi.status='2' ) AND 
        transaksi.deleted_at IS NULL GROUP BY transaksi.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= " UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty 
                FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
                menu.id_partner='$id' AND takeaway=0 AND 
                pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND 
                (`$transactions`.status='1' OR `$transactions`.status='2' ) AND 
                `$transactions`.deleted_at IS NULL GROUP BY `$transactions`.id  ";
            }
        }
        $query.=") AS tmp";
        $sqlCountDineIn = mysqli_query($db_conn, $query);
    
        
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
        menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2') GROUP BY transaksi.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
                menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') GROUP BY `$transactions`.id ";
            }
        }
        $query.=") AS tmp";
        $sqlCountTakeaway = mysqli_query($db_conn, $query);
        
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
        menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND jam BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.pre_order_id !=0 AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL GROUP BY transaksi.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi= `$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
                menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND jam BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.pre_order_id !=0 AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL GROUP BY `$transactions`.id ";
            }
        }
        $query.=") AS tmp";
        $sqlCountPreorder = mysqli_query($db_conn, $query);
        
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT d.id) AS qty FROM delivery d JOIN transaksi t ON t.id= d.transaksi_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=t.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL GROUP BY t.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT d.id) AS qty FROM delivery d JOIN `$transactions` t ON t.id= d.transaksi_id JOIN   $detail_transactions ON   $detail_transactions.id_transaksi=t.id JOIN menu ON menu.id=  $detail_transactions.id_menu WHERE menu.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL GROUP BY t.id ";
            }
        }
        $query.=") AS tmp";
        $sqlCountDelivery = mysqli_query($db_conn, $query);
        
        if(mysqli_num_rows($sqlCountDineIn) > 0 || 
        mysqli_num_rows($sqlCountTakeaway) > 0 || 
        mysqli_num_rows($sqlCountPreorder) > 0 || 
        mysqli_num_rows($sqlCountDelivery) > 0 ) {
            $dineIn1 = mysqli_fetch_all($sqlCountDineIn, MYSQLI_ASSOC);
            foreach ($dineIn1 as  $value) {
                $dineIn[0]['dineIn']+=(int) $value['qty'];
            }
            
            $takeaway1 = mysqli_fetch_all($sqlCountTakeaway, MYSQLI_ASSOC);
            foreach ($takeaway1 as  $value) {
                $takeaway[0]['takeaway']+=(int) $value['qty'];
            }
            
            $preorder1 = mysqli_fetch_all($sqlCountPreorder, MYSQLI_ASSOC);
            foreach ($preorder1 as  $value) {
                $preorder[0]['preorder']+=(int) $value['qty'];
            }
            
            $delivery1 = mysqli_fetch_all($sqlCountDelivery, MYSQLI_ASSOC);
            foreach ($delivery1 as  $value) {
                $delivery[0]['delivery']+=(int) $value['qty'];
                $dineIn[0]['dineIn']-=(int) $value['qty'];
            }
            $success = 1;
            $status = 200;
            $msg = "Success";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    }
    else
    {
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty 
        FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND 
        menu.id_partner='$id' AND takeaway=0 AND 
        pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND 
        (transaksi.status='1' OR transaksi.status='2' ) AND 
        transaksi.deleted_at IS NULL GROUP BY transaksi.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= " UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty 
                FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND 
                menu.id_partner='$id' AND takeaway=0 AND 
                pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND 
                (`$transactions`.status='1' OR `$transactions`.status='2' ) AND 
                `$transactions`.deleted_at IS NULL GROUP BY `$transactions`.id  ";
            }
        }
        $query.=") AS tmp";
        $sqlCountDineIn = mysqli_query($db_conn, $query);
    
        
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND 
        menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2') GROUP BY transaksi.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND 
                menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') GROUP BY `$transactions`.id ";
            }
        }
        $query.=") AS tmp";
        $sqlCountTakeaway = mysqli_query($db_conn, $query);
        
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND 
        menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND DATE(jam) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.pre_order_id !=0 AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL GROUP BY transaksi.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi= `$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND 
                menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND DATE(jam) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.pre_order_id !=0 AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL GROUP BY `$transactions`.id ";
            }
        }
        $query.=") AS tmp";
        $sqlCountPreorder = mysqli_query($db_conn, $query);
        
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT d.id) AS qty FROM delivery d JOIN transaksi t ON t.id= d.transaksi_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=t.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND d.deleted_at IS NULL AND DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL GROUP BY t.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT d.id) AS qty FROM delivery d JOIN `$transactions` t ON t.id= d.transaksi_id JOIN   $detail_transactions ON   $detail_transactions.id_transaksi=t.id JOIN menu ON menu.id=  $detail_transactions.id_menu WHERE menu.id_partner='$id' AND d.deleted_at IS NULL AND DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL GROUP BY t.id ";
            }
        }
        $query.=") AS tmp";
        $sqlCountDelivery = mysqli_query($db_conn, $query);
        
        if(mysqli_num_rows($sqlCountDineIn) > 0 || 
        mysqli_num_rows($sqlCountTakeaway) > 0 || 
        mysqli_num_rows($sqlCountPreorder) > 0 || 
        mysqli_num_rows($sqlCountDelivery) > 0 ) {
            $dineIn1 = mysqli_fetch_all($sqlCountDineIn, MYSQLI_ASSOC);
            foreach ($dineIn1 as  $value) {
                $dineIn[0]['dineIn']+=(int) $value['qty'];
            }
            
            $takeaway1 = mysqli_fetch_all($sqlCountTakeaway, MYSQLI_ASSOC);
            foreach ($takeaway1 as  $value) {
                $takeaway[0]['takeaway']+=(int) $value['qty'];
            }
            
            $preorder1 = mysqli_fetch_all($sqlCountPreorder, MYSQLI_ASSOC);
            foreach ($preorder1 as  $value) {
                $preorder[0]['preorder']+=(int) $value['qty'];
            }
            
            $delivery1 = mysqli_fetch_all($sqlCountDelivery, MYSQLI_ASSOC);
            foreach ($delivery1 as  $value) {
                $delivery[0]['delivery']+=(int) $value['qty'];
                $dineIn[0]['dineIn']-=(int) $value['qty'];
            }
            $success = 1;
            $status = 200;
            $msg = "Success";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    }
   

    
}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "dineIn"=>$dineIn, "takeaway"=>$takeaway, "preorder"=>$preorder, "delivery"=>$delivery]);  

?>