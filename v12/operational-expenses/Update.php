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
        &&isset($data->amount) && !empty($data->amount)
        &&isset($data->categoryID) && !empty($data->categoryID)
    ){
                // $insert = mysqli_query($db_conn,"UPDATE `operational_expenses` SET `name`='$data->name', amount='$data->amount', created_by='$tokenDecoded->id', category_id='$data->categoryID', updated_at=NOW(), created_at='$data->createdAt' WHERE id='$data->id'");
                $insert = mysqli_query($db_conn,"UPDATE `operational_expenses` SET `name`='$data->name', amount='$data->amount', created_by='$data->createdBy', category_id='$data->categoryID', updated_at=NOW(), created_at='$data->createdAt' WHERE id='$data->id'");
                if($insert){
                    $msg = "Berhasil tambah data";
                    $success = 1;
                    $status=200;
                }else{
                    $msg = "Gagal tambah data";
                    $success = 0;
                    $status=204;
                }
    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;
    }

}
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]);

?>
