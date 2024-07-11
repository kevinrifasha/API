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
    foreach($_SERVER as $key => $val) {
      if( preg_match($rx_http, $key) ) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
          foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
          $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
      }
    }
$token = '';
    
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$cf = new CalculateFunction();

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->masterID;
$res = array();
$value = array();
$success=0;
$msg = 'Failed';
$all = "0";

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $id = $_GET['id'];
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];

    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    $res=array();
    $status = 200;
    $success=1;
    $msg="Success";
    $query = "";
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }

    if($newDateFormat == 1){
      if($all !== "1") {
        $idMaster = null;
        $query="SELECT COUNT(`transaksi`.id) AS counted, payment_method.nama AS payment_method FROM `transaksi` JOIN payment_method ON payment_method.id=`transaksi`.tipe_bayar WHERE `transaksi`.id_partner='$id' AND `transaksi`.deleted_at IS NULL AND `transaksi`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND `transaksi`.status IN (2,1) GROUP BY `transaksi`.tipe_bayar";
      } else {
          $query = "SELECT COUNT(`transaksi`.id) AS counted, payment_method.nama AS payment_method FROM `transaksi` JOIN payment_method ON payment_method.id=`transaksi`.tipe_bayar JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND `transaksi`.deleted_at IS NULL AND `transaksi`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND `transaksi`.status IN (2,1) GROUP BY `transaksi`.tipe_bayar";
      }

      $transaksi = mysqli_query($db_conn, $query);
      $values = array();

      while ($row = mysqli_fetch_assoc($transaksi)) {
          array_push($values, array("label" => $row['payment_method'], "value" => $row['counted']));
      }
      $res['paymentMethodPercentage']=$values;
    } 
    else 
    {
      if($all !== "1") {
        $idMaster = null;
        $query="SELECT COUNT(`transaksi`.id) AS counted, payment_method.nama AS payment_method FROM `transaksi` JOIN payment_method ON payment_method.id=`transaksi`.tipe_bayar WHERE `transaksi`.id_partner='$id' AND `transaksi`.deleted_at IS NULL AND DATE(`transaksi`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `transaksi`.status IN (2,1) GROUP BY `transaksi`.tipe_bayar";
      } else {
          $query = "SELECT COUNT(`transaksi`.id) AS counted, payment_method.nama AS payment_method FROM `transaksi` JOIN payment_method ON payment_method.id=`transaksi`.tipe_bayar JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND `transaksi`.deleted_at IS NULL AND DATE(`transaksi`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `transaksi`.status IN (2,1) GROUP BY `transaksi`.tipe_bayar";
      }

      $transaksi = mysqli_query($db_conn, $query);
      $values = array();

      while ($row = mysqli_fetch_assoc($transaksi)) {
          array_push($values, array("label" => $row['payment_method'], "value" => $row['counted']));
      }
      $res['paymentMethodPercentage']=$values;
    }
}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$res]);  

echo $signupJson;
