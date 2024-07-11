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
$value = array();
$success=0;
$msg = 'Failed';
$all = "0";
$idMaster = $token->id_master;
$response = array();
$arrayDone = array();

$status = 200;
        $success=1;
        $msg="Success";
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $year = "";
    $res=array();
    $monthly = array();
    if(isset($_GET['year']) && !empty($_GET['year'])){
        $year = $_GET['year'];
    }else{
        $year = date('Y');
    }
    
    $specificMonth = $_GET['dateFrom']; // Replace with your desired month and year
    $firstDayOfMonth = date("Y-m-01", strtotime($specificMonth));
    
    $dateFrom = $firstDayOfMonth;
    $dateTo = $_GET['dateTo'];
    
    $getPartner = mysqli_query($db_conn,"SELECT id, name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL" );
    
    if(mysqli_num_rows($getPartner) > 0){
        
        $getPartner = mysqli_fetch_all($getPartner, MYSQLI_ASSOC);
        
        $l = 0;
        
        foreach($getPartner as $val){
            
            $id = $val["id"];
            $name = $val["name"];
        
            $addQuery = "id_partner='$id'";
        
            // $query = "SELECT bulan, SUM(program_discount) program_discount, SUM(promo) promo, SUM(diskon_spesial) diskon_spesial, SUM(employee_discount) employee_discount, SUM(total) total, SUM(charge_ur) charge_ur, SUM(point) point, SUM(service) service, SUM(tax) tax, SUM(charge_ewallet) charge_ewallet  FROM (  SELECT MONTH(transaksi.paid_date) as bulan, SUM(program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount ,SUM(transaksi.total) AS total, SUM(transaksi.point) AS point,  SUM(transaksi.charge_ur) AS charge_ur, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+transaksi.charge_ur)*transaksi.tax/100) AS tax,
            // SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet, MONTH(transaksi.paid_date) AS month FROM transaksi JOIN partner ON partner.id = transaksi.id_partner WHERE ". $addQuery ." AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND YEAR(paid_date)='$year' GROUP BY MONTH(transaksi.paid_date)  ) AS tmp GROUP BY  bulan";
            
            $query = "SELECT bulan, tahun, SUM(program_discount) program_discount, SUM(promo) promo, SUM(diskon_spesial) diskon_spesial, SUM(employee_discount) employee_discount, SUM(total) total, SUM(charge_ur) charge_ur, SUM(point) point, SUM(service) service, SUM(tax) tax, SUM(charge_ewallet) charge_ewallet  FROM (  SELECT MONTH(transaksi.paid_date) as bulan,YEAR(transaksi.paid_date) as tahun, SUM(program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount ,SUM(transaksi.total) AS total, SUM(transaksi.point) AS point,  SUM(transaksi.charge_ur) AS charge_ur, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+transaksi.charge_ur)*transaksi.tax/100) AS tax, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet, MONTH(transaksi.paid_date) AS month FROM transaksi JOIN partner ON partner.id = transaksi.id_partner WHERE ". $addQuery ." AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 GROUP BY MONTH(transaksi.paid_date), YEAR(transaksi.paid_date)) AS tmp GROUP BY tahun, bulan";
     
            $transaksi = mysqli_query(
                $db_conn,
                $query
            );
        
            $months = mysqli_fetch_all($transaksi, MYSQLI_ASSOC);
        
            // while ($row = mysqli_fetch_assoc($transaksi)) {
            // $monthly[$row['bulan']-1]['value']=ceil($row['total'])-ceil($row['promo'])-ceil($row['diskon_spesial']-$row['program_discount'])-ceil($row['employee_discount'])-ceil($row['point'])+ceil($row['service'])+ceil($row['tax'])+ceil($row['charge_ur']);
            // }
        
        
            // for ($k = 0; $k < 12; $k++) {
            //     $monthNum = $monthly[$k]['bulan'];
            //     $monthly[$k]['bulan'] = date('M', mktime(0, 0, 0, $monthNum, 10));
            // }
    
            if(count($months) > 0){
                foreach($months as $month){
                    $value = ceil($month['total'])-ceil($month['promo'])-ceil($month['diskon_spesial']-$month['program_discount'])-ceil($month['employee_discount'])-ceil($month['point'])+ceil($month['service'])+ceil($month['tax'])+ceil($month['charge_ur']);
                    
                    $bulan = date('M', mktime(0, 0, 0, $month["bulan"], 10));
                    
                    if($month["bulan"] != null){
                        array_push($monthly, array("bulan" => $bulan . " " . $month["tahun"] ,"tahun" => $month["tahun"], "value" => $value )); 
                        }
                }
            } else {
                array_push($monthly, array("bulan" => "Tidak Ada Data" ,"tahun" => "Tidak Ada Data", "value" => 0 ));
            }
    
    
            
            $arrayDone["id_partner"] = $id;
            $arrayDone["name"] = $name;
            $arrayDone["monthlyIncome"] = $monthly;
        
            $response[$l] = $arrayDone;
        
            $l++;
        
            $arrayDone = array();
            $monthly = array();
            
        }
        
        $status = 200;
        $success=1;
        $msg="Success";
    } else {
        $status = 204;
        $success=0;
        $msg="Data tidak ditemukan";
    }
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$response]);
