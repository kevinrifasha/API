<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

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

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$vals = array();
$vals[0]['id']="";
$vals[0]['start']="";
$vals[0]['end']="";
$vals[0]['petty_cash']=0;
$vals[0]['name']="";
$vals[0]['cash_income']=0;
$vals[0]['delivery_fee']=0;
$vals[0]['menus']=array();
$vals[0]['payment_method_income']=array();
$vals[0]['shift_transactions']=array();
$today = date("Y-m-d");
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$type=0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $vals = array();
    $value = array();
    $value['date']=$today;
    $dateTo = $today;
    $dateFrom = $today;
    $index = 0;
    $query = "SELECT transaksi.tipe_bayar, SUM(transaksi.program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.point) AS point, SUM( ( transaksi.total - transaksi.promo - transaksi.program_discount - transaksi.diskon_spesial - employee_discount - transaksi.point )* transaksi.service / 100 ) AS service, SUM( ( ( ( transaksi.total - transaksi.promo - transaksi.program_discount - transaksi.diskon_spesial - employee_discount - transaksi.point )* transaksi.service / 100 )+ transaksi.total - transaksi.promo - transaksi.program_discount - transaksi.diskon_spesial - employee_discount - transaksi.point + transaksi.charge_ur )* transaksi.tax / 100 ) AS tax, SUM( ( transaksi.total - transaksi.promo - transaksi.program_discount - transaksi.diskon_spesial - employee_discount - transaksi.point +( ( transaksi.total - transaksi.promo - transaksi.program_discount - transaksi.diskon_spesial - employee_discount - transaksi.point )* transaksi.service / 100 )+( ( ( ( transaksi.total - transaksi.promo - transaksi.program_discount - transaksi.diskon_spesial - employee_discount - transaksi.point )* transaksi.service / 100 )+ transaksi.total - transaksi.promo - transaksi.program_discount - transaksi.diskon_spesial - employee_discount - transaksi.point )* transaksi.tax / 100 ) )* transaksi.charge_ewallet / 100 ) AS charge_ewallet, SUM(transaksi.charge_ur) AS charge_ur, IFNULL( SUM(delivery.ongkir), 0 ) as delivery_fee, payment_method.nama AS pmName FROM transaksi JOIN payment_method ON transaksi.tipe_bayar = payment_method.id LEFT JOIN delivery ON transaksi.id = delivery.transaksi_id AND delivery.rate_id = 0 WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.id_partner = '$token->id_partner' AND transaksi.deleted_at IS NULL AND ( transaksi.status = 1 OR transaksi.status = 2 ) GROUP BY transaksi.tipe_bayar";
    $qPM = mysqli_query($db_conn, $query);
    $query = "SELECT SUM(detail_transaksi.qty) AS qty, menu.nama, detail_transaksi.harga_satuan, menu.id_category AS category_id, categories.name AS category_name, detail_transaksi.id_menu FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON menu.id_category=categories.id  WHERE DATE(transaksi.paid_date) BETWEEN '$dateTo' AND '$dateFrom' AND transaksi.id_partner='$token->id_partner' AND transaksi.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 AND (transaksi.status=1 OR transaksi.status=2 )  GROUP BY detail_transaksi.id_menu";
    $qMS = mysqli_query($db_conn, $query);
    $qST = mysqli_query($db_conn, "SELECT shift_transactions.id, `type`, `amount`, `description` FROM `shift_transactions` JOIN shift ON shift_transactions.shift_id=shift.id WHERE DATE(shift_transactions.created_at)='$today' AND shift.partner_id='$token->id_partner' AND shift_transactions.deleted_at IS NULL");
    $qSHIFT = mysqli_query($db_conn, " SELECT  IFNULL(SUM(s.petty_cash),0) as petty_cash FROM shift s WHERE s.partner_id='$token->id_partner' AND DATE(s.created_at) = '$today' AND s.deleted_at IS NULL");
    if (mysqli_num_rows($qSHIFT) > 0) {
        $res = mysqli_fetch_all($qSHIFT, MYSQLI_ASSOC);
        foreach ($res as $a) {
            $value['petty_cash']=ceil($a['petty_cash']);
        }
    }

    if(mysqli_num_rows($qPM) > 0 || mysqli_num_rows($qMS) > 0) {
        $paymentMethodIncome = mysqli_fetch_all($qPM, MYSQLI_ASSOC);
        $value['menus'] = mysqli_fetch_all($qMS, MYSQLI_ASSOC);
        $shiftTransactions = mysqli_fetch_all($qST, MYSQLI_ASSOC);
        $i=0;
        $value['cash_income']=0;
        foreach ($paymentMethodIncome as $valuePMI) {
            if($valuePMI['pmName']=="TUNAI"){
                $value['cash_income'] += ceil($valuePMI['total'])-ceil($valuePMI['promo'])-ceil($valuePMI['program_discount'])-ceil($valuePMI['diskon_spesial'])-ceil($valuePMI['employee_discount'])-ceil($valuePMI['point'])+ceil($valuePMI['service'])+ceil($valuePMI['tax'])+ceil($valuePMI['charge_ur']);
            }
            $value['delivery_fee'] += ceil($valuePMI['delivery_fee']);
            $value['payment_method_income'][$i]['payment_method'] = $valuePMI['pmName'];
            $value['payment_method_income'][$i]['point'] += ceil($valuePMI['point']);
            $value['payment_method_income'][$i]['promo'] += ceil($valuePMI['promo']);
            $value['payment_method_income'][$i]['program_discount'] += ceil($valuePMI['program_discount']);
            $value['payment_method_income'][$i]['diskon_spesial'] += ceil($valuePMI['diskon_spesial']);
            $value['payment_method_income'][$i]['employee_discount'] += ceil($valuePMI['employee_discount']);
            $value['payment_method_income'][$i]['delivery_fee'] += ceil($valuePMI['delivery_fee']);
            $value['payment_method_income'][$i]['income'] += ceil($valuePMI['total'])-ceil($valuePMI['promo'])-ceil($valuePMI['program_discount'])-ceil($valuePMI['diskon_spesial'])-ceil($valuePMI['employee_discount'])-ceil($valuePMI['point'])+ceil($valuePMI['service'])+ceil($valuePMI['tax'])+ceil($valuePMI['charge_ur'])+ceil($valuePMI['delivery_fee']);
            $i+=1;
        }
        if($i == 0){
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
        $vals[$index] = $value;
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =200;
        $msg = "Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "type"=>$type,"shifts"=>$vals]);
?>
