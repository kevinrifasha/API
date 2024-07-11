<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../v2/db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

$payment_type = mysqli_real_escape_string($db_conn, trim($data['payment_type']));

if ($payment_type=="gopay") {
    $order_id = mysqli_real_escape_string($db_conn, trim($data['order_id']));
    $transaction_status = mysqli_real_escape_string($db_conn, trim($data['transaction_status']));
    $gross_amount = mysqli_real_escape_string($db_conn, trim($data['gross_amount']));
    $gross_amount = $gross_amount-(0.02*$gross_amount);
    if ($transaction_status == "settlement") {
        $UpdateCallback = mysqli_query(
            $db_conn,
            "UPDATE `transaksi` SET `status`=1 WHERE id='$order_id'"
        );
        if ($UpdateCallback) {
            $addSaldo = mysqli_query(
                $db_conn,
                "UPDATE partner
                JOIN transaksi ON transaksi.id_partner = partner.id 
                SET partner.saldo_ewallet=partner.saldo_ewallet+$gross_amount 
                WHERE transaksi.id='$order_id'"
            );
            echo json_encode(["success" => 1, "msg" => "Callback Success", "status" => 200]);
        } else {
            echo json_encode(["success" => 0, "msg" => "Callback Fail", "status" => 200]);
        }
    } else {
        $UpdateCallback = mysqli_query(
            $db_conn,
            "UPDATE `transaksi` SET `status`=0 WHERE id='$order_id'"
        );
    }
}

