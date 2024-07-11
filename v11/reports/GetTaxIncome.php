<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

//init var
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
$data = [];
$totalVoucher = 0;

$tax = 0;
$charge_ur = 0;
$avgTax = 0;
$totalBeforeTax = 0;
$all = "0";
//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $id = $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $id = $_GET['partnerID']; 
    }
    
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($all == "1") {
        $addQuery1 = "p.id_master='$idMaster'";
        $addQuery2 = "JOIN partner p ON p.id = transaksi.id_partner";
    } else {
        $addQuery1 = "transaksi.id_partner='$id'";
        $addQuery2 = "";
    }
    
    // $query =  "SELECT total, promo, diskon_spesial, employee_discount, service, charge_ur, point, tax, program_discount, charge_ur FROM ( SELECT transaksi.total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.program_discount FROM `transaksi` ". $addQuery2 ." WHERE ". $addQuery1 ." AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.tax!=0 ) AS tmp";
    $query =  "SELECT total, promo, diskon_spesial, employee_discount, service, charge_ur, point, tax, program_discount, charge_ur FROM ( SELECT transaksi.total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.program_discount FROM `transaksi` ". $addQuery2 ." WHERE ". $addQuery1 ." AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo') AS tmp";

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
            // $program_discount += (int) $row['program_discount'];
            ($program_discount ?? $program_discount = 0) ? $program_discount += (int) $row['program_discount'] : $program_discount = (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']  - (int) $row['employee_discount'] - (int) $row['point'] )*(int) $row['service'] / 100);
            $service += $tempS;
            // $charge_ur += (int) $row['charge _ur'];
            ($charge_ur ?? $charge_ur = 0) ? $charge_ur += (int) $row['charge_ur'] : $charge_ur = (int) $row['charge_ur'];
            $totalBeforeTax += ( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']  - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']);
          $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']  - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( double ) $row['tax'] / 100);
          $tax += $tempT;
          $avgTax +=( double ) $row['tax'];
          $i++;
        }
        $avgTax = round($avgTax/$i, 2);

        // $result['subtotal'] = $subtotal;
        // $result['sales'] = $subtotal+$service+$tax+$charge_ur;
        // $result['promo'] = $promo;
        // $result['program_discount'] = $program_discount;
        // $result['diskon_spesial'] = $diskon_spesial;
        // $result['employee_discount'] = $employee_discount;
        // $result['point'] = $point;
        // $result['clean_sales'] = $result['sales']-$promo-$program_discount-$diskon_spesial-$employee_discount-$point;
        // $result['service'] = $service;
        // $result['charge_ur'] = $charge_ur;
        // $result['tax'] = $tax;
        // $result['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$employee_discount-$point+$service+$charge_ur+$tax;

        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 200;
        $msg = "Data Not Found";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "tax_percentage"=>$avgTax, "totalBeforeTax"=>$totalBeforeTax, "tax"=>$tax]);

?>