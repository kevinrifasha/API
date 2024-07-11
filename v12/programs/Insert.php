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

        $day = "";
        if(
            isset($data->day) && !empty($data->day)
            ){
            $day = json_encode($data->day);
        }

        $payment_method = "";
        if(
            isset($data->payment_method) && !empty($data->payment_method)
            ){
            $payment_method = json_encode($data->payment_method);
        }

        $menus = json_encode($data->menus);

        $prerequisite_menu = null;
        if (
            isset($data->prerequisite_menu) && !empty($data->prerequisite_menu)
        ) {
            $prerequisite_menu = json_encode($data->prerequisite_menu);
            $prerequisite_menu = mysqli_real_escape_string($db_con,$prerequisite_menu);
        }

        if(
            $data->start_hour=="" || $data->end_hour==""
        ){
            $data->start_hour="00:00";
            $data->end_hour="00:00";
        }

            $menuQ = mysqli_query($db_conn, "SELECT id FROM `programs` WHERE partner_id='$data->partner_id' AND enabled='1' AND (('$data->valid_from' BETWEEN `valid_from` AND  `valid_until`) OR ('$data->valid_until' BETWEEN `valid_from` AND  `valid_until`)) AND deleted_at IS NULL AND `master_program_id`=$data->master_program_id");
            if (mysqli_num_rows($menuQ) > 0 && $data->enabled==1) {
                $msg = "Hanya 1 program yang boleh berjalan dalam jangka waktu yang sama.";
                $success = 0;
                $status=400;
            } else{
                $insert = "";
                if($data->master_program_id == "3" ||$data->master_program_id == 3){
                    $qty_redeem = 0;
                    $menus = "";
                    if(isset($data->menus)){
                        $menus = json_encode($data->menus);
                        $menus = mysqli_real_escape_string($db_conn,$menus);
                    }

                    $categories = "";
                    if(isset($data->categories)){
                        $categories = json_encode($data->categories);
                        $categories = mysqli_real_escape_string($db_conn,$categories);
                    }

                    $is_multiple = 0;
                    if(isset($data->is_multiple)){
                        $is_multiple = (int)$data->is_multiple;
                    }

                    $prerequisite_menu = json_encode($data->prerequisite_menu);
                    $prerequisite_menu = mysqli_real_escape_string($db_conn,$prerequisite_menu);
                    if($prerequisite_menu == "null"){
                        $prerequisite_menu = "";
                    }


                    $prerequisite_category = "";
                    if(isset($prerequisite_category)){
                        $prerequisite_category = json_encode($data->prerequisite_category);
                        $prerequisite_category = mysqli_real_escape_string($db_conn,$prerequisite_category);
                    }

                    if($menus == "[]"){
                        $menus = "";
                    }

                    if($categories == "[]"){
                        $categories = "";
                    }

                    if($prerequisite_menu == "[]"){
                        $prerequisite_menu = "";
                    }

                    if($prerequisite_category == "[]"){
                        $prerequisite_category = "";
                    }

                    $insert = mysqli_query($db_conn,"INSERT INTO `programs`(`master_program_id`, `master_id`, `partner_id`, `title`, `minimum_value`, `menus`, `categories`,`enabled`, `valid_from`, `valid_until`, `created_at`, `qty_redeem`, `start_hour`, `end_hour`, `day`, `payment_method`,`is_multiple`,`prerequisite_menu`,`prerequisite_category`) VALUES ('$data->master_program_id', '$data->master_id', '$data->partner_id', '$data->title', '$data->minimum_value', '$menus','$categories', '$data->enabled', '$data->valid_from', '$data->valid_until', NOW(), '$qty_redeem', '$data->start_hour', '$data->end_hour', '$day', '$payment_method','$is_multiple'                  ,'$prerequisite_menu','$prerequisite_category'             )");
                } else {
                    $insert = mysqli_query($db_conn,"INSERT INTO `programs`(`master_program_id`, `master_id`, `partner_id`, `title`, `minimum_value`, `menus`, `enabled`, `valid_from`, `valid_until`, `created_at`, `start_hour`, `end_hour`, `day`, `payment_method`) VALUES ('$data->master_program_id', '$data->master_id', '$data->partner_id', '$data->title', '$data->minimum_value', '$menus', '$data->enabled', '$data->valid_from', '$data->valid_until', NOW(), '$data->start_hour', '$data->end_hour', '$day', '$payment_method')");
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

     
