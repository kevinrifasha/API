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
$res = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $q = mysqli_query($db_conn, "SELECT MAX(id) AS id FROM shift WHERE partner_id='$token->id_partner'");
    if(mysqli_num_rows($q)>0){
    $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
    $shiftID = $res[0]['id'];
    $getAR = mysqli_query($db_conn, "SELECT ar.id, ar.user_name, ar.user_phone, ar.company, ar.deadline, ar.status, e.nama AS employeeName, ar.transaction_id, ar.group_id FROM account_receivables ar JOIN employees e ON ar.created_by=e.id WHERE ar.shift_id='$shiftID' AND ar.deleted_at IS NULL AND ar.partner_id='$token->id_partner'");
    if(mysqli_num_rows($getAR)>0){
        $resAR = mysqli_fetch_all($getAR, MYSQLI_ASSOC);
        $i=0;
        foreach($resAR as $x){
          $arr[$i]=$x;
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
              }
          }else{
              $groupID = $x['group_id'];
              $arr[$i]['transaction_id']="0";
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
              }
          }
          $value['arTotal'] +=$arr[$i]['total'];
          $i++;
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
      }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
      }

    }else{
        $success = 0;
        $status = 204;
        $msg = "Shift tidak ditemukan";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "accountReceivables"=>$arr]);

?>