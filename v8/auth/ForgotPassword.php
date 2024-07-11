<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
// require_once '../../includes/DbOperation.php';
require_once '../../includes/ForgotPassword.php';
$status = 200;
// $db = new DbOperation();
$db = new ForgotPass();
$json = file_get_contents('php://input');
$obj = json_decode($json,true);
if(isset($obj['phone']) && !empty($obj['phone'])){
    // $result = $db->forgotPassword($obj['phone']);
    // if ($result == TRANSAKSI_CREATED) {
    //     $success = 1;
    //     $status = 200;
    //     $msg = 'Berhasil Mengirim Silahkan Cek Email Anda';
    // } else {
    //     $success = 0;
    //     $status = 400;
    //     $msg = 'Gagal Mengirim';
    // }
    $success = 0;
    $status = 400;
    $msg = "Maaf layanan ini tidak dapat digunakan sementara. Mohon hubungi customer service";
}else if(isset($obj['email']) && !empty($obj['email'])){
    $email = $obj['email'];
    $q = mysqli_query($db_conn, "SELECT phone FROM `users` WHERE email='$email'");
    $phone = "";
    if (mysqli_num_rows($q) > 0) {
        $fetched = mysqli_fetch_assoc($q);
        $phone = $fetched['phone'];
        // $result = $db->forgotPassword($phone);
        // if ($result == TRANSAKSI_CREATED) {
        //     $success = 1;
        //     $status = 200;
        //     $msg = 'Berhasil Mengirim Silahkan Cek Email Anda';
        // } else {
        //     $success = 0;
        //     $status = 400;
        //     $msg = 'Gagal Mengirim';
        // }
        // $success = 0;
        // $status = 400;
        // $msg = "Maaf layanan ini tidak dapat digunakan sementara. Mohon hubungi customer service";
        
        $result = $db->forgotPassword($email, "users");
            
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
        $success = 0;
        $status = 204;
        $msg = 'Email Tidak Terdaftar';
    }

}else{
    $success = 0;
    $status = 400;
    $msg = 'Parameter Tidak Boleh Kosong';
}
if($status==204){
    http_response_code(200);
}else{

}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>