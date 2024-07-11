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
    $result = [];
    if (isset($_GET['table']) && !empty($_GET['table'])) {
        $subtotal = 0;
        $service = 0;
        $tax = 0;
        $grandTotal = 0;
        $table = $_GET['table'];
        $query = "SELECT t.id, t.total, t.status, t.jam, t.tax, t.service, t.promo, t.diskon_spesial, t.employee_discount, t.point, t.no_meja AS noMeja, t.queue, t.charge_ur, t.program_discount,case when u.name is null or t.is_helper = 1 or t.is_pos = 1 then t.customer_name else u.name end AS uname FROM transaksi t LEFT JOIN users u ON u.phone=t.phone WHERE t.id_partner='$token->partnerID' AND t.no_meja='$table' AND t.status NOT IN (2,3,4,7) AND t.deleted_at IS NULL ORDER BY t.jam DESC";
        $q = mysqli_query($db_conn, $query);

        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $index = 0;
            foreach ($res as $f) {
                $find =  $f['id'];
                $result[$index]['delivery_fee'] =  '0';
                $result[$index] =  $f;
                $subtotal = (int) $f['total'] - (int)$f['employee_discount'] - (int)$f['program_discount'] - (int)$f['promo'] - (int)$f['diskon_spesial'];
                $service = ceil($subtotal * (int)$f['service'] / 100);
                $tax = ceil(($subtotal + $service + (int)$f['charge_ur']) * (float)$f['tax'] / 100);
                $grandTotal = $subtotal + $service + $tax + (int)$f['charge_ur'];
                $result[$index]['sales'] = $grandTotal;
                $is_program = 0;
                $query = "SELECT is_program FROM ( SELECT is_program FROM `detail_transaksi` WHERE id_transaksi='$find' AND deleted_at IS NULL ";
                $query .= " ) AS tmp ORDER BY is_program DESC LIMIT 1 ";
                $isP = mysqli_query($db_conn, $query);
                if (mysqli_num_rows($isP) > 0) {
                    $deliv = mysqli_fetch_all($isP, MYSQLI_ASSOC);
                    $is_program = $deliv[0]['is_program'];
                }
                $result[$index]['is_program'] = $is_program;
                $index += 1;
            }
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
        $msg = "400 Missing Required Field";
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "transactions" => $result]);
