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
    date_default_timezone_set('Asia/Jakarta');
    $today =date('l');
    $lastOrder = $today."_last_order";
    $lastOrder = strtolower($lastOrder);
    $id = $_GET['id'];
    $getLastOrder = mysqli_query($db_conn, "SELECT $lastOrder FROM partner_opening_hours WHERE partner_id='$id'");
    $shift = mysqli_query($db_conn, "SELECT id FROM shift WHERE partner_id = '$id' AND end IS NULL");
    $res = mysqli_fetch_all($getLastOrder, MYSQLI_ASSOC);
    $currentLastOrder = $res[0][$lastOrder];
    $result = mysqli_num_rows($shift);
    if($result){
        if(date('H:i')>=$currentLastOrder){
            $success =0;
            $status =204;
            $msg = "Sudah melewati batas last order. Coba lagi besok";
        }else{
            $success=1;
            $msg="Order acc";
            $status=200;
        }
    }else{
        $success=0;
        $msg="Resto belum memulai shift. Mohon hubungi kasir";
        $status=204;
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>