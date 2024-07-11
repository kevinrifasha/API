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

            $sent=mysqli_query($db_conn,"INSERT INTO pending_notification (partner_id, dev_token, title, message) SELECT id_partner, tokens, '$obj->title', '$obj->content' FROM device_tokens WHERE id_partner IS NOT NULL AND deleted_at IS NULL AND employee_id IS NOT NULL AND user_phone IS NULL");
            $message = mysqli_query($db_conn, "INSERT INTO partner_messages (partner_id,title,content,type) SELECT id, '$title', '$content', '
                1', FROM partner WHERE deleted_at IS NULL");
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
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "insertedID"=>$iid]);
?>