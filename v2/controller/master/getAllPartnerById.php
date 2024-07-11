<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../includes/fonctions.php");
require '../../db_connection.php';

$id = $_GET['id'];
$db = connectBase();
// $id = '1';

$query = "SELECT * FROM partner WHERE id_master='$id'";
$result = mysqli_query($db_conn, $query);

if(mysqli_num_rows($result) > 0){
    $results = mysqli_fetch_all($result,MYSQLI_ASSOC);
    echo json_encode(["success"=>1,"partners"=>$results]);
}
else{
    echo json_encode(["success"=>0]);
}
?>
