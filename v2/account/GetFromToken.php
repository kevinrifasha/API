<?php
$http_origin = $_SERVER['HTTP_ORIGIN'];

if ($http_origin == "http://localhost:3003" || $http_origin == "http://localhost:3001" || "http://localhost:3000") {
    header("Access-Control-Allow-Origin: $http_origin");
}
header("Access-Control-Allow-Origin: $http_origin");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require "../../db_connection.php";

// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../..");
// $dotenv->load();
// $db = connectBase();

$token = $_GET['token'];

$qUser = "
    SELECT
    	u.id,
        u.phone,
        u.email
    FROM users u
    INNER JOIN reset_password rp ON rp.email = u.email
    WHERE rp.token='$token'
    AND u.organization='UR'
    AND u.deleted_at IS NULL
";
$sqlUser = mysqli_query($db_conn, $qUser);
if($sqlUser){
$user = mysqli_fetch_assoc($sqlUser);
    $success = 1;
    $status = 200;
    $message= "Get user succeed";
}

echo json_encode(["success" => $success, "status" => $status, "message" => $message, "user" => $user]);