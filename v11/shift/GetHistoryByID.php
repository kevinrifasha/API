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

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $id = $_GET['ID'];
    $q = mysqli_query($db_conn, "SELECT s.id, s.start, s.end, s.petty_cash, s.employee_id FROM shift s WHERE s.partner_id='$token->id_partner' AND s.id='$id' AND s.deleted_at IS NULL ORDER BY id DESC");

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
                $query3 = "SELECT SUM(transaksi.promo) AS promo, SUM(program_discount) AS program_discount,SUM(transaksi.diskon_spesial) AS diskon_spesial,SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+charge_ur)*transaksi.tax/100) AS tax,
                SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet, payment_method.nama AS pmName, transaksi.tipe_bayar, SUM(transaksi.dp_total) AS dp_total FROM shift s JOIN transaksi ON transaksi.shift_id=s.id JOIN payment_method ON transaksi.tipe_bayar=payment_method.id WHERE s.id='$sID' AND  transaksi.status IN(1,2,5,7) GROUP BY transaksi.tipe_bayar ORDER BY tipe_bayar ASC";
                $qPM = mysqli_query($db_conn, $query3);
                $query2 = "SELECT SUM(qty) AS qty, nama, harga_satuan, id_menu FROM ( SELECT menu.id as id_menu, menu.nama, SUM(detail_transaksi.qty) AS qty, detail_transaksi.harga_satuan FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi = transaksi.id JOIN menu ON menu.id = detail_transaksi.id_menu WHERE transaksi.shift_id = '$sID' AND transaksi.status IN(1, 2) AND transaksi.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL GROUP BY detail_transaksi.id_menu ) AS tmp GROUP BY id_menu";
                $getAR = mysqli_query($db_conn, "SELECT ar.id, ar.user_name, ar.user_phone, ar.company, ar.deadline, ar.status, e.nama AS employeeName, ar.transaction_id, ar.group_id FROM account_receivables ar JOIN employees e ON ar.created_by=e.id WHERE ar.shift_id='$sID' AND ar.deleted_at IS NULL AND ar.partner_id='$token->id_partner'");
              if(mysqli_num_rows($getAR)>0){
                $resAR = mysqli_fetch_all($getAR, MYSQLI_ASSOC);
                $i=0;
                foreach($resAR as $x){
                  $arr[$i]=$x;
                  $j=0;
                  if($x['group_id']==0||$x['group_id']=="0"){
                      $transactionID=$x['transaction_id'];
                      $getTransactions = mysqli_query($db_conn,"SELECT t.total,
                      t.program_discount,
                      t.promo,
                      t.diskon_spesial,
                      t.employee_discount,
                      t.service,
                      t.tax,
                      t.charge_ur  FROM transaksi t WHERE t.id='$transactionID'");
                      $resTrx = mysqli_fetch_all($getTransactions, MYSQLI_ASSOC);
                      foreach($resTrx as $y){
                          $subtotal = $y['total']-$y['program_discount']-$y['promo']-$y['diskon_spesial']-$y['employee_discount'];
                          $service = ceil($subtotal*$y['service']/100);
                          $serviceandCharge = $service + $y['charge_ur'];
                          $tax = ceil(($subtotal+$serviceandCharge)*$y['tax']/100);
                          $grandTotal = $subtotal+$serviceandCharge+$tax;
                          $arr[$i]['total']=$grandTotal;
                          $value['arTotal'] +=$arr[$i]['total'];
                          $j++;
                      }
                  }else{
                      $groupID = $x['group_id'];
                      $getTransactions = mysqli_query($db_conn,"SELECT SUM(t.total) AS total,
                      SUM(t.program_discount) AS program_discount,
                      SUM(t.promo) AS promo,
                      SUM(t.diskon_spesial) AS diskon_spesial,
                      SUM(t.employee_discount) AS employee_discount,
                      t.service,
                      t.tax,
                      SUM(t.charge_ur) AS charge_ur  FROM transaksi t WHERE t.group_id='$groupID'");
                      $resTrx = mysqli_fetch_all($getTransactions, MYSQLI_ASSOC);
                      foreach($resTrx as $y){
                          $subtotal = $y['total']-$y['program_discount']-$y['promo']-$y['diskon_spesial']-$y['employee_discount'];
                          $service = ceil($subtotal*$y['service']/100);
                          $serviceandCharge = $service + $y['charge_ur'];
                          $tax = ceil(($subtotal+$serviceandCharge)*$y['tax']/100);
                          $grandTotal = $subtotal+$serviceandCharge+$tax;
                          $arr[$i]['total']=$grandTotal;
                          $value['arTotal'] +=$arr[$i]['total'];
                          $j++;
                      }
                  }
                  $i++;
                }

                $value['ar'] = $arr;
              }else{
                $value['ar'] = [];
                $value['arTotal'] = 0;
              }
                $qMS = mysqli_query($db_conn, $query2);
                $qST = mysqli_query($db_conn, "SELECT `id`, `type`, `amount`, `description` FROM `shift_transactions` WHERE `shift_id`='$sID' AND deleted_at IS NULL");
                $paymentMethodIncome = mysqli_fetch_all($qPM, MYSQLI_ASSOC);
                $shiftTransactions = mysqli_fetch_all($qST, MYSQLI_ASSOC);
                $value['menus'] = mysqli_fetch_all($qMS, MYSQLI_ASSOC);
                $i=0;
                foreach ($paymentMethodIncome as $valuePMI) {
                    if($valuePMI['pmName']=="TUNAI"){
                        $value['cash_income'] += ceil($valuePMI['total'])-ceil($valuePMI['promo'])-ceil($valuePMI['program_discount'])-ceil($valuePMI['diskon_spesial'])-ceil($valuePMI['employee_discount'])-ceil($valuePMI['point'])+ceil($valuePMI['service'])+ceil($valuePMI['tax'])+ceil($valuePMI['charge_ur'])-ceil($valuePMI['dp_total']);
                    }
                    $value['pax'] += $valuePMI['pax'];
                    $value['delivery_fee'] += ceil($valuePMI['delivery_fee']);
                    $value['payment_method_income'][$i]['payment_method'] = $valuePMI['pmName'];
                    $value['payment_method_income'][$i]['point'] += ceil($valuePMI['point']);
                    $value['payment_method_income'][$i]['promo'] += ceil($valuePMI['promo']);
                    $value['payment_method_income'][$i]['program_discount'] += ceil($valuePMI['program_discount']);
                    $value['payment_method_income'][$i]['diskon_spesial'] += ceil($valuePMI['diskon_spesial']);
                    $value['payment_method_income'][$i]['employee_discount'] += ceil($valuePMI['employee_discount']);
                    $value['payment_method_income'][$i]['delivery_fee'] += ceil($valuePMI['delivery_fee']);
                    $value['payment_method_income'][$i]['income'] += ceil($valuePMI['total'])-ceil($valuePMI['promo'])-ceil($valuePMI['program_discount'])-ceil($valuePMI['diskon_spesial'])-ceil($valuePMI['employee_discount'])-ceil($valuePMI['point'])+ceil($valuePMI['service'])+ceil($valuePMI['tax'])+ceil($valuePMI['charge_ur'])-ceil($valuePMI['dp_total']);
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
                $dp = mysqli_query($db_conn, "SELECT SUM(dp.amount) AS total, pm.nama AS name FROM down_payments dp JOIN payment_method pm ON pm.id=dp.payment_method_id WHERE dp.shift_id='$sID' AND dp.partner_id='$token->id_partner' AND dp.deleted_at IS NULL GROUP BY dp.payment_method_id");
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
