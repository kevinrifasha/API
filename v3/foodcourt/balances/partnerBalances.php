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

$id = $_GET['id'];
$dateTo = $_GET['dateTo'];
$dateFrom = $_GET['dateFrom'];
$values = [];
$tot = [];

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
    $transaksi = mysqli_query($db_conn, "SELECT transaksi.total, transaksi.tax, transaksi.service, transaksi.charge_ur, transaksi.promo, transaksi.program_discount, transaksi.point, transaksi.tipe_bayar, transaksi.diskon_spesial, transaksi.employee_discount FROM transaksi WHERE transaksi.id_partner='$id' AND (status=2 OR status=1) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY transaksi.tipe_bayar");
    $values = array();
    $tot=0;
    $sumCharge_ur=0;
    $sumPoint=0;
    
    while($row=mysqli_fetch_assoc($transaksi)){
        $countService=0;
        $withService=0;
        $countTax=0;
        
        $tax = $row['tax'];
        $service = $row['service'];
        $charge_ur = $row['charge_ur'];
        $total = $row['total'];
        $promo = $row['promo'];
        $program_discount = $row['program_discount'];
        $diskon_spesial = $row['diskon_spesial'];
        $employee_discount = $row['employee_discount'];
        $point = $row['point'];
        
        $sumPromo+=$promo;
        $sumProgram_discount+=$program_discount;
        $sumDiskonSpesial+=$diskon_spesial;
        $sumEmployeeDiscount+=$employee_discount;
        $sumPoint+=$point;
        $sumTotal+=$total;
        $sumCharge_ur+=$charge_ur;
        
        $countService = ceil((($total-$promo-$program_discount-$diskon_spesial-$employee_discount)*$service)/100);
        $sumService += $countService;
        $countTax = ceil(((($total-$promo-$program_discount-$diskon_spesial-$employee_discount)+$countService+$charge_ur)*$tax)/100) + $charge_ur;
        $sumTax += $countTax;
        $totTemp=$countService+$countTax+$total-$promo-$program_discount-$diskon_spesial-$employee_discount;
        $tot+=$totTemp;
        $values["value"]=$tot;
        $values["charge_ur"]=$sumCharge_ur;
        $values["point"]=$sumPoint;
        // array_push($values, array("value" => $tot, "prom" => $sumCharge_ur));; 
    }
    
    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
    while($row=mysqli_fetch_assoc($transaksi)){
        $table_name = explode("_",$row['table_name']);
        if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            $tableName = $row['table_name'];
            $transaksi = mysqli_query($db_conn, "SELECT `$tableName`.total, `$tableName`.tax, `$tableName`.service, `$tableName`.charge_ur, `$tableName`.promo, `$tableName`.program_discount, `$tableName`.point, `$tableName`.tipe_bayar, `$tableName`.diskon_spesial, `$tableName`.employee_discount FROM `$tableName` WHERE `$tableName`.id_partner='$id' AND (status=2 OR status=1) AND `$tableName`.deleted_at IS NULL AND DATE(`$tableName`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY `$tableName`.tipe_bayar");
            
            while($row=mysqli_fetch_assoc($transaksi)){
                $countService=0;
                $withService=0;
                $countTax=0;
                
                $tax = $row['tax'];
                $service = $row['service'];
                $charge_ur = $row['charge_ur'];
                $total = $row['total'];
                $promo = $row['promo'];
                $program_discount = $row['program_discount'];
                $diskon_spesial = $row['diskon_spesial'];
                $employee_discount = $row['employee_discount'];
                $point = $row['point'];
                
                $sumPromo+=$promo;
                $sumProgram_discount+=$program_discount;
                $sumDiskonSpesial+=$diskon_spesial;
                $sumEmployeeDiscount+=$employee_discount;
                $sumPoint+=$point;
                $sumTotal+=$total;
                $sumCharge_ur+=$charge_ur;
                
                $countService = ceil((($total-$promo-$program_discount-$diskon_spesial-$employee_discount)*$service)/100);
                $sumService += $countService;
                $countTax = ceil(((($total-$promo-$program_discount-$diskon_spesial-$employee_discount)+$countService+$charge_ur)*$tax)/100) + $charge_ur;
                $sumTax += $countTax;
                $totTemp=$countService+$countTax+$total-$promo-$program_discount-$diskon_spesial-$employee_discount;
                $tot+=$totTemp;
                $values["value"]=$tot;
                $values["charge_ur"]=$sumCharge_ur;
                $values["point"]=$sumPoint;
                // array_push($values, array("value" => $tot, "prom" => $sumCharge_ur));; 
            }
            
        }
    }

    $success=1;
    $status=200;
    $msg="Success";
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$values]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
    http_response_code(200);
}else{
    http_response_code($status);
    }
echo $signupJson;
