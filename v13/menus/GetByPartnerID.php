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
$arr = array();
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
    $phone = $token->phone;
    $id = $_GET['id'];
    $dateFirstDb = date('Y-m-d', strtotime('-1 week'));
    $dateLastDb = date('Y-m-d');
    $today = date('Y-m-d');
    $res = array();

    $indexRecom1 = 0;
    $indexRecom = 0;
    $indexFav = 0;
    $i = 0;
    $limit_recom = 0;

    // $allFav = mysqli_query($db_conn, "SELECT m.id, m.id_partner, m.nama, m.Deskripsi, m.id_category, m.img_data, m.enabled, m.stock, m.harga, m.harga_diskon, m.is_variant, m.is_recommended, m.is_recipe, c.name as cname, CASE WHEN m.stock=0 THEN 0 ELSE 1 END AS tempFlag FROM favorites f JOIN menu m ON f.menu_id=m.id JOIN categories c ON m.id_category=c.id WHERE f.phone='$phone' AND m.id_partner='$id' AND f.deleted_at IS NULL AND m.deleted_at IS NULL ORDER BY tempFlag DESC");

    // while($rowFav=mysqli_fetch_assoc($allFav)){
    //     $rowFav['is_favorite']=true;
    //     $id_m = $rowFav['id'];
    //     $arr[$i]["category"] = "Favorit";
    //     $arr[$i]["data"][$indexFav] = $rowFav;

    //     $indexFav+=1;
    // }

    // if($indexFav > 0){
    //     $i+=1;
    // }
    $allRecom = mysqli_query($db_conn, "SELECT menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at,
        partner.name,
        categories.name as cname, CASE WHEN menu.stock=0 THEN 0 ELSE 1 END AS tempFlag
        FROM menu
        JOIN partner ON menu.id_partner = partner.id
        JOIN categories ON categories.id = menu.id_category
        WHERE partner.id = '$id' AND categories.name='Promo' AND menu.deleted_at IS NULL AND menu.enabled=1 AND menu.show_in_sf=1 
        GROUP BY menu.id");

    while($rowR=mysqli_fetch_assoc($allRecom)){
        $id_m = $rowR['id'];

        $rowR['is_favorite']=false;
        $arr[$i]["category"] = "Favorit";
        $arr[$i]["data"][$indexRecom] = $rowR;

        $indexRecom1+=1;
    }

    if($indexRecom1!=0){
        $i+=1;
    }


    $lim = mysqli_query($db_conn, "SELECT value FROM `settings` WHERE name = 'limit_recommended_menu'");

    while($rowL=mysqli_fetch_assoc($lim)){
        $limit_recom = $rowL['value'];
    }

    $allRecom = mysqli_query($db_conn, "SELECT menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at, categories.is_consignment,
        partner.name,
        categories.name as cname, CASE WHEN menu.stock=0 THEN 0 ELSE 1 END AS tempFlag
        FROM menu
        JOIN partner ON menu.id_partner = partner.id
        JOIN categories ON categories.id = menu.id_category
        WHERE partner.id = '$id'
        AND menu.is_recommended=1 AND menu.deleted_at IS NULL AND menu.enabled=1 AND menu.show_in_sf=1
        GROUP BY menu.id
        ORDER BY tempFlag DESC
        LIMIT $limit_recom ");

    while($rowR=mysqli_fetch_assoc($allRecom)){
        $id_m = $rowR['id'];
            $rowR['is_favorite']=false;

        $arr[$i]["category"] = "Favorit";
        $arr[$i]["data"][$indexRecom] = $rowR;

        $indexRecom+=1;
    }

    if($indexRecom!=0){
        $i+=1;
    }

    $allCategories = mysqli_query($db_conn, "SELECT categories.id, categories.id_master, categories.name, categories.sequence, categories.is_consignment FROM categories JOIN partner ON partner.id_master = categories.id_master  WHERE partner.id='$id'
    AND categories.name!='Promo' ORDER BY categories.sequence  ASC");
    while($rowC=mysqli_fetch_assoc($allCategories)){
        $id_c = $rowC['id'];
        $allMenuCategory = mysqli_query($db_conn, "SELECT  menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at, menu.sequence,
            partner.name, categories.is_consignment,
        categories.name as cname, CASE WHEN menu.stock=0 THEN 0 ELSE 1 END AS tempFlag,
            categories.name as cname
            FROM menu
            JOIN partner ON menu.id_partner = partner.id
            JOIN categories ON categories.id = menu.id_category
            WHERE partner.id = '$id'
            AND menu.id_category = '$id_c' AND menu.deleted_at IS NULL AND menu.enabled=1 AND menu.show_in_sf=1
            GROUP BY menu.id
            ORDER BY tempFlag DESC, menu.sequence ASC
            ");
        $arr[$i]["category"] = $rowC['name'];
        $indexMenu = 0;
        while($rowMC=mysqli_fetch_assoc($allMenuCategory)){


        if($indexFav>0){
            $rowMC['is_favorite']=false;
            foreach ($arr[0]['data'] as $fav) {
                $id_m = $rowMC['id'];
                if($fav['id']===$id_m){
                    $rowMC['is_favorite']=true;
                }
            }
        }else{
            $rowMC['is_favorite']=false;
        }
            $arr[$i]["data"][$indexMenu] = $rowMC;
            $indexMenu+=1;
        }

        $i +=1;
    }
    if (mysqli_num_rows($allCategories) > 0) {
        $success = 1;
        $status = 200;
        $msg = "success";
    } else {
        $success = 0;
        $status = 204;
        $msg = "Data Tidak Ada";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "menus"=>$arr]);
