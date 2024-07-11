<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
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
$iid = 0;

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $obj = json_decode(file_get_contents('php://input'));
    $limit = date('Y-m-d H:i:s', strtotime('+15 minutes', strtotime(date("Y-m-d H:i:s"))));
    $userDetail=[];
    $registered = false;
    $today = date('Y-m-d H:i:s');
    if(
        isset($obj->otp) && !empty($obj->otp)
        && isset($obj->phone) && !empty($obj->phone)
        ){
        $otpV = mysqli_query($db_conn, "SELECT name, value FROM settings WHERE id=31");
        $resOtpV = mysqli_fetch_all($otpV, MYSQLI_ASSOC);
        $validationOTP = $resOtpV[0]["value"];
        if($validationOTP == "1" || $validationOTP == 1){
            $q = mysqli_query($db_conn, "SELECT id, phone, otp, created_at FROM otp WHERE source='selforder_web' AND deleted_at IS NULL AND phone='$obj->phone' ORDER BY id DESC LIMIT 1");
        } else {
            $q = mysqli_query($db_conn, "SELECT id, name, phone, email, TglLahir, Gender FROM users WHERE deleted_at IS NULL AND phone='$obj->phone' ORDER BY id DESC LIMIT 1");
        }

        if (mysqli_num_rows($q)>0){
            if($validationOTP == "1" || $validationOTP == 1){
                $res = mysqli_fetch_assoc($q);
                if($res['created_at']>$limit){
                    $success = 0;
                    $status = 204;
                    $msg ="OTP sudah tidak berlaku. Mohon request kembali";
                }else if($res['otp']!=$obj->otp){
                    $success = 0;
                    $status = 204;
                    $msg ="OTP tidak sesuai";
                }else if($res['otp']==$obj->otp){
                    $success = 1;
                    $status = 200;
                    $msg ="OTP match";
                    // $msg ="Verifikasi Sukses";
                    $getUser = mysqli_query($db_conn, "SELECT id, name, phone, email, TglLahir, Gender FROM users WHERE deleted_at IS NULL AND phone='$obj->phone' ORDER BY id DESC LIMIT 1");
                    if(mysqli_num_rows($getUser)>0){
                        $userDetail = mysqli_fetch_assoc($getUser);
                        $registered=true;
                        $jsonToken = json_encode(['email'=>$userDetail['email'], 'phone'=>$userDetail['phone'], 'created_at'=>$today, 'expired'=>500, "id"=>$userDetail['id'] , "email"=>$userDetail['email'], "name"=>$userDetail['name'] ]);
                $token = $tokenizer->stringEncryption('encrypt', $jsonToken);
                    }
                }
            } else {
                $success = 1;
                $status = 200;
                $msg ="Verifikasi Sukses";
                // $msg ="OTP match";
                $getUser = mysqli_query($db_conn, "SELECT id, name, phone, email, TglLahir, Gender FROM users WHERE deleted_at IS NULL AND phone='$obj->phone' ORDER BY id DESC LIMIT 1");
                if(mysqli_num_rows($getUser)>0){
                    $userDetail = mysqli_fetch_assoc($getUser);
                    $registered=true;
                    $jsonToken = json_encode(['email'=>$userDetail['email'], 'phone'=>$userDetail['phone'], 'created_at'=>$today, 'expired'=>500, "id"=>$userDetail['id'] , "email"=>$userDetail['email'], "name"=>$userDetail['name'] ]);
                $token = $tokenizer->stringEncryption('encrypt', $jsonToken);
                }else {
                    $success =0;
                    $status =204;
                    // $msg = "OTP tidak ditemukan. Mohon request kembali";
                    $msg = "Verifikasi Gagal";
                }
            }             
            
        } else {
            $success =1;
            $status =204;
            // $msg = "OTP tidak ditemukan. Mohon request kembali";
            $msg = "Nomor Belum Terdaftar";
            $userDetail = [];
            $registered = false;
        }
    }else{
        $success =0;
        $status =400;
        $msg = "Mohon lengkapi form";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "userDetail"=>$userDetail, "token"=>$token,"registered"=>$registered]);
