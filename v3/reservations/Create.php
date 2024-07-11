<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';

$headers = apache_request_headers();
$token = '';
    
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $data = json_decode(file_get_contents('php://input'));
    $q = mysqli_query($db_conn, "INSERT INTO `reservations`(phone, email, `partner_id`, `user_id`, `for_reservation_id`, `name`, `description`, `persons`, `minimum_transaction`, `booking_price`, `duration`, `duration_metric`, `reservation_time`, `end_time`, `status`, partner_notes) VALUES ( '$data->phone','$data->email','$data->partner_id', '$data->user_id', 0, '$data->name', '$data->description', '$data->capacity', 0, 0, 2, 'Hour', '$data->reservation_time', '$data->end_time', 'Approved', '$data->partner_notes')");
    if ($q) {
        $success = 1;
        $msg = "Berhasil Tambah Reservasi";
        $status = 200;
        $iid = mysqli_insert_id($db_conn);
            if(isset($data->email) && !empty($data->email)){
        $query = "SELECT template FROM `email_template` WHERE id=2";
        $templateQ = mysqli_query($db_conn, $query);
        if (mysqli_num_rows($templateQ) > 0) {
            $templates = mysqli_fetch_all($templateQ, MYSQLI_ASSOC);
            $template = $templates[0]['template'];
            $template = str_replace('$id',$iid,$template);
            $template = str_replace('$customerName',$data->name,$template);
            $template = str_replace('$dateTime', $data->reservation_time, $template);
            $template = str_replace('$specialRequest',$data->description,$template);
            $template = str_replace('$pax',$data->capacity,$template);
            $qp="SELECT name, address,phone, latitude, longitude, reservation_notes FROM partner WHERE id='$data->partner_id'";
            $getPartnerData = mysqli_query($db_conn, $qp);
            if (mysqli_num_rows($getPartnerData) > 0) {
                $partnerData = mysqli_fetch_all($getPartnerData, MYSQLI_ASSOC);
                $template = str_replace('$partnerName',$partnerData[0]['name'],$template);
                $template = str_replace('$address',$partnerData[0]['address'],$template);
                $template = str_replace('$phone',$partnerData[0]['phone'],$template);
                $template = str_replace('$email',$partnerData[0]['email'],$template);
                $template = str_replace('$partnerNotes',$partnerData[0]['reservation_notes'],$template);
            }
            $emailSubject = "Konfirmasi Reservasi Baru Kamu di ".$partnerData[0]['name']." - ".$data->name;
            $email = "INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`) VALUES ('$data->email', '$data->partner_id', '$emailSubject', '$template')";
                $insertTe = mysqli_query($db_conn, $email);
        }
    }
    }else{    
        $success = 0;
        $msg = "Gagal. Mohon coba lagi";
        $status = 204;
    }
}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
    http_response_code(200);
  }else{
    http_response_code($status);
  }   
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]); 
    
?>
     