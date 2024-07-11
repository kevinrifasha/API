<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
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
    // POST DATA
    $data = json_decode(file_get_contents('php://input'));
    if(
        isset($data->id) && !empty($data->id)
        &&isset($data->name) && !empty($data->name)
        &&isset($data->level) && !empty($data->level)
    ){
        if($data->level == 2){
            $update = mysqli_query($db_conn,"UPDATE `payment_method` set `nama`='$data->name', `status`='$data->status', updated_at=NOW() WHERE id='$data->id'");  
        } else {
            $set = "";
            switch ($data->id) {
                case 1:
                    $set = "ovo_active='$data->status'";
                    break;
                case 2:
                    $set = "gopay_active='$data->status'";
                    break;
                case 3:
                    $set = "dana_active='$data->status'";
                    break;
                case 4:
                    $set = "linkaja_active='$data->status'";
                    break;
                case 5:
                    $set = "cash_active='$data->status'";
                    break;
                case 6:
                    $set = "qris_active='$data->status'";
                    break;
                case 7:
                    $set = "cc_active='$data->status'";
                    break;
                case 8:
                    $set = "debit_active='$data->status'";
                    break;                                                                                    
                case 9:
                    $set = "qris_active='$data->status'";
                    break;
                case 10:
                    $set = "shopeepay_active='$data->status'";
                    break;
                case 11:
                    $set = "open_bill='$data->status'";
                    break;
                default:
                    $set = "";
                    break;
            }
            $update = mysqli_query($db_conn,"UPDATE partner SET ".$set.", updated_at=NOW() WHERE id='$tokenDecoded->partnerID' ");  
        }
        if($update){
            $msg = "Berhasil ubah data";
            $success = 1;
            $status=200;
        }else{
            $msg = "Gagal ubah data";
            $success = 0;
            $status=204;
        }
               
    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;  
    }

}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
    http_response_code(200);
}else{
    http_response_code($status);
}     
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]); 
    
?>
     