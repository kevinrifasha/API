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
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt', $token));
$value = array();
$success = 0;
$msg = 'Failed';
if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
} else {
    // POST DATA
    $data = json_decode(file_get_contents('php://input'));
    if (
        isset($data->name) && !empty($data->name)
    ) {

        $menus = "";
        if (
            isset($data->menus) && !empty($data->menus)
        ) {
            $menus = json_encode($data->menus);
            $menus = mysqli_real_escape_string($db_conn, $menus);
        }

        $categories = "";
        if (
            isset($data->categories) && !empty($data->categories)
        ) {
            $categories = json_encode($data->categories);
            $categories = mysqli_real_escape_string($db_conn, $categories);
        }

        $prerequisite_menu = null;
        if (
            isset($data->prerequisite_menu) && !empty($data->prerequisite_menu)
        ) {
            $prerequisite_menu = json_encode($data->prerequisite_menu);
            $prerequisite_menu = mysqli_real_escape_string($db_conn, $prerequisite_menu);
            $pMenu = "prerequisite_menu='$prerequisite_menu'";
        } else {
            $prerequisite_menu = null;
            $pMenu = "prerequisite_menu=null";
        }

        $prerequisite_category = null;
        if (
            isset($data->prerequisite_category) && !empty($data->prerequisite_category)
        ) {

            // $prerequisite_category = json_encode($data->prerequisite_category);
            // $prerequisite_category = mysqli_real_escape_string($db_conn, $prerequisite_category);
            
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
            
            $pCategory = "prerequisite_category='$prerequisite_category'";
        } else {
            $pCategory = "prerequisite_category=null";
        }
        $day = "";
        if (
            isset($data->day) && !empty($data->day)
        ) {
            $day = json_encode($data->day);
            $pDay = "day='$day'";
        } else {
            $pDay = "day=null";
        }
        $discount_type = 0;
        if (
            isset($data->discount_type) && !empty($data->discount_type)
        ) {
            $discount_type = (int) $data->discount_type;
        } else {
            $discount_type = 0;
        }
        $transaction_type = null;
        if (
            isset($data->transaction_type) && !empty($data->transaction_type)
        ) {
            $transaction_type = json_encode($data->transaction_type);
            $pType = "transaction_type='$transaction_type'";
        } else {
            $pType = "transaction_type=null";
        }
        $maximum_discount = 0;

        $is_multiple = 0;
        if (isset($data->is_multiple) && !empty($data->is_multiple)) {
            $is_multiple = (int) $data->is_multiple;
        }

        $payment_method = "";
        if (
            isset($data->payment_method) && !empty($data->payment_method)
        ) {
            $payment_method = json_encode($data->payment_method);
            $pPayment = "payment_method='$payment_method'";
        } else {
            $pPayment = "payment_method=null";
        }

        $title = $data->title;
        $title = mysqli_real_escape_string($db_conn, $title);

        $menuQ = mysqli_query($db_conn, "SELECT id FROM `programs` WHERE partner_id='$data->partner_id' AND enabled='1' AND (('$data->valid_from' BETWEEN `valid_from` AND  `valid_until`) OR ('$data->valid_until' BETWEEN `valid_from` AND  `valid_until`)) AND deleted_at IS NULL AND `id`!='$data->id'");
        if (mysqli_num_rows($menuQ) > 0 && $data->enabled == 1) {
            $msg = "Hanya 1 program yang boleh berjalan dalam jangka waktu yang sama.";
            $success = 0;
            $status = 400;
        } else {
            if (
                isset($data->maximum_discount) && !empty($data->maximum_discount)
            ) {
                $maxDiscount = 0;
                if (isset($data->maximum_discount) && !empty($data->maximum_discount)) {
                    $maxDiscount = (int)$data->maximum_discount;
                }
                $maxDisc = "maximum_discount = '$maxDiscount'";
            } else {
                $maxDisc = "maximum_discount = '0'";
            }
            
            $minimum_value = 0;	
            if (isset($data->minimum_value) && !empty($data->minimum_value)) {	
                $minimum_value = (int)$data->minimum_value;	
            }	
            $qty_redeem = 0;	
            if (isset($data->qty_redeem) && !empty($data->qty_redeem)) {	
                $qty_redeem = (int)$data->qty_redeem;	
            }	
            $discount_percentage = 0;	
            if (isset($data->discount_percentage) && !empty($data->discount_percentage)) {	
                $discount_percentage = (int)$data->discount_percentage;	
            }	
            $enabled = 0;	
            if (isset($data->enabled) && !empty($data->enabled)) {	
                $enabled = (int)$data->enabled;	
            }

            $sql = "UPDATE
                    `programs`
                  SET
                    `master_program_id` = '$data->master_program_id',
                    `title` = '$title',
                    `minimum_value` = '$minimum_value',
                    `menus` = '$menus',
                    `categories` = '$categories',
                    `enabled` = '$enabled',
                    `is_multiple` = '$is_multiple',
                    `valid_from` = '$data->valid_from',
                    `valid_until` = '$data->valid_until',
                    `updated_at` = NOW(),
                    `qty_redeem` = '$qty_redeem',
                    `discount_type` = '$discount_type',
                    " . $pType . ",
                    " . $pPayment . ",
                    " . $pMenu . ",
                    " . $pCategory . ",
                    `discount_percentage` = '$data->discount_percentage',
                    " . $maxDisc . ",
                    `start_hour` = '$data->start_hour',
                    `end_hour` = '$data->end_hour',
                    " . $pDay . "
                  WHERE
                    `id` = '$data->id'
                  ";
            $insert = mysqli_query($db_conn, $sql);
            if ($insert) {
                $msg = "Berhasil mengubah data";
                $success = 1;
                $status = 200;
            } else {
                $msg = "Gagal mengubah data";
                $success = 0;
                $status = 204;
            }
        }
    } else {
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;
    }
}
echo json_encode(["status" => $status, "success" => $success, "msg" => $msg, "q" => $reduced_category]);
