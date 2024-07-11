<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';

$headers = apache_request_headers();
$token = '';
    
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$bool = false;
$res =0;
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    if(!empty($obj['selected'])){
        
        $selected = $obj['selected'];
        foreach ($selected as $value) {
            $id = $value['id'];
            $sql = mysqli_query($db_conn, "SELECT partner_id, name, price, description, image, thumbnail, enabled, cogs, is_variant, is_recipe FROM `pre_order_menus` WHERE id='$id'");
            if (mysqli_num_rows($sql) > 0) {
                $res = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                $partner_id = $res[0]['partner_id'];
                $sql1 = mysqli_query($db_conn, "SELECT c.id FROM categories c JOIN partner p ON p.id_master=c.id_master WHERE p.id='$partner_id' AND c.deleted_at IS NULL");
                $category_id = 0;
                if (mysqli_num_rows($sql1) > 0) {
                    $res1 = mysqli_fetch_all($sql1, MYSQLI_ASSOC);
                    $category_id = $res1[0]['id'];
                }
                $name = str_replace("'","''",$res[0]['name']);
                $price = $res[0]['price'];
                $description = str_replace("'","''",$res[0]['description']);
                $image = $res[0]['image'];
                $thumbnail = $res[0]['thumbnail'];
                $enabled = $res[0]['enabled'];
                $cogs = $res[0]['cogs'];
                $is_variant = $res[0]['is_variant'];
                $is_recipe = $res[0]['is_recipe'];
                $sqlCopy = mysqli_query($db_conn, "INSERT INTO `menu`(`id_partner`, `nama`, `harga`, `Deskripsi`, `id_category`, `img_data`, `enabled`, `stock`, `hpp`, `harga_diskon`, `is_variant`, `is_recipe`, `thumbnail`) VALUES ('$partner_id', '$name', '$price', '$description', '$category_id', '$image', '$enabled', 0, '$cogs', 0, 0,0, '$thumbnail')");
                if($sqlCopy){
                    $bool=true;
                }
            }
        }
   
    if($bool==true) {
        // $all_users = mysqli_fetch_all($users, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Berhasil mengcopy data";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Gagal mengcopy data. mohon coba lagi";
    }
    }else{
        $success = 0;
        $status = 204;
        $msg = "Mohon lengkapi form";
    }
    
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);  

?>