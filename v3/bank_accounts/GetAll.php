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
    
    // if($all == 0){
    //     $query = "SELECT pba.id, pba.account_no, pba.account_name, ab.name, ab.minimum_transaction, pba.partner_id FROM partner_bank_accounts pba JOIN available_banks ab ON pba.bank_id=ab.id WHERE pba.partner_id IN ($stringWhere) AND pba.deleted_at IS NULL";
    // }else{
        $query = "SELECT pba.id, pba.account_no, pba.account_name, ab.name, ab.minimum_transaction FROM partner_bank_accounts pba JOIN available_banks ab ON pba.bank_id=ab.id LEFT JOIN partner p ON p.id=pba.partner_id WHERE p.id_master='$tokenDecoded->masterID' AND pba.deleted_at IS NULL";
    // }
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
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "accounts"=>$data]);
?>