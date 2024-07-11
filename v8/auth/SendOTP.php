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
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
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
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
$iid = 0;
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {

    $obj = json_decode(file_get_contents('php://input'));
    if (
        isset($obj->phone) && !empty($obj->phone)
    ) {
        $otp = rand(1000, 9999);
        $q = mysqli_query($db_conn, "INSERT INTO otp SET phone='$obj->phone', otp='$otp', source='selforder_web'");

        if ($q) {
            //wa message
            // $message = "OTP kamu adalah " . $otp . " berlaku 15 menit";
            $message =  $otp;

            $params = [
                "phone" => $obj->phone,
                "message" => $message,
                "source" => "selforder_web",
                "schedule_send" => date("Y-m-d H:i:s", strtotime("-2 hours"))
            ];
            $ch = curl_init();
            $timestamp = new DateTime();
            $body = json_encode($params);
            // curl_setopt(
            //     $ch,
            //     CURLOPT_URL,
            //     "http://34.101.181.110:4888/saveMessage"

            // );
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // curl_setopt($ch, CURLOPT_POST, 1);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $body);


            // $headers = [];
            // $headers[] = "Content-Type: application/json";
            // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            // $result = curl_exec($ch);
            // if (curl_errno($ch)) {
            //     echo "Error:" . curl_error($ch);
            // }
            // $curlResponse = $result;
            // $res = json_decode($curlResponse);
            // $waStatus = $res->status;
            $waStatus = true;
            curl_close($ch);
            if ($waStatus == true) {
                $insertWA = mysqli_query($db_conn, "INSERT INTO whatsapp_messages SET phone='$obj->phone', content='$message', source='selforder_web', schedule_send=NOW()");
                if ($insertWA) {
                    $waStatus = true;
                } else {
                    $waStatus = false;
                }
                $success = 1;
                $status = 200;
                $msg = "Berhasil kirim OTP";
            } else {
                $success = 0;
                $status = 204;
                $msg = "Gagal kirim OTP. Mohon coba lagi";
            }
        } else {
            $success = 0;
            $status = 204;
            $msg = "Gagal kirim OTP. Mohon coba lagi";
        }
    } else {
        $success = 0;
        $status = 400;
        $msg = "Mohon lengkapi form";
    }
}

echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "waStatus" => $waStatus]);
