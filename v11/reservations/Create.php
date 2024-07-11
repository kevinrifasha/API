<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');

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
    if($data->for_reservation_id==""){
        $data->for_reservation_id = 0;
    }
    if($data->table_id==""){
        $data->table_id = 0;
    }
    $qInsert = "INSERT INTO `reservations`(phone, email, `partner_id`, `user_id`, `for_reservation_id`, `name`, `description`, `persons`, `minimum_transaction`, `booking_price`, `duration`, `duration_metric`, `reservation_time`, `end_time`, `status`, `partner_notes`, `table_id`, source) VALUES ( '$data->phone','$data->email','$data->partner_id', '$data->user_id', '$data->for_reservation_id', '$data->name', '$data->description', '$data->capacity', 0, 0, 2, 'Hour', '$data->reservation_time', '$data->end_time', 'Approved', '$data->partner_notes', '$data->table_id', 'Natta Backoffice App')";
    $q = mysqli_query($db_conn, $qInsert);
    if ($q) {
        $success = 1;
        $msg = "Berhasil Tambah Reservasi";
        $status = 200;
        $iid = mysqli_insert_id($db_conn);
        if(isset($data->email) && !empty($data->email)){
            $query = "SELECT template FROM `email_template` WHERE name='reservation-customer-natta'";
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

$signupJson = json_encode(["msg"=>$msg, "success"=>$success,"status"=>$status]);
echo $signupJson;
