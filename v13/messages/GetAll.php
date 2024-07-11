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
    if(isset($_GET['page'])&&!empty($_GET['page']) && isset($_GET['load'])&&!empty($_GET['load'])){
        
        $page = $_GET['page'];
        $load = $_GET['load'];
        $finish = $load * $page;
        $start = $finish - $load;
        
        $q = mysqli_query($db_conn, "SELECT id, title, content, image, type, read_at, transaction_id, created_at FROM( SELECT m.id, m.title, m.content, m.image, m.type, m.read_at, m.transaction_id, m.created_at, m.deleted_at FROM `messages` as m LEFT JOIN `users` as u ON m.phone = u.phone WHERE u.organization='Natta' UNION SELECT m.id, m.title, m.content, m.image, m.type, m.read_at, m.transaction_id, m.created_at, m.deleted_at FROM `messages` as m LEFT JOIN `transaksi` as t ON m.transaction_id = t.id WHERE t.organization='Natta' ) AS mess WHERE mess.deleted_at IS NULL AND phone='$token->phone' ORDER BY created_at DESC LIMIT $start,$load");
    
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $success =1;
            $status =200;
            $msg = "Success";
        } else {
            $success =0;
            $status =204;
            $msg = "Data Not Found";
        }
    } else {
        $success =0;
        $status =203;
        $msg = "400 Missing Required Field";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "messages"=>$res]);
?>