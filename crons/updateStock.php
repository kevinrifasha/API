<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';
require '../includes/functions.php';


$fs = new functions();
$query = "SELECT id, nama, is_recipe, stock FROM `menu` WHERE is_recipe=1 AND deleted_at IS NULL";
$allRecom = mysqli_query($db_conn, $query);
if (mysqli_num_rows($allRecom) > 0) {
    $rowR = mysqli_fetch_all($allRecom, MYSQLI_ASSOC);
    $res = $fs->stock_menu($rowR);
    foreach ($res as $value) {
        $mID = $value['id'];
        $stock = $value['stock'];
        $update = mysqli_query($db_conn, "UPDATE `menu` SET stock='$stock' WHERE id='$mID'");
    };
}else{
    echo "nothing happens";
}
$query = "SELECT id, name, is_recipe, stock FROM `variant` WHERE is_recipe=1";
$allRecom = mysqli_query($db_conn, $query);
if (mysqli_num_rows($allRecom) > 0) {
    $rowR = mysqli_fetch_all($allRecom, MYSQLI_ASSOC);
    $res = $fs->stock_variant($rowR);
    foreach ($res as $value) {
        $mID = $value['id'];
        $stock = $value['stock'];
        $update = mysqli_query($db_conn, "UPDATE `variant` SET stock='$stock' WHERE id='$mID'");
    };
}else{
    echo "nothing happens";
}


?>