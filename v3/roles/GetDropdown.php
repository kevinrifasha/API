<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
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
    
   
    if(isset($_GET['partner_id']) && !empty($_GET['partner_id'])){
        $partner_id =$_GET['partner_id'];
        $sql = mysqli_query($db_conn, "SELECT r.id, r.partner_id, r.name FROM roles r WHERE r.partner_id='$partner_id' AND r.deleted_at IS NULL ORDER BY r.id DESC");
    }else{
        $sql = mysqli_query($db_conn, "SELECT r.id, r.partner_id, r.name FROM roles r WHERE r.master_id='$tokenDecoded->masterID' AND r.deleted_at IS NULL ORDER BY r.id DESC");
    }
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "roles"=>$data]);  

?>