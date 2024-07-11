<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
date_default_timezone_set('Asia/Jakarta');
$data = json_decode(file_get_contents('php://input'), true);
$rawData = json_encode($data);
echo $data;
$status = $data['status'];
$externalID = $data['external_id'];
$error = $data['failure_code'];
$description = $data['disbursement_description'];
if (strlen($error) < 1) {
    $error = null;
}
$partnerID = explode(" ", $description);
$partnerID = $partnerID[2];
$disbursementID = explode("-", $externalID);
$disbursementID = $disbursementID[4];
$insertCallback = mysqli_query($db_conn, "INSERT INTO xendit_service_callback SET content='$rawData', withdrawal_id='$disbursementID', type='Disbursement'");

$updateDisbursementStatus = mysqli_query($db_conn, "UPDATE withdrawal_requests SET status='$status', error='$error' WHERE id='$disbursementID'");
if ($status == "COMPLETED") {
    $title = "Penarikan Dana Berhasil";
    $content = "Dana berhasil dicairkan. Mohon cek mutasi rekening anda";
} else {
}
$getEmployees = mysqli_query($db_conn, "SELECT id FROM employees WHERE withdraw_notification=1 AND id_partner='$partnerID' AND deleted_at IS NULL");
$insertMessage = mysqli_query($db_conn, "INSERT INTO partner_messages SET partner_id='$partnerID', title='$title', content='$content'");
while ($emp = mysqli_fetch_assoc($getEmployees)) {
    $employeeID = $emp['id'];
    $getToken = mysqli_query($db_conn, "SELECT tokens FROM device_tokens WHERE employee_id='$employeeID' AND deleted_at IS NULL");
    while ($devID = mysqli_fetch_assoc($getToken)) {
        $devToken = $devID['tokens'];
        $sendNotif = mysqli_query($db_conn, "INSERT INTO pending_notification SET title='$title', message='$content', dev_token='$devToken'");
    }
}
if ($insertCallback && $updateDisbursementStatus) {
    $success = 1;
    $status = 200;
    $msg = "Callback Success";
} else {
    $success = 0;
    $status = 204;
    $msg = "Callback Failed";
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg]);
