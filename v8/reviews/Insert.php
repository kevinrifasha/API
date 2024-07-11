<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once "../../includes/DbOperation.php";
//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

//init var
$headers = apache_request_headers();
$tokenizer = new Token();
$token = '';
$db = new DbOperation();
$res = array();
$dataArray = array();
$employeeArray = array();
$i = 0;

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$iid = 0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $data = json_decode(file_get_contents('php://input'));
    if(
        isset($data->transactionID) && !empty($data->transactionID)
        &&isset($data->rating)  &&isset($data->rating)
        ){
        $q = mysqli_query($db_conn, "INSERT INTO `reviews`(`transaction_id`, `review`, `rating`, `attributes`, `anonymous`) VALUES ('$data->transactionID', '$data->review', '$data->rating', '$data->attributes' , '$data->anonymous')");
        $q2 = mysqli_query($db_conn, "UPDATE transaksi SET rated=1, updated_at=NOW() WHERE id='$data->transactionID'");
        if ($q&&$q2) {
            $iid = mysqli_insert_id($db_conn);
            $title="Ulasan baru";
            $content="Ada ulasan bintang ".$data->rating." dari pelanggan. Silakan akses menu ulasan untuk informasi lebih lanjut";
            $devTokens = $db->getPartnerDeviceTokens($data->partnerID);
            $getEmployees = mysqli_query($db_conn, "SELECT employee_id as id FROM device_tokens WHERE id_partner='$data->partnerID' AND deleted_at IS NULL ORDER BY id");
            $insertMessage = mysqli_query($db_conn,"INSERT INTO partner_messages SET partner_id='$data->partnerID', title='$title', content='$content'");
            $action = "review";
            foreach ($devTokens as $val) {
                $dev_token = $val['token'];
                $qInsert = "INSERT INTO `pending_notification` (`title`, `partner_id`,`message`,`dev_token`,`action`) VALUES ('$title','$data->partnerID','$content','$dev_token','$action')";
                $insertNotif = mysqli_query($db_conn, $qInsert);
            }
            $success =1;
            $status =200;
            $msg = "Berhasil menambah review";
        } else {
            $success =0;
            $status =204;
            $msg = "Gagal menambah review";
        }
    }else{
        $success =0;
        $status =400;
        $msg = "Mohon lengkapi form";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "insertedID"=>$iid]);
?>