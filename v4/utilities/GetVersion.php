<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
// require_once('../auth/Token.php');

//init var
// $headers = apache_request_headers();
// $tokenizer = new Token();
$token = '';
$res = array();

//get token
// foreach ($headers as $header => $value) {
//     if($header=="Authorization" || $header=="AUTHORIZATION"){
//         $token=substr($value,7);
//     }
// }

// $tokenValidate = $tokenizer->validate($token);
// $token = json_decode($tokenizer->stringEncryption('decrypt',$token));
// if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
//     $status = $tokenValidate['status'];
//     $msg = $tokenValidate['msg']; 
//     $success = 0; 
// }else{
$data = mysqli_query($db_conn, "SELECT name, value FROM settings WHERE id=18 OR id=19 OR id=22 OR id=23");
$res = mysqli_fetch_all($data, MYSQLI_ASSOC);
$success =1;
$status = 200;
$msg = "Data ditemukan";

// }
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "details"=>$res]);