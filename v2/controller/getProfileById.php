<?php
$http_origin = $_SERVER['HTTP_ORIGIN'];

if ($http_origin == "http://localhost:3000" || $http_origin == "https://partner.ur-hub.com") {
    header("Access-Control-Allow-Origin: $http_origin");
}

header("Access-Control-Allow-Credentials:true");
header('Content-type: application/json');
session_start();
// require_once("../includes/fonctions.php");
// require_once("../modele/partnerManager.php");
require '../db_connection.php';

$id = $_GET['id'];

// $db = connectBase();

// $manager = new PartnerManager($db);
// $partner = $manager->getPartnerDetails($id);

$query = "SELECT * FROM partner WHERE id='$id' LIMIT 1";
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
