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
$masterID = $token->masterID;
if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
} else {
    $id = $_GET['id'];
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];

    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    $res = array();
    $status = 200;
    $success = 1;
    $msg = "Success";
    if (isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if ($all !== "1") {
        $masterID = null;
    }

    if($newDateFormat == 1){
      $query = "";
      if ($all == "1") {
          $query = "SELECT COUNT(`transaksi`.id) AS counted,
          CASE
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 11 AND 20) THEN '11-20'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 21 AND 30) THEN '21-30'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 31 AND 40) THEN '31-40'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 41 AND 50) THEN '41-50'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) >50) THEN '51+'
          ELSE 'Belum isi'
          END AS category
          FROM `transaksi` LEFT JOIN users ON users.phone=`transaksi`.phone JOIN partner ON partner.id = transaksi.id_partner WHERE `partner`.id_master ='$masterID' AND `transaksi`.deleted_at IS NULL AND `transaksi`.paid_date BETWEEN '$dateFrom' AND '$dateTo'
          AND `transaksi`.status<=2 AND `transaksi`.status>=1 GROUP BY category";
      } else {
          $query = "SELECT COUNT(`transaksi`.id) AS counted,
          CASE
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 11 AND 20) THEN '11-20'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 21 AND 30) THEN '21-30'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 31 AND 40) THEN '31-40'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 41 AND 50) THEN '41-50'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) >50) THEN '51+'
          ELSE 'Belum isi'
          END AS category
          FROM `transaksi` LEFT JOIN users ON users.phone=`transaksi`.phone WHERE `transaksi`.id_partner ='$id' AND `transaksi`.deleted_at IS NULL AND `transaksi`.paid_date BETWEEN '$dateFrom' AND '$dateTo'
          AND `transaksi`.status<=2 AND `transaksi`.status>=1 GROUP BY category";
      }
  
      $transaksi = mysqli_query($db_conn, $query);
      $values = array();
  
      while ($row = mysqli_fetch_assoc($transaksi)) {
          array_push($values, array("category" => $row['category'], "value" => (int) $row['counted']));
      }
      $res['ageTransactionCount'] = $values;
    } 
    else 
    {
      $query = "";
      if ($all == "1") {
          $query = "SELECT COUNT(`transaksi`.id) AS counted,
          CASE
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 11 AND 20) THEN '11-20'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 21 AND 30) THEN '21-30'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 31 AND 40) THEN '31-40'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 41 AND 50) THEN '41-50'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) >50) THEN '51+'
          ELSE 'Belum isi'
          END AS category
          FROM `transaksi` LEFT JOIN users ON users.phone=`transaksi`.phone JOIN partner ON partner.id = transaksi.id_partner WHERE `partner`.id_master ='$masterID' AND `transaksi`.deleted_at IS NULL AND DATE(`transaksi`.paid_date) BETWEEN '$dateFrom' AND '$dateTo'
          AND `transaksi`.status<=2 AND `transaksi`.status>=1 GROUP BY category";
      } else {
          $query = "SELECT COUNT(`transaksi`.id) AS counted,
          CASE
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 11 AND 20) THEN '11-20'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 21 AND 30) THEN '21-30'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 31 AND 40) THEN '31-40'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 41 AND 50) THEN '41-50'
          WHEN (YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) >50) THEN '51+'
          ELSE 'Belum isi'
          END AS category
          FROM `transaksi` LEFT JOIN users ON users.phone=`transaksi`.phone WHERE `transaksi`.id_partner ='$id' AND `transaksi`.deleted_at IS NULL AND DATE(`transaksi`.paid_date) BETWEEN '$dateFrom' AND '$dateTo'
          AND `transaksi`.status<=2 AND `transaksi`.status>=1 GROUP BY category";
      }
  
      $transaksi = mysqli_query($db_conn, $query);
      $values = array();
  
      while ($row = mysqli_fetch_assoc($transaksi)) {
          array_push($values, array("category" => $row['category'], "value" => (int) $row['counted']));
      }
      $res['ageTransactionCount'] = $values;
    }
}

$signupJson = json_encode(["success" => $success, "status" => $status, "msg" => $msg, "data" => $res]);

echo $signupJson;

?>