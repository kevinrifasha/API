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
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $id = $_GET['ID'];
    $partner_id = $_GET['partner_id'];
    $q = mysqli_query($db_conn, "SELECT s.id, s.start, s.end, s.petty_cash, s.employee_id, s.actual_cash FROM shift s WHERE s.partner_id='$partner_id' AND s.id='$id' AND s.deleted_at IS NULL ORDER BY id DESC");

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
            // if($value['end']==null){
                // $qT = mysqli_query($db_conn, "SELECT SUM(transaksi.promo) AS promo, SUM(program_discount) AS program_discount, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+charge_ur)*transaksi.tax/100) AS tax,
                // SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet FROM shift s JOIN transaksi ON transaksi.shift_id=s.id WHERE s.id='$sID' AND transaksi.deleted_at IS NULL AND (transaksi.status=1 OR transaksi.status=2 ) ");

                $query = "SELECT t.*, pm.nama AS paymentName, t.paid_date AS finish_date FROM transaksi t JOIN payment_method pm ON pm.id=t.tipe_bayar  WHERE t.shift_id='$sID' AND t.deleted_at IS NULL AND t.organization='Natta' AND (t.status=1 OR t.status=2 OR t.status=7) ORDER BY jam";
                $qT1 = mysqli_query($db_conn, $query);

                $query = "SELECT SUM(transaksi.promo) AS promo, SUM(program_discount) AS program_discount, SUM(transaksi.rounding) AS rounding, SUM(transaksi.diskon_spesial) AS diskon_spesial,SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+charge_ur)*transaksi.tax/100) AS tax,
                SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet, payment_method.nama AS pmName, transaksi.tipe_bayar, SUM(transaksi.dp_total) AS dp_total FROM shift s JOIN transaksi ON transaksi.shift_id=s.id JOIN payment_method ON transaksi.tipe_bayar=payment_method.id WHERE s.id='$sID' AND  transaksi.status IN(1,2,5,7) GROUP BY transaksi.tipe_bayar ORDER BY tipe_bayar ASC";
                $qPM = mysqli_query($db_conn, $query); 

                $query = "SELECT SUM(detail_transaksi.qty) AS qty, menu.nama, detail_transaksi.harga_satuan FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.shift_id='$sID' AND transaksi.deleted_at IS NULL AND transaksi.organization='Natta' AND transaksi.status IN(1,2,5,7) AND detail_transaksi.deleted_at IS NULL GROUP BY detail_transaksi.id_menu ";


                $qMS = mysqli_query($db_conn, $query);
                $qST = mysqli_query($db_conn, "SELECT `id`, `type`, `amount`, `description` FROM `shift_transactions` WHERE `shift_id`='$sID' AND deleted_at IS NULL");
                // $qT = mysqli_query($db_conn, "SELECT `id`, `type`, `amount`, `description` FROM `shift_transactions` WHERE `shift_id`='$sID' AND deleted_at IS NULL");
                // $income = mysqli_fetch_all($qT, MYSQLI_ASSOC);
                $income1 = mysqli_fetch_all($qT1, MYSQLI_ASSOC);
                $paymentMethodIncome = mysqli_fetch_all($qPM, MYSQLI_ASSOC);
                $shiftTransactions = mysqli_fetch_all($qST, MYSQLI_ASSOC);
                $value['menus'] = mysqli_fetch_all($qMS, MYSQLI_ASSOC);
                $i=0;
                // $value['income'] += ceil($income[0]['total'])-ceil($income[0]['promo'])-ceil($income[0]['point'])+ceil($income[0]['service'])+ceil($income[0]['tax'])-ceil($income[0]['charge_ur']);
                $value['transaction']=$income1;
                $tempPM = "";
                foreach ($paymentMethodIncome as $valuePMI) {
                    if($tempPM!= "" && $tempPM!=$valuePMI['pmName']){
                        $i+=1;
                    }
                    $value['payment_method_income'][$i]['pmID'] = $valuePMI['tipe_bayar'];
                    $value['payment_method_income'][$i]['payment_method'] = $valuePMI['pmName'];
                    $value['payment_method_income'][$i]['income'] += ceil($valuePMI['total'])-ceil($valuePMI['promo'])-ceil($valuePMI['program_discount'])-ceil($valuePMI['diskon_spesial'])-ceil($valuePMI['employee_discount'])-ceil($valuePMI['point'])+round($valuePMI['service'])+round($valuePMI['tax'])+ceil($valuePMI['charge_ur'])-ceil($valuePMI['dp_total'])+(int)$valuePMI['rounding']; 
                    if($valuePMI['pmName']=="TUNAI"){
                        $value['cash_income'] += ceil($valuePMI['total'])-ceil($valuePMI['promo'])-ceil($valuePMI['program_discount'])-ceil($valuePMI['diskon_spesial'])-ceil($valuePMI['employee_discount'])-ceil($valuePMI['point'])+round($valuePMI['service'])+round($valuePMI['tax'])+ceil($valuePMI['charge_ur'])-ceil($valuePMI['dp_total'])+(int)$valuePMI['rounding'];
                    }
                    $tempPM=$valuePMI['pmName'];
                }
                if($i == 0 && count($paymentMethodIncome) < 1){
                    $value["payment_method_income"] = array();
                }
                $j=0;
                foreach ($shiftTransactions as $valueST) {
                    $value['shift_transactions'][$j] = $valueST;
                    $value['shift_transactions'][$j]['amount'] = $valueST['amount'];
                    $j+=1;
                }
                if($j == 0){
                    $value["shift_transaction"] = array();
                }
                $dp = mysqli_query($db_conn, "SELECT SUM(dp.amount) AS total, pm.nama AS name FROM down_payments dp JOIN payment_method pm ON pm.id=dp.payment_method_id WHERE dp.shift_id='$sID' AND dp.partner_id='$tokenDecoded->partnerID' AND dp.deleted_at IS NULL GROUP BY dp.payment_method_id");
                if(mysqli_num_rows($dp)>0){
                    $resDP = mysqli_fetch_all($dp, MYSQLI_ASSOC);
                    foreach($resDP as $x){
                        $value['dpTotal'] += $x['total'];
                    }
                    $value["dp"]=$resDP;
                }else{
                    $value["dp"]=[];
                    $value['dpTotal'] = 0;
                }
            // }
            array_push($vals, $value);
        }
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "type"=>$type,"shifts"=>$vals]);
?>
