<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../../tokenModels/tokenManager.php");
require_once("../../connection.php");
require '../../../db_connection.php';

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
    $partnerID = $_GET['partnerID'];
        if(isset($partnerID) && !empty($partnerID)){
            $sql = mysqli_query($db_conn, "SELECT m.id, m.nama AS name, m.thumbnail, p.name AS partnerName FROM menu m JOIN partner p ON m.id_partner = p.id WHERE p.fc_parent_id = '$partnerID' AND is_suggestions!=0 AND m.deleted_at IS NULL ORDER BY is_suggestions");

            if(mysqli_num_rows($sql)>0){
                $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                $success = 1;
                $msg = "Success";
                $status=200;
            }else{
                $success = 0;
                $msg = "Data Not Found";
                $status=200;

            }
        }else{

            $success=0;
            $msg="Missing require field's";
            $status = 200;
        }
    }

    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "menus"=>$data]);

    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;
