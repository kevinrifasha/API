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
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->masterID;
$value = array();
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    
    $array = [];

    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    if($newDateFormat == 1){
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $partner_id = $partner['partner_id'];
                $data = [];
                
                $query = "SELECT s.id, s.start, s.end, s.petty_cash, s.employee_id, s.actual_cash, s.partner_id, p.name AS name_partner FROM shift s JOIN partner p ON p.id = s.partner_id WHERE s.partner_id='$partner_id' AND s.deleted_at IS NULL AND s.created_at BETWEEN '$dateFrom' AND '$dateTo' ORDER BY id DESC";
                
                $q = mysqli_query($db_conn, $query);
            
                if (mysqli_num_rows($q) > 0) {
                    $vals = array();
                    $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                    $i = 0;
                    foreach ($res as $value) {
                        $empID = explode(",",$value['employee_id']);
                        $j = 0;
                        foreach($empID as $eID){
                            $qK = mysqli_query($db_conn, "SELECT nama as name FROM employees WHERE employees.id='$eID'");
                            $resK = mysqli_fetch_all($qK, MYSQLI_ASSOC);
                            $res[$i]['name'][$j] = $resK[0];
                            $j+=1;
                        }
                        $i+=1;
                    }
                    $type=1;
                    foreach ($res as $value) {
                        $sID = $value['id'];
                        $value['cash_income']=0;
                        $value['petty_cash']=ceil($value['petty_cash']);
            
                        $query = "SELECT SUM(transaksi.promo) AS promo, SUM(program_discount) AS program_discount,SUM(transaksi.diskon_spesial) AS diskon_spesial,SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+charge_ur)*transaksi.tax/100) AS tax,
                            SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet, payment_method.nama AS pmName FROM shift s JOIN transaksi ON transaksi.shift_id=s.id JOIN payment_method ON transaksi.tipe_bayar=payment_method.id WHERE s.id='$sID' AND  transaksi.status IN(1,2,5) AND transaksi.tipe_bayar=5 ";
            
                            $qPM = mysqli_query($db_conn, $query);
                    
                            $paymentMethodIncome = mysqli_fetch_all($qPM, MYSQLI_ASSOC);
            
                            foreach ($paymentMethodIncome as $valuePMI) {
                                $value['payment_method_income'][$i]['payment_method'] = $valuePMI['pmName'];
                                $value['payment_method_income'][$i]['income'] += ceil($valuePMI['total'])-ceil($valuePMI['promo']-ceil($valuePMI['diskon_spesial']))-ceil($valuePMI['employee_discount'])-ceil($valuePMI['point'])+ceil($valuePMI['service'])+ceil($valuePMI['tax'])+ceil($valuePMI['charge_ur']);
                                if($valuePMI['pmName']=="TUNAI"){
                                    $value['cash_income'] += ceil($valuePMI['total'])-ceil($valuePMI['promo'])-ceil($valuePMI['diskon_spesial'])-ceil($valuePMI['employee_discount'])-ceil($valuePMI['point'])+ceil($valuePMI['service'])+ceil($valuePMI['tax'])+ceil($valuePMI['charge_ur']);
                                }
            
                                $i+=1;
                            }
                            if($i == 0){
                                $value["payment_method_income"] = array();
                            }
                            
                        array_push($vals, $value);
                    }
                } else {
                    $vals = [];
                }
               
                $partner['shifts'] = $vals;
               
                if(count($vals) > 0)  {
                    array_push($array, $partner);
                }
            }
            
            $success =1;
            $status =200;
            $msg = "Success";
        } else {
            $success =0;
            $status =203;
            $msg = "Data not found";
        }        
    } 
    else 
    {
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $partner_id = $partner['partner_id'];
                $data = [];
                
                $query = "SELECT s.id, s.start, s.end, s.petty_cash, s.employee_id, s.actual_cash, s.partner_id, p.name AS name_partner FROM shift s JOIN partner p ON p.id = s.partner_id WHERE s.partner_id='$partner_id' AND s.deleted_at IS NULL AND DATE(s.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY id DESC";
                
                $q = mysqli_query($db_conn, $query);
            
                if (mysqli_num_rows($q) > 0) {
                    $vals = array();
                    $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                    $i = 0;
                    foreach ($res as $value) {
                        $empID = explode(",",$value['employee_id']);
                        $j = 0;
                        foreach($empID as $eID){
                            $qK = mysqli_query($db_conn, "SELECT nama as name FROM employees WHERE employees.id='$eID'");
                            $resK = mysqli_fetch_all($qK, MYSQLI_ASSOC);
                            $res[$i]['name'][$j] = $resK[0];
                            $j+=1;
                        }
                        $i+=1;
                    }
                    $type=1;
                    foreach ($res as $value) {
                        $sID = $value['id'];
                        $value['cash_income']=0;
                        $value['petty_cash']=ceil($value['petty_cash']);
            
                        $query = "SELECT SUM(transaksi.promo) AS promo, SUM(program_discount) AS program_discount,SUM(transaksi.diskon_spesial) AS diskon_spesial,SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+charge_ur)*transaksi.tax/100) AS tax,
                            SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet, payment_method.nama AS pmName FROM shift s JOIN transaksi ON transaksi.shift_id=s.id JOIN payment_method ON transaksi.tipe_bayar=payment_method.id WHERE s.id='$sID' AND  transaksi.status IN(1,2,5) AND transaksi.tipe_bayar=5 ";
            
                            $qPM = mysqli_query($db_conn, $query);
                    
                            $paymentMethodIncome = mysqli_fetch_all($qPM, MYSQLI_ASSOC);
            
                            foreach ($paymentMethodIncome as $valuePMI) {
                                $value['payment_method_income'][$i]['payment_method'] = $valuePMI['pmName'];
                                $value['payment_method_income'][$i]['income'] += ceil($valuePMI['total'])-ceil($valuePMI['promo']-ceil($valuePMI['diskon_spesial']))-ceil($valuePMI['employee_discount'])-ceil($valuePMI['point'])+ceil($valuePMI['service'])+ceil($valuePMI['tax'])+ceil($valuePMI['charge_ur']);
                                if($valuePMI['pmName']=="TUNAI"){
                                    $value['cash_income'] += ceil($valuePMI['total'])-ceil($valuePMI['promo'])-ceil($valuePMI['diskon_spesial'])-ceil($valuePMI['employee_discount'])-ceil($valuePMI['point'])+ceil($valuePMI['service'])+ceil($valuePMI['tax'])+ceil($valuePMI['charge_ur']);
                                }
            
                                $i+=1;
                            }
                            if($i == 0){
                                $value["payment_method_income"] = array();
                            }
                            
                        array_push($vals, $value);
                    }
                } else {
                    $vals = [];
                }
               
                $partner['shifts'] = $vals;
               
                if(count($vals) > 0)  {
                    array_push($array, $partner);
                }
            }
            
            $success =1;
            $status =200;
            $msg = "Success";
        } else {
            $success =0;
            $status =203;
            $msg = "Data not found";
        }
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "data"=>$array]);
?>
