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
    $name = mysqli_real_escape_string($db_conn, $obj->name);
    $partnerID = $obj->partnerID;
    $search = strtolower($name);
    $id = $obj->id;
    // $validator = mysqli_query($db_conn,"SELECT id FROM raw_material WHERE LOWER(name)='$search' AND id_partner='$tokenDecoded->partnerID' AND deleted_at IS NULL AND id!='$id'");
    $validator = mysqli_query($db_conn,"SELECT id FROM raw_material WHERE LOWER(name)='$search' AND id_partner='$partnerID' AND deleted_at IS NULL AND id!='$id'");
    if(mysqli_num_rows($validator)>0){
        $success=0;
        $msg = "Nama bahan tidak boleh sama. Mohon gunakan nama lain";
        $status = 400;
    }else{
        if(isset($obj->categoryID)){
            $categoryID = $obj->categoryID;
        }else{
            $categoryID = 0;
        }
        $add1 = mysqli_query($db_conn,"UPDATE raw_material SET name = '$name', reminder_allert = '$obj->reminderStock', unit_price='$obj->unitPrice', id_metric_price = '$obj->metricID', level=1, category_id='$categoryID' WHERE id='$id'");
        if($add1!=false){
            $deleteExisting = mysqli_query($db_conn,"DELETE FROM recipe WHERE sfg_id='$id'");
            if($deleteExisting){
                $rm= $obj->rawMaterials;
                foreach ($rm as $value) {
                    $id_raw = $value->rawID;
                    $qty = $value->qty;
                    $id_metric = $value->metricID;
                    $query="INSERT INTO `recipe`(`sfg_id`, `id_raw`, `qty`, `id_metric`, `id_variant`) VALUES ('$id', '$id_raw', '$qty', '$id_metric', '0')";
                    $insert = mysqli_query($db_conn,$query);
                }
                $success=1;
                $msg = "Berhasil Update bahan baku";
                $status = 200;
            }else{
                $success=0;
                $msg = "Gagal update resep existing. Mohon coba lagi";
                $status = 400;
            }

        } else{
            $success=0;
            $msg = "Gagal ubah data. Mohon coba lagi";
            $status = 400;
        }
    }



}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "id"=>$idInsert]);
?>
