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
    $id_partner=$token->id_partner;
    $type = $_GET['type'];
    $shiftID = $_GET['shiftID'];
    if($type=="items"){
        $getVoids = mysqli_query($db_conn, "SELECT tc.id, m.nama, tc.qty, tc.notes, e.nama AS eName, vb.nama AS vbName, dt.id_transaksi AS transactionID FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id=tc.detail_transaction_id JOIN menu m ON m.id=dt.id_menu LEFT JOIN employees e ON e.id=tc.created_by LEFT JOIN employees vb ON vb.id=tc.acc_by WHERE tc.shift_id='$shiftID' AND tc.deleted_at IS NULL AND tc.transaction_id IS NULL ");
        if (mysqli_num_rows($getVoids) > 0) {
            $res = mysqli_fetch_all($getVoids, MYSQLI_ASSOC);
            $success =1;
            $status =200;
            $msg = "Success";
        } else {
            $success =0;
            $status =204;
            $msg = "Data Not Found";
        }
    }else{
        $getVoids = mysqli_query($db_conn, "SELECT tc.id, tc.qty, tc.notes, e.nama AS eName, vb.nama AS vbName, t.id AS transactionID, pm.nama as paymentMethod FROM transaction_cancellation tc JOIN transaksi t ON t.id=tc.transaction_id JOIN payment_method pm ON pm.id=t.tipe_bayar JOIN employees e ON e.id=tc.created_by LEFT JOIN employees vb ON vb.id=tc.acc_by WHERE tc.shift_id='$shiftID' AND tc.deleted_at IS NULL AND tc.detail_transaction_id=0");
        if (mysqli_num_rows($getVoids) > 0) {
            $res = mysqli_fetch_all($getVoids, MYSQLI_ASSOC);
            $success =1;
            $status =200;
            $msg = "Success";
        } else {
            $success =0;
            $status =204;
            $msg = "Data Not Found";
        }
    }

}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "voids"=>$res]);
?>