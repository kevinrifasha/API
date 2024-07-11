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
$test = "";

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
    $avgService = 0;
    $totalBeforeService = 0;
    $service = 0;
    
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

    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);
    
    // $query =  "SELECT total, promo, diskon_spesial, employee_discount, service, charge_ur, point, tax, program_discount, surcharge_percent FROM ( SELECT transaksi.total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.program_discount, transaksi.surcharge_percent FROM `transaksi` ". $addQuery2 ." WHERE ". $addQuery1 ." AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.service!=0) AS tmp ";
    $query =  "SELECT total, promo, diskon_spesial, employee_discount, service, charge_ur, point, tax, program_discount, surcharge_percent FROM ( SELECT transaksi.total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.program_discount, transaksi.surcharge_percent FROM `transaksi` ". $addQuery2 ." WHERE ". $addQuery1 ." AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo') AS tmp ";

    $sql = mysqli_query($db_conn, $query);

    if(mysqli_num_rows($sql) > 0) {
        $i=0;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $employee_discount = 0;
        $point = 0;
        $tax = 0;
        $charge_ur = 0;

        while ($row = mysqli_fetch_assoc($sql)) {
            $subtotal += (int) $row['total'];
            $promo += (int) $row['promo'];
            $program_discount += (int) $row['program_discount'];
            $diskon_spesial += (int) $row['diskon_spesial'];
            $employee_discount += (int) $row['employee_discount'];
            $point += (int) $row['point'];
            $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] )*(int) $row['service'] / 100);
            $totalBeforeService += ( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] );
            $avgService +=( int ) $row['service'];
            $service += $tempS;
            $charge_ur += (int) $row['charge_ur'];
            $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial']- (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $charge_ur) * ( double ) $row['tax'] / 100);
          $tax += $tempT;
          $i++;
        }
        $avgService = round($avgService/$i, 2);

        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 203;
        $msg = "Data Not Found";
    }
    
    $sqlTest = mysqli_query($db_conn, $query);
    $test = mysqli_fetch_all($sqlTest, MYSQLI_ASSOC);

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "service_percentage"=>$avgService, "totalBeforeService"=>$totalBeforeService, "service"=>$service, "test"=>$test]);
