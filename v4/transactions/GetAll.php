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
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
            $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
    }
}
$tokenizer = new Token();
$token = '';
$res = array();
$totalPending = "0";

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}
$total_data = 0;
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    if (isset($_GET['page']) && !empty($_GET['page']) && isset($_GET['load']) && !empty($_GET['load'])) {

        $page = $_GET['page'];
        $load = $_GET['load'];
        $finish = $load * $page;
        $start = $finish - $load;

        $qPending = mysqli_query($db_conn, "SELECT COUNT(t.id) as pending FROM transaksi t JOIN payment_method pm ON t.tipe_bayar = pm.id LEFT JOIN users u ON u.phone=t.phone WHERE t.id_partner='$token->id_partner' AND t.deleted_at IS NULL AND (t.status=5 OR t.status=6) ORDER BY t.jam DESC");

        $q1 = mysqli_query($db_conn, "SELECT COUNT(t.id) as total_data  FROM transaksi t JOIN payment_method pm ON t.tipe_bayar = pm.id LEFT JOIN users u ON u.phone=t.phone WHERE t.id_partner='$token->id_partner' AND t.deleted_at IS NULL AND ((t.status <2 AND tipe_bayar NOT IN (1,10,2,3,4,6,11,14)) OR (t.status=1 AND tipe_bayar IN(1,10,2,3,4,6,11,14)) OR (t.status=0 AND t.source='POS' AND t.tipe_bayar=14)) ORDER BY jam DESC");

        // $q = mysqli_query($db_conn, "SELECT t.employee_discount_percent, t.is_pos as transaction_is_pos, t.id, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.employee_discount, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, t.rounding, pm.nama as payment_method, case when u.name is null or t.is_helper = 1 or t.is_pos = 1 then t.customer_name else u.name end AS uname, case when u.name is null then 1 else 0 end AS is_pos, t.program_discount, t.customer_email, t.is_helper, t.surcharge_id FROM transaksi t JOIN payment_method pm ON t.tipe_bayar = pm.id LEFT JOIN users u ON u.phone=t.phone AND u.deleted_at IS NULL WHERE id_partner='$token->id_partner' AND t.deleted_at IS NULL AND ((t.status <2 AND tipe_bayar NOT IN (1,10,2,3,4,6,11,14)) OR (t.status=1 AND tipe_bayar IN(1,10,2,3,4,6,11, 14))) ORDER BY jam DESC LIMIT $start,$load");
        $q = mysqli_query($db_conn, "SELECT t.employee_discount_percent, t.is_pos as transaction_is_pos, t.id, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.employee_discount, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, t.rounding, pm.nama as payment_method, case when u.name is null or t.is_helper = 1 or t.is_pos = 1 then t.customer_name else u.name end AS uname, case when u.name is null then 1 else 0 end AS is_pos, t.program_discount, t.customer_email, t.is_helper, t.surcharge_id FROM transaksi t JOIN payment_method pm ON t.tipe_bayar = pm.id LEFT JOIN users u ON u.phone=t.phone AND u.deleted_at IS NULL WHERE id_partner='$token->id_partner' AND t.deleted_at IS NULL AND ((t.status <2 AND tipe_bayar NOT IN (1,10,2,3,4,6,14)) OR (t.status=1 AND tipe_bayar IN(1,10,2,3,4,6,11, 14)) OR (t.status=0 AND t.source='POS' AND t.tipe_bayar=14)) ORDER BY jam DESC LIMIT $start,$load");
        if (
            mysqli_num_rows($q) > 0
            || mysqli_num_rows($qPending) > 0
        ) {
            if (
                mysqli_num_rows($q) > 0
            ) {
                $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
                $total_data = $res1[0]['total_data'];
                $i = 0;
                foreach ($res as $r) {
                    $find = $r['id'];
                    $delivery_fee = 0;
                    $allDeliv = mysqli_query($db_conn, "SELECT ongkir as delivery_fee FROM `delivery` WHERE transaksi_id='$find'");
                    if (mysqli_num_rows($allDeliv) > 0) {
                        $deliv = mysqli_fetch_all($allDeliv, MYSQLI_ASSOC);
                        $delivery_fee = $deliv[0]['delivery_fee'];
                    }
                    $res[$i]['delivery_fee'] = $delivery_fee;
                    $is_program = 0;
                    $program_name = "";
                    $query = "SELECT is_program, name FROM ( SELECT dt.is_program, mp.name FROM `detail_transaksi` dt LEFT JOIN `programs` p ON p.id = dt.is_program LEFT JOIN `master_programs` mp ON mp.id = p.master_program_id WHERE dt.deleted_at IS NULL AND dt.id_transaksi='$find' ORDER BY dt.is_program DESC";
    
                    $query .= " ) AS tmp ORDER BY is_program DESC LIMIT 1 ";
                    $isP = mysqli_query($db_conn, $query);
                    if (mysqli_num_rows($isP) > 0) {
                        $deliv = mysqli_fetch_all($isP, MYSQLI_ASSOC);
                        $is_program = $deliv[0]['is_program'];
                        $program_name = $deliv[0]['name'];
                    }
                    $res[$i]['is_program'] = $is_program;
                    $res[$i]['program_name'] = $program_name;
                    $i += 1;
                }
            }
            $resPending = mysqli_fetch_all($qPending, MYSQLI_ASSOC);
            $totalPending = $resPending[0]['pending'];
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
        $status = 203;
        $msg = "400 Missing Required Field";
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "orders" => $res, "total_pending" => $totalPending, "total_data" => $total_data]);
