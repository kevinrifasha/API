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
        isset($data->master_program_id) && !empty($data->master_program_id)
    ){
        $prerequisite_menu = "";
        if(
            isset($data->prerequisite_menu) && !empty($data->prerequisite_menu)
        ){
            $prerequisite_menu = json_encode($data->prerequisite_menu);
        }

        $prerequisite_category = "";
        if(
            isset($data->prerequisite_category) && !empty($data->prerequisite_category)
        ){
            $cat = $data->prerequisite_category;
            $reduced_category = [];
            $data_ids = [];
                
            foreach($cat as $c){
                if($data_ids == []){
                    array_push($data_ids,$c->id);
                    array_push($reduced_category,$c);
                } 
                else {
                    $idChecker = in_array($c->id,$data_ids);
                    if($idChecker){
                    } else {
                        array_push($data_ids,$c->id);
                        array_push($reduced_category,$c);
                    }
                    
                }
            }
            
            $prerequisite_category = json_encode($reduced_category);
            
            $prerequisite_category = mysqli_real_escape_string($db_conn, $prerequisite_category);
        }
        $day = "";
        if(
            isset($data->day) && !empty($data->day)
            ){
            $day = json_encode($data->day);
        }
        $transaction_type = "";
        if(
            isset($data->transaction_type) && !empty($data->transaction_type)
            ){
            $transaction_type = json_encode($data->transaction_type);
        }
        $payment_method = "";
        if(
            isset($data->payment_method) && !empty($data->payment_method)
            ){
            $payment_method = json_encode($data->payment_method);
        }
        $maximum_discount = NULL;
        
        

        $menuQ = mysqli_query($db_conn, "SELECT id FROM `programs` WHERE partner_id='$data->partner_id' AND enabled='1' AND (('$data->valid_from' BETWEEN `valid_from` AND  `valid_until`) OR ('$data->valid_until' BETWEEN `valid_from` AND  `valid_until`)) AND deleted_at IS NULL");
            if (mysqli_num_rows($menuQ) > 0 && $data->enabled == 1) {
                $msg = "Hanya 1 program yang boleh berjalan dalam jangka waktu yang sama.";
                $success = 0;
                $status=400;
            }else{
                if(
                    isset($data->maximum_discount) && !empty($data->maximum_discount)
                    ){
                        $insert = mysqli_query($db_conn,"INSERT INTO `programs`(`master_program_id`, `partner_id`, `master_id`, `title`, `minimum_value`, `prerequisite_menu`, `prerequisite_category`, `payment_method`, `discount_type`, `transaction_type`, `start_hour`, `end_hour`, `day`, `discount_percentage`, `maximum_discount`, `enabled`, `valid_from`, `valid_until`, `created_at`) VALUES ('$data->master_program_id', '$data->partner_id', '$data->master_id', '$data->title', '$data->minimum_value', '$prerequisite_menu', '$prerequisite_category', '$payment_method', '$data->discount_type', '$transaction_type', '$data->start_hour', '$data->end_hour', '$day', '$data->discount_percentage', '$data->maximum_discount', '$data->enabled', '$data->valid_from', '$data->valid_until', NOW())");
                    }else{
                        $insert = mysqli_query($db_conn,"INSERT INTO `programs`(`master_program_id`, `partner_id`, `master_id`, `title`, `minimum_value`, `prerequisite_menu`, `prerequisite_category`, `payment_method`, `discount_type`, `transaction_type`, `start_hour`, `end_hour`, `day`, `discount_percentage`, `enabled`, `valid_from`, `valid_until`, `created_at`) VALUES ('$data->master_program_id', '$data->partner_id', '$data->master_id', '$data->title', '$data->minimum_value', '$prerequisite_menu', '$prerequisite_category', '$payment_method', '$data->discount_type', '$transaction_type', '$data->start_hour', '$data->end_hour', '$day', '$data->discount_percentage', '$data->enabled', '$data->valid_from', '$data->valid_until', NOW())");

                    }
                    if($insert){
                        $msg = "Berhasil tambah data";
                        $success = 1;
                        $status=200;
                    }else{
                        $msg = "Gagal tambah data";
                        $success = 0;
                        $status=204;
                    }
            }
    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;
    }

}
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]);
