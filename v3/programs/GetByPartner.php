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
    $partner_id = $_GET['partner_id'];
    $sql = mysqli_query($db_conn, "SELECT `programs`.`id`, `master_program_id`, `master_programs`.`name` AS `master_program_name`,`master_id`,`is_multiple`, `partner_id`, `title`, `minimum_value`, `menus`,`categories`, `enabled`, `valid_from`, `valid_until`, `discount_percentage`, `prerequisite_menu`, `prerequisite_category`, `payment_method`, `discount_type`, `transaction_type`, `start_hour`, `end_hour`, `day`,`maximum_discount`,
    voucher_types.name AS voucher_types_name
    FROM `programs`  JOIN `master_programs` ON `master_programs`.`id`=`programs`.`master_program_id` LEFT JOIN voucher_types ON voucher_types.id=programs.discount_type WHERE `partner_id`='$partner_id' AND programs.`deleted_at` IS NULL ORDER BY programs.enabled DESC, programs.id DESC");
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $i=0;
        foreach($data as $value){
            $menus = json_decode($value['menus']);
            $categories = json_decode($value['categories']);
            $j = 0;
            foreach($menus as $value1){
                $mid = $value1->id;
                $sqlM = mysqli_query($db_conn, "SELECT nama as name FROM menu WHERE id='$mid'");
                if(mysqli_num_rows($sqlM) > 0) {
                    $dataM = mysqli_fetch_all($sqlM, MYSQLI_ASSOC);
                    $menus[$j]->name=$dataM[0]['name'];
                }
                $j+=1;
            }
            
            if($menus == null){
                $data[$i]['arr_menu']=array();
            } else {
                $data[$i]['arr_menu']=$menus;
            }
            
            $k = 0;
            if(is_array($categories)){
                foreach($categories as $value1){
                    $cid = $value1->id;
                    $sqlM = mysqli_query($db_conn, "SELECT name FROM categories WHERE id='$cid'");
                    if(mysqli_num_rows($sqlM) > 0) {
                        $dataM = mysqli_fetch_all($sqlM, MYSQLI_ASSOC);
                        $categories[$k]->name=$dataM[0]['name'];
                    }
                    $k+=1;
                }
            }

            if($categories == null){
                $data[$i]['arr_category']=array();
            } else {
                $data[$i]['arr_category']=$categories;
            }
            
            $j = 0;
            $prerequisite_menu = array();
            if(isset($value['prerequisite_menu']) && !empty($value['prerequisite_menu'])){
                $prerequisite_menu = json_decode($value['prerequisite_menu']);
            }
            $data[$i]['arr_prerequisite_menu']=$prerequisite_menu;

            $j = 0;
            $prerequisite_category = array();
            if(isset($value['prerequisite_category']) && !empty($value['prerequisite_category'])){
                $prerequisite_category = json_decode($value['prerequisite_category']);
            }
            $data[$i]['arr_prerequisite_category']=$prerequisite_category;

            $j = 0;
            $day = array();
            if(isset($value['day']) && !empty($value['day'])){
                $day = json_decode($value['day']);
            }
            $data[$i]['arr_day']=$day;

            $i+=1;
        }
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
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "programs"=>$data]);

?>