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

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$data = [];
$pay_at_cashier = ["000162","000271"];
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $partner_id = $_GET["partnerID"];
 $i=0;
    $QUERY_LV1 = "SELECT pm.id, pm.nama, pm.level,
    CASE WHEN pm.id = 1 THEN p.ovo_active
    WHEN pm.id = 2 THEN p.gopay_active
    WHEN pm.id = 3 THEN p.dana_active
    WHEN pm.id = 4 THEN p.linkaja_active
    WHEN pm.id = 7 THEN p.cc_active
    WHEN pm.id = 8 THEN p.debit_active
    WHEN pm.id = 9 THEN p.qris_active
    WHEN pm.id = 10 THEN p.shopeepay_active
    WHEN pm.id = 11 THEN p.open_bill
    WHEN pm.id = 14 THEN p.qris_ur_active
    ELSE pm.status END as status
    FROM payment_method AS pm LEFT JOIN partner as p ON p.id = '$partner_id' WHERE pm.level=1 AND pm.deleted_at IS NULL AND pm.nama NOT IN ('Reserved', 'BELUM BAYAR')";

    $sql = mysqli_query($db_conn, "$QUERY_LV1 UNION ALL SELECT id,nama, level, status FROM payment_method WHERE partner_id='$partner_id' AND deleted_at IS NULL");
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
    
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "payment_methods"=>$data, "pay_at_cashier"=>$pay_at_cashier]);
?>