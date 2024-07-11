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

$newDateFormat = 0;

if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
    $dateTo = str_replace("%20"," ",$dateTo);
    $dateFrom = str_replace("%20"," ",$dateFrom);
    $newDateFormat = 1;
}

$values = [];
$tot = [];
$all = "0";

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
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->masterID;
$values = array();
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    if($all !== "1") {
        $idMaster = null;
    }
    
    $values[0]['value']=0;

    if($newDateFormat == 1){
      if($all == "1") {
        $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(charge_ur) AS charge_ur,SUM(point) AS point, SUM(service) AS service, SUM(tax) AS tax, SUM(charge_ewallet) AS charge_ewallet, created_at FROM ( SELECT SUM(transaksi.program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+`transaksi`.charge_ur)*transaksi.tax/100) AS tax, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet,  transaksi.paid_date AS created_at FROM transaksi JOIN partner ON partner.id = transaksi.id_partner WHERE partner.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY DATE(transaksi.paid_date) ";
      } else {
          $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(charge_ur) AS charge_ur,SUM(point) AS point, SUM(service) AS service, SUM(tax) AS tax,
          SUM(charge_ewallet) AS charge_ewallet, created_at FROM ( SELECT SUM(transaksi.program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+`transaksi`.charge_ur)*transaksi.tax/100) AS tax,
          SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet,  transaksi.paid_date AS created_at FROM transaksi JOIN partner ON partner.id = transaksi.id_partner WHERE id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY DATE(transaksi.paid_date) ";
      }
      $query .= ") as tmp GROUP BY created_at ";
      $transaksi = mysqli_query($db_conn,$query);

      $j = 0;
      while ($row = mysqli_fetch_assoc($transaksi)) {
          $values[$j]['value']+=ceil($row['total'])-ceil($row['promo'])-ceil($row['program_discount'])-ceil($row['diskon_spesial'])-ceil($row['employee_discount'])-ceil($row['point'])+ceil($row['service'])+ceil($row['tax'])+ceil($row['charge_ur']);
          $values[$j]['date'] = date('d-m-Y',strtotime($row['created_at']));
          $j+=1;

      }
      // if(mysqli_num_rows($transaksi) > 0) {
      //     $values = mysqli_fetch_all($transaksi, MYSQLI_ASSOC);
      // }

      $success=1;
      $status=200;
      $msg="Success";
    } 
    else 
    {
      if($all == "1") {
        $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(charge_ur) AS charge_ur,SUM(point) AS point, SUM(service) AS service, SUM(tax) AS tax, SUM(charge_ewallet) AS charge_ewallet, created_at FROM ( SELECT SUM(transaksi.program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+`transaksi`.charge_ur)*transaksi.tax/100) AS tax, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet,  DATE(transaksi.paid_date) AS created_at FROM transaksi JOIN partner ON partner.id = transaksi.id_partner WHERE partner.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.paid_date ";
      } else {
        $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(charge_ur) AS charge_ur,SUM(point) AS point, SUM(service) AS service, SUM(tax) AS tax,
        SUM(charge_ewallet) AS charge_ewallet, created_at FROM ( SELECT SUM(transaksi.program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+`transaksi`.charge_ur)*transaksi.tax/100) AS tax,
        SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet,  DATE(transaksi.paid_date) AS created_at FROM transaksi JOIN partner ON partner.id = transaksi.id_partner WHERE id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.paid_date ";
      }
      $query .= ") as tmp GROUP BY created_at ";
      $transaksi = mysqli_query($db_conn,$query);

      $j = 0;
      while ($row = mysqli_fetch_assoc($transaksi)) {
          $values[$j]['value']+=ceil($row['total'])-ceil($row['promo'])-ceil($row['program_discount'])-ceil($row['diskon_spesial'])-ceil($row['employee_discount'])-ceil($row['point'])+ceil($row['service'])+ceil($row['tax'])+ceil($row['charge_ur']);
          $values[$j]['date'] = date('d-m-Y',strtotime($row['created_at']));
          $j+=1;

      }
      // if(mysqli_num_rows($transaksi) > 0) {
      //     $values = mysqli_fetch_all($transaksi, MYSQLI_ASSOC);
      // }

      $success=1;
      $status=200;
      $msg="Success";
      }
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$values]);

echo $signupJson;

?>