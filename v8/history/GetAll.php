<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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


$fcTrx = mysqli_query($db_conn, "SELECT transaksi_foodcourt.id, transaksi_foodcourt.id_foodcourt, transaksi_foodcourt.phone, transaksi_foodcourt.no_meja, transaksi_foodcourt.total, transaksi_foodcourt.id_voucher, transaksi_foodcourt.id_voucher_redeemable, transaksi_foodcourt.tipe_bayar, transaksi_foodcourt.promo, transaksi_foodcourt.tax, transaksi_foodcourt.service, transaksi_foodcourt.status, transaksi_foodcourt.charge_ewallet, transaksi_foodcourt.charge_xendit, transaksi_foodcourt.charge_ur, transaksi_foodcourt.created_at, foodcourt.name FROM transaksi_foodcourt JOIN foodcourt ON transaksi_foodcourt.id_foodcourt = foodcourt.id WHERE transaksi_foodcourt.phone='$token->phone' AND (transaksi.status='0' OR transaksi.status='1') ORDER BY jam DESC");

$query =  "SELECT id, jam, paid_date, phone, customer_name, reference_id, id_partner, shift_id, no_meja, no_meja_foodcourt, status, total, id_voucher, id_voucher_redeemable, tipe_bayar, promo, diskon_spesial, employee_discount, point, queue, takeaway, notes, id_foodcourt, tax, service, pre_order_id, charge_ewallet, charge_xendit, charge_ur, confirm_at, status_callback, callback_at, callback_hit, qr_string, partner_note, group_id, rated, created_at, name FROM ( SELECT transaksi.id, transaksi.jam, transaksi.paid_date, transaksi.phone, transaksi.customer_name, transaksi.reference_id, transaksi.id_partner, transaksi.shift_id, transaksi.no_meja, transaksi.no_meja_foodcourt, transaksi.status, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.point, transaksi.queue, transaksi.takeaway, transaksi.notes, transaksi.id_foodcourt, transaksi.tax, transaksi.service, transaksi.pre_order_id, transaksi.charge_ewallet, transaksi.charge_xendit, transaksi.charge_ur, transaksi.confirm_at, transaksi.status_callback, transaksi.callback_at, transaksi.callback_hit, transaksi.qr_string, transaksi.partner_note, transaksi.group_id, transaksi.rated, transaksi.created_at, partner.name FROM transaksi JOIN partner ON transaksi.id_partner = partner.id WHERE transaksi.phone='$token->phone' AND transaksi.deleted_at IS NULL AND (transaksi.status='0' OR transaksi.status='1') ";

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .=   "SELECT `$transactions`.id, `$transactions`.jam, `$transactions`.paid_date, `$transactions`.phone, `$transactions`.customer_name, `$transactions`.reference_id, `$transactions`.id_partner, `$transactions`.shift_id, `$transactions`.no_meja, `$transactions`.no_meja_foodcourt, `$transactions`.status, `$transactions`.total, `$transactions`.id_voucher, `$transactions`.id_voucher_redeemable, `$transactions`.tipe_bayar, `$transactions`.promo, `$transactions`.diskon_spesial, `$transactions`.employee_discount, `$transactions`.point, `$transactions`.queue, `$transactions`.takeaway, `$transactions`.notes, `$transactions`.id_foodcourt, `$transactions`.tax, `$transactions`.service, `$transactions`.pre_order_id, `$transactions`.charge_ewallet, `$transactions`.charge_xendit, `$transactions`.charge_ur, `$transactions`.confirm_at, `$transactions`.status_callback, `$transactions`.callback_at, `$transactions`.callback_hit, `$transactions`.qr_string, `$transactions`.partner_note, `$transactions`.group_id, `$transactions`.rated, `$transactions`.created_at, partner.name FROM `$transactions` JOIN partner ON `$transactions`.id_partner = partner.id WHERE `$transactions`.phone='$token->phone' AND `$transactions`.deleted_at IS NULL AND (`$transactions`.status='0' OR `$transactions`.status='1') ";
            // }
        }
        $query .= " ) AS tmp ORDER BY jam DESC ";

$Trx = mysqli_query($db_conn,$query);

$array = array();
$i =0;
if (mysqli_num_rows($fcTrx) > 0 || mysqli_num_rows($Trx)>0 ) {
    $fc = mysqli_fetch_all($fcTrx, MYSQLI_ASSOC);
    $tr = mysqli_fetch_all($Trx, MYSQLI_ASSOC);
    foreach ($fc as $f) {
        $array[$i]['id'] =  $f['id'];
        $array[$i]['name'] =  $f['name'];
        $array[$i]['id_foodcourt'] =  $f['id_foodcourt'];
        $array[$i]['id_partner'] = "0";
        $array[$i]['no_meja'] = $f['no_meja'];
        $array[$i]['phone'] = $f['phone'];
        $array[$i]['total'] = $f['total'];
        $array[$i]['id_voucher'] = $f['id_voucher'];
        $array[$i]['id_voucher_reedemable'] = $f['id_voucher_reedemable'];
        $array[$i]['tipe_bayar'] = $f['tipe_bayar'];
        $array[$i]['promo'] = $f['promo'];
        $array[$i]['tax'] = $f['tax'];
        $array[$i]['service'] = $f['service'];
        $array[$i]['status'] = $f['status'];
        $array[$i]['jam'] = $f['created_at'];
        $array[$i]['queue'] = "0";
        $array[$i]['takeaway'] = "0";
        $array[$i]['charge_ur'] = $f['charge_ur'];
        $array[$i]['point'] = "0";

        $i += 1;
    }
    foreach ($tr as $f) {
        $array[$i]['id'] =  $f['id'];
        $array[$i]['name'] =  $f['name'];
        $array[$i]['id_foodcourt'] =  "0";
        $array[$i]['id_partner'] = $f['id_partner'];
        $array[$i]['no_meja'] = $f['no_meja'];
        $array[$i]['phone'] = $f['phone'];
        $array[$i]['total'] = $f['total'];
        $array[$i]['id_voucher'] = $f['id_voucher'];
        $array[$i]['id_voucher_reedemable'] = $f['id_voucher_reedemable'];
        $array[$i]['tipe_bayar'] = $f['tipe_bayar'];
        $array[$i]['promo'] = $f['promo'];
        $array[$i]['tax'] = $f['tax'];
        $array[$i]['service'] = $f['service'];
        $array[$i]['status'] = $f['status'];
        $array[$i]['jam'] = $f['jam'];
        $array[$i]['queue'] = $f['queue'];
        $array[$i]['takeaway'] = $f['takeaway'];
        $array[$i]['charge_ur'] = $f['charge_ur'];
        $array[$i]['point'] = $f['point'];

        $i += 1;
    }

    $j=0;
$flag = true;
$temp=array();

// while ( $flag )
// {
//   $flag = false;
//   for( $j=0;  $j < count($array)-1; $j++)
//   {
//     if ( $array[$j]['jam'] > $array[$j+1]['jam'] )
//     {
//       $temp[$j] = $array[$j];
//       //swap the two between each other
//       $array[$j] = $array[$j+1];
//       $array[$j+1]=$temp[$j];
//       $flag = true; //show that a swap occurred
//     }
//   }
// }

    $success =1;
    $status =200;
    $msg = "Success";
}else{

    $success =0;
    $status =204;
    $msg = "Data Not Found";

}
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "transactions"=>$array]);
?>