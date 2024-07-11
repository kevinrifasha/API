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

$db = new DbOperation();

//init var
date_default_timezone_set('Asia/Jakarta');
$now = date('Y-m-d H:i:s', time());
$today = date('Y-m-d', time());
$headers = array();
    $rx_http = '/\AHTTP_/';
    foreach($_SERVER as $key => $val) {
      if( preg_match($rx_http, $key) ) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
          foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
          $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
      }
    }
$tokenizer = new Token();
$token = '';
$res = array();

function getIDVR($phone, $vr, $trx ,$db_conn){
        $q = mysqli_query($db_conn,"SELECT id  FROM `user_voucher_ownership` WHERE `userid` LIKE '$phone' AND `voucherid`='$vr' AND `transaksi_id`='$trx' ORDER BY id ASC limit 1");
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $id = $res[0]['id'];
            $update = mysqli_query($db_conn,"UPDATE `user_voucher_ownership` SET `transaksi_id`=NULL WHERE id='$id'");
            return $update;
        }else{
            return 0;
        }

}

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $obj = json_decode(json_encode($_POST));
    if(
        isset($obj->transactionID) && !empty($obj->transactionID)
    ){

        $trasactionID = explode(',', $obj->transactionID);
        foreach ($trasactionID as $value) {
            $id = $value;
            $obj->status = 3;
            //transaction
            $qT = mysqli_query($db_conn, "SELECT * FROM `transaksi` WHERE id='$id'");
            if (mysqli_num_rows($qT) > 0) {
                $transactions = mysqli_fetch_all($qT, MYSQLI_ASSOC);
                $transaction = $transactions[0];

                //cek meja antrian
                $mejaID = $transaction['no_meja'];
                $partnerID = $transaction['id_partner'];
                $qM = mysqli_query($db_conn, "SELECT id,is_queue FROM `meja` WHERE idpartner='$partnerID' AND idmeja='$mejaID'");
                if(mysqli_num_rows($qM) > 0){
                    $tables = mysqli_fetch_all($qM, MYSQLI_ASSOC);
                    $table = $tables[0];
                }

                $updateStatus = mysqli_query($db_conn, "UPDATE `transaksi` SET status='$obj->status' WHERE id='$id'");
                $getTrx = mysqli_query($db_conn, "SELECT transaksi.no_meja, transaksi.id_partner, transaksi.status, users.Gender, users.TglLahir as birth_date, users.dev_token AS udev_token, meja.is_queue, transaksi.takeaway, transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, transaksi.point, transaksi.queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.charge_ur, users.name AS uname, payment_method.nama AS payment_method FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar LEFT JOIN meja ON meja.idpartner=transaksi.id_partner AND meja.idmeja=transaksi.no_meja WHERE transaksi.id='$id'");
                $no_meja=0;
                $id_partner=0;
                $dev_token=0;
                $udev_token=0;
                $is_queue = 0;
                $takeaway = 0;
                $tStatus=1;
                $gender = "";
                $queue = 0;
                $order = [];

                while ($row = mysqli_fetch_assoc($getTrx)) {
                    $order = $row;
                    $no_meja=$row['no_meja'];
                    $id_partner=$row['id_partner'];
                    // $dev_token=$row['device_token'];
                    $udev_token=$row['udev_token'];
                    $is_queue = $row['is_queue'];
                    $takeaway = $row['takeaway'];
                    $jam = $row['jam'];
                    $phone = $row['phone'];
                    $total = $row['total'];
                    $gender = $row['Gender'];
                    $birth_date = $row['birth_date'];
                    $id_voucher = $row['id_voucher'];
                    $id_voucher_redeemable = $row['id_voucher_redeemable'];
                    $tipe_bayar = $row['tipe_bayar'];
                    $promo = $row['promo'];
                    $diskon_spesial = $row['diskon_spesial'];
                    $point = $row['point'];
                    $queue = $row['queue'];
                    $takeaway = $row['takeaway'];
                    $notes = $row['notes'];
                    $tax = $row['tax'];
                    $service = $row['service'];
                    $charge_ur = $row['charge_ur'];
                    $payment_method = $row['payment_method'];
                    $uname = $row['uname'];
                    $tStatus=$row['status'];
                }
                $tokensQ = mysqli_query($db_conn, "SELECT tokens FROM `device_tokens` WHERE user_phone='$phone' AND deleted_at IS NULL");
                $tokensQP = mysqli_query($db_conn, "SELECT tokens FROM `device_tokens` WHERE id_partner='$id_partner' AND deleted_at IS NULL");
                $udevTokens = array();
                $devTokens = array();
                $i=0;
                $j=0;
                while ($row = mysqli_fetch_assoc($tokensQP)) {
                    $devTokens[$j]['token'] = $row['tokens'];
                    $j++;
                }
                while ($row = mysqli_fetch_assoc($tokensQ)) {
                    $udevTokens[$i]['token'] = $row['tokens'];
                    $i++;
                }
                $membershipQ = mysqli_query($db_conn,"SELECT m.id FROM memberships m JOIN partner p ON p.id_master=m.master_id WHERE m.user_phone='$phone' AND p.id='$id_partner' ORDER BY m.id DESC LIMIT 1");
                if (mysqli_num_rows($membershipQ) > 0) {
                    $isMembership = true;
                }else{
                    $isMembership = false;
                }

                $insertOST = mysqli_query($db_conn, "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`, `created_at`) VALUES ('$id', '$tStatus', '$obj->status', NOW())");
                if($obj->status==3 || $obj->status=='3'){
                //     getIDVR($phone, $id_voucher_redeemable, $id ,$db_conn);
                //     if($udev_token!="TEMPORARY_TOKEN"){
                //         $fcm_token=$udev_token;
                //         $title="Pesanan Dibatalkan";
                //         $message="Pesanan anda dibatalkan.";
                //         $id = null;
                //         $action = null;

                //             $url = "https://fcm.googleapis.com/fcm/send";
                //             $header = [
                //                 'authorization: key=AIzaSyDYqiHlqZWkBjin6jcMZnF4YXfzy7_T9SQ',
                //                 'content-type: application/json'
                //             ];

                //             $notification = [
                //                 'title' =>$title,
                //                 'body' => $message,
                //                 'android_channel_id' => 'ur-user',
                //                 'vibrate'=> 1,
                //                 'sound'=> 5,
                //                 'show_in_foreground'=> true,
                //                 'priority'=> 'high',
                //                 'content_available'=> true,
                //             ];
                //             $extraNotificationData = ["status"=>$status,"event"=>"payment","queue"=>$queue,"message" => $message,"title"=>$title, "action"=>$action,"id_transaction"=>$id_trans, "partnerID"=>$id_partner, "methodPay"=>$payment_method, "order"=>$order];

                //             $fcmNotification = [
                //                 'to'        => $fcm_token,
                //                 'notification' => $notification,
                //                 'data' => $extraNotificationData
                //             ];

                //         $ch = curl_init();
                //         curl_setopt($ch, CURLOPT_URL, $url);
                //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                //         curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
                //         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                //         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
                //         curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

                //         $result = curl_exec($ch);
                //         curl_close($ch);
                //     }
                    // foreach ($devTokens as $val) {
                    //     $dev_token=$val['token'];
                    //     if($dev_token!="TEMPORARY_TOKEN"){
                    //         $fcm_token=$dev_token;
                    //         $title="Pesanan Dibatalkan";
                    //         $message="Pesanan " .$id." di Meja ".$no_meja." dibatalkan.";
                    //         $id = null;
                    //         $action = null;

                    //             $url = "https://fcm.googleapis.com/fcm/send";
                    //             $header = [
                    //                 'authorization: key=AIzaSyDYqiHlqZWkBjin6jcMZnF4YXfzy7_T9SQ',
                    //                 'content-type: application/json'
                    //             ];

                    //             $notification = [
                    //                 'title' =>$title,
                    //                 'body' => $message,
                    //                 'android_channel_id' => 'rn-push-notification-channel',
                    //                 'time_to_live' => 86400,
                    //                 'collapse_key'=> 'new_message',
                    //                 'delay_while_idle'=> false,
                    //                 'priority'=>'high',
                    //                 'content_available'=>true,
                    //                 'message'=> $message,
                    //                 'sound'=> 'default',
                    //                 'high_priority'=> 'high',
                    //                 'show_in_foreground'=> true

                    //             ];
                    //             $extraNotificationData = ["status"=>$status,"event"=>"ewallet.payment",  "soundAndroid"=> "bell_new_order",
                    //             "soundIos"=> "bell_new_order","queue"=>$queue,"message" => $message,"title"=>$title,"id" =>$id,"action"=>$action,"id_transaction"=>$external_id, "partnerID"=>$id_partner, "methodPay"=>$payment_method, "id"=>$external_id, "order"=>$order];

                    //             $fcmNotification = [
                    //                 'to'        => $fcm_token,
                    //                 'notification' => $notification,
                    //                 'data' => $extraNotificationData
                    //             ];

                    //         $ch = curl_init();
                    //         curl_setopt($ch, CURLOPT_URL, $url);
                    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    //         curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
                    //         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                    //         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
                    //         curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

                    //         $result = curl_exec($ch);
                    //         curl_close($ch);
                    //     }
                    // }
                    foreach ($udevTokens as $val) {
                        $dev_token=$val['token'];
                        if($dev_token!="TEMPORARY_TOKEN"){
                            $fcm_token=$dev_token;
                            $title="Pesanan Dibatalkan";
                            $message="Pesanan anda dibatalkan.";
                            $id = null;
                            $action = null;

                            $notif = $db->savePaymentNotification($fcm_token, $title, $message, $no_meja, 'ur-user', $payment_method, 3, $queue, $id, $id_partner, $action, $order, $gender, $birth_date, $isMembership, 0, '', $phone);
                            $insertMessage = mysqli_query($db_conn, "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$id'");

                            //     $url = "https://fcm.googleapis.com/fcm/send";
                            //     $header = [
                            //         'authorization: key=AIzaSyDYqiHlqZWkBjin6jcMZnF4YXfzy7_T9SQ',
                            //         'content-type: application/json'
                            //     ];

                            //     $notification = [
                            //         'title' =>$title,
                            //         'body' => $message,
                            //         'android_channel_id' => 'ur-user',
                            //         'time_to_live' => 86400,
                            //         'collapse_key'=> 'new_message',
                            //         'delay_while_idle'=> false,
                            //         'priority'=>'high',
                            //         'content_available'=>true,
                            //         'message'=> $message,
                            //         'sound'=> 'default',
                            //         'high_priority'=> 'high',
                            //         'show_in_foreground'=> true

                            //     ];

                            //     $extraNotificationData = ["status"=>$status,"event"=>"ewallet.payment",  "soundAndroid"=> "bell_new_order",
                            //     "soundIos"=> "bell_new_order","queue"=>$queue,"message" => $message,"title"=>$title,"id" =>$id,"action"=>$action,"id_transaction"=>$external_id, "partnerID"=>$id_partner, "methodPay"=>$payment_method, "id"=>$external_id, "order"=>$order];

                            //     $fcmNotification = [
                            //         'to'        => $fcm_token,
                            //         'notification' => $notification,
                            //         'data' => $extraNotificationData
                            //     ];

                            // $ch = curl_init();
                            // curl_setopt($ch, CURLOPT_URL, $url);
                            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
                            // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                            // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
                            // curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

                            // $result = curl_exec($ch);
                            // curl_close($ch);
                        }
                    }
                }
                    $msg = "Success";
                    $success = 1;
                    $status=200;
            }else{
                $msg = "Data Not Registered";
                $success = 0;
                $status=400;
            }
        }
    }else{
        $success=0;
        $msg="Missing require field's";
        $status=400;
    }
}
if($status==204){
    http_response_code(200);
}else{
    http_response_code($status);
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>