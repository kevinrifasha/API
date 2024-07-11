<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

//init var
$headers = apache_request_headers();
$tokenizer = new Token();
$token = '';
$resImages = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$bookmark = "0";
$maxDiscount = 0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $id = $_GET['id'];
    $q = mysqli_query($db_conn, "SELECT `img_map` FROM `partner` WHERE id='$id'");
    $q1 = mysqli_query($db_conn, "SELECT `url` as image, `is_loading_banner` FROM `partner_images` WHERE partner_id='$id' AND `deleted_at` IS NULL AND is_loading_banner='0' ORDER BY sequence ASC");
    if (mysqli_num_rows($q) > 0 || mysqli_num_rows($q1) > 0) {
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $resImages[0]['image']=$res[0]['img_map'];
        }else{
            $resImages[0]['image']="";
        }
        if (mysqli_num_rows($q1) > 0) {
            $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
            foreach ($res1 as $value) {
                array_push($resImages, $value);
            }
        }
            $success =1;
            $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "partners"=>$resImages]);
?>