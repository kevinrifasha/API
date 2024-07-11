<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once '../../includes/CalculateFunctions.php';
require_once("../connection.php");
require '../../db_connection.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

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

foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$cf = new CalculateFunction();

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$res = array();
$value = array();
$success = 0;
$msg = 'Failed';
$idMaster = $token->masterID;
if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
} else {
    $id = $_GET['id'];
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    $res = array();
    $status = 200;
    $success = 1;
    $msg = "Success";
    if (isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    if ($all !== "1") {
        $idMaster = null;
    }
    if ($all == "1") {
        $query = "SELECT COUNT(`transaksi`.id) AS counted, CASE WHEN users.Gender IS NOT NULL AND users.Gender!='' THEN users.Gender ELSE 'Belum isi' END gender FROM `transaksi` LEFT JOIN users ON users.phone=`transaksi`.phone JOIN partner ON partner.id = transaksi.id_partner WHERE DATE(`transaksi`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `transaksi`.deleted_at IS NULL AND `partner`.id_master='$idMaster' AND `transaksi`.status<=2 and `transaksi`.status>=1 GROUP BY users.Gender ORDER BY users.Gender DESC";
    } else {
        $query = "SELECT COUNT(`transaksi`.id) AS counted, CASE WHEN users.Gender IS NOT NULL AND users.Gender!='' THEN users.Gender ELSE 'Belum isi' END gender FROM `transaksi` LEFT JOIN users ON users.phone=`transaksi`.phone WHERE DATE(`transaksi`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `transaksi`.deleted_at IS NULL AND `transaksi`.id_partner='$id' AND `transaksi`.status<=2 and `transaksi`.status>=1 GROUP BY users.Gender ORDER BY users.Gender DESC";
    }

    $transaksi = mysqli_query($db_conn, $query);
    $values = array();

    $total = 0;
    while ($row = mysqli_fetch_assoc($transaksi)) {
        $total += (int)$row['counted'];
        array_push($values, array("label" => $row['gender'], "count" => $row['counted']));
    }
    $j = 0;
    foreach ($values as $value) {
        $values[$j]['value'] = (round((float)($value['count'] / $total) * 100));
        $j += 1;
    }
    $res['genderTransactionPercentage'] = $values;
}

$signupJson = json_encode(["success" => $success, "status" => $status, "msg" => $msg, "data" => $res]);
echo $signupJson;
