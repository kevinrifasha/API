<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

// date_default_timezone_set('Asia/Jakarta');

//init var
$headers = apache_request_headers();
$today1 = date('Y-m-d');
$tokenizer = new Token();
$token = '';
$res = array();
$res1 = array();
$ewallet_response = array();
$id = "";

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
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
}else{
    $data = json_decode(file_get_contents('php://input'));
    if( isset($data->partnerID)
        && !empty($data->partnerID)
    ){

            $distance = 0;
            $available = false;
            $delivery_fee = 0;
            if(isset($data->distance) && !empty($data->distance)){
                $distance=$data->distance;
                $qD = mysqli_query($db_conn,"SELECT radius, price FROM custom_deliveries JOIN partner ON partner.id_master=custom_deliveries.master_id  WHERE radius*1000>='$distance' AND partner.id='$data->partnerID' AND custom_deliveries.deleted_at IS NULL ORDER BY radius ASC LIMIT 1");
                if (mysqli_num_rows($qD) > 0) {
                    $resD = mysqli_fetch_all($qD, MYSQLI_ASSOC);
                    $available = true;
                    $delivery_fee=$resD[0]['price'];
                }
            }
            $grabActive='0';
            $gosendActive='0';
            $hit = 0;
            $qS = mysqli_query($db_conn,"SELECT name,value FROM `settings` WHERE id='13' OR id='14'");
            if (mysqli_num_rows($qS) > 0) {
                $resQ = mysqli_fetch_all($qS, MYSQLI_ASSOC);
                $grabActive=$resQ[0]['value'];
                $gosendActive=$resQ[1]['value'];
                $hit = 1;
            }

            if($hit==1){
                $qS1 = mysqli_query($db_conn,"SELECT grab_active, go_send_active FROM `partner` WHERE id='$data->partnerID'");
                if (mysqli_num_rows($qS1) > 0) {
                    $resQ1 = mysqli_fetch_all($qS1, MYSQLI_ASSOC);
                    $grabActive=$resQ1[0]['grab_active'];
                    $gosendActive=$resQ1[0]['go_send_active'];
                    $hit = 1;
                }
            }

            $success = 1;
            $msg = "Success";
            $status = 200;
            echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "delivery_fee"=>$delivery_fee, "available"=>$available, "grab_active"=>$grabActive, "gosend_active"=>$gosendActive]);
    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
    }
}