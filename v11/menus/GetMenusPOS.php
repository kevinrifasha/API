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
$arr = array();
$arr1 = array();
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
    http_response_code($status);
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $id = "";
    $i = 0;
    $dateFirstDb = date('Y-m-d', strtotime('-1 week'));
    $dateLastDb = date('Y-m-d');
    $today = date('Y-m-d');
    $res = array();
    $gojek = 0;
    $grab = 0;
    $shopee = 0;

    $allCategories = mysqli_query($db_conn, "SELECT categories.id, categories.id_master, categories.name, categories.sequence, categories.is_consignment FROM categories JOIN partner ON partner.id_master = categories.id_master  WHERE partner.id='$token->id_partner'
ORDER BY `categories`.`sequence`  ASC");
    while ($rowC = mysqli_fetch_assoc($allCategories)) {
        $id_c = $rowC['id'];
        $isConsignment = $rowC['is_consignment'];
        $allMenuCategory = mysqli_query($db_conn, "SELECT menu.id, sku, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.is_multiple_price , menu.enabled, CASE WHEN menu.track_stock=1 THEN menu.stock ELSE 10000 END AS stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail ,
        categories.name as cname
        FROM menu
        JOIN categories ON categories.id = menu.id_category
        WHERE menu.id_partner = '$token->id_partner'
        AND menu.id_category = '$id_c' AND menu.deleted_at IS NULL
        GROUP BY menu.id
        ORDER BY categories.sequence ASC
        ");
        $arr[$i]["category"] = $rowC['name'];
        $indexMenu = 0;

        if (mysqli_num_rows($allMenuCategory) > 0) {
            while ($rowMC = mysqli_fetch_assoc($allMenuCategory)) {
                $menuID = $rowMC['id'];
                $getSurchargePrice = mysqli_query($db_conn, "SELECT id, surcharge_id, price FROM menu_surcharge_types WHERE deleted_at IS NULL and partner_id='$token->id_partner' AND menu_id='$menuID'");
                $sp = mysqli_fetch_all($getSurchargePrice, MYSQLI_ASSOC);

                $arr[$i]["data"][$indexMenu] = $rowMC;
                $arr[$i]["data"][$indexMenu]['surchargePrices'] = $sp;
                $arr[$i]["data"][$indexMenu]['isConsignment'] = $isConsignment;
                $indexMenu += 1;
            }
        }

        $i += 1;
    }

    if ($i > 0) {
        $success = 1;
        $status = 200;
        $msg = "Berhasil";
    } else {
        $success = 0;
        $status = 204;
        $msg = "Data tidak ditemukan";
        $arr = array();
        $arr1 = array();
        // http_response_code(204);
    }
}
// http_response_code($status);

echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "menusStock" => $arr, "menusRaws" => $arr1]);
