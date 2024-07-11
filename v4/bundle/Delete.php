<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once '../../includes/DbOperation.php';
require '../../includes/functions.php';
require '../../includes/ValidatorV4.php';

$fs = new functions();
// date_default_timezone_set('Asia/Jakarta');
// POST DATA
$db = new DbOperation();
$validator = new ValidatorV4();

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
$res1 = array();

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $partnerID = $token->id_partner;
    $masterID = $token->id_master;
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