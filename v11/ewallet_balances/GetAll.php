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
$tokenizer = new Token();
$token = '';
$res = array();
$result = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $partnerID = $token->id_partner;
    if(isset($_GET['partnerID'])){
        $partnerID=$_GET['partnerID'];
    }
    $from = $_GET['from'];
    $to = $_GET['to'];
    $totalBalance = "0";
    $all = 0;
    if(isset($_GET["all"])){
        $all = $_GET["all"];
    }
    
    if($all != 1){
        $query = "SELECT id, reference_id, partner_id, type, amount, balance, title, description, created_at FROM ewallet_balances WHERE DATE(created_at) BETWEEN '$from' AND '$to' AND partner_id='$partnerID' AND deleted_at IS NULL ORDER BY id DESC";
        $tb = "SELECT balance FROM ewallet_balances WHERE partner_id='$partnerID' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1";
    } else {
          $query = "SELECT eb.id, p.name, eb.reference_id, eb.partner_id, eb.type, eb.amount, eb.balance, eb.title, eb.description, eb.created_at FROM ewallet_balances eb LEFT JOIN partner p ON p.id = eb.partner_id WHERE eb.created_at BETWEEN '$from' AND '$to' AND p.id_master='$token->id_master' AND eb.deleted_at IS NULL ORDER BY id DESC";
          $tb = "SELECT SUM(databalance.balance) as balance FROM (select eb.* from ewallet_balances eb, (select balance, partner_id, max(id) as ebid from ewallet_balances group by partner_id) per_partner where eb.partner_id=per_partner.partner_id and eb.id=per_partner.ebid) databalance LEFT JOIN partner p ON p.id = databalance.partner_id WHERE p.id_master = '$token->id_master' GROUP BY p.id_master";
    }
    
    $q = mysqli_query($db_conn, $query);
    $qTB = mysqli_query($db_conn, $tb);
    
    if(mysqli_num_rows($qTB) > 0) {
        $resTB = mysqli_fetch_all($qTB, MYSQLI_ASSOC);
        $totalBalance = $resTB[0]['balance'];
    }

    if (mysqli_num_rows($q) > 0 || (double)$totalBalance>0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "balances"=>$res, "totalBalance"=>$totalBalance]);
?>