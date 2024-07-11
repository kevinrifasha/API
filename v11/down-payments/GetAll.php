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
$data = array();


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
    $query = "SELECT dp.id, dp.reservation_id, dp.customer_name, dp.customer_phone, dp.amount, dp.notes, IFNULL(pm.nama,'RESERVASI') AS pmName, pm.id AS pmID, e.nama AS employeeName FROM down_payments dp LEFT JOIN payment_method pm ON dp.payment_method_id = pm.id JOIN employees e ON e.id=dp.created_by WHERE dp.deleted_at IS NULL AND dp.used_at IS NULL AND dp.transaction_id IS NULL AND dp.partner_id='$token->id_partner' ORDER BY dp.id DESC";
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
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "downPayments"=>$data]);
?>