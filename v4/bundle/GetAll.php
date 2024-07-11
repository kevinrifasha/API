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
    
    $queryGet = mysqli_query($db_conn,"SELECT id, name, price, valid_from, valid_until FROM bundle_packages WHERE partner_id = '$partnerID' AND deleted_at IS NULL");
    
    if (mysqli_num_rows($queryGet) < 1) {
            $success = 0;
            $msg = "Paket Bundle Tidak Ditemukan";
    } else {
               $selectedData = mysqli_fetch_all($queryGet, MYSQLI_ASSOC);
        
        $data = $selectedData;
        
        $i = 0;
        foreach($selectedData as $val){
            $bundle_id = $val["id"];
            
            $queryGet = mysqli_query($db_conn,"SELECT bpd.id as detail_id, bpd.menu_id, m.is_variant, bpd.qty, bpd.all_variants, bpd.variant_id, m.nama as menu_name, v.name as variant_name, v.id_variant_group, CASE WHEN bpd.variant_id = 0 THEN NULL ELSE vg.name END as variant_group_name FROM bundle_package_details bpd LEFT JOIN menu m ON m.id = bpd.menu_id LEFT JOIN variant v ON v.id = bpd.variant_id LEFT JOIN menus_variantgroups mvg ON mvg.menu_id = bpd.menu_id LEFT JOIN variant_group vg ON vg.id = mvg.variant_group_id WHERE bpd.bundle_id='$bundle_id' AND (vg.id = v.id_variant_group OR bpd.variant_id = 0) AND bpd.deleted_at IS NULL GROUP BY bpd.id ORDER BY bpd.created_at DESC");
            
            $selectedDetail = mysqli_fetch_all($queryGet, MYSQLI_ASSOC);
            
            $selectedData[$i]["detail"] = $selectedDetail;
            $i++;
        }
        
        $res = $selectedData;
        
        if($res){
            $status = 200;
            $msg = "Get Data Success";
            $success = 1;
        } else {
            $status = 204;
            $msg = "Get Data Failed";
            $success = 0;
            
        }
        
    }

echo json_encode(["status" => $status,"success" => $success, "msg"=>$msg, "data"=>$res, "test"=>$test]);
}