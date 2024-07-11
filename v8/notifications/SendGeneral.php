<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
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
$iid = 0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{

    $obj = json_decode(file_get_contents('php://input'));
    if(
        isset($obj->title) && !empty($obj->title)
        &&isset($obj->content) && !empty($obj->content)
        ){
            $title = $obj->title;
            $content = $obj->content;
            $tempTitle = $title;
            $tempContent = $content;
            if(str_contains($obj->content,'@name') || str_contains($obj->title,'@name')){
                $getName = mysqli_query($db_conn,"SELECT d.user_phone, d.tokens, u.name FROM device_tokens d JOIN users u ON u.phone=d.user_phone WHERE d.user_phone IS NOT NULL AND d. deleted_at IS NULL and d. employee_id IS NULL AND id_partner IS NULL");
                while($row=mysqli_fetch_assoc($getName)){
                    $token = $row['tokens'];
                    $phone = $row['user_phone'];
                    $title=str_replace("@name", $row['name'], $title);
                    $content=str_replace("@name", $row['name'], $content);
                    $sent = mysqli_query($db_conn,"INSERT INTO pending_notification (phone, dev_token, title, message) VALUES ('$phone','$token','$title', '$content')");
                    $title=$tempTitle;
                    $content=$tempContent;
                }
            }else{
                $sent=mysqli_query($db_conn,"INSERT INTO pending_notification (phone, dev_token, title, message) SELECT user_phone, tokens, '$obj->title', '$obj->content' FROM device_tokens WHERE user_phone IS NOT NULL AND deleted_at IS NULL AND employee_id IS NULL AND id_partner IS NULL");
            }
            $message = mysqli_query($db_conn, "INSERT INTO messages (phone,title,content,type) SELECT phone, '$title', '$content', '
                1', FROM users WHERE deleted_at IS NULL");
        if ($sent) {
            $success =1;
            $status =200;
            $msg = "Berhasil kirim notif";
        } else {
            $success =0;
            $status =204;
            $msg = "Gagal kirim notif";
        }
    }else{
        $success =0;
        $status =400;
        $msg = "Mohon lengkapi form";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "insertedID"=>$iid]);
?>