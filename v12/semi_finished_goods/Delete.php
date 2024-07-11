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
    $today = date("Y-m-d");
    // POST DATA
    $obj = json_decode(file_get_contents('php://input'));
    // $expiredDate = date("Y-m-d", $obj->exp_date);
    $id = $obj->id;
        $add1 = mysqli_query($db_conn,"UPDATE raw_material SET deleted_at=NOW() WHERE id='$id'");
        if($add1!=false){
            $deleteExisting = mysqli_query($db_conn,"DELETE FROM recipe WHERE sfg_id='$id'");
            if($deleteExisting){
                $success=1;
                $msg = "Berhasil hapus bahan baku";
                $status = 200;
            }else{
                $success=0;
                $msg = "Gagal hapus resep existing. Mohon coba lagi";
                $status = 400;
            }

        } else{
            $success=0;
            $msg = "Gagal hapus data. Mohon coba lagi";
            $status = 400;
        }



}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "id"=>$idInsert]);
?>
