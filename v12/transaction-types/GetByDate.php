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
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $tokenDecoded->masterID;
$value = array();
$success=0;
$msg = 'Failed';
$all = 0;

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
    
    if($newDateFormat == 1){
        if(isset($_GET['all'])) {
            $all = $_GET['all'];
        }
        if($all !== "1") {
            $idMaster = null;
        }
        
        if($all == "1") {
            $query = "SELECT COUNT(transaksi.id) AS dineIn FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' AND takeaway=0 AND pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.id NOT LIKE '%DL%' AND transaksi.deleted_at IS NULL";
        } else {
            $query="SELECT COUNT(transaksi.id) AS dineIn 
            FROM transaksi 
            WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
            transaksi.id_partner='$id' AND takeaway=0 AND 
            pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND 
            (status='1' OR status='2' ) AND 
            transaksi.id NOT LIKE '%DL%' AND
            transaksi.deleted_at IS NULL ";
        }
            $queryTrans = "SELECT table_name FROM information_schema.tables
            WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(`$transactions`.id) AS dineIn 
                FROM `$transactions` 
                WHERE `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
                `$transactions`.id_partner='$id' AND takeaway=0 AND 
                pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND 
                (status='1' OR status='2' ) AND 
                `$transactions`.deleted_at IS NULL ";
            }
        }
        $sqlCountDineIn = mysqli_query($db_conn, $query);
    
        if($all == "1") {
            $query = "SELECT COUNT(transaksi.id) AS takeaway FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2')";
        } else {
            $query="SELECT COUNT(transaksi.id) AS takeaway FROM transaksi 
            WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
            transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2') ";
        }
        
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(`$transactions`.id) AS takeaway FROM `$transactions` 
                WHERE `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
                `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') ";
            }
        }
        $sqlCountTakeaway = mysqli_query($db_conn, $query);
        
        if($all == "1") {
            $query = "SELECT COUNT(transaksi.id) AS preorder FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE transaksi.paid_date BETWEEN '2022-12-01' AND '2022-12-01' AND p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND jam BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.pre_order_id !=0 AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL";
        } else {
            $query="SELECT COUNT(transaksi.id) AS preorder FROM transaksi 
            WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
            transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND jam BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.pre_order_id !=0 AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL ";
        }
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(`$transactions`.id) AS preorder FROM `$transactions` 
                WHERE `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
                `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND jam BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.pre_order_id !=0 AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL ";
            }
        }
        $sqlCountPreorder = mysqli_query($db_conn, $query);
        
        if($all == "1") {
            $query = "SELECT COUNT(d.id) AS delivery FROM delivery d JOIN transaksi t ON t.id= d.transaksi_id JOIN partner p ON p.id = t.id_partner WHERE p.id_master = '$idMaster' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL";
        } else {
            $query="SELECT COUNT(d.id) AS delivery FROM delivery d JOIN transaksi t ON t.id= d.transaksi_id WHERE t.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL ";
        }
        
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(d.id) AS delivery FROM delivery d JOIN `$transactions` t ON t.id= d.transaksi_id WHERE t.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL ";
            }
        }
        $sqlCountDelivery = mysqli_query($db_conn, $query);
        
        if(mysqli_num_rows($sqlCountDineIn) > 0 || 
        mysqli_num_rows($sqlCountTakeaway) > 0 || 
        mysqli_num_rows($sqlCountPreorder) > 0 || 
        mysqli_num_rows($sqlCountDelivery) > 0 ) {
            $dineIn1 = mysqli_fetch_all($sqlCountDineIn, MYSQLI_ASSOC);
            foreach ($dineIn1 as  $value) {
                // $dineIn[0]['dineIn'] += (int) $value['dineIn'];
                ($dineIn[0]['dineIn'] ?? $dineIn[0]['dineIn'] = 0) ? $dineIn[0]['dineIn'] += (int) $value['dineIn'] : $dineIn[0]['dineIn'] = (int) $value['dineIn'];
            }
            
            $takeaway1 = mysqli_fetch_all($sqlCountTakeaway, MYSQLI_ASSOC);
            foreach ($takeaway1 as  $value) {
                // $takeaway[0]['takeaway']+=(int) $value['takeaway'];
                ($takeaway[0]['takeaway'] ?? $takeaway[0]['takeaway'] = 0) ? $takeaway[0]['takeaway'] += (int) $value['takeaway'] : $takeaway[0]['takeaway'] = (int) $value['takeaway'];
            }
            
            $preorder1 = mysqli_fetch_all($sqlCountPreorder, MYSQLI_ASSOC);
            foreach ($preorder1 as  $value) {
                // $preorder[0]['preorder']+=(int) $value['preorder'];
                ($preorder[0]['preorder'] ?? $preorder[0]['preorder'] = 0) ? $preorder[0]['preorder'] += (int) $value['preorder'] : $preorder[0]['preorder'] = (int) $value['preorder'];
            }
            
            $delivery1 = mysqli_fetch_all($sqlCountDelivery, MYSQLI_ASSOC);
            foreach ($delivery1 as  $value) {
                // $delivery[0]['delivery']+=(int) $value['delivery'];
                // $dineIn[0]['dineIn']-=(int) $value['delivery'];
                ($delivery[0]['delivery'] ?? $delivery[0]['delivery'] = 0) ? $delivery[0]['delivery'] += (int) $value['delivery'] : $delivery[0]['delivery'] = (int) $value['delivery'];
                
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

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "dineIn"=>$dineIn, "takeaway"=>$takeaway, "preorder"=>$preorder, "delivery"=>$delivery]);  

?>