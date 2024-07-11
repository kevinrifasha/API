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
        (isset($obj->id) && !empty($obj->id)) || (isset($obj->menuID) && !empty($obj->menuID))
        ){
        $q = mysqli_query($db_conn, "UPDATE favorites SET deleted_at=NOW() WHERE id='$obj->id' OR (phone='$token->phone' AND menu_id='$obj->menuID')");

        if ($q) {
            $iid = mysqli_insert_id($db_conn);
            $success =1;
            $status =200;
            $msg = "Berhasil hapus favorit";
        } else {
            $success =0;
            $status =204;
            $msg = "Gagal hapus favorit. Mohon coba lagi";
        }
    }else{
        $success =0;
        $status =400;
        $msg = "Mohon lengkapi form";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>