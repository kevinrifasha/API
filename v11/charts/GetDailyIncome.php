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
$idMaster = $token->id_master;
$value = array();
$success=0;
$msg = 'Failed';
$all = "0";

$status = 200;
        $success=1;
        $msg="Success";
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $id = $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $id = $_GET['partnerID']; 
    }
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    $res=array();
    $monthly = array();
    if(isset($_GET['year']) && !empty($_GET['year'])){
        $year = $_GET['year'];
    }else{
        $year = date('Y');
    }
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($all == "1") {
        $addQuery = "partner.id_master='$idMaster'";
    } else {
        $addQuery = "id_partner='$id'";
    }

    $query = "SELECT SUM(program_discount) program_discount, SUM(promo) promo, SUM(diskon_spesial) diskon_spesial, SUM(employee_discount) employee_discount, SUM(total) total, SUM(charge_ur) charge_ur, SUM(point) point, SUM(service) service, SUM(tax) tax, SUM(charge_ewallet) charge_ewallet, created_at FROM ( SELECT SUM(transaksi.program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+transaksi.charge_ur)*transaksi.tax/100) AS tax,
    SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet,  DATE(transaksi.paid_date) AS created_at FROM transaksi JOIN partner ON partner.id = transaksi.id_partner
     WHERE ". $addQuery ." AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY created_at  ) AS tmp GROUP BY created_at";
    $daily = array();
    $transaksi = mysqli_query(
        $db_conn,
        $query
    );

    $days = mysqli_fetch_all($transaksi, MYSQLI_ASSOC); 



    // for ($i = 1; $i <= 12; $i++) {
    //     array_push($monthly, array("bulan" => $i, "value" => 0));
    // }
    
    if(mysqli_num_rows($transaksi)>0){
        $status = 200;
        $success=1;
        $msg="Success";
        
        $j = 0;
        if(count($days) < 16){
            foreach($days as $day){
                $value = ceil($day['total'])-ceil($day['promo'])-ceil($day['diskon_spesial'])-ceil($day['program_discount'])-ceil($day['employee_discount'])-ceil($day['point'])+ceil($day['service'])+ceil($day['tax'])+ceil($day['charge_ur']);
                    
                    $expCur = explode("-",$day["created_at"]);
                    $curDate = date('d/m/y', mktime(0, 0, 0, $expCur[1], $expCur[2], $expCur[0]));
                        
                    array_push($daily, array("date" => $curDate , "value" => $value ));
            }
        }
        
        $value = 0;
        $prevDate = "";
        $divider = ceil(count($days)/15);
        if(count($days) >= 16){
            foreach($days as $day){
                if(($j+1) % $divider == 0){
                    $value += ceil($day['total'])-ceil($day['promo'])-ceil($day['diskon_spesial'])-ceil($day['program_discount'])-ceil($day['employee_discount'])-ceil($day['point'])+ceil($day['service'])+ceil($day['tax'])+ceil($day['charge_ur']);
                    
                    $expPrev = explode("-",$prevDate);
                    $prevDate = date('d/m/y', mktime(0, 0, 0, $expPrev[1], $expPrev[2], $expPrev[0]));
                    $expCur = explode("-",$day["created_at"]);
                    $curDate = date('d/m/y', mktime(0, 0, 0, $expCur[1], $expCur[2], $expCur[0]));
                    
                    array_push($daily, array("date" => $prevDate . "-" . $curDate , "value" => $value ));
                    
                    $value = 0;
                } else if(($j+1) % $divider == 1){
                    $prevDate = $day["created_at"];
                    $value += ceil($day['total'])-ceil($day['promo'])-ceil($day['diskon_spesial'])-ceil($day['program_discount'])-ceil($day['employee_discount'])-ceil($day['point'])+ceil($day['service'])+ceil($day['tax'])+ceil($day['charge_ur']);
                    if($j+1 == count($days)){
                        $expPrev = explode("-",$prevDate);
                        $prevDate = date('d/m/y', mktime(0, 0, 0, $expPrev[1], $expPrev[2], $expPrev[0]));
                        $expCur = explode("-",$day["created_at"]);
                        $curDate = date('d/m/y', mktime(0, 0, 0, $expCur[1], $expCur[2], $expCur[0]));
                        
                        array_push($daily, array("date" => $curDate , "value" => $value ));
                    
                        $value = 0;
                    }
                } else{
                    $value += ceil($day['total'])-ceil($day['promo'])-ceil($day['diskon_spesial'])-ceil($day['program_discount'])-ceil($day['employee_discount'])-ceil($day['point'])+ceil($day['service'])+ceil($day['tax'])+ceil($day['charge_ur']);
                        if($j+1 == count($days)){
                        $expPrev = explode("-",$prevDate);
                        $prevDate = date('d/m/y', mktime(0, 0, 0, $expPrev[1], $expPrev[2], $expPrev[0]));
                        $expCur = explode("-",$day["created_at"]);
                        $curDate = date('d/m/y', mktime(0, 0, 0, $expCur[1], $expCur[2], $expCur[0]));
                        
                        array_push($daily, array("date" => $prevDate . "-" . $curDate , "value" => $value ));
                    
                        $value = 0;
                    }
                }
                $j++;
            }
        }
    }else{
        $status = 204;
        $success=0;
        $msg="Data tidak ditemukan";
    }
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "dailyIncome"=>$daily, "test"=>$days]);
