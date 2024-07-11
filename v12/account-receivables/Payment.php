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
    $data = json_decode(file_get_contents('php://input'));
    if(isset($data->id)&&isset($data->paymentMethod)){
        $id=$data->id;
        $validate = "SELECT ar.transaction_id, ar.group_id FROM account_receivables ar WHERE ar.id='$id'";
        $qV = mysqli_query($db_conn, $validate);
        if(mysqli_num_rows($qV) > 0) {
            $resV = mysqli_fetch_all($qV, MYSQLI_ASSOC);
            if($resV[0]['group_id']==0||$resV[0]['group_id']=="0"){
                $transactionID = $resV[0]['transaction_id'];
                $qParams="id='$transactionID'";
            }else{
                $groupID = $resV[0]['group_id'];
                $qParams="group_id='$groupID'";
            }
            $qPayment = "UPDATE transaksi SET paid_date=NOW(), status=2, tipe_bayar='$data->paymentMethod' WHERE ".$qParams." AND is_ar=1";
            $payment = mysqli_query($db_conn, $qPayment);
            if($payment){
                $updateAR = mysqli_query($db_conn, "UPDATE account_receivables SET paid_date=NOW(), received_by='$tokenDecoded->id', status=1, updated_at=NOW(), payment_method_id='$data->paymentMethod' WHERE id='$id'");
                if($updateAR) {
                    $success = 1;
                    $status = 200;
                    $msg = "Success";
                }else{
                    $success = 0;
                    $status = 204;
                    $msg = "Gagal bayar piutang. Mohon periksa data";
                }
            }
        }
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data tidak lengkap";
    }


}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "qPayment"=>$qPayment]);

?>