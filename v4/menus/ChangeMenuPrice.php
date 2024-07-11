<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header(
    "Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"
);
require "../../db_connection.php";
require_once "../auth/Token.php";

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
$res = [];

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
} else {
    // POST DATA
    $obj = json_decode(file_get_contents("php://input"));
    $now = date("Y-m-d H:i:s");
    if (
        isset($obj->category_id) &&
        !empty($obj->category_id) &&
        isset($obj->percentage) &&
        !empty($obj->percentage)
    ) {
        $implodeCat = implode(",",$obj->category_id);
        
        if($obj->calculation_type == "1"){
            $newMultiplier = (100 + (int) $obj->percentage) /100;
        } else {
            $newMultiplier = (100 - (int) $obj->percentage) /100;
        }
        
        $updatePrice = mysqli_query($db_conn, "UPDATE menu SET harga = harga * $newMultiplier WHERE id_category IN(" . $implodeCat . ")");
        
        if($updatePrice){
            $success = 1;
            $msg = "Berhasil Update Harga";
            $status = 200;
        } else {
            $success = 0;
            $msg = "Gagal Update Harga";
            $status = 204;
        }
        
    } else {
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 400;
    }
}
echo json_encode(["status" => $status, "success" => $success, "msg" => $msg]);

?>
