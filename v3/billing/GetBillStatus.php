<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';

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

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$partnerId = $token->partnerID;
$value = array();
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $q = mysqli_query($db_conn, "
        SELECT
            id,
            grand_total,
            status,
            qr_string,
            expired_at,
            CASE WHEN expired_at < NOW() THEN 1 ELSE 0 END AS is_expire
        FROM
            subscription_transactions
        WHERE deleted_at IS NULL
        AND partner_id = '$partnerId'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    if(mysqli_num_rows($q)>0){
        $fetchBill = mysqli_fetch_all($q, MYSQLI_ASSOC)[0];
        $billId = $fetchBill['id'];
        
        if ($fetchBill['status'] == 'PENDING'){
            if ($fetchBill['is_expire'] == '1'){
                mysqli_query($db_conn, "
                    UPDATE subscription_transactions
                    SET status='EXPIRED'
                    WHERE id='$billId'
                ");
                $fetchBill['status'] = 'EXPIRED';
            }
        }
        
        $success=1;
        $status=200;
        $msg="Success";
    }else{
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "data"=>$fetchBill]);
?>