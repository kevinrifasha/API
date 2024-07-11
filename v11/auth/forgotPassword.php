<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// require_once '../../includes/DbOperation.php';
require_once '../../includes/ForgotPassword.php';

// $db = new DbOperation();
$db = new ForgotPass();

$json = file_get_contents('php://input');
$obj = json_decode($json,true);
$today = DATE("Y-m-d H:i:s");
$success=0;
$email = $obj['email'];
$res = array();
$status=200;

    if (!empty($email)) {
        $email = strtolower($email);
        //validate email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // $result = $db->forgotPasswordEmployee($email);
            // if ($result == TRANSAKSI_CREATED) {
            //     $msg = "Berhasil Mengirim Silahkan Cek Email Anda";
            //     $success = 1;
            // } else {
            //     $msg = 'Gagal mohon periksa email anda';
            // }
           
           $result = $db->forgotPassword($email, "employees");
            
            if($result == "SUCCESS") {
                $msg = "Permintaan reset password berhasil, mohon cek email anda";
                $status = 200;
                $success = 1;
            } elseif($result = "USER_NOT_FOUND") {
                $msg = "Akun email tidak terdaftar";
            } else {
                $msg = "Maaf layanan ini tidak dapat digunakan sementara. Mohon hubungi customer service";
            }

        }else{
            $msg = 'Format Email Salah' ;
        }
    }else
        $msg = 'Tidak Boleh Kosong' ;
// }

$signupJson = json_encode(["msg"=>$msg,  "success"=>$success]);
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;

 ?>
