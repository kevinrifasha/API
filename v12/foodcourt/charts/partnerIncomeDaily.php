<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../tokenModels/tokenManager.php"); 
require_once '../../../includes/CalculateFunctions.php';
require_once("../../connection.php");
require '../../../db_connection.php';
require  __DIR__ . '/../../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
$dotenv->load();

$id = $_GET['id'];
$dateTo = $_GET['dateTo'];
$dateFrom = $_GET['dateFrom'];

$newDateFormat = 0;

if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
    $dateTo = str_replace("%20"," ",$dateTo);
    $dateFrom = str_replace("%20"," ",$dateFrom);
    $newDateFormat = 1;
}


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
  if($newDateFormat == 1){
    $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point, 
    SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service, 
    SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax, 
    SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet, 
    SUM(charge_ur) AS charge_ur, created_at  FROM ( 
        SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur, transaksi.paid_date as created_at FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
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
             $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, `$transactions`.paid_date as created_at FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  `$detail_transactions`.id_transaksi ";
         }
     }
     $query .= ") as tmp GROUP BY created_at ";
    $transaksi = mysqli_query(
        $db_conn,
        $query
    );

    $j = 0;
    while ($row = mysqli_fetch_assoc($transaksi)) {
        $values[$j]=array();
        $values[$j]['value']=0;
        $values[$j]['value']+=ceil($row['total'])-ceil($row['promo'])-ceil($row['program_discount'])-ceil($row['diskon_spesial'])-ceil($row['employee_discount'])-ceil($row['point'])+ceil($row['service'])+ceil($row['tax'])+ceil($row['charge_ur']);
        $values[$j]['date'] = DATE('d-m-Y',strtotime($row['created_at']));
        $j+=1;
    }

    $success=1;
    $status=200;
    $msg="Success";
  } 
  else 
  {
    $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point, 
    SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service, 
    SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax, 
    SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet, 
    SUM(charge_ur) AS charge_ur, created_at  FROM ( 
        SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur, DATE(transaksi.paid_date) as created_at FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY transaksi.id ";
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
             $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur, DATE(`$transactions`.paid_date) as created_at FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY  `$detail_transactions`.id_transaksi ";
         }
     }
     $query .= ") as tmp GROUP BY created_at ";
    $transaksi = mysqli_query(
        $db_conn,
        $query
    );

    $j = 0;
    while ($row = mysqli_fetch_assoc($transaksi)) {
        $values[$j]=array();
        $values[$j]['value']=0;
        $values[$j]['value']+=ceil($row['total'])-ceil($row['promo'])-ceil($row['program_discount'])-ceil($row['diskon_spesial'])-ceil($row['employee_discount'])-ceil($row['point'])+ceil($row['service'])+ceil($row['tax'])+ceil($row['charge_ur']);
        $values[$j]['date'] = DATE('d-m-Y',strtotime($row['created_at']));
        $j+=1;
    }

    $success=1;
    $status=200;
    $msg="Success";
  }
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$values]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
?>