<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';

    $query = "SELECT `pending_notification`.`id`, `pending_notification`.`phone`, `pending_notification`.`partner_id`, `pending_notification`.`dev_token`, `pending_notification`.`title`, `pending_notification`.`message`, `pending_notification`.`no_meja`, `pending_notification`.`channel_id`, `pending_notification`.`method_pay`, `pending_notification`.`status`, `pending_notification`.`queue`, `pending_notification`.`id_trans`, `pending_notification`.`action`, `pending_notification`.`orders`, `pending_notification`.`gender`, `pending_notification`.`is_membership`, `pending_notification`.`delivery_fee`, `pending_notification`.`type`, `pending_notification`.`birth_date`, `pending_notification`.`created_at`, `transaksi`.`diskon_spesial`, `transaksi`.`employee_discount` FROM `pending_notification` JOIN `transaksi` ON `transaksi`.`id`=`pending_notification`.`id_trans` WHERE `pending_notification`.`deleted_at` IS NULL ORDER BY id DESC";
    $notif = mysqli_query($db_conn, $query);
    while($row=mysqli_fetch_assoc($notif)){
        $url = "https://fcm.googleapis.com/fcm/send";            
        $header = [
            'authorization: key=AIzaSyDYqiHlqZWkBjin6jcMZnF4YXfzy7_T9SQ',
            'content-type: application/json'
        ];    
        
        $notification = [
            'title' =>$row['title'],
            'body' => $row['message'],
            'android_channel_id' => $row['channel_id'],
            'time_to_live' => 86400,
            'collapse_key'=> 'new_message',
            'delay_while_idle'=> false,
            'priority'=>'high',
            'content_available'=>true, 
            'message'=> $row['message'],
            'sound'=> 'default',
            'high_priority'=> 'high',
            'show_in_foreground'=> true
        ];
        
        $action = null;
        if(isset($row['action']) && !empty($row['action'])){
            $action = $row['action'];
        }
        
        $order = json_decode($row['orders']);
        
        $isMembership=true;
        if($row['is_membership']=="false"){
            $isMembership=false;
        }
        
        $extraNotificationData = ["status"=>$row['status'],"event"=>"payment","queue"=>$queue,"message" => $row['message'],"title"=>$row['title'], "action"=>$action,"id_transaction"=>$row['id_trans'], "partnerID"=>$row['partner_id'], "methodPay"=>$row['method_pay'],  "soundAndroid"=> "bell_new_order", "soundIos"=> "bell_new_order", "order"=>$order, "gender"=>$row['gender'], "birthDate"=>$row['birth_date'], "isMembership"=>$isMembership, "delivery_fee"=>$row['delivery_fee'], "type"=>$row['type'], "id"=>$row['id_trans'], "employee_discount"=>$row['employee_discount'], "diskon_spesial"=>$row['diskon_spesial'] ];
        
        $fcmNotification = [
            'to'    => $row['dev_token'],
            'notification'  => $notification,
            'data'  => $extraNotificationData
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        
        $result = curl_exec($ch);
        curl_close($ch);
        $result1 = json_decode($result);
        $id = $row['id'];
        if($result1->success==1){
            $phone = $row['phone'];
            $partner_id = $row['partner_id'];
            $dev_token = $row['dev_token'];
            $title = $row['title'];
            $message = $row['message'];
            $no_meja = $row['no_meja'];
            $channel_id = $row['channel_id'];
            $method_pay = $row['method_pay'];
            $status = $row['status'];
            $queue = $row['queue'];
            $id_trans = $row['id_trans'];
            $action = $row['action'];
            $orders = $row['orders'];
            $gender = $row['gender'];
            $birth_date = $row['birth_date'];
            $is_membership = $row['is_membership'];
            $delivery_fee = $row['delivery_fee'];
            $type = $row['type'];
            $notifInsert = mysqli_query($db_conn, "INSERT INTO `sent_notification`(`phone`, `partner_id`, `dev_token`, `title`, `message`, `no_meja`, `channel_id`, `method_pay`, `status`, `queue`, `id_trans`, `action`, `orders`, `gender`, `birth_date`, `is_membership`, `delivery_fee`, `type`, `created_at`, `response`) VALUES ('$phone', '$partner_id', '$dev_token', '$title', '$message', '$no_meja', '$channel_id', '$method_pay', '$status', '$queue', '$id_trans', '$action', '$orders', '$gender', '$birth_date', '$is_membership', '$delivery_fee', '$type', NOW(), '$result')");
            $query = "DELETE FROM `pending_notification` WHERE id='$id'";
            $delete = mysqli_query($db_conn, $query);
            
        }else{
            $dev_token = $row['dev_token'];
            if($result1->results[0]->error==="NotRegistered"){
                $query = "DELETE FROM `device_tokens` WHERE tokens='$dev_token'";
                $delete = mysqli_query($db_conn, $query);
                
            }
            // if($result1->results[0])
            $phone = $row['phone'];
            $partner_id = $row['partner_id'];
            $dev_token = $row['dev_token'];
            $title = $row['title'];
            $message = $row['message'];
            $no_meja = $row['no_meja'];
            $channel_id = $row['channel_id'];
            $method_pay = $row['method_pay'];
            $status = $row['status'];
            $queue = $row['queue'];
            $id_trans = $row['id_trans'];
            $action = $row['action'];
            $orders = $row['orders'];
            $gender = $row['gender'];
            $birth_date = $row['birth_date'];
            $is_membership = $row['is_membership'];
            $delivery_fee = $row['delivery_fee'];
            $type = $row['type'];
            $notifInsert = mysqli_query($db_conn, "INSERT INTO `failed_notification`(`phone`, `partner_id`, `dev_token`, `title`, `message`, `no_meja`, `channel_id`, `method_pay`, `status`, `queue`, `id_trans`, `action`, `orders`, `gender`, `birth_date`, `is_membership`, `delivery_fee`, `type`, `created_at`, `response`) VALUES ('$phone', '$partner_id', '$dev_token', '$title', '$message', '$no_meja', '$channel_id', '$method_pay', '$status', '$queue', '$id_trans', '$action', '$orders', '$gender', '$birth_date', '$is_membership', '$delivery_fee', '$type', NOW(), '$result')");
            $query = "DELETE FROM `pending_notification` WHERE id='$id'";
            $delete = mysqli_query($db_conn, $query);
        }
    }
    ?>