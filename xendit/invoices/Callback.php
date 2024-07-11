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
$now = date("Y-m-d");
$status = $data['status'];
$externalID = $data['external_id'];
$error = $data['failure_code'];
$status = $data['status'];
$paymentMethod = $data['payment_method'];
$paymentChannel = $data['payment_channel'];
$paidAmount = $data['paid_amount'];
$invoiceStatus = $status;

if (strlen($error) < 1) {
    $error = null;
}
//functions
function sendRsvEmail($db_conn, $rsv)
{
    $partnerTemplate = mysqli_query($db_conn, "SELECT template FROM email_template WHERE id=3");
    if (mysqli_num_rows($partnerTemplate) > 0) {
        $partnerID = $rsv['partner_id'];
        $templates = mysqli_fetch_all($partnerTemplate, MYSQLI_ASSOC);
        $template = $templates[0]['template'];
        $template = str_replace('$id', $rsv['id'], $template);
        $template = str_replace('$customerName', $rsv['name'], $template);
        $template = str_replace('$dateTime', $rsv['reservation_time'], $template);
        $template = str_replace('$specialRequest', $rsv['description'], $template);
        $template = str_replace('$pax', $rsv['persons'], $template);
        $template = str_replace('$partnerName', $rsv['partnerName'], $template);
        $emailSubject = "Reservasi Baru Dari " . $rsv['name'];
        $getEmails = mysqli_query($db_conn, "SELECT e.email FROM employees e WHERE e.deleted_at IS NULL AND e.id_partner='$partnerID'");
        while ($row = mysqli_fetch_assoc($getEmails)) {
            $emailAddress = $row['email'];
            $emailPartner = "INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`) VALUES ('$emailAddress', '$partnerID', '$emailSubject', '$template')";
            $insertTe = mysqli_query($db_conn, $emailPartner);
        }
    }
}
//main
if (str_contains($externalID, 'RSV/')) {
    $rsvID = explode("/", $externalID);
    $rsvID = $rsvID[1];
    if ($invoiceStatus == "PAID") {
        $updateRsv = mysqli_query($db_conn, "UPDATE reservations SET status='Paid', paid_date=NOW(), payment_method='$paymentMethod', payment_channel='$paymentChannel', reference_id='$externalID' WHERE id='$rsvID'");
        $getRsvDetails = mysqli_query($db_conn, "SELECT p.name AS partnerName, p.id_master AS masterID, m.idmeja AS tableName, r.id, r.partner_id, r.user_id, r.name, r.phone, r.email, r.reservation_time, r.description, r.persons FROM reservations r LEFT JOIN for_reservation fr ON fr.id=r.for_reservation_id LEFT JOIN users u ON u.id=r.user_id LEFT JOIN partner p ON p.id=r.partner_id LEFT JOIN meja m ON m.id=r.table_id WHERE r.id='$rsvID'");
        $rsvDetails = mysqli_fetch_all($getRsvDetails, MYSQLI_ASSOC);
        $rsv = $rsvDetails[0];
        $masterID = $rsv['masterID'];
        $partnerID = $rsv['partner_id'];
        $customerName = $rsv['name'];
        $customerPhone = $rsv['phone'];
        $tableName = $rsv['tableName'];
        $notes = "Reservasi meja " . $rsv['tableName'] . " tgl " . $rsv['reservation_time'];
        sendRsvEmail($db_conn, $rsv);
        if ($updateRsv) {
            $insertDP = mysqli_query($db_conn, "INSERT INTO down_payments SET master_id='$masterID', partner_id='$partnerID', amount='$paidAmount', customer_name='$customerName', customer_phone='$customerPhone', reservation_id='$rsvID', notes='$notes'");
        }
    } else if ($invoiceStatus == "EXPIRED") {
        $updateRsv = mysqli_query($db_conn, "UPDATE reservations SET deleted_at=NOW() WHERE id='$rsvID'");
    }
    $insertCallback = mysqli_query($db_conn, "INSERT INTO xendit_service_callback SET content='$rawData', reservation_id='$rsvID', type='Reservation Invoice'");
} else {
    $invoiceID = explode("_", $externalID);
    $partnerID = $invoiceID[2];
    $invoiceID = $invoiceID[3];
    $insertCallback = mysqli_query($db_conn, "INSERT INTO xendit_service_callback SET content='$rawData', invoice_id='$invoiceID', type='Invoice'");
    $paymentMethod = $data['payment_method'] . " - " . $data['payment_channel'];
    $paymentMethod = str_replace("_", " ", $paymentMethod);
    if ($status == "PAID") {
        $sqlUpdateTrx = "UPDATE subscription_transactions SET status='PAID', paid_at=NOW(), payment_method='$paymentMethod' WHERE id='$invoiceID'";
        $resUpdateTrx = mysqli_query($db_conn, $sqlUpdateTrx);
        if ($resUpdateTrx) {
            $getProductID = mysqli_query($db_conn, "SELECT sp.id, sp.type, sp.name FROM subscription_packages sp JOIN subscription_transaction_details std ON std.item_id=sp.id JOIN subscription_transactions st ON st.id=std.transaction_id WHERE st.id='$invoiceID'");
            $resProductID = mysqli_fetch_all($getProductID, MYSQLI_ASSOC);
            $productID = $resProductID[0]['id'];
            $productType = $resProductID[0]['type'];
            $productName = $resProductID[0]['name'];
            if ($productType == "Monthly") {
                $subsUntil = date("Y-m-d", strtotime("+1 months"));
            } else if ($productType == "Yearly") {
                $subsUntil = date("Y-m-d", strtotime("+1 years"));
            }
            $title = "Transaksi Berhasil";
            $content = "Pembelian paket " . $productName . " berhasil. Mohon restart aplikasi UR Partner dan cek status berlangganan.";
            $updatePartner = "UPDATE partner SET subscription_status='Subscribed', primary_subscription_id='$productID', subscription_until='$subsUntil' WHERE id='$partnerID'";
            $qUpdatePartner = mysqli_query($db_conn, $updatePartner);
            $getEmployees = mysqli_query($db_conn, "SELECT id FROM employees WHERE withdraw_notification=1 AND id_partner='$partnerID' AND deleted_at IS NULL");
            $insertMessage = mysqli_query($db_conn, "INSERT INTO partner_messages SET partner_id='$partnerID', title='$title', content='$content'");
            while ($emp = mysqli_fetch_assoc($getEmployees)) {
                $employeeID = $emp['id'];
                $getToken = mysqli_query($db_conn, "SELECT tokens FROM device_tokens WHERE employee_id='$employeeID' AND deleted_at IS NULL");
                while ($devID = mysqli_fetch_assoc($getToken)) {
                    $devToken = $devID['tokens'];
                    $sendNotif = mysqli_query($db_conn, "INSERT INTO pending_notification SET title='$title', message='$content', dev_token='$devToken', orders='$invoiceID'");
                }
            }
        }
    }

    // $updateDisbursementStatus = mysqli_query($db_conn, "UPDATE withdrawal_requests SET status='$status', error='$error' WHERE id='$disbursementID'");
    // if($status=="COMPLETED"){
    //     $title="Penarikan Dana Berhasil";
    //     $content="Dana berhasil dicairkan. Mohon cek mutasi rekening anda";
    // }else{

    // }
    // $getEmployees = mysqli_query($db_conn, "SELECT id FROM employees WHERE withdraw_notification=1 AND id_partner='$partnerID' AND deleted_at IS NULL");
    // $insertMessage = mysqli_query($db_conn,"INSERT INTO partner_messages SET partner_id='$partnerID', title='$title', content='$content'");
    // while($emp = mysqli_fetch_assoc($getEmployees)){
    //     $employeeID = $emp['id'];
    //     $getToken = mysqli_query($db_conn, "SELECT tokens FROM device_tokens WHERE employee_id='$employeeID' AND deleted_at IS NULL");
    //     while($devID = mysqli_fetch_assoc($getToken)){
    //         $devToken = $devID['tokens'];
    //         $sendNotif=mysqli_query($db_conn, "INSERT INTO pending_notification SET title='$title', message='$content', dev_token='$devToken'");
    //     }
    // }
}

if ($insertCallback) {
    $success = 1;
    $status = 200;
    $msg = "Callback Success";
} else {
    $success = 0;
    $status = 204;
    $msg = "Callback Failed";
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "id" => $rsvID]);
