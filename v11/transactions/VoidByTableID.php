<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header(
    "Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"
);

//import require file
require "../../db_connection.php";
require_once "../auth/Token.php";
require_once "../../includes/DbOperation.php";
require "../../includes/functions.php";

$fs = new functions();
$db = new DbOperation();

//init var
$headers = [];
$rx_http = "/\AHTTP_/";
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, "", $key);
        $rx_matches = [];
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode("_", $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) {
                $rx_matches[$ak_key] = ucfirst($ak_val);
            }
            $arh_key = implode("-", $rx_matches);
        }
        $headers[$arh_key] = $val;
    }
}
$tokenizer = new Token();
$token = "";
$res = [];
$totalPending = "0";
function updateMV($phone, $vr, $trx, $db_conn)
{
    $q = mysqli_query(
        $db_conn,
        "SELECT id  FROM `user_voucher_ownership` WHERE `userid` LIKE '$phone' AND `voucherid`='$vr' AND `transaksi_id`='$trx' ORDER BY id ASC limit 1"
    );
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id = $res[0]["id"];
        $update = mysqli_query(
            $db_conn,
            "UPDATE `user_voucher_ownership` SET `transaksi_id`=NULL WHERE id='$id'"
        );
        return $update;
    } else {
        return 0;
    }
}
//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}
$total_data = 0;
$currentShiftID=0;
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption("decrypt", $token));
if (isset($tokenValidate["success"]) && $tokenValidate["success"] == 0) {
    $status = $tokenValidate["status"];
    $msg = $tokenValidate["msg"];
    $success = 0;
} else {
    $obj = json_decode(file_get_contents("php://input"));
    if (
        isset($obj->cancel_notes) &&
        !empty($obj->cancel_notes) &&
        isset($obj->created_by) &&
        !empty($obj->created_by) &&
        isset($obj->accBy) &&
        !empty($obj->accBy) &&
        isset($obj->tableID) &&
        !empty($obj->tableID)
    ) {
        $getLastShift = mysqli_query($db_conn, "SELECT MAX(id) as id FROM `shift` WHERE partner_id='$token->id_partner' AND deleted_at IS NULL");
            while($row=mysqli_fetch_assoc($getLastShift)){
                $currentShiftID = $row['id'];
            }
        $getTrx = mysqli_query(
            $db_conn,
            "SELECT id, phone FROM transaksi WHERE status=5 AND deleted_at IS NULL AND id_partner='$token->id_partner' AND no_meja='$obj->tableID'"
        );
        if (mysqli_num_rows($getTrx) > 0) {
            $trx = mysqli_fetch_all($getTrx, MYSQLI_ASSOC);
            foreach ($trx as $x) {
                $phone = $x['phone'];
                $trxID = $x["id"];
                $updateStatus = mysqli_query(
                    $db_conn,
                    "UPDATE `transaksi` SET status=4, group_id=null WHERE id='$trxID'"
                );

                $updateDetailS = mysqli_query(
                    $db_conn,
                    "INSERT INTO `transaction_cancellation`(`transaction_id`,  `notes`, `created_by`, `created_at`, shift_id, acc_by) VALUES ('$trxID', '$obj->cancel_notes', '$obj->created_by', NOW(), '$currentShiftID', '$obj->accBy')"
                );
                if($phone=="POS/PARTNER" || $phone=="WAITERAPP"){
                    
                }else
                {
                    updateMV($phone, $id_voucher_redeemable, $trxID, $db_conn);
                }
                
                $title = "Pesanan Dibatalkan";
                $message = "Pesanan anda dibatalkan Kasir.";
                $qDT = mysqli_query(
                    $db_conn,
                    "SELECT id_menu, qty, variant FROM `detail_transaksi` WHERE id_transaksi='$trxID' AND deleted_at IS NULL"
                );
                if (mysqli_num_rows($qDT) > 0) {
                    $detailsTransaction = mysqli_fetch_all($qDT, MYSQLI_ASSOC);
                    $menusOrder = [];
                    $variantOrder = [];
                    $imo = 0;
                    $iv = 0;
                    foreach ($detailsTransaction as $value) {
                        $menusOrder[$imo]["id_menu"] = $value["id_menu"];
                        $menusOrder[$imo]["qty"] = (int) $value["qty"];
                        if (!empty($value["variant"])) {
                            $cut = $value["variant"];
                            $cut = substr($cut, 11);
                            $cut = substr($cut, 0, -1);
                            $cut = str_replace("'", '"', $cut);
                            $menusOrder[$imo]["variant"] = json_decode($cut);
                            if ($menusOrder[$imo]["variant"] != null) {
                                foreach (
                                    $menusOrder[$imo]["variant"]
                                    as $value1
                                ) {
                                    foreach ($value1->detail as $value2) {
                                        $variantOrder[$iv]["id"] = $value2->id;
                                        $variantOrder[$iv]["qty"] =
                                            $value2->qty;
                                        $ch = $fs->variant_stock_return(
                                            $value2->id,
                                            intval($value2->qty)
                                        );
                                        $iv += 1;
                                    }
                                }
                            }
                        }
                        $imo += 1;
                    }
                    //Menu
                    foreach ($menusOrder as $value) {
                        $qtyOrder = $value["qty"];
                        $menuID = $value["id_menu"];
                        $ch = $fs->stock_return($menuID, $qtyOrder);
                    }
                }
                if($phone=="POS/PARTNER" || $phone=="WAITERAPP"){
                    
                }else
                {
                    $insertMessage = mysqli_query(
                    $db_conn,
                    "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$trxID'"
                );
                $udevTokens = $db->getUserDeviceTokens($phone);
                foreach ($udevTokens as $val) {
                    $dev_token = $val["token"];
                    if ($dev_token != "TEMPORARY_TOKEN") {
                        $fcm_token = $dev_token;
                        $id = null;
                        $action = null;
                        $notif = $db->savePaymentNotification(
                            $fcm_token,
                            $title,
                            $message,
                            $no_meja,
                            "ur-user",
                            $payment_method,
                            $obj->status,
                            $queue,
                            $external_id,
                            $id_partner,
                            $action,
                            $order,
                            $gender,
                            $birthDate,
                            $isMembership,
                            0,
                            "",
                            $phone
                        );
                    }
                }
                }
                
            }
        
            $success = 1;
            $status = 200;
            $msg = "Berhasil batalkan semua pesanan di meja ".$obj->tableID;
        } else {
            $success = 0;
            $status = 404;
            $msg = "Tidak ditemukan transaksi di meja ini";
        }
    } else {
        $success = 0;
        $status = 400;
        $msg = "Missing required fields";
    }
}

echo json_encode([
    "success" => $success,
    "status" => $status,
    "msg" => $msg,
]);
?>
