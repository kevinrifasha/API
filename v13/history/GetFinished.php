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
$headers = apache_request_headers();
$tokenizer = new Token();
$token = '';
$res = array();
$success = 0;
$status = 200;
$msg = "success";

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
// $fcTrx = mysqli_query($db_conn, "SELECT transaksi_foodcourt.id, transaksi_foodcourt.id_foodcourt, transaksi_foodcourt.phone, transaksi_foodcourt.no_meja, transaksi_foodcourt.total, transaksi_foodcourt.id_voucher, transaksi_foodcourt.id_voucher_redeemable, transaksi_foodcourt.tipe_bayar, transaksi_foodcourt.promo, transaksi_foodcourt.tax, transaksi_foodcourt.service, transaksi_foodcourt.status, transaksi_foodcourt.charge_ewallet, transaksi_foodcourt.charge_xendit, transaksi_foodcourt.charge_ur, transaksi_foodcourt.created_at, foodcourt.name FROM transaksi_foodcourt JOIN foodcourt ON transaksi_foodcourt.id_foodcourt = foodcourt.id WHERE transaksi_foodcourt.phone='$token->phone' AND (transaksi_foodcourt.status!=0 OR transaksi_foodcourt.status!=1) ORDER BY created_at DESC");

$query =  "SELECT  id, jam, paid_date, phone, customer_name, reference_id, id_partner, shift_id, no_meja, no_meja_foodcourt,rounding, status, total, id_voucher, id_voucher_redeemable, tipe_bayar, promo, diskon_spesial, employee_discount, point, queue, takeaway, notes, id_foodcourt, tax, service, pre_order_id, charge_ewallet, charge_xendit, charge_ur, confirm_at, status_callback, callback_at, callback_hit, qr_string, partner_note, group_id, rated, created_at, name, program_discount, tenant_name FROM ( SELECT  transaksi.id, transaksi.jam, transaksi.paid_date, transaksi.phone,transaksi.rounding, transaksi.customer_name, transaksi.reference_id, transaksi.id_partner, transaksi.shift_id, transaksi.no_meja, transaksi.no_meja_foodcourt, transaksi.status, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.point, transaksi.queue, transaksi.takeaway, transaksi.notes, transaksi.id_foodcourt, transaksi.tax, transaksi.service, transaksi.pre_order_id, transaksi.charge_ewallet, transaksi.charge_xendit, transaksi.charge_ur, transaksi.confirm_at, transaksi.status_callback, transaksi.callback_at, transaksi.callback_hit, transaksi.qr_string, transaksi.partner_note, transaksi.group_id, transaksi.rated, transaksi.created_at, partner.name, transaksi.program_discount, tenant.name AS tenant_name FROM transaksi JOIN partner ON transaksi.id_partner = partner.id LEFT JOIN partner tenant ON tenant.id=transaksi.tenant_id WHERE transaksi.phone='$token->phone' AND transaksi.deleted_at IS NULL AND partner.organization='Natta' AND transaksi.status <> 0 AND transaksi.status <> 1 AND transaksi.status <> 5  ) AS tmp ORDER BY jam DESC ";
$Trx = mysqli_query($db_conn, $query);

$Tmp = mysqli_query($db_conn, "SELECT tmp.tranasaction_code, tmp.type, tmp.operator, tmp.price, tmp.payment_method, tmp.status, pm.nama as payment_name, tmp.created_at as jam, tmp.status, tmp.data, tmp.packet, tmp.callback_response_mobile_pulsa FROM transaction_mobilepulsa tmp JOIN payment_method pm ON pm.id=tmp.payment_method WHERE tmp.phone='$token->phone' AND tmp.deleted_at IS NULL AND (tmp.status=2 OR tmp.status=1)");
$array = array();
$i =0;

if (mysqli_num_rows($Trx)>0 ) {
    // $fc = mysqli_fetch_all($fcTrx, MYSQLI_ASSOC);
    $tr = mysqli_fetch_all($Trx, MYSQLI_ASSOC);
    $tmp = mysqli_fetch_all($Tmp, MYSQLI_ASSOC);
    // foreach ($fc as $f) {
    //     $array[$i]['id'] =  $f['id'];
    //     $find = $f['id'];
    //     $qD = mysqli_query($db_conn, "SELECT ongkir, rate_id, user_address_id, delivery_detail, is_insurance FROM `delivery` WHERE transaksi_id='$find'");
    //     $array[$i]['is_delivery'] =  '0';
    //     $array[$i]['delivery_fee'] =  '0';
    //     if (mysqli_num_rows($qD) > 0) {
    //         $resDel = mysqli_fetch_all($qD, MYSQLI_ASSOC);
    //         $array[$i]['is_delivery'] =  '1';
    //         $array[$i]['delivery_fee'] =  $resDel[0]['ongkir'];
    //     }
    //     $array[$i]['name'] =  $f['name'];
    //     $array[$i]['id_foodcourt'] =  $f['id_foodcourt'];
    //     $array[$i]['id_partner'] = '0';
    //     $array[$i]['no_meja'] = $f['no_meja'];
    //     $array[$i]['phone'] = $f['phone'];
    //     $array[$i]['total'] = $f['total'];
    //     $array[$i]['id_voucher'] = $f['id_voucher'];
    //     $array[$i]['id_voucher_reedemable'] = $f['id_voucher_reedemable'];
    //     $array[$i]['tipe_bayar'] = $f['tipe_bayar'];
    //     $array[$i]['promo'] = $f['promo'];
    //     $array[$i]['tax'] = $f['tax'];
    //     $array[$i]['service'] = $f['service'];
    //     $array[$i]['status'] = $f['status'];
    //     $array[$i]['jam'] = $f['created_at'];
    //     $array[$i]['queue'] = "0";
    //     $array[$i]['takeaway'] = "0";
    //     $array[$i]['charge_ur'] = $f['charge_ur'];
    //     $array[$i]['point'] = "0";
    //     $array[$i]['rated']= "1";
    //     $array[$i]['diskon_spesial']= "0";
    //     $array[$i]['employee_discount']= "0";
    //     $array[$i]['program_discount']= "0";
    //     $array[$i]['type'] =  'order';
    //     $array[$i]['pre_order_id'] =  "0";

    //     $i += 1;
    // }
    foreach ($tr as $f) {
        $array[$i]['id'] =  $f['id'];
        $find = $f['id'];
        $qD = mysqli_query($db_conn, "SELECT ongkir, rate_id, user_address_id, delivery_detail, is_insurance FROM `delivery` WHERE transaksi_id='$find'");
        $array[$i]['is_delivery'] =  '0';
        $array[$i]['delivery_fee'] =  '0';
        if (mysqli_num_rows($qD) > 0) {
            $resDel = mysqli_fetch_all($qD, MYSQLI_ASSOC);
            $array[$i]['is_delivery'] =  '1';
            $array[$i]['delivery_fee'] =  $resDel[0]['ongkir'];
        }
        $array[$i]['tenant_name'] =  $f['tenant_name'];
        $array[$i]['name'] =  $f['name'];
        $array[$i]['rounding'] =  $f['rounding'];
        $array[$i]['id_foodcourt'] =  "0";
        $array[$i]['id_partner'] = $f['id_partner'];
        $array[$i]['no_meja'] = $f['no_meja'];
        $array[$i]['phone'] = $f['phone'];
        $array[$i]['total'] = $f['total'];
        $array[$i]['id_voucher'] = $f['id_voucher'];
        $array[$i]['id_voucher_reedemable'] = $f['id_voucher_redeemable'];
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
        $array[$i]['rated']= $f['rated'];
        $array[$i]['diskon_spesial']= $f['diskon_spesial'];
        $array[$i]['employee_discount']= $f['employee_discount'];
        $array[$i]['program_discount']= $f['program_discount'];
        $array[$i]['type'] =  'order';
        $array[$i]['pre_order_id'] =  $f['pre_order_id'];

        $i += 1;
    }
    foreach ($tmp as $f) {
        $array[$i]['id'] =  $f['tranasaction_code'];
        $array[$i] =  $f;
        $array[$i]['type'] =  'mobile_service';

        $i += 1;
    }

    $j=0;
$flag = true;
$temp=array();

function bubble_Sort($my_array )
{
	do
	{
		$swapped = false;
		for( $i = 0, $c = count( $my_array ) - 1; $i < $c; $i++ )
		{
			if( strtotime($my_array[$i]['jam']) < strtotime($my_array[$i + 1]['jam']) )
			{
				list( $my_array[$i + 1], $my_array[$i] ) =
						array( $my_array[$i], $my_array[$i + 1] );
				$swapped = true;
			}
		}
	}
	while( $swapped );
return $my_array;
}
$array = bubble_Sort($array);

    if ($i > 0) {
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "transactions"=>$array]);
?>