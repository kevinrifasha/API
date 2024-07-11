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

$data = array();
if (
    isset($obj->phone) && !empty($obj->phone)
    && isset($obj->name) && !empty($obj->name)
    // && isset($obj->email)&&!empty($obj->email)
) {

    $phone = $obj->phone;
    $name = $obj->name;
    $q = "";
    if (empty($obj->email)) {
        $obj->email = "";
    }
    if (empty($obj->name)) {
        $obj->name = "";
    }
    if (empty($obj->phone)) {
        $obj->phone = "";
    }
    if (empty($obj->gender)) {
        $qBirthday = " TglLahir = null ";
        // $obj->birthDate = "";
    } else {
        $qBirthday = " TglLahir = '$obj->birthDate' ";
    }
    if(empty($obj->masterID)) {
        $obj->masterID = 0;
    }
    if(!isset($obj->firstPartner) || empty($obj->firstPartner)) {
        $obj->firstPartner = NULL;
    }
    //adding user to database
    if (isset($obj->email) && !empty($obj->email)) {
        $q = mysqli_query($db_conn, "SELECT id FROM users WHERE (phone = '$phone' OR email='$obj->email') AND deleted_at IS NULL");
    } else {
        $q = mysqli_query($db_conn, "SELECT id FROM users WHERE phone = '$phone' AND deleted_at IS NULL");
    }

    if (mysqli_num_rows($q) > 0 && (!isset($obj->registerMembership) || $obj->registerMembership == false)) {
        $success = 0;
        $status = 204;
        $msg = "Akun dengan nomor HP yang sama sudah terdaftar";
    } else if (mysqli_num_rows($q) > 0 && $obj->registerMembership == true){
        $insertMember = mysqli_query($db_conn, "INSERT INTO memberships SET user_phone='$obj->phone', master_id='$obj->masterID'");
        $update = mysqli_query($db_conn, "UPDATE users SET email='$obj->email', " . $qBirthday . ", Gender='$obj->gender', source='web', first_partner = '$obj->firstPartner' WHERE phone='$obj->phone'");
        $success = 1;
        $status = 200;
        $msg = "Pendaftaran membership berhasil";
    } else {
        $insert = mysqli_query($db_conn, "INSERT INTO users SET email='$obj->email', name='$obj->name', phone='$obj->phone', " . $qBirthday . ", Gender='$obj->gender', source='web', first_partner = '$obj->firstPartner'");
        if ($insert) {
            $iid = mysqli_insert_id($db_conn);
            $jsonToken = json_encode(['email' => $obj->email, 'phone' => $phone, 'created_at' => $today, 'expired' => 50000, "id" => $iid, "email" => $obj->email]);
            $token = $tokenizer->stringEncryption('encrypt', $jsonToken);
            $success = 1;
            $status = 200;
            $msg = "Pendaftaran berhasil";
        } else {
            $success = 0;
            $status = 204;
            $msg = "Kesalahan Sistem, Silahkan Coba Lagi";
        }
    }
} else {
    $success = 0;
    $status = 400;
    $msg = "Mohon lengkapi form";
}

echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "token" => $token, "obj"=>$obj]);
