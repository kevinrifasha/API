<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');


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
  $from = $_GET['from'];
  $to = $_GET['to'];
  if (!empty($from) && !empty($to)) {
    $dateParam = "AND DATE(p.created_at) BETWEEN '$from' AND '$to'";
  } else {
    $dateParam = "AND MONTH(p.created_at)= '$month'
      AND YEAR(p.created_at)= '$year'";
  }
  $year = substr($from, 0, 4);
  $month = substr($from, 5, 2);
  $date = $year . "-" . $month . "-" . "01";
  $extraQuery = " deleted_at IS NULL AND DATE(validity_period)='$date' AND period='Monthly' ";
  $getVisitTarget = mysqli_query($db_conn, "SELECT IFNULL(value,0) AS value FROM sa_targets WHERE" . $extraQuery . " AND attributes='visits_global'");
  $vt = mysqli_fetch_all($getVisitTarget, MYSQLI_ASSOC);
  $getECTarget = mysqli_query($db_conn, "SELECT IFNULL(value,0) AS value FROM sa_targets WHERE" . $extraQuery . " AND attributes='register_global'");
  $ect = mysqli_fetch_all($getECTarget, MYSQLI_ASSOC);
  $getRTarget = mysqli_query($db_conn, "SELECT IFNULL(value,0) AS value FROM sa_targets WHERE" . $extraQuery . " AND attributes='free_global'");
  $rt = mysqli_fetch_all($getRTarget, MYSQLI_ASSOC);
  $getPTarget = mysqli_query($db_conn, "SELECT IFNULL(value,0) AS value FROM sa_targets WHERE" . $extraQuery . " AND attributes='paid_global'");
  $rp = mysqli_fetch_all($getPTarget, MYSQLI_ASSOC);
  $sqlVisits = "SELECT COUNT(id) AS count FROM sa_visitations WHERE DATE(created_at) BETWEEN '$from' AND '$to'";

  $visits = mysqli_query($db_conn, $sqlVisits);
  $sqlEffectiveCalls = "SELECT COUNT(id) AS count FROM partner p WHERE DATE(created_at) BETWEEN '$from' AND '$to' AND referral REGEXP '^[0-9]+$'";
  $sqlRegistered = mysqli_query($db_conn, "SELECT
    p.id,
    CASE WHEN (DATEDIFF(
      DATE(
        MAX(t.paid_date)
      ),
      DATE(p.created_at)
    ) > 3) THEN '1' ELSE '0' END AS counted
  FROM
    partner p
    LEFT JOIN transaksi t ON t.id_partner = p.id
  WHERE
    p.deleted_at IS NULL
    AND DATE(p.created_at) BETWEEN '$from' AND '$to'
    AND p.referral REGEXP '^[0-9]+$'
  GROUP BY
    p.id

  ");
  $i = 0;
  $sqlPaid = mysqli_query($db_conn, "SELECT COUNT(p.id) AS count FROM partner p WHERE p.deleted_at IS NULL AND DATE(p.created_at) BETWEEN '$from' AND '$to' AND p.referral REGEXP '^[0-9]+$' AND p.primary_subscription_id!=0");
  $ec = mysqli_query($db_conn, $sqlEffectiveCalls);
  $resVisits = mysqli_fetch_all($visits, MYSQLI_ASSOC);
  $resEC = mysqli_fetch_all($ec, MYSQLI_ASSOC);
  $visitCount = $resVisits[0]['count'];
  $resRegistered = mysqli_fetch_all($sqlRegistered, MYSQLI_ASSOC);
  $resPaid = mysqli_fetch_all($sqlPaid, MYSQLI_ASSOC);
  $ecCount = $resEC[0]['count'] ?? 0;
  $res['visits']['target'] = $vt[0]['value'] ?? "0";
  $res['visits']['count'] = $visitCount;
  $res['visits']['percentage'] = ceil($visitCount / ($vt[0]['value'] ?? 1) * 100);
  $res['ec']['target'] = $ect[0]['value'] ?? "0";
  $res['ec']['count'] = $ecCount ?? 0;
  $res['ec']['percentage'] = ceil($ecCount / ($ect[0]['value'] ?? 1) * 100);
  $res['registered']['target'] = $rt[0]['value'] ?? "0";
  foreach ($resRegistered as $x) {
    if ((int)$x['counted'] == 1) {
      $i++;
    }
  }
  $res['registered']['count'] = $i;
  $res['registered']['percentage'] = ceil($i / ($rt[0]['value'] ?? 1) * 100);
  $res['paid']['target'] = $rp[0]['value'] ?? "0";
  $res['paid']['count'] = $resPaid[0]['count'] ?? 0;
  $res['paid']['percentage'] = ceil(($resPaid[0]['count'] ?? 0) / ($rp[0]['value'] ?? 1) * 100);
  if (mysqli_num_rows($visits) > 0) {
    $success = 1;
    $status = 200;
    $msg = "Success";
  } else {
    $success = 0;
    $status = 204;
    $msg = "Data Not Found";
  }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "res" => $res]);
