<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require '../../db_connection.php';
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
$token = '';

$idInsert = 0;
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt', $token));
$success = 0;
$msg = "";

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {
    $status = $tokens['status'];
    $signupMsg = $tokens['msg'];
} else {
    $partnerID = $tokenDecoded->partnerID;
    $masterID = $tokenDecoded->masterID;
    $data = json_decode(file_get_contents('php://input'));
    $queryDeleteBundle = "";
    
    if(isset($data->id)){
        $queryDeleteBundle = "UPDATE bundle_packages SET deleted_at = NOW() WHERE id= '$data->id' AND partner_id='$partnerID'";

        $deleteBundlePackage = mysqli_query($db_conn, $queryDeleteBundle);
        
        if ($deleteBundlePackage) {
            $queryDeleteDetail = "UPDATE bundle_package_details SET deleted_at = NOW() WHERE bundle_id= '$data->id' AND partner_id='$partnerID'";

            $deleteBundleDetail = mysqli_query($db_conn, $queryDeleteDetail);
        
            if($deleteBundleDetail){
                $success = 1;
                $status = 200;
                $msg = "Berhasil Menghapus Data Paket Bundle";
            }else {
                $status = 204;
                $success = 0;
                $msg = "Gagal Menghapus Data Paket Bundle";
            }
        } else {
            $status = 204;
            $success = 0;
            $msg = "Data Tidak Ditemukan";
        }
        
    } else {
        $status = 400;
        $success = 0;
        $msg = "Data Belum Lengkap";
    }
}
echo json_encode(["status" => $status, "success" => $success, "msg"=>$msg, "query"=>$queryDeletBundle]);