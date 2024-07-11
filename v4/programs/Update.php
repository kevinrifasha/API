<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');

//init var
$headers = array();
    $rx_http = '/\AHTTP_/';
    foreach($_SERVER as $key => $val) {
      if( preg_match($rx_http, $key) ) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
          foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
          $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
      }
    }
$tokenizer = new Token();
$token = '';
$res = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];

    $msg = $tokenValidate['msg'];
    $success = 0;

}else{
    // POST DATA
    $data = json_decode(file_get_contents('php://input'));
    if(
        isset($data->title) && !empty($data->title)
        && isset($data->id) && !empty($data->id)
    ){
        $menus = "";
        if(
            isset($data->menus) && !empty($data->menus)
            ){
            $menus = json_encode($data->menus);
            $menus = mysqli_real_escape_string($db_conn, $menus);
        }
        $categories = "";
        if(
            isset($data->categories) && !empty($data->categories)
            ){
            $categories = json_encode($data->categories);
            $categories = mysqli_real_escape_string($db_conn, $categories);
        }
        $prerequisite_menu = "";
        if(
            isset($data->prerequisite_menu) && !empty($data->prerequisite_menu)
        ){
            $prerequisite_menu = json_encode($data->prerequisite_menu);
            $prerequisite_menu = mysqli_real_escape_string($db_conn, $prerequisite_menu);
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

        $title = "";
        if(
            isset($data->title) && !empty($data->title)
            ){
            $title = mysqli_real_escape_string($db_conn, $data->title);
        }

        $is_multiple = "0";
        if(
            isset($data->is_multiple) && !empty($data->is_multiple)
            ){
            $is_multiple = $data->is_multiple;
        }
        $discount_type = "0";
        if(
            isset($data->discount_type) && !empty($data->discount_type)
            ){
            $discount_type = $data->discount_type;
        }
        $transaction_type = "";
        if(
            isset($data->transaction_type) && !empty($data->transaction_type)
            ){
            $transaction_type = json_encode($data->transaction_type);
        }
        $maximum_discount = NULL;

        $payment_method = "";
        if(
            isset($data->payment_method) && !empty($data->payment_method)
            ){
            $payment_method = json_encode($data->payment_method);
        }

        // $menuQ = mysqli_query($db_conn, "SELECT id FROM `programs` WHERE partner_id='$data->partner_id' AND enabled='1' AND (('$data->valid_from' BETWEEN `valid_from` AND  `valid_until`) OR ('$data->valid_until' BETWEEN `valid_from` AND  `valid_until`)) AND deleted_at IS NULL AND `master_program_id` = '$data->master_program_id' AND `id`!='$data->id'");

        //default below
        $menuQ = mysqli_query($db_conn, "SELECT id FROM `programs` WHERE partner_id='$data->partner_id' AND enabled='1' AND (('$data->valid_from' BETWEEN `valid_from` AND  `valid_until`) OR ('$data->valid_until' BETWEEN `valid_from` AND  `valid_until`)) AND deleted_at IS NULL AND `id`!='$data->id'");
        if (mysqli_num_rows($menuQ) > 0 && $data->enabled == 1) {
            $msg = "Hanya 1 program yang boleh berjalan dalam jangka waktu yang sama.";
            $success = 0;
            $status=203;
        } else {
            if(
                isset($data->maximum_discount) && !empty($data->maximum_discount)
            ){

                    $insert = mysqli_query($db_conn,"UPDATE `programs` SET `master_program_id`='$data->master_program_id', `title`='$title',`minimum_value`='$data->minimum_value',`menus`='$menus',`categories`='$categories',`is_multiple`='$is_multiple',`enabled`='$data->enabled',`valid_from`='$data->valid_from',`valid_until`='$data->valid_until',`updated_at`=NOW(), `qty_redeem`='$data->qty_redeem', `discount_type`='$discount_type', 
                    `transaction_type`='$transaction_type' ,
                    `payment_method`='$payment_method' ,
                    `prerequisite_menu`='$prerequisite_menu' ,
                    `prerequisite_category`='$prerequisite_category' ,
                    `discount_percentage`='$data->discount_percentage' ,
                    `maximum_discount`='$data->maximum_discount' ,
                    `start_hour`='$data->start_hour' ,
                    `end_hour`='$data->end_hour' ,
                    `day`='$day' 
                    WHERE `id`='$data->id'");
            }else{
                    $insert = mysqli_query($db_conn,"UPDATE `programs` SET `master_program_id`='$data->master_program_id', `title`='$title',`categories`='$categories',`is_multiple`='$is_multiple',`minimum_value`='$data->minimum_value',`menus`='$menus',`enabled`='$data->enabled',`valid_from`='$data->valid_from',`valid_until`='$data->valid_until',`updated_at`=NOW(), `qty_redeem`='$data->qty_redeem', `discount_type`='$data->discount_type', 
                    `transaction_type`='$transaction_type' ,
                    `payment_method`='$payment_method' ,
                    `prerequisite_menu`='$prerequisite_menu' ,
                    `prerequisite_category`='$prerequisite_category' ,
                    `discount_percentage`='$data->discount_percentage' ,
                    `maximum_discount`=0 ,
                    `start_hour`='$data->start_hour' ,
                    `end_hour`='$data->end_hour' ,
                    `day`='$day' 
                    WHERE `id`='$data->id'");
            }
            
            if($insert){
                $msg = "Berhasil mengubah data";
                $success = 1;
                $status=200;
            }else{
                $msg = "Gagal mengubah data";
                $success = 0;
                $status=200;
            }
        }
    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 200;  
    }
}
http_response_code($status);    
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]); 

