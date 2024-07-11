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
            $sql = mysqli_query($db_conn, "SELECT id_partner, nama, harga, Deskripsi, img_data, thumbnail, enabled, hpp FROM `menu` WHERE id='$id'");
            if (mysqli_num_rows($sql) > 0) {
                $res = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                $partner_id = $res[0]['id_partner'];
                $name = str_replace("'","''",$res[0]['nama']);
                $price = $res[0]['harga'];
                $description = str_replace("'","''",$res[0]['Deskripsi']);
                $image = $res[0]['img_data'];
                $thumbnail = $res[0]['thumbnail'];
                $enabled = $res[0]['enabled'];
                $cogs = $res[0]['hpp'];
                $sqlCopy = mysqli_query($db_conn, "INSERT INTO `pre_order_menus`(`partner_id`, `name`, `price`, `description`, `image`, `thumbnail`, `enabled`, `cogs`, `created_at`) VALUES ('$partner_id', '$name', '$price', '$description', '$image', '$thumbnail', '$enabled', '$cogs', NOW())");
                if($sqlCopy){
                    $bool=true;
                }
            }
        }
   
    if($bool==true) {
        // $all_users = mysqli_fetch_all($users, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Berhasil menyalin data";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Gagal menyalin data. mohon coba lagi";
    }
    }else{
        $success = 0;
        $status = 204;
        $msg = "Mohon lengkapi form";
    }
    
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);  

?>