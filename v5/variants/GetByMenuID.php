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

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "menus"=>$arr]);
}else{
$dateFirstDb = date('Y-m-d', strtotime('-1 week'));
$dateLastDb = date('Y-m-d');
$today = date('Y-m-d');
$res = array();

$today = date("Y-m-d");
$idMenu = $_GET['idMenu'];
$variant = mysqli_query($db_conn, "SELECT  menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at,variant_group.id AS id_variant_group, variant_group.name AS variant_group_name, variant_group.type AS variant_group_type FROM menus_variantgroups JOIN variant_group ON variant_group.id=menus_variantgroups.variant_group_id JOIN menu ON menu.id=menus_variantgroups.menu_id WHERE menus_variantgroups.menu_id='$idMenu' AND menus_variantgroups.deleted_at IS NULL");
$i=0;
$vg = array();
if (mysqli_num_rows($variant) > 0) {
    // $variants = mysqli_fetch_all($variant, MYSQLI_ASSOC);
    $harga_diskon = 0;
    $harga = 0;
    $row = mysqli_fetch_array($variant);

    $one_menu = mysqli_fetch_all($variant, MYSQLI_ASSOC);

    if($row['harga_diskon'] != 0){
        $harga = $row['harga_diskon'];
        $harga_diskon = $row['harga'];
    }else{
        $harga = $row['harga'];
    }
    $menu_data = array(
        "nama" => $row['nama'],
        "harga" => $harga,
        "harga_diskon" => $harga_diskon,
        "id_category" =>$row['id_category'],
        "img_data" => $row['img_data'],
        "enabled" => $row['enabled'],
        "Deskripsi" => $row['Deskripsi'],
        "stock" => $row['stock']
    );
    $i = 0;
    foreach ($variant as $vr ) {
        $vg[$i]["variant_group_id"] = $vr['id_variant_group'];
        $vg[$i]["variant_group_name"] = $vr['variant_group_name'];
        $vg[$i]["variant_group_type"] = $vr['variant_group_type'];
        $id = $vr['id_variant_group'];

        $j=0;
        $v= array();
        $variants = mysqli_query($db_conn, "SELECT variant.id AS variant_id, variant.name AS variant_name, variant.price AS variant_price, variant.stock AS variant_stock FROM variant WHERE variant.id_variant_group=$id");

        foreach ($variants as $vrs) {
            $find = $vrs['variant_id'];
            $v[$j]["id"] = $vrs['variant_id'];
            $v[$j]["name"] = $vrs['variant_name'];
            $v[$j]["price"] = $vrs['variant_price'];


            $v[$j]["stock"] = $vrs['variant_stock'];
            $v[$j]["variant_group_id"] = $vg[$i]["variant_group_id"] ;
            $v[$j]["variant_group_type"] = $vg[$i]["variant_group_type"];
            $j+=1;
        }
        $vg[$i]["variant"]=$v;
        $i+=1;
    }
    http_response_code(200);
    echo json_encode(["success" => 1, "menu_data" => $menu_data, "variants" => $vg]);
} else {
    $variant = mysqli_query($db_conn, "SELECT * FROM menu WHERE id = $idMenu");
    if (mysqli_num_rows($variant) > 0) {
    $row = mysqli_fetch_array($variant);

    $one_menu = mysqli_fetch_all($variant, MYSQLI_ASSOC);

        $menu_data = array(
            "nama" => $row['nama'],
            "harga" => $row['harga'],
            "id_category" =>$row['id_category'],
            "img_data" => $row['img_data'],
            "enabled" => $row['enabled'],
            "Deskripsi" => $row['Deskripsi'],
            "stock" => $row['stock']
        );
        http_response_code(200);
        echo json_encode(["success" => 1, "menu_data" => $menu_data, "variants" => $vg]);
    }else{
        http_response_code(204);
        echo json_encode(["success" => 0]);
    }
}
}
?>

