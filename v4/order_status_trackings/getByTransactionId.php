<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

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
$total = 0;
$promo = 0;
$table_group_id = 0;
$program_discount = 0;
$charge_ur = 0;
$service = 0;
$tax = 0;
$diskon_spesial = 0;
$employee_discount = 0;
$res = array();
$is_special_member = false;
$delivery_data = array();
$trxStatus = 0;
$trxPNote = "";
$trxPMName = "";
$address = array();
$printed = array();
$preOrder = array();
$edp = "0";
$dpTotal = "0";
function getMasterID($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT p.id_master FROM transaksi t JOIN partner p ON p.id=t.id_partner WHERE t.id LIKE '$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['id_master'];
    } else {
        return 0;
    }
}

function getPhone($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT phone  FROM `transaksi` WHERE `id` LIKE '$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return $res[0]['phone'];
    } else {
        return 0;
    }
}

function checkSM($id, $phone, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT max_disc FROM `special_member` WHERE id_master='$id' AND phone='$phone' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        return true;
    } else {
        return false;
    }
}

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}
$customer_email = "";
$customer_name = "";
$voucherID = "";
$redeemableVoucherID = "";
$trxType = "dine-in";
$dpID = "0";
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
$serverName = "";
$serverID = "0";
$data = array();
$queue = "";
$rounding = "0";
$cashier_name = "";
$partnerID = $token->id_partner;
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    if (isset($_GET['transactionID']) && !empty($_GET['transactionID'])) {
        // $idlen = str_len($transactionID);
        
        $transactionID = $_GET['transactionID'];
        
        if(str_contains($transactionID, "-")){
            $transactionID = substr($transactionID,0,-2);
        }
        
        $query = "SELECT ost.transaction_id, ost.status_before, ost.status_after, ost.payment_method_before, ost.payment_method_after, e.nama as name, ost.created_at FROM order_status_trackings ost LEFT JOIN employees e ON e.id = ost.created_by LEFT JOIN transaksi t ON t.id = ost.transaction_id WHERE ost.deleted_at IS NULL AND e.deleted_at IS NULL AND ost.transaction_id LIKE '%$transactionID%' AND ost.created_by IS NOT NULL 
        UNION ALL 
            SELECT ost.transaction_id, ost.status_before, ost.status_after, ost.payment_method_before, ost.payment_method_after, t.customer_name as name, ost.created_at FROM order_status_trackings ost LEFT JOIN transaksi t ON t.id = ost.transaction_id AND t.source LIKE '%sf%' WHERE ost.created_by IS NULL AND ost.deleted_at IS NULL AND ost.transaction_id LIKE '%$transactionID%'";
        
        $mq = mysqli_query($db_conn, $query);
        $fetchData = mysqli_fetch_all($mq, MYSQLI_ASSOC);
        
        if(count($fetchData) > 0){
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    } else {
        $success = 0;
        $status = 203;
        $msg = "Missing required field";
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "data"=>$fetchData]);
