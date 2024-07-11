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
$data=array();
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
    $obj = json_decode(file_get_contents('php://input'));
    $now = date("Y-m-d H:i:s");
    if(
        isset($obj->name) && !empty($obj->name)
        &&isset($obj->id) && !empty($obj->id)
    ){
        // UPDATE `table_group_details` SET `deleted_at`=NOW() WHERE id='$obj-
        $update = mysqli_query($db_conn,"UPDATE `table_groups` SET `name`='$obj->name', `updated_at`=NOW() WHERE `id`='$obj->id'");
        if($update){
            $deleteExisting = mysqli_query($db_conn, "DELETE FROM table_group_details WHERE table_group_id='$obj->id'");
            if($deleteExisting){
                $query = "INSERT INTO `table_group_details`(`table_group_id`, `table_id`) VALUES";
                $i = 0;
                foreach($obj->tables as $value){
                    $query .= " ('$obj->id', '$value')";
                    if(count($obj->tables)-1==$i){
                        $query .= ";";
                    }else{
                        $query .= ",";
                    }
                    $i +=1;
                }
                $insert = mysqli_query($db_conn,$query);
            }else{
                $msg = "Berhasil menghapus data existing";
                $success = 1;
                $status=200;
            }
            $msg = "Berhasil mengubah data";
            $success = 1;
            $status=200;
        }else{
            $msg = "Gagal mengubah data";
            $success = 0;
            $status=200;
        }
    }else{
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 400;
    }

}
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]);

?>
