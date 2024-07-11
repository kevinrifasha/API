<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';
require_once '../includes/DbOperation.php';
require_once '../includes/functions.php';
date_default_timezone_set('Asia/Jakarta');

$db = new DbOperation();
$fs = new functions();
$today11 = date("Y-m-d H:i:s");
$data = json_decode(file_get_contents('php://input'), true);
$ewallet_type = $data['data']['channel_code'];
$status = $data['data']['status'];
$external_id = $data['data']['reference_id'];
// }
$test = "";

if ($ewallet_type == "ID_OVO" || $ewallet_type == "ID_DANA" || $ewallet_type == "ID_LINKAJA" || $ewallet_type == "ID_SHOPEEPAY") {

    $external_id = $data['data']['reference_id'];
    $status = mysqli_real_escape_string($db_conn, trim($data['data']['status']));
    $amount = mysqli_real_escape_string($db_conn, trim($data['data']['charge_amount']));
    $amount = $amount - (ceil($amount * 0.02)) - (ceil(ceil($amount * 0.02) * 0.1));
    $dataTrxCallback = $data;
    $data1 = json_encode($data);

    $UpdateCallback = mysqli_query(
        $db_conn,
        "INSERT INTO `xendit_callbacks`(`transaction_id`, `value`, `created_at`) VALUES ('$external_id', '$data1', NOW())"
    );

    if (strpos($external_id, 'TOPUP') !== false || strpos($external_id, 'BILL') !== false) {
        $UpdateCallback = mysqli_query(
            $db_conn,
            "UPDATE `transaction_mobilepulsa` SET `callback_response_xendit`='$data1', `updated_at`=NOW() WHERE `tranasaction_code`='$external_id'"
        );

        $getTrx = mysqli_query($db_conn, "SELECT `transaction_mobilepulsa`.`id`, `transaction_mobilepulsa`.`tranasaction_code`, `transaction_mobilepulsa`.`phone`, `transaction_mobilepulsa`.`data`, `transaction_mobilepulsa`.`type`, `transaction_mobilepulsa`.`operator`, `transaction_mobilepulsa`.`price`, `transaction_mobilepulsa`.`payment_method`, `transaction_mobilepulsa`.`status`, `transaction_mobilepulsa`.`callback_response_xendit`, `transaction_mobilepulsa`.`callback_response_mobile_pulsa`, `transaction_mobilepulsa`.`created_at`, `payment_method`.`nama` as payment_name FROM `transaction_mobilepulsa` JOIN `payment_method` ON `payment_method`.`id`=`transaction_mobilepulsa`.`payment_method` WHERE `transaction_mobilepulsa`.`tranasaction_code`='$external_id'");

        $phone = "";
        $data = "";
        $type = "";
        $operator = "";
        $price = "";
        $payment_method = "";
        $tStatus = "";
        $payment_name = "";
        while ($row = mysqli_fetch_assoc($getTrx)) {
            $phone = $row['phone'];
            $data = $row['data'];
            $type = $row['type'];
            $operator = $row['operator'];
            $price = $row['price'];
            $payment_method = $row['payment_method'];
            $tStatus = $row['status'];
            $payment_name = $row['payment_name'];
        }

        if ($status == "SUCCEEDED" || $status == "PAID" || $status == "SETTLED" || $status == "COMPLETED" || $status == "completed" || $status == "paid" || $status == "settled") {
            if (strpos($external_id, 'TOPUP') !== false) {

                $obj = json_decode($data);
                $ref_id     = $external_id;
                $hp         = $obj->hp;
                $pulsa_code = $obj->pulsa_code;
                $username   = "085155053040";
                $apiKey     = "7296075190d9d5f7";
                $signature  = md5($username . $apiKey . $ref_id);
                $json = array(
                    "commands"   => "topup",
                    "username"   => $username,
                    "sign"       => $signature,
                    "ref_id"     => $ref_id,
                    "hp"         => $hp,
                    "pulsa_code" => $pulsa_code,
                );
                $json = json_encode($json);
                $url = "https://testprepaid.mobilepulsa.net/v1/legacy/index/";
                $ch  = curl_init();
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $data = curl_exec($ch);
                if (curl_errno($ch)) {
                    $msg = 'Request Error:' . curl_error($ch);
                }
                curl_close($ch);
                $response   = json_decode($data);

                $UpdateCallback = mysqli_query(
                    $db_conn,
                    "UPDATE `transaction_mobilepulsa` SET `status`='1', `updated_at`=NOW() WHERE `tranasaction_code`='$external_id'"
                );
                $status = 1;
            } else {
                $obj = json_decode($data);
                $tr_id     = $operator;
                $username   = "085155053040";
                $apiKey     = "7296075190d9d5f7";
                $signature  = md5($username . $apiKey . $tr_id);
                $json = array(
                    "commands" => "pay-pasca",
                    "username" => $username,
                    "tr_id" => $tr_id,
                    "sign"    => $signature
                );

                $json = json_encode($json);
                $url = "https://testpostpaid.mobilepulsa.net/api/v1/bill/check";
                $ch  = curl_init();
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $data = curl_exec($ch);
                if (curl_errno($ch)) {
                    $msg = 'Request Error:' . curl_error($ch);
                }
                curl_close($ch);
                $response   = json_decode($data);
                $ures = json_encode($response);

                $UpdateCallback = mysqli_query(
                    $db_conn,
                    "UPDATE `transaction_mobilepulsa` SET `status`='1', `updated_at`=NOW(), `callback_response_mobile_pulsa`='$ures' WHERE `tranasaction_code`='$external_id'"
                );
                $status = 1;
            }
            $udevTokens = $db->getUserDeviceTokens($phone);
            $title = "Pesanan Diproses";
            $message = "Pembayaran untuk pesanan anda(" . $external_id . ") telah diterima, silahkan tunggu pesanan anda.";
            $id = null;
            $action = null;
            $insertMessage = mysqli_query($db_conn, "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$external_id'");
            foreach ($udevTokens as $val) {
                $dev_token = $val['token'];
                $fcm_token = $dev_token;
                $title = "Pembayaran Diterima";
                $message = "Pembayaran untuk pesanan anda telah diterima, silahkan tunggu pesanan anda.";
                $id = null;
                $action = null;
                $notif = $db->savePaymentNotification($fcm_token, $title, $message, $no_meja, 'rn-push-notification-channel', $payment_method, $status, $queue, $external_id, $id_partner, $action, $order, $gender, $birthDate, $isMembership, 0, '', $phone);
            }
        } else {
            $status = 0;
            $udevTokens = $db->getUserDeviceTokens($phone);
            $title = "Pembayaran Gagal";
            $message = "Pembayaran untuk pesanan anda(" . $external_id . ") Gagal";
            $id = null;
            $action = null;
            $insertMessage = mysqli_query($db_conn, "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$external_id'");
            foreach ($udevTokens as $val) {
                $dev_token = $val['token'];
                $fcm_token = $dev_token;
                $title = "Pembayaran Gagal";
                $message = "Pembayaran untuk pesanan anda(" . $external_id . ") Gagal";
                $id = null;
                $action = null;
                $notif = $db->savePaymentNotification($fcm_token, $title, $message, $no_meja, 'rn-push-notification-channel', $payment_method, $status, $queue, $external_id, $id_partner, $action, $order, $gender, $birthDate, $isMembership, 0, '', $phone);
            }
        }
    } else {

        if (strpos($external_id, 'INV') !== false) {
            $getTrx = mysqli_query($db_conn, "SELECT transaksi.id ,transaksi.pre_order_id, transaksi.no_meja, transaksi.id_partner, users.dev_token AS udev_token, meja.is_queue, transaksi.takeaway, transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, transaksi.point, transaksi.queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.charge_ur, users.TglLahir ,users.name AS uname, payment_method.nama AS payment_method, transaksi.status, users.Gender as gender, employee_discount, 	transaksi.tenant_id FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN payment_method ON payment_method.id=transaksi.tipe_bayar JOIN users ON users.phone=transaksi.phone JOIN meja ON meja.idpartner=transaksi.id_partner AND meja.idmeja=transaksi.no_meja WHERE transaksi.id='$external_id' AND (status_callback NOT LIKE '%SUCCEEDED%' AND status_callback NOT LIKE '%PAID%' AND status_callback NOT LIKE '%SETTLED%' AND status_callback NOT LIKE '%COMPLETED%') OR status_callback IS NULL AND transaksi.id='$external_id'");
            if (mysqli_num_rows($getTrx) < 1) {
                $getTrx = mysqli_query($db_conn, "SELECT transaksi.id, transaksi.pre_order_id, transaksi.id_partner, users.dev_token AS udev_token, transaksi.takeaway,transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, employee_discount, transaksi.point, transaksi.queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.charge_ur, users.TglLahir, users.name AS uname, payment_method.nama AS payment_method, transaksi.status, users.Gender as gender, transaksi.tenant_id FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id='$external_id' AND (status_callback NOT LIKE '%SUCCEEDED%' AND status_callback NOT LIKE '%PAID%' AND status_callback NOT LIKE '%SETTLED%' AND status_callback NOT LIKE '%COMPLETED%') OR status_callback IS NULL AND transaksi.id='$external_id'");
            }
        } else {
            
            $mainID = explode("-",$external_id);
            $external_id = $mainID[0];
            
            $getTrx = mysqli_query($db_conn, "SELECT transaksi.id ,transaksi.pre_order_id, transaksi.no_meja, transaksi.id_partner, users.dev_token AS udev_token, meja.is_queue, transaksi.takeaway, transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, transaksi.point, transaksi.queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.charge_ur, users.TglLahir ,users.name AS uname, payment_method.nama AS payment_method, transaksi.status, users.Gender as gender, employee_discount FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN payment_method ON payment_method.id=transaksi.tipe_bayar JOIN users ON users.phone=transaksi.phone JOIN meja ON meja.idpartner=transaksi.id_partner AND meja.idmeja=transaksi.no_meja WHERE transaksi.id LIKE '%$external_id%' AND ((status_callback NOT LIKE '%SUCCEEDED%' AND status_callback NOT LIKE '%PAID%' AND status_callback NOT LIKE '%SETTLED%' AND status_callback NOT LIKE '%COMPLETED%') OR status_callback IS NULL) AND transaksi.deleted_at IS NULL ORDER BY transaksi.jam DESC LIMIT 1");
            if (mysqli_num_rows($getTrx) < 1) {
                $getTrx = mysqli_query($db_conn, "SELECT transaksi.id, transaksi.pre_order_id, transaksi.no_meja, transaksi.id_partner, users.dev_token AS udev_token, transaksi.takeaway,transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, employee_discount, transaksi.point, transaksi.queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.charge_ur, users.TglLahir, users.name AS uname, payment_method.nama AS payment_method, transaksi.status, users.Gender as gender FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id LIKE '%$external_id%' AND ((status_callback NOT LIKE '%SUCCEEDED%' AND status_callback NOT LIKE '%PAID%' AND status_callback NOT LIKE '%SETTLED%' AND status_callback NOT LIKE '%COMPLETED%') OR status_callback IS NULL) AND transaksi.deleted_at IS NULL ORDER BY transaksi.jam DESC LIMIT 1");
            }
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
        $delivery_fee =  '0';
        $tStatus =  '0';
        $is_delivery = 0;
        $pre_order_id = '0';
        $tenant_id = '';

        $order = [];
        while ($row = mysqli_fetch_assoc($getTrx)) {
            $tStatus =  $row['status'];
            $qD = mysqli_query($db_conn, "SELECT ongkir, rate_id, user_address_id, delivery_detail, is_insurance FROM `delivery` WHERE transaksi_id='$external_id'");
            if (mysqli_num_rows($qD) > 0) {
                $resDel = mysqli_fetch_all($qD, MYSQLI_ASSOC);
                $delivery_fee =  $resDel[0]['ongkir'];
                $is_delivery = 1;
            }
            if (isset($row['no_meja'])) {
                $no_meja = $row['no_meja'];
            } else {
                $no_meja = "";
            }
            if (isset($row['tenant_id'])) {
                $tenant_id = $row['tenant_id'];
            } else {
                $tenant_id = "";
            }
            $pre_order_id = $row['pre_order_id'];
            $id_partner = $row['id_partner'];
            $dev_token = $row['device_token'];
            $udev_token = $row['udev_token'];
            if (isset($row['is_queue'])) {
                $is_queue = $row['is_queue'];
            } else {
                $is_queue = 0;
            }
            $takeaway = $row['takeaway'];
            $gender = $row['gender'];
            $jam = $row['jam'];
            $phone = $row['phone'];
            $birthDate = $row['TglLahir'];
            $total = $row['total'];
            $id_voucher = $row['id_voucher'];
            $id_voucher_redeemable = $row['id_voucher_redeemable'];
            $tipe_bayar = $row['tipe_bayar'];
            $promo = $row['promo'];
            $diskon_spesial = $row['diskon_spesial'];
            $employee_discount = $row['employee_discount'];
            $point = $row['point'];
            $queue = $row['queue'];
            $takeaway = $row['takeaway'];
            $notes = $row['notes'];
            $tax = $row['tax'];
            $service = $row['service'];
            $charge_ur = $row['charge_ur'];
            $payment_method = $row['payment_method'];
            $payment_method_before = $row['payment_method'];
            $uname = $row['uname'];
            $external_id = $row['id'];
            $row['status'] = "1";
            $order = $row;

            $qIM = mysqli_query($db_conn, "SELECT m.id FROM memberships m JOIN partner p ON p.id_master=m.master_id WHERE m.user_phone='$phone' AND p.id='$id_partner' ORDER BY m.id DESC LIMIT 1");
            if (mysqli_num_rows($qIM) > 0) {
                $isMembership = true;
            } else {
                $isMembership = false;
            }
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
        
        if ($status == "SUCCEEDED" || $status == "PAID" || $status == "SETTLED" || $status == "completed" || $status == "paid" || $status == "settled") {
            $status = 1;
            
            $payment_method = "";
            if($dataTrxCallback["data"]["channel_code"] == "ID_OVO"){
                $payment_method = 1;
            } else if($dataTrxCallback["data"]["channel_code"] == "ID_DANA"){
                $payment_method = 3;
            } else if($dataTrxCallback["data"]["channel_code"] == "ID_LINKAJA"){
                $payment_method = 4;
            } else if($dataTrxCallback["data"]["channel_code"] == "ID_SHOPEEPAY"){
                $payment_method = 10;
            }
            
            $insertOST = mysqli_query($db_conn, "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`, `payment_method_before` ,`payment_method_after` ) VALUES ('$external_id', '$tStatus', '$status', '$tipe_bayar', '$payment_method')");

            $updateTrans = mysqli_query($db_conn, "UPDATE transaksi SET paid_date='$today11', tipe_bayar='$payment_method', shift_id='$lastShiftID' WHERE id='$external_id'");
            if ($is_queue != 1 && $takeaway != 1 && $is_delivery != 1) {
                $updateTrans = mysqli_query($db_conn, "UPDATE transaksi SET status='$status' WHERE id='$external_id'");
                if ($updateTrans) {
                    $response['error'] = false;
                    $response['message'] = 'Transaksi Update Successfully wihtout queue';
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Transaksi not updated';
                }
                if ($status == 1 || $status == '1') {

                    $masterID = mysqli_query($db_conn, "SELECT id_master FROM `partner` WHERE id='$id_partner'");
                    $id_master = 0;
                    while ($row = mysqli_fetch_assoc($masterID)) {
                        $id_master = $row['id_master'];
                    }
                    // $updateDeposit = mysqli_query($db_conn,"UPDATE `master` SET `deposit_balance`=`deposit_balance`-'$charge_ur' WHERE id='$id_master'");

                    $getIdMenu = mysqli_query(
                        $db_conn,
                        "SELECT dt.id_menu, dt.qty, dt.variant, m.is_recipe FROM `detail_transaksi` dt JOIN menu m ON m.id = dt.id_menu WHERE id_transaksi='$external_id' AND dt.deleted_at IS NULL"
                    );
                    $getTipeBayar = mysqli_query(
                        $db_conn,
                        "SELECT tipe_bayar FROM `transaksi` WHERE id = '$external_id'"
                    );
                    while ($rowTipeBayar = mysqli_fetch_assoc($getTipeBayar)) {
                        $tipe = $rowTipeBayar['tipe_bayar'];
                        while ($rowIdMenu = mysqli_fetch_assoc($getIdMenu)) {
                            $idmenu = $rowIdMenu['id_menu'];
                            $qty = $rowIdMenu['qty'];
                            $isRecipe = $rowIdMenu['is_recipe'];
                            $variant = $rowIdMenu['variant'];
                            $var =  substr($variant, 11);
                            $var = substr($var, 0, -1);
                            $var =  str_replace("'", '"', $var);
                            $arr_var = json_decode($var, true);
                            if ($arr_var != NULL) {
                                foreach ($arr_var as $vars) {
                                    $d_vars = $vars['detail'];
                                    foreach ($d_vars as $detail) {
                                        $var_id = $detail['id'];
                                        $ch = $fs->variant_stock_reduce($var_id, $qty);
                                    }
                                }
                            }
                            $ch = $fs->stock_reduce($idmenu, $qty, 0, $id_master, $id_partner, $isRecipe);
                        }
                    }
                }
            } else {
                $dates1 = date('Y-m-d', time());
                $var = 0;
                $queueQ = mysqli_query($db_conn, "SELECT MAX(queue) as LastQueue FROM transaksi WHERE id_partner = '$id_partner' AND DATE(jam) = '$dates1' LIMIT 1");
                while ($row = mysqli_fetch_assoc($queueQ)) {
                    if (isset($row['LastQueue']) && !empty($row['LastQueue'])) {
                        $var = $row['LastQueue'];
                    } else {
                        $var = 1;
                    }
                    $queue = $var;
                }
                $updateTrans = mysqli_query($db_conn, "UPDATE transaksi SET status='$status', queue='$var' WHERE id='$external_id'");
                if ($updateTrans) {
                    $response['error'] = false;
                    $response['message'] = 'Transaksi Update Successfully';
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Transaksi not updated';
                }
                if ($status == 1 || $status == '1') {
                    $masterID = mysqli_query($db_conn, "SELECT id_master FROM `partner` WHERE id='$id_partner'");
                    $id_master = 0;
                    while ($row = mysqli_fetch_assoc($masterID)) {
                        $id_master = $row['id_master'];
                    }
                    // $updateDeposit = mysqli_query($db_conn,"UPDATE `master` SET `deposit_balance`=`deposit_balance`-'$charge_ur' WHERE id='$id_master'");
                    $getIdMenu = mysqli_query(
                        $db_conn,
                        "SELECT dt.id_menu, dt.qty, dt.variant, m.is_recipe FROM `detail_transaksi` dt JOIN menu m ON m.id = dt.id_menu WHERE id_transaksi='$external_id' AND dt.deleted_at IS NULL"
                    );
                    $getTipeBayar = mysqli_query(
                        $db_conn,
                        "SELECT tipe_bayar FROM `transaksi` WHERE id = '$external_id'"
                    );
                    while ($rowTipeBayar = mysqli_fetch_assoc($getTipeBayar)) {
                        $tipe = $rowTipeBayar['tipe_bayar'];
                        while ($rowIdMenu = mysqli_fetch_assoc($getIdMenu)) {
                            $idmenu = $rowIdMenu['id_menu'];
                            $qty = $rowIdMenu['qty'];
                            $isRecipe = $rowIdMenu['is_recipe'];
                            $variant = $rowIdMenu['variant'];
                            $var =  substr($variant, 11);
                            $var = substr($var, 0, -1);
                            $var =  str_replace("'", '"', $var);
                            $arr_var = json_decode($var, true);
                            if ($arr_var != NULL) {
                                foreach ($arr_var as $vars) {
                                    $d_vars = $vars['detail'];
                                    foreach ($d_vars as $detail) {
                                        $var_id = $detail['id'];
                                        $ch = $fs->variant_stock_reduce($var_id, $qty);
                                    }
                                }
                            }
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
                $dev_token = $val['token'];
                $fcm_token = $dev_token;
                $title = "Pesanan baru";
                $message = "Ada pesanan baru dari meja " . $no_meja . ", mohon refresh ulang untuk menampilkan transaksi terbaru";
                if ($takeaway == "1") {
                    $message = "Ada pesanan takeaway baru, mohon refresh ulang untuk menampilkan transaksi terbaru";
                }
                if ($is_delivery == 1) {
                    $message = "Ada pesanan delivery baru, mohon refresh ulang untuk menampilkan transaksi terbaru";
                }
                if ($pre_order_id == "1") {
                    $message = "Ada pesanan pre order baru, mohon refresh ulang untuk menampilkan transaksi terbaru";
                }
                $id = null;
                $action = null;


                $notif = $db->savePaymentNotification($fcm_token, $title, $message, $no_meja, 'rn-push-notification-channel', $payment_method, $status, $queue, $external_id, $id_partner, $action, $order, $gender, $birthDate, $isMembership, 0, 'employee', $phone);
            }

            $title = "Pembayaran Diterima";
            $message = "Pembayaran untuk pesanan anda(" . $external_id . ") telah diterima, silahkan tunggu pesanan anda.";
            $id = null;
            $action = null;

            $sql = mysqli_query($db_conn, "SELECT phone, tipe_bayar FROM transaksi WHERE id = '$external_id' AND deleted_at IS NULL");
            $getData = mysqli_fetch_all($sql, MYSQLI_ASSOC);
            $phone = $getData[0]['phone'];
            $payment_method = $getData[0]['tipe_bayar'];

            $insertMessage = mysqli_query($db_conn, "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$external_id'");
            $udevTokens = $db->getUserDeviceTokens($phone);
            foreach ($udevTokens as $val) {
                $dev_token = $val['token'];
                $fcm_token = $dev_token;
                $title = "Pembayaran Diterima";
                $message = "Pembayaran untuk pesanan anda telah diterima, silahkan tunggu pesanan anda.";
                $id = null;
                $action = null;
                $notif = $db->savePaymentNotification($fcm_token, $title, $message, $no_meja, 'rn-push-notification-channel', $payment_method, $status, $queue, $external_id, $id_partner, $action, $order, $gender, $birthDate, $isMembership, 0, '', $phone);
            }

            $UpdateCallback = mysqli_query(
                $db_conn,
                "UPDATE `transaksi` SET `status`=1, `status_callback`='$data1',`callback_hit`=`callback_hit`+1, `callback_at`=NOW() WHERE id='$external_id'"
            );
            if ($UpdateCallback) {
            }
        } else {
            if(($status == "FAILED" || $status == "failed") && $dataTrxCallback["data"]["channel_code"] == "ID_OVO"){
                $status = 3;
                if ($is_queue != 1 && $takeaway != 1) {
                    $updateTrans = mysqli_query($db_conn, "UPDATE transaksi SET status='$status' WHERE id='$id'");
                    if ($updateTrans) {
                        $response['error'] = false;
                        $response['message'] = 'Transaksi Update Successfully wihtout queue';
                    } else {
                        $response['error'] = true;
                        $response['message'] = 'Transaksi not updated';
                    }
                } else {
                    $dates1 = date('Y-m-d', time());
                    $var = 0;
                    $queue = mysqli_query($db_conn, "SELECT MAX(queue) as LastQueue FROM transaksi WHERE id_partner = '$id_partner' AND DATE(jam) = '$dates1' LIMIT 1");
                    while ($row = mysqli_fetch_assoc($queue)) {
                        $var = $row['LastQueue'] + 1;
                        $queue = $var;
                    }
                    $updateTrans = mysqli_query($db_conn, "UPDATE transaksi SET status='$status', queue='$var' WHERE id='$id'");
                    if ($updateTrans) {
                        $response['error'] = false;
                        $response['message'] = 'Transaksi Update Successfully';
                    } else {
                        $response['error'] = true;
                        $response['message'] = 'Transaksi not updated';
                    }
                }
    
                $insertOST = mysqli_query($db_conn, "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`) VALUES ('$external_id', '$tStatus', '0')");
                // $updateTrans = mysqli_query($db_conn,"UPDATE transaksi SET paid_date='$today11' WHERE id='$external_id'");
    
                $title = "Pesanan Dibatalkan";
                $message = "Pesanan untuk pesanan anda(" . $external_id . ") telah dibatalkan";
                $id = null;
                $action = null;
                $insertMessage = mysqli_query($db_conn, "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$external_id'");
                $udevTokens = $db->getUserDeviceTokens($phone);
                foreach ($udevTokens as $val) {
                    $dev_token = $val['token'];
                    $fcm_token = $dev_token;
                    $title = "Pesanan Dibatalkan - Timeout OVO";
                    $message = "Pesanan untuk pesanan anda (" . $external_id . ") telah dibatalkan karena pembayaran melebihi waktu timeout OVO";
                    $id = null;
                    $action = null;
                    $notif = $db->savePaymentNotification($fcm_token, $title, $message, $no_meja, 'rn-push-notification-channel', $payment_method, $status, $queue, $external_id, $id_partner, $action, $order, $gender, $birthDate, $isMembership, 0, '', $phone);
                }
    
                $UpdateCallback = mysqli_query(
                    $db_conn,
                    "UPDATE `transaksi` SET `status`='3', `status_callback`='$data1',`callback_hit`=`callback_hit`+1, `callback_at`=NOW() WHERE id LIKE '%$external_id%'"
                );
            if ($UpdateCallback) {
            }
            
            } else {
                $status = 0;
                if ($is_queue != 1 && $takeaway != 1) {
                    $updateTrans = mysqli_query($db_conn, "UPDATE transaksi SET status='$status' WHERE id='$id'");
                    if ($updateTrans) {
                        $response['error'] = false;
                        $response['message'] = 'Transaksi Update Successfully wihtout queue';
                    } else {
                        $response['error'] = true;
                        $response['message'] = 'Transaksi not updated';
                    }
                } else {
                    $dates1 = date('Y-m-d', time());
                    $var = 0;
                    $queue = mysqli_query($db_conn, "SELECT MAX(queue) as LastQueue FROM transaksi WHERE id_partner = '$id_partner' AND DATE(jam) = '$dates1' LIMIT 1");
                    while ($row = mysqli_fetch_assoc($queue)) {
                        $var = $row['LastQueue'] + 1;
                        $queue = $var;
                    }
                    $updateTrans = mysqli_query($db_conn, "UPDATE transaksi SET status='$status', queue='$var' WHERE id='$id'");
                    if ($updateTrans) {
                        $response['error'] = false;
                        $response['message'] = 'Transaksi Update Successfully';
                    } else {
                        $response['error'] = true;
                        $response['message'] = 'Transaksi not updated';
                    }
                }
    
                $insertOST = mysqli_query($db_conn, "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`) VALUES ('$external_id', '$tStatus', '0')");
                // $updateTrans = mysqli_query($db_conn,"UPDATE transaksi SET paid_date='$today11' WHERE id='$external_id'");
    
                $title = "Pesanan Dibatalkan";
                $message = "Pesanan untuk pesanan anda(" . $external_id . ") telah dibatalkan";
                $id = null;
                $action = null;
                $insertMessage = mysqli_query($db_conn, "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$external_id'");
                $udevTokens = $db->getUserDeviceTokens($phone);
                foreach ($udevTokens as $val) {
                    $dev_token = $val['token'];
                    $fcm_token = $dev_token;
                    $title = "Pesanan Dibatalkan";
                    $message = "Pesanan untuk pesanan anda(" . $external_id . ") telah dibatalkan";
                    $id = null;
                    $action = null;
                    $notif = $db->savePaymentNotification($fcm_token, $title, $message, $no_meja, 'rn-push-notification-channel', $payment_method, $status, $queue, $external_id, $id_partner, $action, $order, $gender, $birthDate, $isMembership, 0, '', $phone);
                }
    
                $UpdateCallback = mysqli_query(
                    $db_conn,
                    "UPDATE `transaksi` SET `status_callback`='$data1',`callback_hit`=`callback_hit`+1, `callback_at`=NOW() WHERE id LIKE '%$external_id%'"
                );
                if ($UpdateCallback) {
                }
            }
            
            
        }
    }
}

// if ($addSaldo) {
echo json_encode(["success" => 1, "msg" => "Callback Success", "status" => 200]);
// } else {
//     echo json_encode(["success" => 0, "msg" => "Callback Fail", "status" => 200]);
// }
