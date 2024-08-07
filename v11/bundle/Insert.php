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
    
    if(isset($data->bundle_name) && !empty($data->bundle_name) && isset($data->menus) && !empty($data->menus) && isset($data->price)){
        $queryBundlePackage = "";
        $valid_from = "";
        $valid_until = "";
        $name = mysqli_real_escape_string($db_conn, $data->bundle_name);
        if(isset($data->valid_from) && isset($data->valid_to)){
            $phpdateValidFrom = strtotime( $data->valid_from );
            $valid_from = date( 'Y-m-d H:i:s', $phpdateValidFrom );
            $phpdateValidUntil = strtotime( $data->valid_until );
            $valid_until = date( 'Y-m-d H:i:s', $phpdateValidUntil );
            $queryBundlePackage = "INSERT INTO bundle_packages (name, price, valid_from, valid_until, master_id, partner_id) VALUES ('$name','$data->price', '$valid_from', '$valid_until', '$masterID','$partnerID')";
        } else {
            $queryBundlePackage = "INSERT INTO bundle_packages (name, price, master_id, partner_id) VALUES ('$name','$data->price', '$masterID','$partnerID')";
        }
        
        $queryValidator = mysqli_query($db_conn,"SELECT id, name, price FROM bundle_packages WHERE name = '$name' AND price = '$data->price' AND partner_id = '$partnerID'");        
        
        if (mysqli_num_rows($queryValidator) > 0) {
            $success = 0;
            $msg = "Paket Bundle Dengan Nama '$data->bundle_name' Sudah Terdaftar";
        } else {
            $insertBundlePackage = mysqli_query($db_conn, $queryBundlePackage );
        
            if($insertBundlePackage){
                $selectID = mysqli_query($db_conn,"SELECT id FROM bundle_packages WHERE name = '$data->bundle_name' AND price = '$data->price' AND partner_id = '$partnerID'"); 
                
                $getID = mysqli_fetch_all($selectID, MYSQLI_ASSOC);
                
                $bundle_id = $getID[0]["id"];
                
                $menus = $data->menus;
                
                $queryBundleDetail = ""; 
                
                foreach($menus as $val){
                    
                    $menu_id = $val->id;
                    $menu_qty = $val->qty;
                    $variant_id = $val->variants;
                
                    if($val->variants == "0"){
                        $queryBundleDetail = "INSERT INTO bundle_package_details (menu_id, bundle_id, qty, all_variants, master_id, partner_id) VALUES ('$menu_id','$bundle_id','$menu_qty' , 1 ,'$masterID','$partnerID')";
                        
                        $insertBundleDetail = mysqli_query($db_conn, $queryBundleDetail );
                    } else {
                        $queryBundleDetail = "INSERT INTO bundle_package_details (menu_id, bundle_id, qty, all_variants, variant_id, master_id, partner_id) VALUES ('$menu_id','$bundle_id','$menu_qty' , 0 ,'$variant_id' ,'$masterID','$partnerID')";
                        
                        $insertBundleDetail = mysqli_query($db_conn, $queryBundleDetail );
                    }
                    
                }
                $status = 200;
                $success = 1;
                $msg = "Berhasil Menambah Data Paket Bundle";
            } else {
                $status = 204;
                $success = 0;
                $msg = "Gagal Menambah Data Paket Bundle";
                
            }
        }
        
    } else {
        $status = 400;
        $success = 0;
        $msg = "Data Belum Lengkap";
    }
}
echo json_encode(["status"=>$status, "success" => $success, "msg"=>$msg, "test"=>$test]);