<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header(
    "Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"
);
require "../../db_connection.php";
require_once "../../includes/DbOperation.php";
require_once "../../includes/functions.php";
date_default_timezone_set("Asia/Jakarta");

$db = new DbOperation();
$fs = new functions();
$today11 = date("Y-m-d H:i:s");
$data = json_decode(file_get_contents("php://input"), true);
// $ewallet_type = $data["payment_details"]["channel_code"];
if(isset($data["status"])){
    $status = $data["status"];
} else {
    $status = $data["data"]["status"];
}

if(isset($data["qr_code"]["external_id"])){
    $external_id = $data["qr_code"]["external_id"];
} else if($data["data"]) {
    $external_id = $data["data"]["reference_id"];
}
$event = $data["event"];
// }

if ($event == "qr.payment") {

    $data1 = json_encode($data);
    $partnerId = explode('/', $external_id)[1];
    $billingId = explode('/', $external_id)[2];

    // $UpdateCallback = mysqli_query(
    //     $db_conn,
    //     "INSERT INTO `xendit_callbacks`(`transaction_id`, `value`, `created_at`) VALUES ('$external_id', '$data1', NOW())"
    // );

    if (
        $status == "COMPLETED" ||
        $status == "PAID" ||
        $status == "SETTLED" ||
        $status == "SUCCEEDED" ||
        $status == "completed" ||
        $status == "paid" ||
        $status == "settled" ||
        $status == "succeeded"  
    ) {
        $status = 'PAID';
    } else {
        $status = 'CANCELLED';
    }
    $updateBill = mysqli_query(
        $db_conn,
        "UPDATE subscription_transactions SET paid_at=NOW(), updated_at=NOW(), status='$status' WHERE id='$billingId'"
    );
    $getPartnerSubs = mysqli_query(
        $db_conn,
        "SELECT
        	sp.type,
        	sp.name,
            p.subscription_until,
            st.package_id
        FROM subscription_transactions AS st
        LEFT JOIN partner AS p ON p.id=st.partner_id
        LEFT JOIN subscription_packages AS sp ON st.package_id=sp.id
        WHERE p.id='$partnerId'
        ORDER BY st.created_at DESC
        LIMIT 1"
    );
    $getPartnerSubs = mysqli_fetch_assoc($getPartnerSubs);
    $packageId = $getPartnerSubs['package_id'];
    $packageName = $getPartnerSubs['name'];
    $now = date_create('now');
    $expiry = date_create($getPartnerSubs['subscription_until']);
    if ($getPartnerSubs['type'] == 'Monthly'){
        $interval = "1 month";
    } else {
        $interval = "1 year";
    }
    $newDate = '';
    if ($now > $expiry){
        date_add($now, date_interval_create_from_date_string($interval));
        $newDate = date_format($now,"Y-m-d H:i:s");
    } else {
        date_add($expiry, date_interval_create_from_date_string($interval));
        $newDate = date_format($expiry,"Y-m-d H:i:s");
    }
    $addOnQuery = '';
    if ($packageName == 'Professional'){
        $addOnQuery = "
            is_reservation=1,
        	is_special_reservation=1,
            is_dp=1,
        ";
    }
    $query = "
        UPDATE partner 
        SET 
            subscription_status = 'Subscribed',
            primary_subscription_id = $packageId,
            subscription_paid_date = NOW(),
            subscription_until = '$newDate',".
            $addOnQuery
            ."updated_at = NOW()
        WHERE id = '$partnerId'";
    $updatePartner = mysqli_query($db_conn, $query);
    $UpdateCallback = mysqli_query(
        $db_conn,
        "INSERT INTO `xendit_callbacks`(`transaction_id`, `value`, `created_at`) VALUES ('$external_id', '$data1', NOW())"
    );
}

// if ($addSaldo) {
echo json_encode([
    "success" => 1,
    "msg" => "Callback Success",
    "status" => 200
]);
// } else {
//     echo json_encode(["success" => 0, "msg" => "Callback Fail", "status" => 200]);
// }
