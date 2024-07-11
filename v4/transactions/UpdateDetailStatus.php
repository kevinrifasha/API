<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

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
    $data = json_decode(json_encode($_POST));
    if (
        isset($data->transactionDetailID)
        && !empty($data->transactionDetailID)
    ) {
        $transactionDetailID = mysqli_real_escape_string($db_conn, trim($data->transactionDetailID));
        $transactionID = mysqli_real_escape_string($db_conn, trim($data->transactionID));
        $status1 = mysqli_real_escape_string($db_conn, trim($data->status));
    
        $transactionDetailIDs = explode(",",$transactionDetailID);

        foreach ($transactionDetailIDs as $transactionDetailID) {
            if($status==2){
                $updateDetail = mysqli_query($db_conn, "UPDATE `detail_transaksi` SET `status` = '$status1', `qty`=`qty_delivered` WHERE `detail_transaksi`.`id` = '$transactionDetailID'");
            }else{
                $updateDetail = mysqli_query($db_conn, "UPDATE `detail_transaksi` SET `status` = '$status1' WHERE `detail_transaksi`.`id` = '$transactionDetailID'");
            }

            $listTransaksiDetail = mysqli_query($db_conn, "SELECT `id`, `id_transaksi`, `id_menu`, `harga_satuan`, `qty`, `notes`, `harga`, `variant`, `status` FROM `detail_transaksi` WHERE `id`='$transactionDetailID'");
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
                $updateDetail1 = mysqli_query($db_conn, "INSERT INTO `detail_transactions_history`(`id_detail`, `id_transaksi`, `id_menu`, `harga_satuan`, `qty`, `notes`, `harga`, `variant`, `status`, `created_at`) VALUES ('$d_id_detail', '$d_id_transaksi', '$d_id_menu', '$d_harga_satuan', '$d_qty', '$d_notes', '$d_harga', '$d_variant', '$d_status', NOW())");
            }
        
            $transaksiStatus = false;
            if ($updateDetail) {
                $udev_token = "TEMPORARY_TOKEN";
                $counterStatus = 0;
                $listTransaksi = mysqli_query($db_conn, "SELECT dt.status, u.dev_token FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id LEFT JOIN users u ON u.phone=t.phone WHERE dt.id_transaksi='$transactionID'");
                while ($rowL = mysqli_fetch_assoc($listTransaksi)) {
                    $udev_token = $rowL['dev_token'];
                    if ($rowL['status'] == 2) {
        
                        $counterStatus += 1;
        
                        if (mysqli_num_rows($listTransaksi) == $counterStatus) {
                            $updateTransaksi = mysqli_query($db_conn, "UPDATE `transaksi` SET `status` = '2' WHERE `transaksi`.`id` = '$transactionID' ");
                            if ($updateTransaksi) {
                                $transaksiStatus = true;
                                if($udev_token!="TEMPORARY_TOKEN"){
                                    $fcm_token=$udev_token;
                                    $title="Pesanan Selesai";
                                    $message="Pesanan anda telah selesai."; 
                                    $id = null;
                                    $action = null;
                                        
                                        $url = "https://fcm.googleapis.com/fcm/send";            
                                        $header = [
                                            'authorization: key=AIzaSyDYqiHlqZWkBjin6jcMZnF4YXfzy7_T9SQ',
                                            'content-type: application/json'
                                        ];    
                                    
                                        $notification = [
                                            'title' =>$title,
                                            'body' => $message,
                                            'android_channel_id' => 'ur-user',
                                            'vibrate'=> 1,
                                            'sound'=> 5,
                                            'show_in_foreground'=> true,
                                            'priority'=> 'high',
                                            'content_available'=> true,
                                        ];
                                        $extraNotificationData = ["status"=>$status,"event"=>"payment","queue"=>$queue,"message" => $message,"title"=>$title, "action"=>$action,"id_transaction"=>$obj->transactionID, "partnerID"=>$id_partner, "methodPay"=>$payment_method, "order"=>$order];
                                    
                                        $fcmNotification = [
                                            'to'        => $fcm_token,
                                            'notification' => $notification,
                                            'data' => $extraNotificationData
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
                                }
                            }
                        }
                    }
                }
                
        
                if ($transaksiStatus == true) {
                    $msg = "Transaksi Sudah di selesai!";
                    $success = 1;
                    $status=200;
                } else {
                    $msg = "Detail Transaksi Sudah di updated!";
                    $success = 1;
                    $status=200;
                }
        
            } else {
                $msg = "Gagal";
                $success = 0;
                $status=400;
            }
        }
    } else {
        $success=0;
        $msg="Missing required fields";
        $status=400;
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>