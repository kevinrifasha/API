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
$dineIn = array();
$takeaway = array();
$preorder = array();
$delivery = array();
$all = "0";

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
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }

    if($newDateFormat == 1){
        if($all == "1") {
            $query ="SELECT COUNT(transaksi.id) AS qty FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' AND takeaway=0 AND pre_order_id=0 AND transaksi.id NOT LIKE '%DL%' AND (no_meja IS NOT NULL OR no_meja!='') AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL";
        } else {
            $query ="SELECT COUNT(transaksi.id) AS qty
            FROM transaksi
            WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND
            transaksi.id_partner='$id' AND takeaway=0 AND 
            transaksi.id NOT LIKE '%DL%' AND
            pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND
            (status='1' OR status='2' ) AND
            transaksi.deleted_at IS NULL";
        }
        
        $queryTrans = "SELECT table_name FROM information_schema.tables WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        
        $transaksi = mysqli_query($db_conn, $queryTrans);
    
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                
                if($all == "1") {
                    $query .= "";
                } else {
                    $query .= "SELECT COUNT(`$transactions`.id) AS qty
                    FROM `$transactions`
                    WHERE `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND
                    `$transactions`.id_partner='$id' AND takeaway=0 AND
                    pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND
                    (status='1' OR status='2' ) AND
                    `$transactions`.deleted_at IS NULL ";
                }
            }
        }
        $sqlCountDineIn = mysqli_query($db_conn, $query);
    
        if($all == "1") {
            $query ="SELECT COUNT(transaksi.id) AS qty 
            FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE 
            transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND 
            p.id_master = '$idMaster' AND takeaway=1 AND pre_order_id=0 AND 
            (no_meja IS NOT NULL OR no_meja!='') AND (transaksi.status='1' 
            OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL";
        } else {
            $query =  "SELECT COUNT(transaksi.id) AS qty FROM transaksi
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
                $query .= "SELECT COUNT(`$transactions`.id) AS qty FROM `$transactions`
                WHERE `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND
                `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') ";
            }
        }
        $sqlCountTakeaway = mysqli_query($db_conn, $query);
    
        if($all == "1") {
            $query = "SELECT COUNT(transaksi.id) AS qty FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND jam BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.pre_order_id !=0 AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL";
        } else {
            $query =  "SELECT COUNT(transaksi.id) AS qty FROM transaksi
            WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND
            transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND jam) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.pre_order_id !=0 AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL ";
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
                $query .= "SELECT COUNT(`$transactions`.id) AS qty FROM `$transactions`
                WHERE `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND
                `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND jam BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.pre_order_id !=0 AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL ";
            }
        }
        $sqlCountPreorder = mysqli_query($db_conn, $query);
    
        if($all == "1") {
            $query = "SELECT COUNT(d.id) AS qty FROM delivery d JOIN transaksi t ON t.id= d.transaksi_id JOIN partner p ON p.id = t.id_partner WHERE p.id_master = '$idMaster' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL";
        } else {
            $query =  "SELECT COUNT(d.id) AS qty FROM delivery d JOIN transaksi t ON t.id= d.transaksi_id WHERE t.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL ";
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
                $query .= "SELECT COUNT(d.id) AS qty FROM delivery d JOIN `$transactions` t ON t.id= d.transaksi_id WHERE t.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL ";
            }
        }
        $sqlCountDelivery = mysqli_query($db_conn, $query);
    
        if(
            mysqli_num_rows($sqlCountDineIn) > 0 ||
            mysqli_num_rows($sqlCountTakeaway) > 0 ||
            mysqli_num_rows($sqlCountPreorder) > 0 ||
            mysqli_num_rows($sqlCountDelivery) > 0
            ) {
            $data1 = mysqli_fetch_all($sqlCountDineIn, MYSQLI_ASSOC);
            foreach ($data1 as $value) {
            //   $dineIn['qty']+=(int) $value['qty'];
              ($dineIn['qty'] ?? $dineIn['qty'] = 0) ? $dineIn['qty'] +=(int) $value['qty'] : $dineIn['qty'] = $value['qty'];
            }
            $data2 = mysqli_fetch_all($sqlCountTakeaway, MYSQLI_ASSOC);
            foreach ($data2 as $value) {
            //   $takeaway['qty']+=(int) $value['qty'];
              ($takeaway['qty'] ?? $takeaway['qty'] = 0) ? $takeaway['qty'] +=(int) $value['qty'] : $takeaway['qty'] = (int)$value['qty'];
            }
            $data3 = mysqli_fetch_all($sqlCountPreorder, MYSQLI_ASSOC);
            foreach ($data3 as $value) {
            //   $preorder['qty'] +=(int) $value['qty'];
                ($preorder['qty'] ?? $preorder['qty'] = 0) ? $preorder['qty'] +=(int) $value['qty'] : $preorder['qty'] = (int)$value['qty'];
            }
            $data4 = mysqli_fetch_all($sqlCountDelivery, MYSQLI_ASSOC);
            foreach ($data4 as $value) {
              $delivery['qty'] = (int) $value['qty'];
            }
            // $dineIn['qty']-=$delivery['qty'] ;
            $dineIn['qty'] = (int)$dineIn['qty'] ;
    
            if($all == "1") {
                $query = "SELECT transaksi.total, transaksi.promo, program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.id NOT LIKE '%DL%' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=0 AND transaksi.pre_order_id=0 AND (transaksi.no_meja IS NOT NULL OR transaksi.no_meja!='') AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo'";
            } else {
                $query = "SELECT transaksi.total, transaksi.promo, program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=0 AND transaksi.pre_order_id=0 AND (transaksi.no_meja IS NOT NULL OR transaksi.no_meja!='') AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
            }
            
        $queryTrans = "SELECT table_name FROM information_schema.tables WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= "UNION ALL " ;
                    $query .= "SELECT `$transactions`.total, `$transactions`.promo, program_discount, `$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.charge_ur, `$transactions`.point, `$transactions`.tax FROM `$transactions`  WHERE `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=0 AND `$transactions`.pre_order_id=0 AND (`$transactions`.no_meja IS NOT NULL OR `$transactions`.no_meja!='') AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
                }
            }
            $trxDineIn = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxDineIn)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] )*(int) $row['service'] / 100);
              $service += $tempS;
              $charge_ur += (int) $row['charge_ur'];
              $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( double ) $row['tax'] / 100);
              $tax += $tempT;
              $i++;
            }
    
            $dineIn['subtotal'] = $subtotal;
            $dineIn['sales'] = $subtotal+$service+$tax+$charge_ur;
            $dineIn['promo'] = $promo;
            $dineIn['program_discount'] = $program_discount;
            $dineIn['diskon_spesial'] = $diskon_spesial;
            $dineIn['point'] = $point;
            $dineIn['clean_sales'] = $dineIn['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $dineIn['service'] = $service;
            $dineIn['charge_ur'] = $charge_ur;
            $dineIn['tax'] = $tax;
            $dineIn['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
            $test = $dineIn;
    
            if($all == "1") {
                $query = "SELECT transaksi.total, transaksi.promo, program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM transaksi JOIN partner p On p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.no_meja IS NOT NULL OR transaksi.no_meja!='') AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo'";
            } else {
                $query = "SELECT transaksi.total, transaksi.promo, program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
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
                    $query .= "SELECT `$transactions`.total, `$transactions`.promo, program_discount, `$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.charge_ur, `$transactions`.point, `$transactions`.tax FROM `$transactions`  WHERE `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
                }
            }
            $trxTakeAway = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxTakeAway)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount']- (int) $row['diskon_spesial'] - (int) $row['point'] )*(int) $row['service'] / 100);
              $service += $tempS;
              $charge_ur += (int) $row['charge_ur'];
              $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( double ) $row['tax'] / 100);
              $tax += $tempT;
              $i++;
            }
    
            $takeaway['subtotal'] = $subtotal;
            $takeaway['sales'] = $subtotal+$service+$tax+$charge_ur;
            $takeaway['promo'] = $promo;
            $takeaway['program_discount'] = $program_discount;
            $takeaway['diskon_spesial'] = $diskon_spesial;
            $takeaway['point'] = $point;
            $takeaway['clean_sales'] = $takeaway['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $takeaway['service'] = $service;
            $takeaway['charge_ur'] = $charge_ur;
            $takeaway['tax'] = $tax;
            $takeaway['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            if($all == "1") {
                $query = "SELECT transaksi.total, transaksi.promo, program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.pre_order_id!=0 AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo'";
            } else {
                $query = "SELECT transaksi.total, transaksi.promo, program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.pre_order_id!=0 AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
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
                    $query .= "SELECT `$transactions`.total, `$transactions`.promo, program_discount, `$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.charge_ur, `$transactions`.point, `$transactions`.tax FROM `$transactions`  WHERE `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.pre_order_id=!0 AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
                }
            }
            $trxPreorder = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxPreorder)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] )*(int) $row['service'] / 100);
              $service += $tempS;
              $charge_ur += (int) $row['charge_ur'];
              $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( double ) $row['tax'] / 100);
              $tax += $tempT;
              $i++;
            }
    
            $preorder['subtotal'] = $subtotal;
            $preorder['sales'] = $subtotal+$service+$tax+$charge_ur;
            $preorder['promo'] = $promo;
            $preorder['program_discount'] = $program_discount;
            $preorder['diskon_spesial'] = $diskon_spesial;
            $preorder['point'] = $point;
            $preorder['clean_sales'] = $preorder['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $preorder['service'] = $service;
            $preorder['charge_ur'] = $charge_ur;
            $preorder['tax'] = $tax;
            $preorder['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            if($all == "1") {
                $query = "SELECT t.total, t.promo, t.program_discount, t.diskon_spesial, t.service, t.charge_ur, t.point, t.tax, d.ongkir FROM transaksi t JOIN delivery d ON t.id= d.transaksi_id JOIN partner p ON p.id = t.id_partner WHERE p.id_master = '$idMaster' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2')";
            } else {
                $query = "SELECT total, promo, program_discount,diskon_spesial, service, charge_ur, point, tax, d.ongkir FROM transaksi t JOIN delivery d ON t.id= d.transaksi_id WHERE t.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (status='1' OR status='2' ) ";
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
                    $query .= "SELECT total, promo, program_discount,diskon_spesial, service, charge_ur, point, tax FROM `$transactions` t JOIN delivery d ON t.id= d.transaksi_id WHERE t.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (status='1' OR status='2' ) ";
                }
            }
            $trxDelivery = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            $ongkir = 0;
    
            while ($row = mysqli_fetch_assoc($trxDelivery)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] )*(int) $row['service'] / 100);
              $service += $tempS;
              $ongkir += (int) $row['ongkir'];
              $charge_ur += (int) $row['charge_ur'];
              $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( double ) $row['tax'] / 100);
              $tax += $tempT;
              $i++;
            }
    
            $delivery['subtotal'] = $subtotal;
            $delivery['sales'] = $subtotal+$service+$tax+$charge_ur;
            $delivery['promo'] = $promo;
            $delivery['program_discount'] = $program_discount;
            $delivery['diskon_spesial'] = $diskon_spesial;
            $delivery['point'] = $point;
            $delivery['clean_sales'] = $delivery['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $delivery['service'] = $service;
            $delivery['charge_ur'] = $charge_ur;
            $delivery['tax'] = $tax;
            $delivery['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax+$ongkir;
            $test = $delivery;
    
            // $dineIn['subtotal'] -= $subtotal;
            // $dineIn['sales'] -= $subtotal+$service+$tax+$charge_ur;
            // $dineIn['promo'] -= $promo;
            // $dineIn['program_discount'] -= $program_discount;
            // $dineIn['diskon_spesial'] -= $diskon_spesial;
            // $dineIn['point'] -= $point;
            // $dineIn['clean_sales'] -= $dineIn['sales']-$promo-$program_discount-$diskon_spesial-$point;
            // $dineIn['service'] -= $service;
            // $dineIn['charge_ur'] -= $charge_ur;
            // $dineIn['tax'] -= $tax;
            // $dineIn['total'] -= $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            $success = 1;
            $status = 200;
            $msg = "Success";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    }
    else{
        if($all == "1") {
            $query ="SELECT COUNT(transaksi.id) AS qty FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master = '$idMaster' AND takeaway=0 AND pre_order_id=0 AND transaksi.id NOT LIKE '%DL%' AND (no_meja IS NOT NULL OR no_meja!='') AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL";
        } else {
            $query ="SELECT COUNT(transaksi.id) AS qty
            FROM transaksi
            WHERE transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND
            transaksi.id_partner='$id' AND takeaway=0 AND 
            transaksi.id NOT LIKE '%DL%' AND
            pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND
            (status='1' OR status='2' ) AND
            transaksi.deleted_at IS NULL";
        }
        
        $queryTrans = "SELECT table_name FROM information_schema.tables WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        
        $transaksi = mysqli_query($db_conn, $queryTrans);
    
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                
                if($all == "1") {
                    $query .= "";
                } else {
                    $query .= "SELECT COUNT(`$transactions`.id) AS qty
                    FROM `$transactions`
                    WHERE `$transactions`.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND
                    `$transactions`.id_partner='$id' AND takeaway=0 AND
                    pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND
                    (status='1' OR status='2' ) AND
                    `$transactions`.deleted_at IS NULL ";
                }
            }
        }
        $sqlCountDineIn = mysqli_query($db_conn, $query);
    
        if($all == "1") {
            $query ="SELECT COUNT(transaksi.id) AS qty 
            FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE 
            transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND 
            p.id_master = '$idMaster' AND takeaway=1 AND pre_order_id=0 AND 
            (no_meja IS NOT NULL OR no_meja!='') AND (transaksi.status='1' 
            OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL";
        } else {
            $query =  "SELECT COUNT(transaksi.id) AS qty FROM transaksi
            WHERE transaksi.paid_date BETWEEN DATE?('$dateFrom') AND DATE('$dateTo') AND
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
                $query .= "SELECT COUNT(`$transactions`.id) AS qty FROM `$transactions`
                WHERE `$transactions`.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND
                `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') ";
            }
        }
        $sqlCountTakeaway = mysqli_query($db_conn, $query);
    
        if($all == "1") {
            $query = "SELECT COUNT(transaksi.id) AS qty FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND transaksi.pre_order_id !=0 AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL";
        } else {
            $query =  "SELECT COUNT(transaksi.id) AS qty FROM transaksi
            WHERE transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND
            transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND jam) BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND transaksi.pre_order_id !=0 AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL ";
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
                $query .= "SELECT COUNT(`$transactions`.id) AS qty FROM `$transactions`
                WHERE `$transactions`.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND
                `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND `$transactions`.pre_order_id !=0 AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL ";
            }
        }
        $sqlCountPreorder = mysqli_query($db_conn, $query);
    
        if($all == "1") {
            $query = "SELECT COUNT(d.id) AS qty FROM delivery d JOIN transaksi t ON t.id= d.transaksi_id JOIN partner p ON p.id = t.id_partner WHERE p.id_master = '$idMaster' AND d.deleted_at IS NULL AND t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL";
        } else {
            $query =  "SELECT COUNT(d.id) AS qty FROM delivery d JOIN transaksi t ON t.id= d.transaksi_id WHERE t.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL ";
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
                $query .= "SELECT COUNT(d.id) AS qty FROM delivery d JOIN `$transactions` t ON t.id= d.transaksi_id WHERE t.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL ";
            }
        }
        $sqlCountDelivery = mysqli_query($db_conn, $query);
    
        if(
            mysqli_num_rows($sqlCountDineIn) > 0 ||
            mysqli_num_rows($sqlCountTakeaway) > 0 ||
            mysqli_num_rows($sqlCountPreorder) > 0 ||
            mysqli_num_rows($sqlCountDelivery) > 0
            ) {
            $data1 = mysqli_fetch_all($sqlCountDineIn, MYSQLI_ASSOC);
            foreach ($data1 as $value) {
            //   $dineIn['qty']+=(int) $value['qty'];
              ($dineIn['qty'] ?? $dineIn['qty'] = 0) ? $dineIn['qty'] +=(int) $value['qty'] : $dineIn['qty'] = $value['qty'];
            }
            $data2 = mysqli_fetch_all($sqlCountTakeaway, MYSQLI_ASSOC);
            foreach ($data2 as $value) {
            //   $takeaway['qty']+=(int) $value['qty'];
              ($takeaway['qty'] ?? $takeaway['qty'] = 0) ? $takeaway['qty'] +=(int) $value['qty'] : $takeaway['qty'] = (int)$value['qty'];
            }
            $data3 = mysqli_fetch_all($sqlCountPreorder, MYSQLI_ASSOC);
            foreach ($data3 as $value) {
            //   $preorder['qty'] +=(int) $value['qty'];
                ($preorder['qty'] ?? $preorder['qty'] = 0) ? $preorder['qty'] +=(int) $value['qty'] : $preorder['qty'] = (int)$value['qty'];
            }
            $data4 = mysqli_fetch_all($sqlCountDelivery, MYSQLI_ASSOC);
            foreach ($data4 as $value) {
              $delivery['qty'] = (int) $value['qty'];
            }
            // $dineIn['qty']-=$delivery['qty'] ;
            $dineIn['qty'] = (int)$dineIn['qty'] ;
    
            if($all == "1") {
                $query = "SELECT transaksi.total, transaksi.promo, program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.id NOT LIKE '%DL%' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=0 AND transaksi.pre_order_id=0 AND (transaksi.no_meja IS NOT NULL OR transaksi.no_meja!='') AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo')";
            } else {
                $query = "SELECT transaksi.total, transaksi.promo, program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=0 AND transaksi.pre_order_id=0 AND (transaksi.no_meja IS NOT NULL OR transaksi.no_meja!='') AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') ";
            }
            
        $queryTrans = "SELECT table_name FROM information_schema.tables WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= "UNION ALL " ;
                    $query .= "SELECT `$transactions`.total, `$transactions`.promo, program_discount, `$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.charge_ur, `$transactions`.point, `$transactions`.tax FROM `$transactions`  WHERE `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=0 AND `$transactions`.pre_order_id=0 AND (`$transactions`.no_meja IS NOT NULL OR `$transactions`.no_meja!='') AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') ";
                }
            }
            $trxDineIn = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxDineIn)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] )*(int) $row['service'] / 100);
              $service += $tempS;
              $charge_ur += (int) $row['charge_ur'];
              $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( double ) $row['tax'] / 100);
              $tax += $tempT;
              $i++;
            }
    
            $dineIn['subtotal'] = $subtotal;
            $dineIn['sales'] = $subtotal+$service+$tax+$charge_ur;
            $dineIn['promo'] = $promo;
            $dineIn['program_discount'] = $program_discount;
            $dineIn['diskon_spesial'] = $diskon_spesial;
            $dineIn['point'] = $point;
            $dineIn['clean_sales'] = $dineIn['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $dineIn['service'] = $service;
            $dineIn['charge_ur'] = $charge_ur;
            $dineIn['tax'] = $tax;
            $dineIn['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
            $test = $dineIn;
    
            if($all == "1") {
                $query = "SELECT transaksi.total, transaksi.promo, program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM transaksi JOIN partner p On p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.no_meja IS NOT NULL OR transaksi.no_meja!='') AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo')";
            } else {
                $query = "SELECT transaksi.total, transaksi.promo, program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') ";
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
                    $query .= "SELECT `$transactions`.total, `$transactions`.promo, program_discount, `$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.charge_ur, `$transactions`.point, `$transactions`.tax FROM `$transactions`  WHERE `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') ";
                }
            }
            $trxTakeAway = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxTakeAway)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount']- (int) $row['diskon_spesial'] - (int) $row['point'] )*(int) $row['service'] / 100);
              $service += $tempS;
              $charge_ur += (int) $row['charge_ur'];
              $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( double ) $row['tax'] / 100);
              $tax += $tempT;
              $i++;
            }
    
            $takeaway['subtotal'] = $subtotal;
            $takeaway['sales'] = $subtotal+$service+$tax+$charge_ur;
            $takeaway['promo'] = $promo;
            $takeaway['program_discount'] = $program_discount;
            $takeaway['diskon_spesial'] = $diskon_spesial;
            $takeaway['point'] = $point;
            $takeaway['clean_sales'] = $takeaway['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $takeaway['service'] = $service;
            $takeaway['charge_ur'] = $charge_ur;
            $takeaway['tax'] = $tax;
            $takeaway['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            if($all == "1") {
                $query = "SELECT transaksi.total, transaksi.promo, program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.pre_order_id!=0 AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo')";
            } else {
                $query = "SELECT transaksi.total, transaksi.promo, program_discount, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.pre_order_id!=0 AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') ";
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
                    $query .= "SELECT `$transactions`.total, `$transactions`.promo, program_discount, `$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.charge_ur, `$transactions`.point, `$transactions`.tax FROM `$transactions`  WHERE `$transactions`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.pre_order_id=!0 AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') ";
                }
            }
            $trxPreorder = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxPreorder)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] )*(int) $row['service'] / 100);
              $service += $tempS;
              $charge_ur += (int) $row['charge_ur'];
              $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( double ) $row['tax'] / 100);
              $tax += $tempT;
              $i++;
            }
    
            $preorder['subtotal'] = $subtotal;
            $preorder['sales'] = $subtotal+$service+$tax+$charge_ur;
            $preorder['promo'] = $promo;
            $preorder['program_discount'] = $program_discount;
            $preorder['diskon_spesial'] = $diskon_spesial;
            $preorder['point'] = $point;
            $preorder['clean_sales'] = $preorder['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $preorder['service'] = $service;
            $preorder['charge_ur'] = $charge_ur;
            $preorder['tax'] = $tax;
            $preorder['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            if($all == "1") {
                $query = "SELECT t.total, t.promo, t.program_discount, t.diskon_spesial, t.service, t.charge_ur, t.point, t.tax, d.ongkir FROM transaksi t JOIN delivery d ON t.id= d.transaksi_id JOIN partner p ON p.id = t.id_partner WHERE p.id_master = '$idMaster' AND d.deleted_at IS NULL AND t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND (t.status='1' OR t.status='2')";
            } else {
                $query = "SELECT total, promo, program_discount,diskon_spesial, service, charge_ur, point, tax, d.ongkir FROM transaksi t JOIN delivery d ON t.id= d.transaksi_id WHERE t.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND (status='1' OR status='2' ) ";
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
                    $query .= "SELECT total, promo, program_discount,diskon_spesial, service, charge_ur, point, tax FROM `$transactions` t JOIN delivery d ON t.id= d.transaksi_id WHERE t.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND (status='1' OR status='2' ) ";
                }
            }
            $trxDelivery = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            $ongkir = 0;
    
            while ($row = mysqli_fetch_assoc($trxDelivery)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] )*(int) $row['service'] / 100);
              $service += $tempS;
              $ongkir += (int) $row['ongkir'];
              $charge_ur += (int) $row['charge_ur'];
              $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( double ) $row['tax'] / 100);
              $tax += $tempT;
              $i++;
            }
    
            $delivery['subtotal'] = $subtotal;
            $delivery['sales'] = $subtotal+$service+$tax+$charge_ur;
            $delivery['promo'] = $promo;
            $delivery['program_discount'] = $program_discount;
            $delivery['diskon_spesial'] = $diskon_spesial;
            $delivery['point'] = $point;
            $delivery['clean_sales'] = $delivery['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $delivery['service'] = $service;
            $delivery['charge_ur'] = $charge_ur;
            $delivery['tax'] = $tax;
            $delivery['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax+$ongkir;
            $test = $delivery;
    
            // $dineIn['subtotal'] -= $subtotal;
            // $dineIn['sales'] -= $subtotal+$service+$tax+$charge_ur;
            // $dineIn['promo'] -= $promo;
            // $dineIn['program_discount'] -= $program_discount;
            // $dineIn['diskon_spesial'] -= $diskon_spesial;
            // $dineIn['point'] -= $point;
            // $dineIn['clean_sales'] -= $dineIn['sales']-$promo-$program_discount-$diskon_spesial-$point;
            // $dineIn['service'] -= $service;
            // $dineIn['charge_ur'] -= $charge_ur;
            // $dineIn['tax'] -= $tax;
            // $dineIn['total'] -= $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
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
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "dineIn"=>$dineIn, "takeaway"=>$takeaway, "preorder"=>$preorder, "delivery"=>$delivery, "test"=>$test]);

?>