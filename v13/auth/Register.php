<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../db_connection.php';
require_once('./Token.php');

$token = "";
$today = date("Y-m-d H:i:s");
$tokenizer = new Token();

$obj = json_decode(file_get_contents("php://input"));
if(gettype($obj)=="NULL"){
    $obj = json_decode(json_encode($_POST));
}
$data = array();
if(
    isset($obj->phone)&&!empty($obj->phone)
    && isset($obj->name)&&!empty($obj->name)
    && isset($obj->email)&&!empty($obj->email)
    && isset($obj->password)&&!empty($obj->password)
){

    $phone = $obj->phone;
    $name = $obj->name;
    $test = [];
    //adding user to database
    $q = mysqli_query($db_conn, "SELECT id FROM users WHERE (phone = '$phone' OR email='$obj->email') AND deleted_at IS NULL AND organization='Natta'");
    
    array_push($test, "SELECT id FROM users WHERE (phone = '$phone' OR email='$obj->email') AND deleted_at IS NULL AND organization='Natta'");
    array_push($test, "INSERT INTO users ( name, phone, email, organization, password) VALUES ('$name', '$phone', '$obj->email', 'Natta', MD5('$obj->password'))");
    if (mysqli_num_rows($q) > 0) {
        $success = 0;
        $status = 204;
        $msg = "Akun dengan nomor HP yang sama sudah terdaftar";
    } else {
        $insert = mysqli_query($db_conn, "INSERT INTO users ( name, phone, email, organization, password) VALUES ('$name', '$phone', '$obj->email', 'Natta', MD5('$obj->password'))");
        if($insert){

            $iid = mysqli_insert_id($db_conn);
            $jsonToken = json_encode(['email'=>$obj->email, 'phone'=>$phone, 'created_at'=>$today, 'expired'=>50000, "id"=>$iid , "email"=>$obj->email ]);
            $token = $tokenizer->stringEncryption('encrypt', $jsonToken);
            $success = 1;
            $status = 200;
            $msg = "Pendaftaran berhasil";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Kesalahan Sistem, Silahkan Coba Lagi";
        }
    }

}else{
    $success = 0;
    $status = 400;
    $msg = "Mohon lengkapi form";
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "token"=>$token, "test"=>$test]);
?>