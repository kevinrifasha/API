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
    $id = $_GET['id'];
    $dateFirstDb = date('Y-m-d', strtotime('-1 week'));
    $dateLastDb = date('Y-m-d');
    $today = date('Y-m-d');
    $res = array();

    $arr = array();
    $arr1 = array();

    $allCategories = mysqli_query($db_conn, "SELECT categories.id, categories.id_master, categories.name, categories.sequence FROM categories JOIN partner ON partner.id_master = categories.id_master  WHERE partner.id='$token->id_partner'  
    ORDER BY `categories`.`sequence`  ASC");
    while($rowC=mysqli_fetch_assoc($allCategories)){
        $id_c = $rowC['id'];
        $allMenuCategory = mysqli_query($db_conn, "SELECT menu.id, menu.sku, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail ,
            partner.name, 
            categories.name as cname
            FROM menu 
            JOIN partner ON menu.id_partner = partner.id
            JOIN categories ON categories.id = menu.id_category
            WHERE partner.id = '$token->id_partner' 
            AND menu.id_category = '$id_c' AND menu.deleted_at IS NULL
            GROUP BY menu.id
            ORDER BY categories.sequence ASC
            ");
        $arr[$i]["category"] = $rowC['name'];
        $indexMenu = 0;
        
        while($rowMC=mysqli_fetch_assoc($allMenuCategory)){  
            $menuID = $rowMC['id'];
            $getSurchargePrice = mysqli_query($db_conn, "SELECT id, surcharge_id, price FROM menu_surcharge_types WHERE deleted_at IS NULL and partner_id='$token->id_partner' AND menu_id='$menuID'");
            $sp = mysqli_fetch_all($getSurchargePrice, MYSQLI_ASSOC);
            $arr[$i]["data"][$indexMenu] = $rowMC;
            $arr[$i]["data"][$indexMenu]['surcharges'] = $sp;
            $indexMenu+=1;
        }

        $i +=1;
    }

    if ($i > 0) {
        $success = 1;
        $status = 200;
        http_response_code(200);
        $msg="Berhasil";
    } else {
        $success = 0;
        $status = 204;
        $msg="Data tidak ditemukan";
        http_response_code(204);
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "menusStock"=>$arr, "menusRaws"=>$arr1]);
?>