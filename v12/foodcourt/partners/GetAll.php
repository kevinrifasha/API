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
    // $users = mysqli_query($db_conn, "SELECT id, name, created_at FROM partner WHERE fc_parent_id='$tokenDecoded->partnerID' AND deleted_at IS NULL ORDER BY id DESC");
    $users = mysqli_query($db_conn, "SELECT partner.status, partner.id, partner.name, partner.phone, partner.img_map, partner.thumbnail, partner.created_at, stall_id, stalls.name AS stall_name, partner.id_master FROM partner JOIN stalls ON stalls.id=partner.stall_id WHERE fc_parent_id='$tokenDecoded->partnerID' AND partner.deleted_at IS NULL ORDER BY partner.id DESC");
    if(mysqli_num_rows($users) > 0) {
        $all_users = mysqli_fetch_all($users, MYSQLI_ASSOC);
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
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "partners"=>$all_users]);
