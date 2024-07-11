<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('Asia/Jakarta');
$date=date("Y-m-d H:i:s");
require '../db_connection.php';

$transaksi = mysqli_query($db_conn, "SELECT id,status FROM transaksi WHERE jam < DATE_SUB('$date',INTERVAL 60 MINUTE) AND status =0");
while ($row = mysqli_fetch_assoc($transaksi)) {
    $id = $row['id'];
    $status = $row['status'];
    $change = mysqli_query($db_conn, "UPDATE transaksi SET status=3 WHERE id ='$id'");
    $insertOST = mysqli_query($db_conn, "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`, `created_at`) VALUES ('$id', '$status', '3', NOW())");
}

if ($transaksi) {
    echo json_encode(["response" => true]);
} else {
    echo json_encode(["response" => false]);
}
?>