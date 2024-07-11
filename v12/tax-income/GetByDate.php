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

$tax = 0;
$charge_ur = 0;
$avgTax = 0;
$totalBeforeTax = 0;
$all = "0";
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

    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    $query = "";
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }


    if($newDateFormat == 1){
        
        if($all !== "1") {
        $query = "SELECT transaksi.total, transaksi.promo, program_discount,transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM `transaksi` WHERE transaksi.id_partner='$id' AND (status='1' OR status='2' ) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL ";
        } else {
            $query = "SELECT transaksi.total, transaksi.promo, program_discount,transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM `transaksi` JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL ";
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
                $query .= "SELECT `$transactions`.total, `$transactions`.promo, program_discount,`$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.charge_ur, `$transactions`.point, `$transactions`.tax FROM `$transactions`
                WHERE `$transactions`.id_partner='$id' AND (status='1' OR status='2' ) AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $sql = mysqli_query($db_conn, $query);
    
        if(mysqli_num_rows($sql) > 0) {
            $i=0;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $employee_discount = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            $avgTax = 0;
            $totalBeforeTax = 0;
    
            while ($row = mysqli_fetch_assoc($sql)) {
                $subtotal += (int) $row['total'];
                $promo += (int) $row['promo'];
                $program_discount += (int) $row['program_discount'];
                $diskon_spesial += (int) $row['diskon_spesial'];
                $employee_discount += (int) $row['employee_discount'];
                $point += (int) $row['point'];
                $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] )*(int) $row['service'] / 100);
                $service += $tempS;
                $charge_ur += (int) $row['charge_ur'];
                $totalBeforeTax += ( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] + $tempS +(int) $row['charge_ur']);
              $tempT = ceil(( (int) $row['total'] - (int) $row['promo']  - (int) $row['program_discount']- (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] + $tempS +(int) $row['charge_ur']) * ( double ) $row['tax'] / 100);
              $tax += $tempT;
              $avgTax +=( double ) $row['tax'];
              $i++;
            }
            $avgTax = $avgTax/$i;
    
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal+$service+$tax+$charge_ur;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales']-$promo-$program_discount-$diskon_spesial-$employee_discount-$point;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$employee_discount-$point+$service+$charge_ur+$tax;
    
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
        if($all !== "1") {
        $query = "SELECT transaksi.total, transaksi.promo, program_discount,transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM `transaksi` WHERE transaksi.id_partner='$id' AND (status='1' OR status='2' ) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL ";
        } else {
            $query = "SELECT transaksi.total, transaksi.promo, program_discount,transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax FROM `transaksi` JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND (transaksi.status='1' OR transaksi.status='2' ) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL ";
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
                $query .= "SELECT `$transactions`.total, `$transactions`.promo, program_discount,`$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.service, `$transactions`.charge_ur, `$transactions`.point, `$transactions`.tax FROM `$transactions`
                WHERE `$transactions`.id_partner='$id' AND (status='1' OR status='2' ) AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $sql = mysqli_query($db_conn, $query);
    
        if(mysqli_num_rows($sql) > 0) {
            $i=0;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $employee_discount = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
            $avgTax = 0;
            $totalBeforeTax = 0;
    
            while ($row = mysqli_fetch_assoc($sql)) {
                $subtotal += (int) $row['total'];
                $promo += (int) $row['promo'];
                $program_discount += (int) $row['program_discount'];
                $diskon_spesial += (int) $row['diskon_spesial'];
                $employee_discount += (int) $row['employee_discount'];
                $point += (int) $row['point'];
                $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] )*(int) $row['service'] / 100);
                $service += $tempS;
                $charge_ur += (int) $row['charge_ur'];
                $totalBeforeTax += ( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] + $tempS +(int) $row['charge_ur']);
              $tempT = ceil(( (int) $row['total'] - (int) $row['promo']  - (int) $row['program_discount']- (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] + $tempS +(int) $row['charge_ur']) * ( double ) $row['tax'] / 100);
              $tax += $tempT;
              $avgTax +=( double ) $row['tax'];
              $i++;
            }
            $avgTax = $avgTax/$i;
    
            $result['subtotal'] = $subtotal;
            $result['sales'] = $subtotal+$service+$tax+$charge_ur;
            $result['promo'] = $promo;
            $result['program_discount'] = $program_discount;
            $result['diskon_spesial'] = $diskon_spesial;
            $result['employee_discount'] = $employee_discount;
            $result['point'] = $point;
            $result['clean_sales'] = $result['sales']-$promo-$program_discount-$diskon_spesial-$employee_discount-$point;
            $result['service'] = $service;
            $result['charge_ur'] = $charge_ur;
            $result['tax'] = $tax;
            $result['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$employee_discount-$point+$service+$charge_ur+$tax;
    
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

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "tax_percentage"=>$avgTax, "totalBeforeTax"=>$totalBeforeTax, "tax"=>$tax]);


?>