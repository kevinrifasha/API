<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';

$token = '';
$res = array();


    $obj = json_decode(json_encode($_POST));
    if(
        isset($obj->deviceToken) && !empty($obj->deviceToken)
        && isset($obj->id_partner) && !empty($obj->id_partner)
        && isset($obj->id) && !empty($obj->id)
        ){
        $validateToken = mysqli_query($db_conn, "SELECT id FROM `device_tokens` WHERE tokens = '$obj->deviceToken' AND id_partner='$obj->id_partner' AND employee_id='$obj->id'");
        if(mysqli_num_rows($validateToken) > 0){
            $q = mysqli_query($db_conn, "UPDATE `device_tokens` SET deleted_at=NOW() WHERE id_partner='$obj->id_partner' AND employee_id='$obj->id' AND tokens='$obj->deviceToken'");
            if ($q) {
                $iid = mysqli_insert_id($db_conn);
                $success =1;
                $status =200;
                $msg = "Success";
            } else {
                $success =0;
                $status =204;
                $msg = "Failed";
            }
        }else{
            $success =1;
            $status =200;
            $msg = "Not Registered Registered";
        }
        
    }else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }

http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>