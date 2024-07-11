<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
require '../../db_connection.php';
require_once('../auth/Token.php');

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
$token = '';

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

// $db = connectBase();
$tokenizer = new Token();
$tokens = $tokenizer->validate($token);
$today = date("Y-m-d H:i:s");


$data = json_decode(json_encode($_POST));
$res=array();

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
}else{

    if(isset($data->userPhone) && !empty($data->userPhone) && isset($data->token) && !empty($data->token)){


            $resq = mysqli_query($db_conn, "SELECT * FROM `device_tokens` WHERE tokens='$data->token' AND user_phone='$data->userPhone' AND deleted_at IS NULL");


            if(mysqli_num_rows($resq) == 0){

                $insert = mysqli_query($db_conn, "INSERT INTO `device_tokens`(`user_phone`, `tokens`, `created_at`) VALUES ('$data->userPhone','$data->token',NOW())");
                if($insert){
                    $msg = "Berhasil";
                    $success = 1;
                }else{
                    $msg = "Gagal, Kesalahan Sistem";
                    $success = 0;
                }

            }else{
                $msg = "Sudah Terdaftar";
                $success = 0;

            }


    }else{
        $msg = "Missing Value";
        $success = 0;
    }

}



$signupJson = json_encode(["success"=>$success, "msg"=>$msg]);
// Echo the message.

echo $signupJson;

 ?>
