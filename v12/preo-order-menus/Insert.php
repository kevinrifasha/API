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
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    if(!empty($obj['name']) && !empty($obj['partnerID']) ){
        
        $partnerID = $obj['partnerID'];
        $name = str_replace("'","''",$obj['name']);
        $price = $obj['price'];
        $cogs = $obj['cogs'];
        $description = str_replace("'","''",$obj['description']);
        $image = $obj['image'];
        $thumbnail = $obj['thumbnail'];
        $enabled = $obj['enabled'];
        $is_variant = $obj['is_variant'];
        $is_recipe = $obj['is_recipe'];
    $sql = mysqli_query($db_conn, "INSERT INTO `pre_order_menus`(`partner_id`, `name`, `price`, `description`, `image`, `thumbnail`, `enabled`, `cogs`, `is_variant`, `is_recipe`, `created_at`) VALUES ('$partnerID', '$name', '$price', '$description', '$image', '$thumbnail', '$enabled', '$cogs','$is_variant', '$is_recipe', NOW())");
   
    if($sql) {
        $all_users = mysqli_fetch_all($users, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Berhasil tambah data";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Gagal tambah data. mohon coba lagi";
    }
    }else{
        $success = 0;
        $status = 204;
        $msg = "Mohon lengkapi form";
    }
    
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);  

?>