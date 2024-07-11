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
$headers = apache_request_headers();
$tokenizer = new Token();
$token = '';
$res = array();

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {

    // $y=date('Y');
    $partnerID = $_GET['partnerID'];
    $Trx = mysqli_query($db_conn, "SELECT
  transaksi.id,
  transaksi.jam,
  transaksi.paid_date,
  transaksi.phone,
  transaksi.customer_name,
  transaksi.reference_id,
  transaksi.id_partner,
  transaksi.no_meja,
  transaksi.no_meja_foodcourt,
  transaksi.status,
  transaksi.total,
  transaksi.id_voucher,
  transaksi.id_voucher_redeemable,
  transaksi.tipe_bayar,
  transaksi.promo,
  transaksi.diskon_spesial,
  transaksi.employee_discount,
  transaksi.point,
  transaksi.queue,
  transaksi.takeaway,
  transaksi.notes,
  transaksi.id_foodcourt,
  transaksi.tax,
  transaksi.service,
  transaksi.pre_order_id,
  transaksi.charge_ewallet,
  transaksi.charge_xendit,
  transaksi.charge_ur,
  transaksi.confirm_at,
  transaksi.group_id,
  transaksi.rated,
  transaksi.created_at,
  transaksi.program_discount,
  transaksi.rounding,
  pm.nama AS pmName,
  transaksi.employee_discount_percent
FROM
  transaksi LEFT JOIN payment_method pm ON transaksi.tipe_bayar=pm.id
WHERE
  transaksi.phone = '$token->phone'
  AND transaksi.status IN(0,1,2,3,4,5)
  AND transaksi.deleted_at IS NULL
--   AND YEAR(transaksi.jam)='$y'
  AND transaksi.id_partner = '$partnerID'
ORDER BY
  jam DESC
");

    $array = array();
    $i = 0;
    if (mysqli_num_rows($Trx) > 0) {
        // $fc = mysqli_fetch_all($fcTrx, MYSQLI_ASSOC);
        $tr = mysqli_fetch_all($Trx, MYSQLI_ASSOC);
        foreach ($tr as $f) {
            $array[$i]['id'] =  $f['id'];
            $find = $f['id'];
            $qD = mysqli_query($db_conn, "SELECT ongkir, rate_id, user_address_id, delivery_detail, is_insurance FROM `delivery` WHERE transaksi_id='$find'");

            $array[$i]['delivery_fee'] =  '0';
            $array[$i]['is_delivery'] =  '0';
            if (mysqli_num_rows($qD) > 0) {

                $resDel = mysqli_fetch_all($qD, MYSQLI_ASSOC);
                $array[$i]['is_delivery'] =  '1';
                $array[$i]['delivery_fee'] =  $resDel[0]['ongkir'];
            }
            $array[$i]['tenant_name'] =  $f['tenant_name'];
            $array[$i]['name'] =  $f['name'];
            $array[$i]['id_foodcourt'] =  "0";
            $array[$i]['id_partner'] = $f['id_partner'];
            $array[$i]['no_meja'] = $f['no_meja'];
            $array[$i]['phone'] = $f['phone'];
            $array[$i]['id_voucher'] = $f['id_voucher'];
            $array[$i]['id_voucher_reedemable'] = $f['id_voucher_reedemable'];
            $array[$i]['tipe_bayar'] = $f['tipe_bayar'];
            $array[$i]['promo'] = $f['promo'];
            $array[$i]['diskon_spesial'] = $f['diskon_spesial'];
            // $array[$i]['tax'] = $f['tax'];
            $array[$i]['subtotal'] = (int)$f['total'];
            $array[$i]['service'] = ceil((((int)$f['total'] - (int)$f['program_discount'] - (int)$f['promo'] - (int)$f['diskon_spesial']) * (int)$f['service']) / 100);
            $array[$i]['tax'] = ceil((((int)$f['total'] - (int)$f['program_discount'] - (int)$f['promo'] - (int)$f['diskon_spesial'] + $array[$i]['service']) * $f['tax']) / 100);
            $array[$i]['status'] = $f['status'];
            $array[$i]['jam'] = $f['jam'];
            $array[$i]['queue'] = $f['queue'];
            $array[$i]['takeaway'] = $f['takeaway'];
            $array[$i]['charge_ur'] = $f['charge_ur'];
            $array[$i]['point'] = $f['point'];
            $array[$i]['rated'] = $f['rated'];
            $array[$i]['employee_discount'] = $f['employee_discount'];
            $array[$i]['program_discount'] = $f['program_discount'];
            $array[$i]['type'] =  'order';
            $array[$i]['pre_order_id'] =  $f['pre_order_id'];
            $array[$i]['pmName'] =  $f['pmName'];
            // $array[$i]['total'] = $f['total'];
            $array[$i]['total'] = (int)$f['total'] - (int)$f['program_discount'] - (int)$f['diskon_spesial'] + $array[$i]['service'] + $array[$i]['tax'];
            $array[$i]['rounding'] = (int)$f['rounding'] + $array[$i]['total'];

            $i += 1;
        }

        $j = 0;
        $flag = true;
        $temp = array();
        function bubble_Sort($my_array)
        {
            do {
                $swapped = false;
                for ($i = 0, $c = count($my_array) - 1; $i < $c; $i++) {
                    if (strtotime($my_array[$i]['jam']) < strtotime($my_array[$i + 1]['jam'])) {
                        list($my_array[$i + 1], $my_array[$i]) =
                            array($my_array[$i], $my_array[$i + 1]);
                        $swapped = true;
                    }
                }
            } while ($swapped);
            return $my_array;
        }
        $array = bubble_Sort($array);
        // }

        if ($i > 0) {
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    } else {
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
}

echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "transactions" => $array]);
