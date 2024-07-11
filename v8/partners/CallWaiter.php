<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header(
    "Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"
);

//import require file
require "../../db_connection.php";
require_once "../auth/Token.php";
require_once "../../includes/DbOperation.php";

// date_default_timezone_set('Asia/Jakarta');

// POST DATA
$db = new DbOperation();

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
$today1 = date("Y-m-d");
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
    $data = json_decode(file_get_contents("php://input"));
    if (
        isset($data->partnerID) &&
        isset($data->message) &&
        isset($data->tableCode) &&
        !empty($data->partnerID) &&
        !empty($data->message) &&
        !empty($data->tableCode)
    ) {
        $partnerID = $data->partnerID;
        $content = $data->message;
        $tableCode = $data->tableCode;

        $title = "Panggilan dari meja" . $tableCode;

        $sent = mysqli_query(
            $db_conn,
            "INSERT INTO pending_notification (partner_id, dev_token, title, message) SELECT dt.id_partner, dt.tokens, '$title', '$content' FROM device_tokens dt JOIN employees e ON e.id=dt.employee_id AND e.order_notification=1 WHERE dt.id_partner ='$partnerID' AND dt.deleted_at IS NULL AND dt.employee_id IS NOT NULL AND dt.user_phone IS NULL"
        );
        // $message = mysqli_query($db_conn, "INSERT INTO partner_messages (partner_id,title,content,type) SELECT id, '$title', '$content', '
        //     1', FROM partner WHERE deleted_at IS NULL");
        if ($sent) {
            if ($content == "Minta Bill") {
                $msg = "Berhasil minta bill";
            } else {
                $msg = "Berhasil panggil waiter";
            }
            $msg .=". Mohon tunggu beberapa saat lagi";
            $success = 1;
            $status = 200;
        } else {
            $success = 0;
            $status = 204;
            $msg = "Gagal panggil waiter";
        }
    } else {
        $success = 1;
        $status = 400;
        $msg = "Missing Required Field!";
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg]);
?>
