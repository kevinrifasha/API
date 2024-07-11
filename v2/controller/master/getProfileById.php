<?php
$http_origin = $_SERVER['HTTP_ORIGIN'];

if ($http_origin == "http://localhost:3000" || $http_origin == "https://master.ur-hub.com") {
    header("Access-Control-Allow-Origin: $http_origin");
}
header("Access-Control-Allow-Credentials:true");
header('Content-type: application/json');
session_start();
require '../../db_connection.php';

$id = $_GET['id'];

$query = "SELECT * FROM master WHERE id='$id' LIMIT 1";
$result = mysqli_query($db_conn, $query);

if (mysqli_num_rows($result) == 1) {
    $data = mysqli_fetch_all($result, MYSQLI_ASSOC);

    $json = [
        'status' => 200,
        'result' => $data
    ];
} else {
    $json = [
        'status' => 204,
        'result' => 'Fetch partner failed'
    ];
}

$json = json_encode($json);

echo $json;
