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
$result = array();
$all = "0";

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $id_partner = $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $id_partner = $_GET['partnerID']; 
    }
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($all == "1") {
        $q = "SELECT t.id, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.employee_discount, t.employee_discount, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, pm.nama as payment_method, case when u.name is null or t.is_helper = 1 or t.is_pos = 1 then t.customer_name else u.name end AS uname, t.program_discount, t.surcharge_id, t.employee_discount_percent FROM transaksi t JOIN payment_method pm ON t.tipe_bayar = pm.id LEFT JOIN users u ON u.phone=t.phone JOIN partner p ON p.id = t.id_partner WHERE p.id_master = '$idMaster' AND t.deleted_at IS NULL ORDER BY t.jam DESC LIMIT 10";
    } else {
        $q = "SELECT t.id, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.employee_discount, t.employee_discount, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, pm.nama as payment_method, case when u.name is null or t.is_helper = 1 or t.is_pos = 1 then t.customer_name else u.name end AS uname, t.program_discount, t.surcharge_id, t.employee_discount_percent FROM transaksi t JOIN payment_method pm ON t.tipe_bayar = pm.id LEFT JOIN users u ON u.phone=t.phone WHERE id_partner='$id_partner' AND t.deleted_at IS NULL ORDER BY t.jam DESC LIMIT 10";
    }

    $sql = mysqli_query($db_conn, $q);


        if (mysqli_num_rows($sql) > 0) {
            $res = mysqli_fetch_all($sql, MYSQLI_ASSOC);
            $index = 0;
            foreach ($res as $f) {
                $find =  $f['id'];
                $result[$index]['delivery_fee'] =  '0';
                $result[$index] =  $f;
                $result[$index]['sales'] =(int) $f['total']+(int) $f['service']+(int) $f['tax'] + (int) $f['charge_ur'];
                $result[$index]['hpp']=0;
                $hppQ = mysqli_query(
                    $db_conn,
                    "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id='$find'"
                );
                if (mysqli_num_rows($hppQ) > 0) {
                    $resQ = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
                    $result[$index]['hpp']=(double)$resQ[0]['hpp'];
                }
                $result[$index]['gross_profit'] = $result[$index]['sales'] -$result[$index]['hpp'];

                $qD = mysqli_query($db_conn, "SELECT ongkir, rate_id, user_address_id, delivery_detail, is_insurance FROM `delivery` WHERE transaksi_id='$find'");
                if (mysqli_num_rows($qD) > 0) {
                    $resDel = mysqli_fetch_all($qD, MYSQLI_ASSOC);
                    $result[$index]['delivery_fee'] =  $resDel[0]['ongkir'];
                }
                $index+=1;
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
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "orders"=>$result]);
?>