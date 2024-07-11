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
$status = $data["status"];
$external_id = $data["qr_code"]["external_id"];
$event = $data["event"];
// }

if ($event == "qr.payment") {

    $data1 = json_encode($data);

    $UpdateCallback = mysqli_query(
        $db_conn,
        "INSERT INTO `xendit_callbacks`(`transaction_id`, `value`, `created_at`) VALUES ('$external_id', '$data1', NOW())"
    );
    
    $mainID = explode("-",$external_id);
    $external_id = $mainID[0];
    $testExt = $external_id;

    $getTrx = mysqli_query(
        $db_conn,
        "SELECT transaksi.id ,transaksi.pre_order_id, transaksi.no_meja, transaksi.id_partner, users.dev_token AS udev_token, meja.is_queue, transaksi.takeaway, transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, transaksi.point, transaksi.queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.charge_ur, users.TglLahir ,users.name AS uname, payment_method.nama AS payment_method, transaksi.status, users.Gender as gender, employee_discount FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN payment_method ON payment_method.id=transaksi.tipe_bayar JOIN users ON users.phone=transaksi.phone JOIN meja ON meja.idpartner=transaksi.id_partner AND meja.idmeja=transaksi.no_meja WHERE transaksi.id LIKE '%$external_id%' AND (status_callback NOT LIKE '%SUCCEEDED%' AND status_callback NOT LIKE '%PAID%' AND status_callback NOT LIKE '%SETTLED%' AND status_callback NOT LIKE '%COMPLETED%') OR status_callback IS NULL AND transaksi.id LIKE '%$external_id%' AND transaksi.deleted_at IS NULL ORDER BY transaksi.jam DESC LIMIT 1"
    );
    if (mysqli_num_rows($getTrx) < 1) {
        $testGetTrx = "hit this 1";
        $getTrx = mysqli_query(
            $db_conn,
            "SELECT transaksi.id, transaksi.pre_order_id, transaksi.no_meja, transaksi.id_partner, users.dev_token AS udev_token, transaksi.takeaway,transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, employee_discount, transaksi.point, transaksi.queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.charge_ur, users.TglLahir, users.name AS uname, payment_method.nama AS payment_method, transaksi.status, users.Gender as gender FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id LIKE '%$external_id%' AND (status_callback NOT LIKE '%SUCCEEDED%' AND status_callback NOT LIKE '%PAID%' AND status_callback NOT LIKE '%SETTLED%' AND status_callback NOT LIKE '%COMPLETED%') OR status_callback IS NULL AND transaksi.id LIKE '%$external_id%' AND transaksi.deleted_at IS NULL ORDER BY transaksi.jam DESC LIMIT 1"
        );
    }

    $no_meja = 0;
    $id_partner = 0;
    $dev_token = 0;
    $udev_token = 0;
    $is_queue = 0;
    $takeaway = 0;
    // $status=1;
    $queue = 0;
    $gender = "";
    $birthDate = null;
    $isMembership = false;
    $queue = 0;
    $delivery_fee = "0";
    $tStatus = "0";
    $is_delivery = 0;
    $pre_order_id = "0";
    $tenant_id = "";

    $order = [];
    while ($row = mysqli_fetch_assoc($getTrx)) {
        $tStatus = $row["status"];
        $qD = mysqli_query(
            $db_conn,
            "SELECT ongkir, rate_id, user_address_id, delivery_detail, is_insurance FROM `delivery` WHERE transaksi_id='$external_id'"
        );
        if (mysqli_num_rows($qD) > 0) {
            $resDel = mysqli_fetch_all($qD, MYSQLI_ASSOC);
            $delivery_fee = $resDel[0]["ongkir"];
            $is_delivery = 1;
        }
        if (isset($row["no_meja"])) {
            $no_meja = $row["no_meja"];
        } else {
            $no_meja = "";
        }
        if (isset($row["tenant_id"])) {
            $tenant_id = $row["tenant_id"];
        } else {
            $tenant_id = "";
        }
        $external_id = $row["id"];
        $testExtRow = $external_id;
        $pre_order_id = $row["pre_order_id"];
        $id_partner = $row["id_partner"];
        $dev_token = $row["device_token"];
        $udev_token = $row["udev_token"];
        if (isset($row["is_queue"])) {
            $is_queue = $row["is_queue"];
        } else {
            $is_queue = 0;
        }
        $takeaway = $row["takeaway"];
        $gender = $row["gender"];
        $jam = $row["jam"];
        $phone = $row["phone"];
        $birthDate = $row["TglLahir"];
        $total = $row["total"];
        $id_voucher = $row["id_voucher"];
        $id_voucher_redeemable = $row["id_voucher_redeemable"];
        $tipe_bayar = $row["tipe_bayar"];
        $promo = $row["promo"];
        $diskon_spesial = $row["diskon_spesial"];
        $employee_discount = $row["employee_discount"];
        $point = $row["point"];
        $queue = $row["queue"];
        $takeaway = $row["takeaway"];
        $notes = $row["notes"];
        $tax = $row["tax"];
        $service = $row["service"];
        $charge_ur = $row["charge_ur"];
        $payment_method = $row["payment_method"];
        $uname = $row["uname"];
        $row["status"] = "1";
        $order = $row;

        $qIM = mysqli_query(
            $db_conn,
            "SELECT m.id FROM memberships m JOIN partner p ON p.id_master=m.master_id WHERE m.user_phone='$phone' AND p.id='$id_partner' ORDER BY m.id DESC LIMIT 1"
        );
        if (mysqli_num_rows($qIM) > 0) {
            $isMembership = true;
        } else {
            $isMembership = false;
        }
    }
    if (
        $status == "COMPLETED" ||
        $status == "PAID" ||
        $status == "SETTLED" ||
        $status == "completed" ||
        $status == "paid" ||
        $status == "settled"
    ) {
        $status = 1;
        $insertOST = mysqli_query(
            $db_conn,
            "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`, `created_at`) VALUES ('$external_id', '$tStatus', '$status', NOW())"
        );

        $updateTrans = mysqli_query(
            $db_conn,
            "UPDATE transaksi SET paid_date='$today11', shift_id='$lastShiftID', tipe_bayar='14' WHERE id='$external_id'"
        );
        
        $test = [];
        $test[0] = "gateway 1 " . $updateTrans . "<br />";
        $test[1] = "query: " . "UPDATE transaksi SET paid_date='$today11', shift_id='$lastShiftID', tipe_bayar='14' WHERE id='$external_id'"; 
        if ($is_queue != 1 && $takeaway != 1 && $is_delivery != 1) {
            $updateTrans = mysqli_query(
                $db_conn,
                "UPDATE transaksi SET paid_date=NOW(), status='$status', shift_id='$lastShiftID', tipe_bayar='14' WHERE id='$external_id'"
            );
            $test1 = [];
            $test1[0] = "gateway 2 " . $updateTrans;
            $test1[1] = "query: ". "UPDATE transaksi SET paid_date=NOW(), status='$status', shift_id='$lastShiftID', tipe_bayar='14' WHERE id='$external_id'";
            if ($updateTrans) {
                $response["error"] = false;
                $response["message"] =
                    "Transaksi Update Successfully wihtout queue";
            } else {
                $response["error"] = true;
                $response["message"] = "Transaksi not updated";
            }
            if ($status == 1 || $status == "1") {
                $masterID = mysqli_query(
                    $db_conn,
                    "SELECT id_master FROM `partner` WHERE id='$id_partner'"
                );
                $id_master = 0;
                while ($row = mysqli_fetch_assoc($masterID)) {
                    $id_master = $row["id_master"];
                }
                $updateDeposit = mysqli_query(
                    $db_conn,
                    "UPDATE `master` SET `deposit_balance`=`deposit_balance`-'$charge_ur' WHERE id='$id_master'"
                );

                $getIdMenu = mysqli_query(
                    $db_conn,
                    "SELECT dt.id_menu, dt.qty, dt.variant, m.is_recipe FROM `detail_transaksi` dt JOIN menu m ON m.id = dt.id_menu WHERE id_transaksi='$external_id' AND dt.deleted_at IS NULL"
                );
                $getTipeBayar = mysqli_query(
                    $db_conn,
                    "SELECT tipe_bayar FROM `transaksi` WHERE id = '$external_id'"
                );
                while ($rowTipeBayar = mysqli_fetch_assoc($getTipeBayar)) {
                    $tipe = $rowTipeBayar["tipe_bayar"];
                    while ($rowIdMenu = mysqli_fetch_assoc($getIdMenu)) {
                        $idmenu = $rowIdMenu["id_menu"];
                        $qty = $rowIdMenu["qty"];
                        $isRecipe = $rowIdMenu['is_recipe'];
                        $variant = $rowIdMenu["variant"];
                        $var = substr($variant, 11);
                        $var = substr($var, 0, -1);
                        $var = str_replace("'", '"', $var);
                        $arr_var = json_decode($var, true);
                        if ($arr_var != null) {
                            foreach ($arr_var as $vars) {
                                $d_vars = $vars["detail"];
                                foreach ($d_vars as $detail) {
                                    $var_id = $detail["id"];
                                    $ch = $fs->variant_stock_reduce(
                                        $var_id,
                                        $qty
                                    );
                                }
                            }
                        }
                        // $ch = $fs->stock_reduce($idmenu, $qty);
                        $ch = $fs->stock_reduce($idmenu, $qty, 0, $id_master, $id_partner, $isRecipe);
                    }
                }
            }
        } else {
            $dates1 = date("Y-m-d", time());
            $var = 0;
            $queueQ = mysqli_query(
                $db_conn,
                "SELECT MAX(queue) as LastQueue FROM transaksi WHERE id_partner = '$id_partner' AND DATE(jam) = '$dates1' LIMIT 1"
            );
            while ($row = mysqli_fetch_assoc($queueQ)) {
                if (isset($row["LastQueue"]) && !empty($row["LastQueue"])) {
                    $var = $row["LastQueue"];
                } else {
                    $var = 1;
                }
                $queue = $var;
            }
            $updateTrans = mysqli_query(
                $db_conn,
                "UPDATE transaksi SET paid_date=NOW(), status='$status', queue='$var', shift_id='$lastShiftID', tipe_bayar='14' WHERE id='$external_id'"
            );
            $test2 = [];
            $test2[0] = "gateway 3 " . $updateTrans;
            // echo "<br />";
            $test2[1] = "query: ". "UPDATE transaksi SET paid_date=NOW(), status='$status', queue='$var', shift_id='$lastShiftID', tipe_bayar='14' WHERE id='$external_id'";
            // echo "<br />";
            // echo "<br />";
            if ($updateTrans) {
                $response["error"] = false;
                $response["message"] = "Transaksi Update Successfully";
            } else {
                $response["error"] = true;
                $response["message"] = "Transaksi not updated";
            }
            if ($status == 1 || $status == "1") {
                $masterID = mysqli_query(
                    $db_conn,
                    "SELECT id_master FROM `partner` WHERE id='$id_partner'"
                );
                $id_master = 0;
                while ($row = mysqli_fetch_assoc($masterID)) {
                    $id_master = $row["id_master"];
                }
                $updateDeposit = mysqli_query(
                    $db_conn,
                    "UPDATE `master` SET `deposit_balance`=`deposit_balance`-'$charge_ur' WHERE id='$id_master'"
                );
                $getIdMenu = mysqli_query(
                    $db_conn,
                    "SELECT dt.id_menu, dt.qty, dt.variant, m.is_recipe FROM `detail_transaksi` dt JOIN menu m ON m.id = dt.id_menu WHERE id_transaksi='$external_id' AND dt.deleted_at IS NULL"
                );
                $getTipeBayar = mysqli_query(
                    $db_conn,
                    "SELECT tipe_bayar FROM `transaksi` WHERE id = '$external_id'"
                );
                while ($rowTipeBayar = mysqli_fetch_assoc($getTipeBayar)) {
                    $tipe = $rowTipeBayar["tipe_bayar"];
                    while ($rowIdMenu = mysqli_fetch_assoc($getIdMenu)) {
                        $idmenu = $rowIdMenu["id_menu"];
                        $qty = $rowIdMenu["qty"];
                        $isRecipe = $rowIdMenu['is_recipe'];
                        $variant = $rowIdMenu["variant"];
                        $var = substr($variant, 11);
                        $var = substr($var, 0, -1);
                        $var = str_replace("'", '"', $var);
                        $arr_var = json_decode($var, true);
                        if ($arr_var != null) {
                            foreach ($arr_var as $vars) {
                                $d_vars = $vars["detail"];
                                foreach ($d_vars as $detail) {
                                    $var_id = $detail["id"];
                                    $ch = $fs->variant_stock_reduce(
                                        $var_id,
                                        $qty
                                    );
                                }
                            }
                        }
                        // $ch = $fs->stock_reduce($idmenu, $qty);
                        $ch = $fs->stock_reduce($idmenu, $qty, 0, $id_master, $id_partner, $isRecipe);
                    }
                }
            }
        }
        if (isset($tenant_id) && !empty($tenant_id)) {
            $devTokens = $db->getPartnerDeviceTokens($tenant_id);
        } else {
            $devTokens = $db->getPartnerDeviceTokens($id_partner);
        }
        foreach ($devTokens as $val) {
            $dev_token = $val["token"];
            $fcm_token = $dev_token;
            $title = "Pesanan baru";
            $message =
                "Ada pesanan baru dari meja " .
                $no_meja .
                ", mohon refresh ulang untuk menampilkan transaksi terbaru";
            if ($takeaway == "1") {
                $message =
                    "Ada pesanan takeaway baru, mohon refresh ulang untuk menampilkan transaksi terbaru";
            }
            if ($is_delivery == 1) {
                $message =
                    "Ada pesanan delivery baru, mohon refresh ulang untuk menampilkan transaksi terbaru";
            }
            if ($pre_order_id == "1") {
                $message =
                    "Ada pesanan pre order baru, mohon refresh ulang untuk menampilkan transaksi terbaru";
            }
            $id = null;
            $action = null;

            $notif = $db->savePaymentNotification(
                $fcm_token,
                $title,
                $message,
                $no_meja,
                "rn-push-notification-channel",
                $payment_method,
                $status,
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

        $title = "Pembayaran Diterima";
        $message =
            "Pembayaran untuk pesanan anda(" .
            $external_id .
            ") telah diterima, silahkan tunggu pesanan anda.";
        $id = null;
        $action = null;
        $insertMessage = mysqli_query(
            $db_conn,
            "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$external_id'"
        );
        $udevTokens = $db->getUserDeviceTokens($phone);
        foreach ($udevTokens as $val) {
            $dev_token = $val["token"];
            $fcm_token = $dev_token;
            $title = "Pembayaran Diterima";
            $message =
                "Pembayaran untuk pesanan anda telah diterima, silahkan tunggu pesanan anda.";
            $id = null;
            $action = null;
            $notif = $db->savePaymentNotification(
                $fcm_token,
                $title,
                $message,
                $no_meja,
                "rn-push-notification-channel",
                $payment_method,
                $status,
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

            $insertMessage = mysqli_query(
                $db_conn,
                "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$external_id'"
            );
        }

        // get last shift id nya dulu
        $qGetLastShift = "SELECT id FROM `shift` WHERE partner_id = '$id_partner' AND end IS NULL AND deleted_at IS NULL ORDER BY `id` DESC LIMIT 1";
        $sqlGetLastShift = mysqli_query($db_conn, $qGetLastShift);
        if (mysqli_num_rows($sqlGetLastShift) > 0) {
            $fetchGetLastShift = mysqli_fetch_all($sqlGetLastShift, MYSQLI_ASSOC);
            $lastShiftID = $fetchGetLastShift[0]['id'];
        } else {
            $lastShiftID = 0;
        }
        // get last shift id nya dulu end

        $UpdateCallback = mysqli_query(
            $db_conn,
            "UPDATE `transaksi` SET `status`=1, `status_callback`='$data1',`callback_hit`=`callback_hit`+1, `callback_at`=NOW(), shift_id='$lastShiftID', paid_date=NOW() WHERE id='$external_id'"
        );
        $test3 = [];
        $test3[0] = "gateway 6 " . $updateTrans;
        // echo "<br />";
        $test3[1] = "query: ". "UPDATE `transaksi` SET `status`=1, `status_callback`='$data1',`callback_hit`=`callback_hit`+1, `callback_at`=NOW(), shift_id='$lastShiftID', paid_date=NOW() WHERE id='$external_id'";
        // echo "<br />";
        // echo "<br />";
    } else {
        $status = 0;
        if ($is_queue != 1 && $takeaway != 1) {
            $updateTrans = mysqli_query(
                $db_conn,
                "UPDATE transaksi SET paid_date=NOW(), status='$status', shift_id='$lastShiftID', tipe_bayar='14' WHERE id='$id'"
            );
            $test4 = [];
            $test4[0] = "gateway 4 " . $updateTrans;
            // echo "<br />";
            $test4[1] = "query: ". "UPDATE transaksi SET paid_date=NOW(), status='$status', shift_id='$lastShiftID', tipe_bayar='14' WHERE id='$id'";
            // echo "<br />";
            // echo "<br />";
            if ($updateTrans) {
                $response["error"] = false;
                $response["message"] =
                    "Transaksi Update Successfully wihtout queue";
            } else {
                $response["error"] = true;
                $response["message"] = "Transaksi not updated";
            }
        } else {
            $dates1 = date("Y-m-d", time());
            $var = 0;
            $queue = mysqli_query(
                $db_conn,
                "SELECT MAX(queue) as LastQueue FROM transaksi WHERE id_partner = '$id_partner' AND DATE(jam) = '$dates1' LIMIT 1"
            );
            while ($row = mysqli_fetch_assoc($queue)) {
                $var = $row["LastQueue"] + 1;
                $queue = $var;
            }
            $updateTrans = mysqli_query(
                $db_conn,
                "UPDATE transaksi SET paid_date=NOW(), status='$status', queue='$var', shift_id='$lastShiftID', tipe_bayar='14' WHERE id='$id'"
            );
            $test5 = [];
            $test5[0] = "gateway 5 " . $updateTrans;
            // echo "<br />";
            $test5[1] = "query: ". "UPDATE transaksi SET paid_date=NOW(), status='$status', shift_id='$lastShiftID', tipe_bayar='14' WHERE id='$id'";
            // echo "<br />";
            // echo "<br />";
            if ($updateTrans) {
                $response["error"] = false;
                $response["message"] = "Transaksi Update Successfully";
            } else {
                $response["error"] = true;
                $response["message"] = "Transaksi not updated";
            }
        }

        $insertOST = mysqli_query(
            $db_conn,
            "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`, `created_at`) VALUES ('$external_id', '$tStatus', '0', NOW())"
        );
        // $updateTrans = mysqli_query($db_conn,"UPDATE transaksi SET paid_date='$today11' WHERE id='$external_id'");

        $udevTokens = $db->getUserDeviceTokens($phone);
        $title = "Pesanan Dibatalkan";
        $message =
            "Pesanan untuk pesanan anda(" . $external_id . ") telah dibatalkan";
        $id = null;
        $action = null;
        $insertMessage = mysqli_query(
            $db_conn,
            "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$external_id'"
        );
        foreach ($udevTokens as $val) {
            $dev_token = $val["token"];
            $fcm_token = $dev_token;
            $title = "Pesanan Anda Dibatalkan";
            $message = "Pesanan anda dibatalkan.";
            $id = null;
            $action = null;
            $notif = $db->savePaymentNotification(
                $fcm_token,
                $title,
                $message,
                $no_meja,
                "rn-push-notification-channel",
                $payment_method,
                $status,
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

            $insertMessage = mysqli_query(
                $db_conn,
                "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$external_id'"
            );
        }

        $UpdateCallback = mysqli_query(
            $db_conn,
            "UPDATE `transaksi` SET `status_callback`='$data1',`callback_hit`=`callback_hit`+1, `callback_at`=NOW(), shift_id='$lastShiftID' WHERE id='$external_id'"
        );
        $test6 = [];
        $test6[0] = "gateway 7 " . $UpdateCallback;
        // echo "<br />";
        $test6[1] = "query: ". "UPDATE `transaksi` SET `status_callback`='$data1',`callback_hit`=`callback_hit`+1, `callback_at`=NOW(), shift_id='$lastShiftID' WHERE id='$external_id'";
        // echo "<br />";
        // echo "<br />";
    }
}

// if ($addSaldo) {
echo json_encode([
    "success" => 1,
    "msg" => "Callback Success",
    "status" => 200,
    "gateways" => [
        "1" => $test,
        "2" => $test1,
        "3" => $test2,
        "4" => $test3,
        "5" => $test4,
        "6" => $test5,
        "7" => $test6,
    ],
    "external_id" => $external_id,
    "testExt" => $testExt,
    "testExtRow" => $testExtRow,
    "testGetTrx" => $testGetTrx
]);
// } else {
//     echo json_encode(["success" => 0, "msg" => "Callback Fail", "status" => 200]);
// }
