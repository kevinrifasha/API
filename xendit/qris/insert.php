<?php
date_default_timezone_set('Asia/Jakarta');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../v2/db_connection.php';
$timestamp = new DateTime();

$data = json_decode(file_get_contents('php://input'));

$id_external = mysqli_real_escape_string($db_conn, trim($qr_code['id_external']));
$id_partner = mysqli_real_escape_string($db_conn, trim($qr_code['id_partner']));
$amount = mysqli_real_escape_string($db_conn, trim($data['amount']));
$status = mysqli_real_escape_string($db_conn, trim($data['status']));
$updated_at = $timestamp->format('YYYY-MM-DD HH:MI:SS');

$insertTagihanUr = mysqli_query(
    $db_conn,
    "INSERT INTO tagihan_ur (id_external, id_partner, amount, status, created_at, updated_at) VALUES ('$id_external', '$id_partner', $amount, '$status', created_at)",
);

if ($insertTagihanUr) {
    echo json_encode(["success" => 1, "msg" => "Buat Tagihan Sukses", "status" => 201]);
} else {
    echo json_encode(["success" => 0, "msg" => "Buat Tagihan Gagal", "status" => 200]);
}
