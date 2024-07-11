<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

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
    $id = $_GET['id'];
    $query = "SELECT t.subtotal, t.surcharge, t.tax, t.grand_total, t.status, t.payment_method, sp.name, sp.price FROM subscription_transactions t JOIN subscription_transaction_details std ON std.transaction_id=t.id JOIN subscription_packages sp ON sp.id=std.item_id WHERE t.id='$id'";
    $q = mysqli_query($db_conn, $query);
    if (mysqli_num_rows($q)) {
        $data = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $success =1;
        $status =200;
        $msg = "Berhasil ambil data";
    } else {
        $success =0;
        $status =204;
        $msg = "Data tidak ditemukan";
    }

}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "data"=>$data]);
?>