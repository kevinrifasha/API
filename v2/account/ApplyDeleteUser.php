<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once '../../includes/DeleteAccount.php';

// $db = new DbOperation();
$deleteUser = new DeleteUser();

$json = file_get_contents('php://input');
$obj = json_decode($json,true);
$today = DATE("Y-m-d H:i:s");
$success=0;
$token = $obj['token'];
$res = array();
$status=200;

    if (!empty($token)) {
        $result = $deleteUser->applyDelete($token, 'UR');
        
        if($result == "SUCCESS") {
            $msg = "Hapus akun berhasil";
            $status = 200;
            $success = 1;
        } elseif($result = "USER_NOT_FOUND") {
            $msg = "Akun email tidak terdaftar";
        } else {
            $msg = "Maaf layanan ini tidak dapat digunakan sementara. Mohon hubungi customer service";
        }
    }else
        $msg = 'Token Tidak Boleh Kosong' ;
// }

$signupJson = json_encode(["msg"=>$msg,"success"=>$success]);
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;

 ?>
