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
$email = $obj['email'];
$partner_id = $obj['partnerID'];
$res = array();
$status=200;

if (!empty($email) && !empty($partner_id)) {
    //validate email format
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result = $deleteUser->deleteEmployee($email, $partner_id, 'UR');
        
        if($result == "SUCCESS") {
            $msg = "Permintaan hapus akun berhasil, mohon cek email anda";
            $status = 200;
            $success = 1;
        } elseif($result == "USER_NOT_FOUND") {
            $msg = "Akun email tidak terdaftar";
        } else {
            $msg = "Maaf layanan ini tidak dapat digunakan sementara. Mohon hubungi customer service";
        }
        
    }else{
        $msg = 'Format Email Salah' ;
    }
}else
    $msg = 'Email dan ID Partner Tidak Boleh Kosong' ;
// }

$signupJson = json_encode(["msg"=>$msg,"success"=>$success]);
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;

 ?>
