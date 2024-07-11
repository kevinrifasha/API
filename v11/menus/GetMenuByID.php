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
    $id = $_GET['id'];
    $i = 0;

    $gojek = 0;
    $grab = 0;
    $shopee = 0;
    $arr = array();
    $arr1 = array();
    $q = mysqli_query($db_conn, "SELECT id, nama, harga, sku, Deskripsi as deskripsi, category, img_data, enabled, stock, harga_diskon, is_variant, is_recommended, is_recipe, is_multiple_price, show_in_sf, show_in_waiter FROM menu WHERE id='$id' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $i = 0;
        foreach ($res as $value) {

            $menu_id = $value['id'];
            $res1[$i] = $value;
            if ($value['is_variant'] == 1) {
                $qv = mysqli_query($db_conn, "SELECT vg.id, vg.name, vg.type FROM menus_variantgroups m LEFT JOIN variant_group vg ON m.variant_group_id = vg.id WHERE m.menu_id='$menu_id' AND m.deleted_at IS NULL");
                if (mysqli_num_rows($qv) > 0) {
                    $resv = mysqli_fetch_all($qv, MYSQLI_ASSOC);
                    foreach ($resv as $keyv => $valuev) {
                        $res1[$i]['variants'] = $resv;
                    }
                }
            }
            if ($value['is_recipe'] == 1) {
                $qv = mysqli_query($db_conn, "SELECT r.id, r.id_menu, r.id_raw, r.qty, r.id_metric, r.id_variant, m.nama AS menuName, m.img_data AS menuImage,rw.name AS rawName, me.name AS metricName FROM recipe r JOIN menu m ON m.id=r.id_menu JOIN raw_material rw ON rw.id=r.id_raw JOIN metric me ON me.id=r.id_metric WHERE r.id_menu='$menu_id' AND r.deleted_at IS NULL ORDER BY r.id_menu");
                if (mysqli_num_rows($qv) > 0) {
                    $resv = mysqli_fetch_all($qv, MYSQLI_ASSOC);
                    foreach ($resv as $keyv => $valuev) {
                        $res1[$i]['recipes'] = $resv;
                    }
                }
            }
            //     $getSurchargePrice = mysqli_query($db_conn, "SELECT id, surcharge_id, price FROM menu_surcharge_types WHERE deleted_at IS NULL and partner_id='$token->id_partner' AND menu_id='$id'");
            // $sp = mysqli_fetch_all($getSurchargePrice, MYSQLI_ASSOC);
            // $res1[$i]['surcharges'] = $sp;
            $i += 1;
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    } else {
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "menus" => $res1]);
