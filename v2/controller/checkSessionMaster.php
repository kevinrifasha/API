<?php
$http_origin = $_SERVER['HTTP_ORIGIN'];

if ($http_origin == "http://localhost:3000" || $http_origin == "https://partner.ur-hub.com" || $http_origin == "https://master.ur-hub.com" || $http_origin == "https://admin.ur-hub.com"){
    header("Access-Control-Allow-Origin: $http_origin");
}
header("Access-Control-Allow-Credentials:true");
header('Content-type: application/json');
session_start();
require_once("../includes/fonctions.php");
// require_once("../modele/partnerManager.php");

// $json = file_get_contents('php://input');

// decoding the received JSON and store into $obj variable.
// $obj = json_decode($json, true);

// Populate User email from JSON $obj array and store into $email.

// Populate Password from JSON $obj array and hash it (is stored on db hashed with sha1) store into $password.

if (isset($_SESSION['email_user'])) {
    //    $Msg = "Log in success";
    $Msg = [
        'status' => 200,
        'id' => $_SESSION['id'],
        // 'name' => $_SESSION['name']
        // 'id' => 1
    ];
} else {
    $Msg = "You must be logged to show shops";
}

$Msg = json_encode($Msg);
echo ($Msg);
