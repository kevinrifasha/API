<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../../db_connection.php';
require_once('../../auth/Token.php');
require_once '../../../includes/DbOperation.php';

// date_default_timezone_set('Asia/Jakarta');

// POST DATA
$db = new DbOperation();

//init var
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
$today1 = date('Y-m-d');
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
    
    $data = json_decode(file_get_contents('php://input'));
    if( isset($data->partnerID)
    && isset($data->message)
    && isset($data->tableCode)
    && !empty($data->partnerID)
    && !empty($data->message)
    && !empty($data->tableCode) ){
        
        $partnerID = $data->partnerID;
        $message = $data->message;
        $tableCode = $data->tableCode;
        
    $devTokens = $db->getPartnerDeviceTokens($partnerID);
    foreach ($devTokens as $val) {
        $dev_token=$val['token'];
        if($dev_token!="TEMPORARY_TOKEN"){
            $fcm_token=$dev_token;
            $title="Panggilan Waiter - Meja ".$data->tableCode;
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
                'android_channel_id' => 'ur-partner-employees',
                'time_to_live' => 86400,
                'collapse_key'=> 'new_message',
                'delay_while_idle'=> false,
                'priority'=>'high',
                'content_available'=>true, 
                'message'=> $message,
                'sound'=> 'default',
                'high_priority'=> 'high',
                'show_in_foreground'=> true
            ];
            
            $extraNotificationData = ["status"=>200,  "soundAndroid"=> "bell_new_order", "soundIos"=> "bell_new_order","event"=>"ewallet.payment", "tableCode"=>$tableCode, "message"=>$message, "type"=>"employee"];
                
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
        $success=1;
        $status=200;
        $msg=json_encode($result);
    }else{
        $success=1;
        $status=400;
        $msg="Missing Required Field!";
    }
        
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg ]);