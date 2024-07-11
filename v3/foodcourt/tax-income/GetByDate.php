<?php

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

$tax = 0;
$charge_ur = 0;
$avgTax = 0;
$totalBeforeTax = 0;
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

    if($newDateFormat == 1){
        $query = "SELECT name, tipe_bayar, SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point, SUM(tax) as tax_percentage,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
        SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
        SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
        SUM(charge_ur) AS charge_ur, day  FROM (
            SELECT transaksi.tipe_bayar, transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur, DAYNAME(transaksi.paid_date) AS day ,payment_method.nama as name FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE (menu.id_partner='$id' OR transaksi.id_partner='$id') AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY detail_transaksi.id_transaksi ";
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
                $query .= " UNION ALL " ;
                $query .= "SELECT `$transactions`.tipe_bayar ,`$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, DAYNAME(`$transactions`.paid_date) AS day, payment_method.nama as name FROM `$transactions` JOIN payment_method ON `$transactions`.tipe_bayar=payment_method.id JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE (menu.id_partner='$id' OR `$transactions`.id_partner='$id') AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  `$detail_transactions`.id_transaksi ";
            }
        }
        $query .=") AS tmp GROUP BY tipe_bayar ";
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
                $tempS = (int) $row['service'];
                $service += $tempS;
                $charge_ur += (int) $row['charge_ur'];
                $totalBeforeTax += ( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] + $tempS +(int) $row['charge_ur']);
            $tempT = (double) $row['tax'];
            $tax += $tempT;
            $avgTax +=( int ) $row['tax_percentage'];
            $i++;
            }
            $avgTax = round($avgTax/$i , 2);

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
        $query = "SELECT name, tipe_bayar, SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point, SUM(tax) as tax_percentage,
    SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
    SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
    SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
    SUM(charge_ur) AS charge_ur, day  FROM (
        SELECT transaksi.tipe_bayar, transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur, DAYNAME(transaksi.paid_date) AS day ,payment_method.nama as name FROM transaksi JOIN payment_method ON transaksi.tipe_bayar=payment_method.id JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE (menu.id_partner='$id' OR transaksi.id_partner='$id') AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY detail_transaksi.id_transaksi ";
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
            $query .= " UNION ALL " ;
            $query .= "SELECT `$transactions`.tipe_bayar ,`$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, DAYNAME(`$transactions`.paid_date) AS day, payment_method.nama as name FROM `$transactions` JOIN payment_method ON `$transactions`.tipe_bayar=payment_method.id JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE (menu.id_partner='$id' OR `$transactions`.id_partner='$id') AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  `$detail_transactions`.id_transaksi ";
        }
    }
    $query .=") AS tmp GROUP BY tipe_bayar ";
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
            $tempS = (int) $row['service'];
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $totalBeforeTax += ( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] + $tempS +(int) $row['charge_ur']);
          $tempT = (double) $row['tax'];
          $tax += $tempT;
          $avgTax +=( int ) $row['tax_percentage'];
          $i++;
        }
        $avgTax = round($avgTax/$i , 2);

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
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "tax_percentage"=>$avgTax, "totalBeforeTax"=>$totalBeforeTax, "tax"=>$tax]);


?>