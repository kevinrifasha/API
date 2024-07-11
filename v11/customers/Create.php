<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require '../../includes/functions.php';
require_once('../auth/Token.php');

// //init var
$fs = new functions();
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
$status=200;

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
    if(isset($data->name) &&!empty($data->name) && isset($data->phone) &&!empty($data->phone)){
        $sqlGetUser =  "SELECT name FROM `users` WHERE phone='$data->phone' AND deleted_at IS NULL";
        $user = mysqli_query($db_conn,$sqlGetUser);
        if (mysqli_num_rows($user > 0)) {
            $status = 204;
            $msg = "Nomor Telp. sudah terdaftar! "; 
            $success = 0; 
        }else{
            $sql =  "INSERT INTO `users`(`name`, `phone`, `created_at`) VALUES ('$data->name', '$data->phone', NOW())";
            $insert = mysqli_query($db_conn,$sql);
                
            if($insert){
                $status = 200;
                $msg = "Berhasil"; 
                $success = 1; 
            }else{
                $status = 204;
                $msg = "Gagal! silahkan coba lagi"; 
                $success = 0; 
            }
        }
    }else{ 
        $status = 400;
        $msg = "Missing Required Fields!"; 
        $success = 0; 
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);