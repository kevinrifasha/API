<?php
$http_origin = $_SERVER['HTTP_ORIGIN'];

if ($http_origin == "http://localhost:3000" || $http_origin == "https://partner.ur-hub.com" || $http_origin == "https://master.ur-hub.com" || $http_origin == "https://admin.ur-hub.com") {
    header("Access-Control-Allow-Origin: $http_origin");
}
header("Access-Control-Allow-Credentials:true");
header('Content-type: application/json');
session_start();
require_once("../includes/fonctions.php");

if (isset($_SESSION['email_user'])) {
    $Msg = [
        'status' => 200,
        'id' => $_SESSION['id']
    ];
} else {
    $Msg = "You must be logged to show shops";
}

$Msg = json_encode($Msg);
echo ($Msg);
