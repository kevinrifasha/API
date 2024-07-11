<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();


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

$tokenizer = new Token();
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$values = array();
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $id =$token->id_partner;
    $year = $_GET['year'];
    $monthly = array();
    $query = "SELECT bulan, SUM(program_discount) program_discount, SUM(promo) promo, SUM(diskon_spesial) diskon_spesial, SUM(employee_discount) employee_discount, SUM(total) total, SUM(charge_ur) charge_ur, SUM(point) point, SUM(service) service, SUM(tax) tax, SUM(charge_ewallet) charge_ewallet  FROM (  SELECT MONTH(transaksi.paid_date) as bulan, SUM(program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount ,SUM(transaksi.total) AS total, SUM(transaksi.point) AS point,  SUM(transaksi.charge_ur) AS charge_ur, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+transaksi.charge_ur)*transaksi.tax/100) AS tax, 
    SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet, MONTH(transaksi.paid_date) AS month FROM transaksi JOIN partner ON partner.id = transaksi.id_partner WHERE id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND YEAR(paid_date)='$year' GROUP BY MONTH(transaksi.paid_date) ";
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
    while($row=mysqli_fetch_assoc($transaksi)){
        $table_name = explode("_",$row['table_name']);
        $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            $query .= "UNION ALL " ;
            $query .= "SELECT MONTH(`$transactions`.paid_date) as bulan,SUM(program_discount) AS program_discount, SUM(`$transactions`.promo) AS promo, SUM(`$transactions`.diskon_spesial) AS diskon_spesial, SUM(`$transactions`.employee_discount) AS employee_discount ,SUM(`$transactions`.total) AS total, SUM(`$transactions`.point) AS point,  SUM(`$transactions`.charge_ur) AS charge_ur, SUM((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.service/100) AS service, SUM((((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.service/100)+`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point+`$transactions`.charge_ur)*`$transactions`.tax/100) AS tax, 
            SUM((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point+((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.service/100)+((((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.service/100)+`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.tax/100))*`$transactions`.charge_ewallet/100) AS charge_ewallet, MONTH(`$transactions`.paid_date) AS month FROM `$transactions` JOIN partner ON partner.id = `$transactions`.id_partner WHERE id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1 AND YEAR(paid_date)='$year' GROUP BY MONTH(`$transactions`.paid_date)  ";
        }
    }
    $query .= " ) AS tmp GROUP BY  bulan";
    $transaksi = mysqli_query(
        $db_conn,
        $query
    );


    for ($i = 1; $i <= 12; $i++) {
        array_push($monthly, array("bulan" => $i, "value" => 0));
    }

    while ($row = mysqli_fetch_assoc($transaksi)) {
        $monthly[$row['bulan']-1]['value']=ceil($row['total'])-ceil($row['promo'])-ceil($row['diskon_spesial']-$row['program_discount'])-ceil($row['employee_discount'])-ceil($row['point'])+ceil($row['service'])+ceil($row['tax'])+ceil($row['charge_ur']);
    }

    for ($k = 0; $k < 12; $k++) {
        $monthNum = $monthly[$k]['bulan'];
        $monthly[$k]['bulan'] = date('F', mktime(0, 0, 0, $monthNum, 10));
    }
    $values=$monthly;

    $success=1;
    $status=200;
    $msg="Success";
}
http_response_code($status);
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$values]);  
// Echo the message.
echo $signupJson;
