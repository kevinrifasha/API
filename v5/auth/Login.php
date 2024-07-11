<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin,Content-Type,X-Amz-Date,Authorization,X-Api-Key,X-Amz-Security-Token,locale");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");
require '../../db_connection.php';
require_once('./Token.php');

$token = "";
$today = date("Y-m-d H:i:s");
$tokenizer = new Token();
$pin = 0;

$obj = json_decode(file_get_contents("php://input"));
if(gettype($obj)=="NULL"){
    $obj = json_decode(json_encode($_POST));
}
$data = array();
$fetched = array();

if(
    (isset($obj->phone)&&!empty($obj->phone) || isset($obj->email)&&!empty($obj->email))
    && (isset($obj->password)&&!empty($obj->password) || isset($obj->pin)&&!empty($obj->pin))
){
    $phone = "";
    $email = "";
    $password = "";
    $pin = "";
    
    if(isset($obj->email)&&!empty($obj->email)) {
        $email = $obj->email;
    }
    if(isset($obj->password)&&!empty($obj->password)) {
        $password = $obj->password;
    }
    if(isset($obj->phone)&&!empty($obj->phone)) {
        $phone = $obj->phone;
    }
    if(isset($obj->pin)&&!empty($obj->pin)) {
        $pin = $obj->pin;
    }
    
    $newPassword = md5($password);
    $newPin = md5($pin);
    if(!empty($obj->email)){
        $q = mysqli_query($db_conn, "SELECT name FROM `users` WHERE  email='$email'");
    }else{
        $q = mysqli_query($db_conn, "SELECT name FROM `users` WHERE  phone='$phone'");
    }
    if (mysqli_num_rows($q) > 0) {
        if(!empty($obj->email)){
            $q = mysqli_query($db_conn, "SELECT id, email, name, phone, pin FROM `users` WHERE  email='$email' AND (password ='$newPassword' OR pin='$newPassword' OR pin='$newPin')");
        }else{
            $q = mysqli_query($db_conn, "SELECT id, email, name, phone, pin FROM `users` WHERE phone='$phone' AND (password ='$newPassword' OR pin='$newPassword' OR pin='$newPin')");
        }
        if (mysqli_num_rows($q) > 0) {
            $fetched = mysqli_fetch_assoc($q);
            if($fetched['pin']==null){
                $pin=0;
            }else{
                $pin=1;
            }
            $jsonToken = json_encode(['email'=>$fetched['email'], 'phone'=>$fetched['phone'], 'created_at'=>$today, 'expired'=>30000, "id"=>$fetched['id'] , "email"=>$fetched['email'] ]);
            $token = $tokenizer->stringEncryption('encrypt', $jsonToken);
            $success = 1;
            $status = 200;
            $msg = "Logged";
        } else {
            $fetched['name'] = "";
            $fetched['phone'] = "";
            $fetched['email'] = "";
            $fetched['id'] = "";
            $success = 0;
            $status = 204;
            $msg = "Username Atau Password Salah!";
        }
    }else{
        $fetched['name'] = "";
        $fetched['phone'] = "";
        $fetched['email'] = "";
        $fetched['id'] = "";
        $success = 0;
        $status = 404;
        $msg = "Email Atau Nomor Telp. Tidak Terdaftar!";
    }

}else{
    $success = 0;
    $status = 400;
    $msg = "Missing Require Field";
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "token"=>$token, "name"=>$fetched['name'], "pin"=>$pin, "phone"=>$fetched['phone'], "email"=>$fetched['email'], "id"=>$fetched['id']]);
?>