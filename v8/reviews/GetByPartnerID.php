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
  $average = 0;
  $sum = 0;
  $count = 0;
  $grouppedReviews = [];
  $id = $_GET['id'];
  $query = "SELECT r.id, r.review, r.rating, u.name,r.anonymous, r.created_at FROM reviews r JOIN transaksi t ON t.id = r.transaction_id JOIN users u ON t.phone = u.phone WHERE t.id_partner='$id' AND r.deleted_at IS NULL AND t.deleted_at IS NULL ORDER BY id DESC LIMIT 20";
  $rating = "SELECT SUM(r.rating) AS sum, COUNT(r.rating) AS count FROM reviews r JOIN transaksi t ON t.id = r.transaction_id WHERE t.id_partner='$id' AND r.deleted_at IS NULL AND t.deleted_at IS NULL";
  $ratingGroup = "SELECT r.rating, COUNT(r.rating) AS count FROM reviews r JOIN transaksi t ON t.id = r.transaction_id WHERE t.id_partner='$id' AND r.deleted_at IS NULL AND t.deleted_at IS NULL GROUP BY r.rating";

  $data = mysqli_query($db_conn, $query);
  $data2 = mysqli_query($db_conn, $rating);
  $data3 = mysqli_query($db_conn, $ratingGroup);
  if (mysqli_num_rows($data) > 0) {
    $res = mysqli_fetch_all($data, MYSQLI_ASSOC);
  } else {
    $res = [];
  }
  if (mysqli_num_rows($data2) > 0) {
    $res2 = mysqli_fetch_all($data2, MYSQLI_ASSOC);
    $sum = $res2[0]['sum'];
    $count = $res2[0]['count'];
  } else {
    $sum = 0;
    $count = 0;
  }
  if (mysqli_num_rows($data3) > 0) {
    $res3 = mysqli_fetch_all($data3, MYSQLI_ASSOC);
  } else {
    $res3 = [];
  }

  if ($count == 0) {
    $average = 0;
  } else {
    $average = $sum / $count;
  }
  $grouppedReviews = $res3 ?? [];

  $success = 1;
  $status = 200;
  $msg = "Success";
}

echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "reviews" => $res, "average" => $average, "count" => $count, "grouppedReviews" => $grouppedReviews]);
