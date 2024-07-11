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
        
        $id = $obj['id'];
        $partnerID = $obj['partnerID'];
        $name = str_replace("'","''",$obj['name']); 
        $item_sales = $obj['item_sales']; 
        $order_from = $obj['order_from']; 
        $order_to = $obj['order_to']; 
        $delivery_from = $obj['delivery_from']; 
        $delivery_to = $obj['delivery_to']; 
    $sql = mysqli_query($db_conn, "UPDATE `pre_order_schedules` SET`name`='$name',`item_sales`='$item_sales',`order_from`='$order_from',`order_to`='$order_to',`delivery_from`='$delivery_from',`delivery_to`='$delivery_to',`updated_at`=NOW() WHERE id='$id'");
   
    if($sql) {
        $all_users = mysqli_fetch_all($users, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Berhasil mengubah data";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Gagal mengubah data. mohon coba lagi";
    }
    }else{
        $success = 0;
        $status = 204;
        $msg = "Mohon lengkapi form";
    }
    
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);  

?>