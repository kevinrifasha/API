<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../db_connection.php';
require_once('./Token.php');


$phone = $_GET['phone'];
$email = $_GET['email'];

    $q = mysqli_query($db_conn, "SELECT id FROM users WHERE (phone = '$phone' OR email='$email') AND deleted_at IS NULL");

    if (mysqli_num_rows($q) > 0) {
        $success = 0;
        // http_response_code(404);
        $msg = "Akun dengan nomor HP yang sama sudah terdaftar";
    } else {
        $success = 1;
    }

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>