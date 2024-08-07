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
        $sql = mysqli_query($db_conn, "SELECT r.id, r.partner_id, r.name, r.is_owner, r.is_owner_mode, r.web,r.mobile, r.w1,r.w2,r.w3,r.w4,r.w5,r.w6,r.w7,r.w8,r.w9,r.w10,r.w11,r.w12,r.w13,r.w14,r.w15,r.w16,r.w17,r.w18,r.w19,r.w20,r.w21,r.w22,r.w23,r.w24,r.w25,r.w26,r.w27,r.w28,r.w29,r.w30,r.w31,r.w32,r.w33,r.w34,r.w35,r.w36,r.w37,r.w38,r.w39,r.w40,r.w41,r.w42,r.w43,r.w44,r.w45,r.w46,r.w47,r.m1,r.m2,r.m3,r.m4,r.m5,r.m6,r.m7,r.m8,r.m9,r.m10,r.m11,r.m12,r.m13,r.m14,r.m15,r.m16,r.m17,r.m18,r.m19,r.m20, r.m21, r.m22, r.m23,r.m24,r.m25,r.m26,r.m27,r.m28,r.m29,r.m30,r.m31,r.m32,r.m33,r.m34,r.m35,r.m36,r.m37,r.m38,r.m39,r.m40,r.m41,r.m42,r.m43,r.m44,r.m45,r.m46,r.m47,r.m48,r.m49,r.m50,r.m51, r.max_discount, r.is_order_notif, r.is_reservation_notif, r.is_withdrawal_notif, r.department_access,(SELECT COUNT( e.id )FROM employees e WHERE e.role_id=r.id AND e.deleted_at IS NULL) AS employees FROM roles r WHERE r.partner_id='$partner_id' AND r.deleted_at IS NULL ORDER BY r.id DESC");
    }else{
        $sql = mysqli_query($db_conn, "SELECT r.id, r.partner_id, r.name, r.is_owner, r.is_owner_mode, r.web,r.mobile, r.w1,r.w2,r.w3,r.w4,r.w5,r.w6,r.w7,r.w8,r.w9,r.w10,r.w11,r.w12,r.w13,r.w14,r.w15,r.w16,r.w17,r.w18,r.w19,r.w20,r.w21,r.w22,r.w23,r.w24,r.w25,r.w26,r.w27,r.w28,r.w29,r.w30,r.w31,r.w32,r.w33,r.w34,r.w35,r.w36,r.w37,r.w38,r.w39,r.w40,r.w41,r.w42,r.w43,r.w44,r.w45, r.w46, r.w46, r.m1,r.m2,r.m3,r.m4,r.m5,r.m6,r.m7,r.m8,r.m9,r.m10,r.m11,r.m12,r.m13,r.m14,r.m15,r.m16,r.m17,r.m18,r.m19,r.m20, r.m21, r.m22, r.m23,r.m24,r.m25,r.m26,r.m27,r.m28,r.m29,r.m30,r.m31,r.m32,r.m33,r.m34,r.m35,r.m36,r.m37, r.m38,r.m39,r.m40,r.m41,r.m42,r.m43,r.m44,r.m45,r.m46,r.m47,r.m48,r.m49,r.m50,r.m51, (SELECT COUNT( e.id )FROM employees e WHERE e.role_id=r.id AND e.deleted_at IS NULL) AS employees, r.max_discount, r.is_order_notif, r.is_reservation_notif, r.is_withdrawal_notif, r.department_access FROM roles r WHERE r.master_id='$tokenDecoded->masterID' AND r.deleted_at IS NULL ORDER BY r.id DESC");
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
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "roles"=>$data]);  

?>