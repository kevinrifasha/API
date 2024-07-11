<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header(
    "Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"
);

//import require file
require "../../db_connection.php";
require_once "../auth/Token.php";
require __DIR__ . "/../../vendor/autoload.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../..");
$dotenv->load();

//init var
$headers = [];
$rx_http = "/\AHTTP_/";
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, "", $key);
        $rx_matches = [];
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode("_", $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) {
                $rx_matches[$ak_key] = ucfirst($ak_val);
            }
            $arh_key = implode("-", $rx_matches);
        }
        $headers[$arh_key] = $val;
    }
}
$tokenizer = new Token();
$token = "";
$data = [];

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption("decrypt", $token));
if (isset($tokenValidate["success"]) && $tokenValidate["success"] == 0) {
    $status = $tokenValidate["status"];
    $msg = $tokenValidate["msg"];
    $success = 0;

    echo json_encode([
        "success" => $success,
        "status" => $status,
        "msg" => $msg,
    ]);
} else {
    $partnerID = $_GET["partnerID"];
    
    $query = "SELECT * FROM `programs` WHERE partner_id = '$partnerID' AND enabled = '1' AND deleted_at IS NULL AND CURRENT_DATE() BETWEEN valid_from AND valid_until";
    $sql = mysqli_query($db_conn, $query);
    
    if(mysqli_num_rows($sql) > 0) {
        $fetch = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $data = $fetch[0];
        $data['payment_method'] = json_decode($fetch[0]['payment_method']);
        $data['day'] = json_decode($fetch[0]['day']);
        $data['prerequisite_category'] = json_decode($fetch[0]['prerequisite_category']);
        $data['prerequisite_menu'] = json_decode($fetch[0]['prerequisite_menu']);
        $data['transaction_type'] = json_decode($fetch[0]['transaction_type']);
        
        $msg = "success";
        $status = 200;
        $success = 1;
    } else {
        
        $msg = "Data not found";
        $status = 203;
        $success = 0;
    }
    
    echo json_encode([
        "success" => $success,
        "status" => $status,
        "msg" => $msg,
        "data" => $data,
    ]);
}
