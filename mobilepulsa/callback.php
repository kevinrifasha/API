<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';
date_default_timezone_set('Asia/Jakarta');


$obj = json_decode(file_get_contents('php://input'));
$ref_id = $obj->data->ref_id;
$external_id = $obj->data->ref_id;
$status = $obj->data->status;

$obj = json_encode($obj);
$insert = mysqli_query(
    $db_conn,
    "INSERT INTO `temp_xendit_callback`(`value`) VALUES ('$obj')"
);
if($status == "1"){
    $UpdateCallback = mysqli_query(
        $db_conn,
        "UPDATE `transaction_mobilepulsa` SET `status`='1', `updated_at`=NOW(), `callback_response_mobile_pulsa`='$obj' WHERE `tranasaction_code`='$external_id'"
    );
}else{
    $UpdateCallback = mysqli_query(
        $db_conn,
        "UPDATE `transaction_mobilepulsa` SET `status`='2', `updated_at`=NOW(), `callback_response_mobile_pulsa`='$obj' WHERE `tranasaction_code`='$external_id'"
    );
}
?>