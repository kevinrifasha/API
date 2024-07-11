<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';

$query = "SELECT id, in_time, out_time FROM `attendance` WHERE DATE(in_time)<='2021-12-07' ORDER BY `attendance`.`id`  DESC";
$allRecom = mysqli_query($db_conn, $query);
if (mysqli_num_rows($allRecom) > 0) {
    $rowR = mysqli_fetch_all($allRecom, MYSQLI_ASSOC);
    foreach ($rowR as $value) {
        $id = $value['id'];
        $in_time = substr($value['in_time'],0,11); 
        $out_time = substr($value['out_time'],11); 
        $in_time .= $out_time;
        $query = mysqli_query($db_conn,"UPDATE `attendance` SET `out_time`='$in_time' WHERE `id`='$id'");
        var_dump($query);
    };
}else{
    echo "nothing happens";
}