<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
ini_set('memory_limit','256M');

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

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
    $from = $_GET['from']??"";
    $to = $_GET['to']??"";
    if(!empty($from)&&!empty($to)){
        $query = "SELECT transaction_groups.id, name, pm.nama AS payment_method, transaction_groups.paid_date FROM transaction_groups JOIN payment_method pm ON transaction_groups.payment_method=pm.id WHERE transaction_groups.status IN (2,7) AND transaction_groups.partner_id='$token->id_partner' AND transaction_groups.deleted_at IS NULL AND transaction_groups.created_at BETWEEN '$from' AND '$to' ORDER BY transaction_groups.id DESC";
    }else{
        $query = "SELECT transaction_groups.id, name, pm.nama AS payment_method, transaction_groups.paid_date FROM transaction_groups JOIN payment_method pm ON transaction_groups.payment_method=pm.id WHERE transaction_groups.status IN (2,7) AND transaction_groups.partner_id='$token->id_partner' AND transaction_groups.deleted_at IS NULL ORDER BY transaction_groups.id DESC";
    }

    $q = mysqli_query($db_conn, $query);

    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $i =0;
        foreach($res as $r){
            $find = $r['id'];
            $query2 = "SELECT `id`, `jam`, `phone`, `id_partner`, `shift_id`, `no_meja`, `status`, `total`, `id_voucher`, `rounding`, `id_voucher_redeemable`, `tipe_bayar`, tipe_bayar AS payment_method, `promo`, `diskon_spesial`, `point`,  `notes`, `tax`, `service`, `qr_string`, `charge_ur`, `confirm_at`, `partner_note`, `created_at`, `employee_discount`, `program_discount`, dp_total FROM `transaksi` WHERE group_id='$find' AND deleted_at IS NULL ";
            $q1 = mysqli_query($db_conn, $query2);

            if($q1 != false) {
                if(mysqli_num_rows($q1)>0){
                    $res[$i]['transactions'] = mysqli_fetch_all($q1, MYSQLI_ASSOC);
                }else{
                    $res[$i]['transactions'] = [];
                }
            } else {
                $res[$i]['transactions'] = [];
            }

            $i+=1;
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
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "transaction_groups"=>$res]);
