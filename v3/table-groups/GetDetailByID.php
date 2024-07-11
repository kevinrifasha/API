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
    $id = $_GET['id'];
    $q = mysqli_query($db_conn, "SELECT `table_groups`.`id`, `table_groups`.`partner_id`, `table_groups`.`name`, `table_group_details`.`table_id`, `table_group_details`.`id` AS `table_detail_group_id`, `meja`.`idmeja` FROM `table_groups` LEFT JOIN `table_group_details` ON `table_groups`.`id`=`table_group_details`.`table_group_id` LEFT JOIN `meja` ON `table_group_details`.`table_id`=`meja`.`id` WHERE `table_groups`.`id`='$id' AND `table_groups`.`deleted_at` IS NULL AND `table_group_details`.`deleted_at` IS NULL ORDER BY `table_groups`.`id`");

    if (mysqli_num_rows($q) > 0) {
        $data = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $compareID = 0;
        $i=0;
        $j=0;
        foreach ($data as $value) {
            if($compareID==$value['id']){
                $j+=1;
            }else{
                if($compareID!=0){
                    $i+=1;
                    $j=0;
                }
            }
            $res[$i]['id']=$value['id'];
            $res[$i]['partner_id']=$value['partner_id'];
            $res[$i]['name']=$value['name'];
            $res[$i]['details'][$j]['table_id']=$value['table_id'];
            $res[$i]['details'][$j]['id']=$value['table_detail_group_id'];
            $res[$i]['details'][$j]['idmeja']=$value['idmeja'];
            $compareID = $value['id'];
        }

        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =200;
        $msg = "Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "tables_groups"=>$res]);
?>