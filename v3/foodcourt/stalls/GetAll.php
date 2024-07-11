<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../tokenModels/tokenManager.php"); 
require_once("../../connection.php");
require '../../../db_connection.php';

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
    $i = 0;
    $res = [];
    $sql = mysqli_query($db_conn, "SELECT id, name, description, created_at FROM stalls WHERE partner_id='$tokenDecoded->partnerID' AND deleted_at IS NULL ORDER BY id DESC");
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        // $res=$data;
        foreach($data as $x){
            $res[$i]=$x;
            $id = $x['id'];
            $sqlCount = mysqli_query($db_conn, "SELECT COUNT(id) AS partners FROM partner WHERE stall_id='$id' AND deleted_at IS NULL");
            while($row = mysqli_fetch_assoc($sqlCount)){
                $res[$i]['count']=$row['partners'];
            }
            $i++;
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
    
}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "stalls"=>$res]);  

?>