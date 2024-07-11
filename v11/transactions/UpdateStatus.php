<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once '../../includes/DbOperation.php';
require '../../includes/functions.php';
require '../../includes/ValidatorV4.php';

$fs = new functions();
$db = new DbOperation();
$validator = new ValidatorV4();
$today11 = date("Y-m-d H:i:s");
//init var

date_default_timezone_set('Asia/Jakarta');
$now = date('Y-m-d H:i:s', time());
$today = date('Y-m-d', time());
$headers = array();
$rx_http = '/\AHTTP_/';
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
            $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
    }
}
$tokenizer = new Token();
$token = '';
$res = array();

function getIDVR($phone, $vr, $trx, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT id  FROM `user_voucher_ownership` WHERE `userid` LIKE '$phone' AND `voucherid`='$vr' AND `transaksi_id`='$trx' ORDER BY id ASC limit 1");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id = $res[0]['id'];
        $update = mysqli_query($db_conn, "UPDATE `user_voucher_ownership` SET `transaksi_id`=NULL WHERE id='$id'");
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

$obj = array();
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $validator->checkShiftIDActive($db_conn, $token);
    $obj = json_decode(json_encode($_POST));
    if (
        isset($obj->transactionID) && !empty($obj->transactionID)
        && isset($obj->status) && !empty($obj->status)
    ) {
        if (empty($obj->accBy)) {
            $obj->accBy = $token->id;
        }
        $external_id = $obj->transactionID;
        //transaction
        $qT = mysqli_query($db_conn, "SELECT * FROM `transaksi` WHERE id='$obj->transactionID' AND (status!=3 || status!=4) ");
        if (mysqli_num_rows($qT) > 0) {
            $transactions = mysqli_fetch_all($qT, MYSQLI_ASSOC);
            $transaction = $transactions[0];

            //cek meja antrian
            $mejaID = $transaction['no_meja'];
            $partnerID = $transaction['id_partner'];
            $qM = mysqli_query($db_conn, "SELECT id,is_queue FROM `meja` WHERE idpartner='$partnerID' AND idmeja='$mejaID'");
            if (mysqli_num_rows($qM) > 0) {
                $tables = mysqli_fetch_all($qM, MYSQLI_ASSOC);
                $table = $tables[0];

                if ((int)$table['is_queue'] == 1 && (int)$obj->status == 1) {
                    $lastQueue = 0;
                    $qLQ = mysqli_query($db_conn, "SELECT queue as LastQueue FROM transaksi WHERE id_partner = '$partnerID' AND DATE(jam) = '$today' ORDER BY queue DESC LIMIT 1");
                    if (mysqli_num_rows($qLQ) > 0) {
                        $lastQueue = mysqli_fetch_all($qLQ, MYSQLI_ASSOC);
                        $lastQueue = (int) $lastQueue[0]['LastQueue'];
                    } else {
                        $lastQueue = 1;
                    }
                    // $updateQueue = mysqli_query($db_conn, "UPDATE `transaksi` SET queue='$lastQueue' WHERE id='$obj->transactionID'");
                }
            }

            //pesanan selesai
            $getTrx = mysqli_query($db_conn, "SELECT transaksi.no_meja, transaksi.shift_id, transaksi.id_partner, users.dev_token AS udev_token, meja.is_queue, transaksi.takeaway, transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount,transaksi.point, transaksi.queue AS queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.charge_ur, users.name AS uname, payment_method.nama AS payment_method, users.Gender, users.TglLahir as birth_date, transaksi.status, transaksi.rounding, transaksi.is_helper, partner.name as partner_name FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar JOIN meja ON meja.idmeja=transaksi.no_meja WHERE transaksi.id='$obj->transactionID'");
            if (mysqli_num_rows($getTrx) == 0) {
                $getTrx = mysqli_query($db_conn, "SELECT transaksi.no_meja, transaksi.shift_id, transaksi.id_partner, users.dev_token AS udev_token, transaksi.takeaway, transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.point, transaksi.queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.rounding, transaksi.charge_ur, users.name AS uname, payment_method.nama AS payment_method, users.Gender, users.TglLahir as birth_date, transaksi.status, transaksi.is_helper, partner.name as partner_name FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id='$obj->transactionID'");
                if (mysqli_num_rows($getTrx) == 0) {
                    $getTrx = mysqli_query($db_conn, "SELECT transaksi.no_meja, transaksi.shift_id, transaksi.id_partner, transaksi.takeaway, transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.point, transaksi.queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.rounding, transaksi.charge_ur, transaksi.customer_name uname, payment_method.nama AS payment_method, 'unknown' Gender, '2000-01-01' as birth_date, transaksi.status, transaksi.is_helper, partner.name as partner_name FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id='$obj->transactionID'");
                }
            }
            $no_meja = 0;
            $id_partner = 0;
            $dev_token = 0;
            $udev_token = 0;
            $is_queue = 0;
            $is_helper = 0;
            $takeaway = 0;
            $tStatus = 1;
            $queue = 0;
            $order = [];
            $phone = "";

            while ($row = mysqli_fetch_assoc($getTrx)) {
                $order = $row;
                $no_meja = $row['no_meja'];
                $is_helper = $row['is_helper'];
                $id_partner = $row['id_partner'];
                // $dev_token=$row['device_token'];
                $udev_token = "";
                if (isset($row['udev_token'])) {
                    $udev_token = $row['udev_token'];
                }
                if (isset($row['is_queue'])) {
                    $is_queue = $row['is_queue'];
                }
                $jam = $row['jam'];
                $phone = $row['phone'];
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
                $uname = $row['uname'];
                $birthDate = $row['birth_date'];
                $gender = $row['Gender'];
                $tStatus = $row['status'];
                $shiftID = $row['shift_id'];
                $order['is_delivery'] = "0";
                if(str_contains($obj->transactionID, 'DL/')){
                    $order['is_delivery'] = "1"; 
                }
            }
            //cashier name
            $collector_id = $token->id;
            if($obj->status == 2 || $obj->status == '2'){
                $updateCashier = mysqli_query($db_conn, "UPDATE `transaksi` SET collector_id='$collector_id', group_id=null WHERE id='$obj->transactionID'");
            }
            //cashier name end
            
            if ($obj->status == 4 || $obj->status == '4') {
                $updateStatus = mysqli_query($db_conn, "UPDATE `transaksi` SET status='$obj->status', group_id=null WHERE id='$obj->transactionID'");
            } else {
                $updateStatus = mysqli_query($db_conn, "UPDATE `transaksi` SET status='$obj->status' WHERE id='$obj->transactionID'");
            }

            $getLastShift = mysqli_query($db_conn, "SELECT MAX(id) as id FROM `shift` WHERE partner_id='$token->id_partner' AND deleted_at IS NULL");
            while ($row = mysqli_fetch_assoc($getLastShift)) {
                $currentShiftID = $row['id'];
            }
            if (($tStatus == 0 && $obj->status == 1) || ($tStatus == 1 && $obj->status == 2 && ($shiftID == 0)) ||  ($tStatus == 5 && $obj->status == 2)) {
                $updateStatus = mysqli_query($db_conn, "UPDATE `transaksi` SET shift_id='$currentShiftID' WHERE id='$obj->transactionID'");
            }

            $membershipQ = mysqli_query($db_conn, "SELECT m.id FROM memberships m JOIN partner p ON p.id_master=m.master_id WHERE m.user_phone='$phone' AND p.id='$id_partner' ORDER BY m.id DESC LIMIT 1");
            if (mysqli_num_rows($membershipQ) > 0) {
                $isMembership = true;
            } else {
                $isMembership = false;
            }
            if ($is_helper == 1) {
                $tokensQ = mysqli_query($db_conn, "SELECT tokens FROM `device_tokens` WHERE no_meja='$no_meja' AND deleted_at IS NULL");
            } else {
                $tokensQ = mysqli_query($db_conn, "SELECT tokens FROM `device_tokens` WHERE user_phone='$phone' AND deleted_at IS NULL");
            }
            $udevTokens = array();
            $i = 0;
            while ($row = mysqli_fetch_assoc($tokensQ)) {
                $udevTokens[$i]['token'] = $row['tokens'];
                $i++;
            }
            $tokensQ = mysqli_query($db_conn, "SELECT device_tokens.tokens FROM transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON detail_transaksi.id_menu=menu.id JOIN partner ON partner.id=menu.id_partner JOIN device_tokens ON device_tokens.id_partner=partner.id JOIN employees ON employees.id=device_tokens.employee_id JOIN partner parent ON parent.id=partner.fc_parent_id WHERE transaksi.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL AND device_tokens.deleted_at IS NULL AND employees.order_notification='1' AND employees.deleted_at IS NULL AND transaksi.id='$obj->transactionID' AND parent.is_foodcourt='1' AND parent.is_centralized='0' GROUP BY device_tokens.tokens ORDER BY partner.id");
            $tenantTokens = array();
            $i = 0;
            while ($row = mysqli_fetch_assoc($tokensQ)) {
                $tenantTokens[$i]['token'] = $row['tokens'];
                $i++;
            }


            $insertOST = mysqli_query($db_conn, "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`, `created_at`) VALUES ('$obj->transactionID', '$tStatus', '$obj->status', NOW())");
            $listTransaksiDetail = mysqli_query($db_conn, "SELECT `id`, `id_transaksi`, `id_menu`, `harga_satuan`, `qty`, `notes`, `harga`, `variant`, `status` FROM `detail_transaksi` WHERE `id_transaksi`='$obj->transactionID'");
            while ($rowL = mysqli_fetch_assoc($listTransaksiDetail)) {
                $d_id_detail = $rowL['id'];
                $d_id_transaksi = $rowL['id_transaksi'];
                $d_id_menu = $rowL['id_menu'];
                $d_harga_satuan = $rowL['harga_satuan'];
                $d_qty = $rowL['qty'];
                $d_notes = $rowL['notes'];
                $d_harga = $rowL['harga'];
                $d_variant = $rowL['variant'];
                $d_status = $rowL['status'];
                $updateDetail1 = mysqli_query($db_conn, "INSERT INTO `detail_transactions_history`(`id_detail`, `id_transaksi`, `id_menu`, `harga_satuan`, `qty`, `notes`, `harga`, `variant`, `status`, `created_at`) VALUES ('$d_id_detail', '$d_id_transaksi', '$d_id_menu', '$d_harga_satuan', '$d_qty', '$d_notes', '$d_harga', '$d_variant', '$obj->status', NOW())");
            }
            if ($obj->status == 1 || $obj->status == '1') {
                $updateTrans = mysqli_query($db_conn, "UPDATE transaksi SET paid_date='$today11' WHERE id='$obj->transactionID'");
                $title = "Pembayaran Diterima";
                $message = "Pembayaran untuk pesanan kamu sudah diterima, silahkan tunggu pesanan kamu.";
                $id = null;
                $action = null;
                $insertMessage = mysqli_query($db_conn, "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$obj->transactionID'");
                foreach ($udevTokens as $val) {
                    $dev_token = $val['token'];
                    if ($dev_token != "TEMPORARY_TOKEN") {
                        $fcm_token = $dev_token;
                        $notif = $db->savePaymentNotification($fcm_token, $title, $message, $no_meja, 'ur-user', $payment_method, $obj->status, $queue, $external_id, $id_partner, $action, $order, $gender, $birthDate, $isMembership, 0, '', $phone);
                    }
                }
                foreach ($tenantTokens as $val) {
                    $dev_token = $val['token'];
                    if ($dev_token != "TEMPORARY_TOKEN") {
                        $fcm_token = $dev_token;
                        $notif = $db->savePaymentNotification($fcm_token, $title, $message, $no_meja, 'rn-push-notification-channel', $payment_method, $obj->status, $queue, $external_id, $id_partner, $action, $order, $gender, $birthDate, $isMembership, 0, '', $phone);
                    }
                }
            }

            if ($obj->status == 2 || $obj->status == '2') {
                $updateDetailS = mysqli_query($db_conn, "UPDATE `detail_transaksi` SET `status`='2' WHERE `id_transaksi`='$obj->transactionID'");
                $title = "Pesanan Selesai";
                $message = "Pesanan kamu telah selesai.";
                if ($takeaway == 1 || $takeaway == '1') {
                    $message = "Pesanan kamu sudah selesai nih. Silakan ambil pesanan ke lokasi yang telah ditentukan.";
                }
                $insertMessage = mysqli_query($db_conn, "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$obj->transactionID'");

                foreach ($udevTokens as $val) {
                    $dev_token = $val['token'];
                    if ($dev_token != "TEMPORARY_TOKEN") {
                        $fcm_token = $dev_token;
                        $id = null;
                        $action = null;
                        $notif = $db->savePaymentNotification($fcm_token, $title, $message, $no_meja, 'ur-user', $payment_method, $obj->status, $queue, $external_id, $id_partner, $action, $order, $gender, $birthDate, $isMembership, 0, '', $phone);
                    }
                }
                $query = "SELECT DISTINCT transaksi.no_meja, transaksi.total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.rounding, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.program_discount, partner.name AS partner_name, users.email, users.name, partner.id AS partner_id, partner.is_pickup_notification, payment_method.nama AS payment_method_name, transaksi.customer_email FROM `transaksi` JOIN `partner` ON `partner`.`id`=`transaksi`.`id_partner` LEFT JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id='$obj->transactionID'";
                $trxQ = mysqli_query($db_conn, $query);
                $subtotal = 0;
                $promo = 0;
                $program_discount = 0;
                $diskon_spesial = 0;
                $employee_discount = 0;
                $point = 0;
                $rounding = 0;
                $service = 0;
                $tax = 0;
                $charge_ur = 0;
                $payment_method_name = "";
                $partner_name = "";
                $partner_id = "";
                $user_name = "";
                $user_email = "";
                $rounding = "";
                $no_meja = "";
                $isPickupNotification = 0;

                while ($row = mysqli_fetch_assoc($trxQ)) {
                    $payment_method_name = $row['payment_method_name'];
                    // $partner_name = $row['partner_name'];
                    $partner_name = mysqli_real_escape_string($db_conn, $row['partner_name']);
                    $partner_id = $row['partner_id'];
                    $user_name = $row['name'];
                    $user_email = $row['customer_email'];
                    $isPickupNotification = $row['is_pickup_notification'];
                    if (isset($row['email']) && !empty($row['email'])) {
                        $user_email = $row['email'];
                    }
                    $no_meja = $row['no_meja'];
                    $subtotal += (int) $row['total'];
                    $promo += (int) $row['promo'];
                    $rounding += (int) $row['rounding'];
                    // $rounding += intval($row['rounding']);
                    $program_discount += (int) $row['program_discount'];
                    $diskon_spesial += (int) $row['diskon_spesial'];
                    $employee_discount += (int) $row['employee_discount'];
                    $point += (int) $row['point'];
                    $tempS = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
                    $service += $tempS;
                    $charge_ur += (int) $row['charge_ur'];
                    $tempT = ceil(((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
                    $tax += $tempT;
                }
                $scheduleSend = date("Y-m-d H:i:s", strtotime("+30 minutes"));

                // $scheduleSend=date("Y-m-d H:i:s");
                $waContent = "Bagaimana pengalaman belanjamu di " . $partner_name . "? Yuk beri ulasan di link ini: " . "https://selforder.ur-hub.com/create-review?transactionID=" . $obj->transactionID;
                // WA message for qontak
                // $waContent =  $partner_name . "," . $obj->transactionID ;

                
                $insertRatingReminder = mysqli_query($db_conn, "INSERT INTO whatsapp_messages SET phone='$phone', source='selforder_web', schedule_send='$scheduleSend', content='$waContent'");
                // $qInsertRatingReminder = "INSERT INTO whatsapp_messages SET phone='$phone', source='selforder_web', schedule_send= NOW(), content='$waContent'";
                // $insertRatingReminder = mysqli_query($db_conn, $qInsertRatingReminder);
                if ($isPickupNotification == 1) {
                    $waContent = "Hai, " . $user_name . ". Pesanan kamu (" . $obj->transactionID . ") sudah selesai. Mohon ambil pesanan di tempat yang sudah disediakan";
                    // WA message for qontak
                    // $waContent = $user_name . "," . "(" .  $obj->transactionID . ")";

                    $insertPickupNotification = mysqli_query($db_conn, "INSERT INTO whatsapp_messages SET phone='$phone', `source`='selforder_web', schedule_send=NOW(), content='$waContent'");
                }
                $subtotal = $subtotal;
                $sales = $subtotal + $service + $tax + $charge_ur;
                $promo = $promo;
                $rounding = $rounding;
                $program_discount = $program_discount;
                $diskon_spesial = $diskon_spesial;
                $employee_discount = $employee_discount;
                $point = $point;
                $clean_sales = $sales - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
                $service = $service;
                $charge_ur = $charge_ur;
                $tax = $tax;
                $total = $subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax + $rounding;
                $dateNow = date('d M Y');
                $timeNow = date("H:i");

                    $query = "SELECT template FROM `email_template` WHERE name='receipt-natta'";
                $templateQ = mysqli_query($db_conn, $query);
                if (mysqli_num_rows($templateQ) > 0) {
                    $templates = mysqli_fetch_all($templateQ, MYSQLI_ASSOC);
                    $template = $templates[0]['template'];
                    $template = str_replace('$strTottotal', $fs->rupiah($total), $template);
                    $template = str_replace('$dateNow ', $dateNow, $template);
                    $template = str_replace('$timeNow ', $timeNow, $template);
                    $template = str_replace('$name ', $user_name, $template);
                    $template = str_replace('$partnerName ', $partner_name, $template);
                    $template = str_replace('$no_meja ', $no_meja, $template);
                    $template = str_replace('$strTot ', $fs->rupiah($subtotal), $template);
                    $template = str_replace('$strServ ', $fs->rupiah($service), $template);
                    $template = str_replace('$strChargeUR ', $fs->rupiah($charge_ur), $template);
                    $template = str_replace('$strTax ', $fs->rupiah($tax), $template);
                    $template = str_replace('$strSUbtot ', $fs->rupiah($total), $template);
                    $template = str_replace('$strRounding ',$fs->rupiah($rounding), $template);
                    $template = str_replace('$type ', $payment_method_name, $template);
                    $template = str_replace('$id ', $obj->transactionID, $template);
                    if (substr($obj->transactionID, 0, 2) == "DI") {
                        $template = str_replace('$trx_type', "Dine In", $template);
                    } elseif (substr($obj->transactionID, 0, 2) == "TA") {
                        $template = str_replace('$trx_type', "Take Away", $template);
                    }
                    if (substr($obj->transactionID, 0, 2) == "DL") {
                        $template = str_replace('$trx_type', "Delivery", $template);
                    } else {
                        $template = str_replace('$trx_type', "Pre Order", $template);
                    }
                    if ($promo > 0) {
                        $template = str_replace('$promo', '
                        <tr>
                        <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Promo Voucher</td>
                        <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($promo) . ' </td>
                       </tr>', $template);
                    } else {
                        $template = str_replace('$promo', '', $template);
                    }

                    if ($diskon_spesial > 0) {
                        $template = str_replace('$Diskon Spesial', '<tr>
                        <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Diskon Spesial</td>
                        <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($diskon_spesial) . '</td>
                       </tr>', $template);
                    } else {
                        $template = str_replace('$Diskon Spesial', '', $template);
                    }

                    if ($employee_discount > 0) {
                        $template = str_replace('$Diskon Karyawan', '<tr>
                        <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Diskon Karyawan</td>
                        <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($employee_discount) . '</td>
                       </tr>', $template);
                    } else {
                        $template = str_replace('$Diskon Karyawan', '', $template);
                    }
                    if ($program_discount > 0) {
                        $template = str_replace('$Diskon Program', '<tr>
                        <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Diskon Program</td>
                        <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($program_discount) . '</td>
                       </tr>', $template);
                    } else {
                        $template = str_replace('$Diskon Program', '', $template);
                    }
                }
                $query = "SELECT menu.nama,qty, detail_transaksi.harga FROM `detail_transaksi` JOIN menu ON menu.id=detail_transaksi.id_menu WHERE detail_transaksi.id_transaksi='$obj->transactionID' AND detail_transaksi.deleted_at IS NULL";
                $detail_trx = mysqli_query($db_conn, $query);
                $detail_transaction = "";
                while ($row = mysqli_fetch_assoc($detail_trx)) {
                    $nama_menu = $row['nama'];
                    $qty_menu = $row['qty'];
                    $harga_menu = $row['harga'];
                    $detail_transaction .= '
                    <tr>
                    <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $nama_menu . ' X  ' . $qty_menu . ' </td>
                    <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($harga_menu) . ' </td>
                    </tr>';
                }
                $template = str_replace('$loop detail', $detail_transaction, $template);
                $template = json_encode($template);
                // $template = mysqli_real_escape_string($db_conn, $template);
                if (isset($user_email) && !empty($user_email)) {
                    $insertTe = mysqli_query($db_conn, "INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`, `created_at`) VALUES ('$user_email', '$partner_id', 'Natta E-Receipt', $template, NOW())");
                }
            }
            if ($obj->status == 4 || $obj->status == '4') {
                $updateDetailS = mysqli_query($db_conn, "INSERT INTO `transaction_cancellation`(`transaction_id`,  `notes`, `created_by`, `created_at`, shift_id, acc_by) VALUES ('$obj->transactionID', '$obj->cancel_notes', '$obj->created_by', NOW(), '$currentShiftID', '$obj->accBy')");
                getIDVR($phone, $id_voucher_redeemable, $obj->transactionID, $db_conn);
                $title = "Pesanan Dibatalkan";
                $message = "Pesanan anda dibatalkan Kasir.";
                $qDT = mysqli_query($db_conn, "SELECT id_menu, qty, variant FROM `detail_transaksi` WHERE id_transaksi='$obj->transactionID' AND deleted_at IS NULL");
                if (mysqli_num_rows($qDT) > 0) {
                    $detailsTransaction = mysqli_fetch_all($qDT, MYSQLI_ASSOC);
                    $menusOrder = array();
                    $variantOrder = array();
                    $imo = 0;
                    $iv = 0;
                    foreach ($detailsTransaction as $value) {
                        $menusOrder[$imo]['id_menu'] = $value['id_menu'];
                        $menusOrder[$imo]['qty'] = (int) $value['qty'];
                        if (!empty($value['variant'])) {
                            $cut = $value['variant'];
                            $cut = substr($cut, 11);
                            $cut = substr($cut, 0, -1);
                            $cut = str_replace("'", '"', $cut);
                            $menusOrder[$imo]['variant'] = json_decode($cut);
                            if ($menusOrder[$imo]['variant'] != NULL) {
                                foreach ($menusOrder[$imo]['variant'] as $value1) {
                                    foreach ($value1->detail as $value2) {
                                        $variantOrder[$iv]['id'] = $value2->id;
                                        $variantOrder[$iv]['qty'] = $value2->qty;
                                        $ch = $fs->variant_stock_return($value2->id, intval($value2->qty));
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
                        $menuID = $value['id_menu'];
                        $ch = $fs->stock_return($menuID, $qtyOrder);
                    }
                }
                $insertMessage = mysqli_query($db_conn, "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$obj->transactionID'");
                foreach ($udevTokens as $val) {
                    $dev_token = $val['token'];
                    if ($dev_token != "TEMPORARY_TOKEN") {
                        $fcm_token = $dev_token;
                        $id = null;
                        $action = null;
                        $notif = $db->savePaymentNotification($fcm_token, $title, $message, $no_meja, 'ur-user', $payment_method, $obj->status, $queue, $external_id, $id_partner, $action, $order, $gender, $birthDate, $isMembership, 0, '', $phone);
                    }
                }
            }
            if ($obj->status == 6 || $obj->status == '6') {

                $detailNotes = $obj->cancel_notes;
                $created_by = $obj->created_by;
                $updateDetailS = mysqli_query($db_conn, "UPDATE `detail_transaksi` SET `status`='6' WHERE `id_transaksi`='$obj->transactionID'");
            }

            //kurangi stock
            if ($obj->status == 1 || $obj->status == '1') {

                //Detail Transaction
                $masterID = mysqli_query($db_conn, "SELECT id_master FROM `partner` WHERE id='$partnerID'");
                $id_master = 0;
                while ($row = mysqli_fetch_assoc($masterID)) {
                    $id_master = $row['id_master'];
                }
                $updateDeposit = mysqli_query($db_conn, "UPDATE `master` SET `deposit_balance`=`deposit_balance`-'$charge_ur' WHERE id='$id_master'");

                // $qDT = mysqli_query($db_conn, "SELECT * FROM `detail_transaksi` WHERE id_transaksi='$obj->transactionID'");
                $qDT = mysqli_query($db_conn, "SELECT dt.id_menu, dt.qty, dt.variant, m.is_recipe FROM `detail_transaksi` dt JOIN menu m ON m.id = dt.id_menu WHERE dt.id_transaksi='$obj->transactionID' AND dt.deleted_at IS NULL");
                if (mysqli_num_rows($qDT) > 0) {
                    $detailsTransaction = mysqli_fetch_all($qDT, MYSQLI_ASSOC);

                    $menusOrder = array();
                    $variantOrder = array();
                    $imo = 0;
                    $iv = 0;
                    foreach ($detailsTransaction as $value) {
                        $menusOrder[$imo]['id_menu'] = $value['id_menu'];
                        $menusOrder[$imo]['qty'] = $value['qty'];
                        $menusOrder[$imo]["is_recipe"] = $value["is_recipe"];
                        $cut = $value['variant'];
                        $cut = substr($cut, 11);
                        $cut = substr($cut, 0, -1);
                        $cut = str_replace("'", '"', $cut);
                        $menusOrder[$imo]['variant'] = json_decode($cut);
                        if ($menusOrder[$imo]['variant'] != NULL) {
                            foreach ($menusOrder[$imo]['variant'] as $value1) {
                                foreach ($value1->detail as $value2) {
                                    $variantOrder[$iv]['id'] = $value2->id;
                                    $variantOrder[$iv]['qty'] = $value2->qty;
                                    $ch = $fs->variant_stock_reduce($value2->id, $value2->qty);
                                    $iv += 1;
                                }
                            }
                        }
                        $imo += 1;
                    }

                    //Menu
                    foreach ($menusOrder as $value) {
                        $qtyOrder = $value["qty"];
                        $menuID = $value['id_menu'];
                        $isRecipe = $value["is_recipe"];
                        // $ch = $fs->stock_reduce($menuID, $qtyOrder);
                        $ch = $fs->stock_reduce($menuID, $qtyOrder, 0, $id_master, $id_partner, $isRecipe);
                    }
                }
            }
            $msg = "Success";
            $success = 1;
            $status = 200;
        } else {
            $msg = "Transaction Has Been Canceled";
            $success = 0;
            $status = 200;
        }
    } else {
        $success = 0;
        $msg = "Missing require field's";
        $status = 400;
    }
}
http_response_code($status);
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "transactionDetails" => $obj, "order"=>$order]);
