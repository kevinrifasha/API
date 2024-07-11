<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
require '../../db_connection.php';

$today = date("Y-m-d H:i:s");


$data = json_decode(json_encode($_POST));
    if(isset($data->userPhone) && !empty($data->userPhone) && isset($data->token) && !empty($data->token)){
            $resq = mysqli_query($db_conn, "SELECT tokens FROM `device_tokens` WHERE tokens='$data->token' AND user_phone='$data->userPhone'");

            if(mysqli_num_rows($resq) > 0){

            $insert = mysqli_query($db_conn, "UPDATE `device_tokens` SET deleted_at=NOW() WHERE user_phone='$data->userPhone' AND tokens='$data->token'");
                if($insert){
                    $msg = "Berhasil";
                    $status =200;
                    $success = 1;
                }else{
                    $msg = "Gagal, Kesalahan Sistem";
                    $status =400;
                    $success = 0;
                }

            }else{
                $msg = "tidak ditemukan";
                $status =204;
                $success = 0;

            }
    }else{
        $msg = "Missing Value";
        $success = 0;
        $status =400;
    }


echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);

 ?>
