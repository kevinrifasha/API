<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

date_default_timezone_set('Asia/Jakarta');
$now = date("Y-m-d");
$today = date('l');
$lastOrder = $today . "_last_order";
$lastOrder = strtolower($lastOrder);
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
$iq = 0;
$fc_parent_id = "";
$is_foodcourt = "";
$id = "";
$partner_type = "";
$shift = "0";

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
// if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
//     $status = $tokenValidate['status'];
//     $msg = $tokenValidate['msg'];
//     $success = 0;
// }else{
$tableCode = $_GET['tableCode'];
$partnerID = $_GET['partnerID'];
if (
    isset($partnerID)
    && isset($tableCode)
    && !empty($partnerID)
    && !empty($tableCode)
) {
    $isDineIn = "0";
    // $q = mysqli_query($db_conn, "SELECT is_queue FROM `meja` WHERE idpartner='$partnerID' AND idmeja='$tableCode' AND deleted_at IS NULL");
    $q = mysqli_query($db_conn, "SELECT partner.fc_parent_id, partner.partner_type, CASE WHEN partner.fc_parent_id!=0 THEN parent.is_foodcourt ELSE partner.is_foodcourt END is_foodcourt , meja.is_queue, partner.is_dine_in, partner_opening_hours.$lastOrder FROM `meja` JOIN partner ON partner.id=meja.idpartner LEFT JOIN partner parent ON partner.fc_parent_id=parent.id JOIN partner_opening_hours ON partner_opening_hours.partner_id=partner.id  WHERE idpartner='$partnerID' AND idmeja='$tableCode' AND meja.deleted_at IS NULL AND partner.deleted_at IS NULL");
    $query = "SELECT id FROM shift WHERE partner_id = '$partnerID' AND end IS NULL";
    $getShift = mysqli_query($db_conn, $query);
    if (mysqli_num_rows($getShift) > 0) {
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $isDineIn = $res[0]['is_dine_in'];
            $iq = $res[0]['is_queue'];
            $is_foodcourt = $res[0]['is_foodcourt'];
            $fc_parent_id = $res[0]['fc_parent_id'];
            $partner_type = $res[0]['partner_type'];
            $currentLastOrder = $res[0][$lastOrder];
            if ($isDineIn == "0" || $isDineIn == 0) {
                $success = 0;
                $status = 204;
                $msg = "Merchant ini tidak support self order. Mohon langsung pesan ke kasir";
            } else if (date('H:i') >= $currentLastOrder) {
                $success = 0;
                $status = 204;
                $msg = "Sudah melewati batas last order. Coba lagi besok";
            } else {
                $shift = "1";
                $success = 1;
                $status = 200;
                $msg = "Success";
            }
        } else {
            $success = 0;
            $status = 204;
            $msg = "Data Meja Tidak Ditemukan";
        }
    } else {
        $shift = "0";
        $success = 0;
        $status = 204;
        $msg = "Resto belum memulai shift. Mohon hubungi kasir";
    }
} else {
    $success = 0;
    $status = 204;
    $msg = "Missing Required Field";
}
// }
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "is_queue" => $iq, "is_foodcourt" => $is_foodcourt, "fc_parent_id" => $fc_parent_id, "partner_type" => $partner_type, "isDineIn" => $isDineIn, "shift" => $shift]);
