<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';

$headers = apache_request_headers();
$token = '';
    
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
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
    ELSE pm.status END as status
    FROM payment_method AS pm LEFT JOIN partner as p ON p.id = '$tokenDecoded->partnerID' WHERE pm.level=1 AND pm.deleted_at IS NULL AND pm.nama NOT IN ('Reserved', 'BELUM BAYAR')";

    $sql = mysqli_query($db_conn, "$QUERY_LV1 UNION ALL SELECT id,nama, level, status FROM payment_method WHERE partner_id='$tokenDecoded->partnerID' AND deleted_at IS NULL");
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
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "payment_methods"=>$data]);  

?>