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
$res = array();
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
$idMaster = $token->masterID;
$value = array();
$success = 0;
$msg = 'Failed';
$all = "0";

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
  $query = "";
  $query2 = "";
  $development = 0;
  $otherPM = [];
  $test = "";

  $newDateFormat = 0;

  if (strlen($dateTo) !== 10 && strlen($dateFrom) !== 10) {
    $dateTo = str_replace("%20", " ", $dateTo);
    $dateFrom = str_replace("%20", " ", $dateFrom);
    $newDateFormat = 1;
  }

  if (isset($_GET['all'])) {
    $all = $_GET['all'];
  }
  if ($all !== "1") {
    $idMaster = null;
  }

  if ($newDateFormat == 1) {
    if ($all == "1") {
      // p.id_master = '$idMaster';
      $query = "SELECT DISTINCT COUNT(transaksi.id) as Total FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.status IN (1,2)";
      $query2 = "SELECT DISTINCT COUNT(t.id) as count, t.tipe_bayar, pm.nama as payment_method FROM transaksi t JOIN partner p ON p.id = t.id_partner LEFT JOIN payment_method pm ON pm.id = t.tipe_bayar WHERE t.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' AND t.deleted_at IS NULL AND t.status IN (1,2) GROUP BY t.tipe_bayar";
    } else {
      $query = "SELECT DISTINCT COUNT(id) as Total FROM transaksi  WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)";
      $query2 = "SELECT DiSTINCT COUNT(t.id) as count, t.tipe_bayar, pm.nama as payment_method FROM transaksi t LEFT JOIN payment_method pm ON pm.id = t.tipe_bayar WHERE t.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.deleted_at IS NULL AND t.status IN (1,2) GROUP BY t.tipe_bayar";
    }
  } else {
    if ($all == "1") {
      $query = "SELECT DISTINCT COUNT(transaksi.id) as Total FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.status IN (1,2)";
      $query2 = "SELECT DISTINCT COUNT(t.id) as count, t.tipe_bayar, pm.nama as payment_method FROM transaksi t JOIN partner p ON p.id = t.id_partner LEFT JOIN payment_method pm ON pm.id = t.tipe_bayar WHERE DATE(t.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' AND t.deleted_at IS NULL AND t.status IN (1,2) GROUP BY t.tipe_bayar";
    } else {
      $query = "SELECT DISTINCT COUNT(id) as Total FROM transaksi  WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)";
      $query2 = "SELECT DISTINCT COUNT(t.id) as count, t.tipe_bayar, pm.nama as payment_method FROM transaksi t LEFT JOIN payment_method pm ON pm.id = t.tipe_bayar WHERE DATE(t.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.deleted_at IS NULL AND t.status IN (1,2) GROUP BY t.tipe_bayar";
    }
  }

  $total = mysqli_query($db_conn, $query);
  $totalData = mysqli_fetch_all($total, MYSQLI_ASSOC);
  $transaksi = mysqli_query($db_conn, $query2);
  $values = array();


  $otherPM = mysqli_fetch_all($transaksi, MYSQLI_ASSOC);

  foreach ($otherPM as $val) {
    $percentage = round(($val["count"] / $totalData[0]["Total"]) * 100);
    $color = '#' . substr(str_shuffle('ABCDEF0123456789'), 0, 6);
    array_push($values, array("label" => $val["payment_method"], "value" => $percentage, "value1" => $val["count"], "color" => $color));
  }

  foreach ($values as $key => $row) {
    $value[$key]  = $row['value'];
    $label[$key] = $row['label'];
  }

  $value  = array_column($values, 'value');
  $label = array_column($values, 'label');

  array_multisort($value, SORT_DESC, $label, SORT_ASC, $values);
  $res['paymentMethodPercentage'] = $values;
}
$signupJson = json_encode(["success" => $success, "status" => $status, "msg" => $msg, "data" => $res, "test" => $query2]);

echo $signupJson;
