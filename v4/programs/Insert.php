<?php
error_reporting(E_ALL);
ini_set(‘display_errors’, 1);
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
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
            $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
    }
}
$tokenizer = new Token();
$token = '';
$res = array();
$test = array();
//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}
$idInsert = "";
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    // POST DATA
    $data = json_decode(file_get_contents('php://input'));
    if (
        isset($data->master_program_id) && !empty($data->master_program_id)
    ) {
        if (empty($data->minimum_value)) {
            $data->minimum_value = 0;
        }
        // $menuQ = mysqli_query($db_conn, "SELECT id FROM `programs` WHERE partner_id='$data->partner_id' AND enabled='1' AND (('$data->valid_from' BETWEEN `valid_from` AND  `valid_until`) OR ('$data->valid_until' BETWEEN `valid_from` AND  `valid_until`)) AND `master_program_id` = '$data->master_program_id' AND deleted_at IS NULL");
        // 1 program
        $menuQ = mysqli_query($db_conn, "SELECT id FROM `programs` WHERE partner_id='$data->partner_id' AND enabled='1' AND (('$data->valid_from' BETWEEN `valid_from` AND  `valid_until`) OR ('$data->valid_until' BETWEEN `valid_from` AND  `valid_until`)) AND deleted_at IS NULL");
        if (mysqli_num_rows($menuQ) > 0 && $data->enabled == 1) {
            $msg = "Hanya 1 program yang boleh berjalan dalam jangka waktu yang sama.";
            $success = 0;
            $status = 400;
        } else {
            $day = "";
            if (
                isset($data->day) && !empty($data->day)
            ) {
                $day = json_encode($data->day);
            }
            $payment_method = "";
            if (
                isset($data->payment_method) && !empty($data->payment_method)
            ) {
                $payment_method = json_encode($data->payment_method);
            } else {
                $payment_method = null;
            }

            if (empty($data->qty_redeem)) {
                $data->qty_redeem = 0;
            }
            $title = mysqli_real_escape_string($db_conn, $data->title);
            if ($data->master_program_id == 1) {

                $menus = json_encode($data->menus);
                $query = "INSERT INTO `programs`(`master_program_id`, `master_id`, `partner_id`, `title`, `minimum_value`, `menus`, `enabled`, `valid_from`, `valid_until`, `created_at`, `qty_redeem`, `start_hour`, `end_hour`, `day`, `payment_method`) VALUES ('$data->master_program_id', '$data->master_id', '$data->partner_id', '$title', '$data->minimum_value', '$menus', '$data->enabled', '$data->valid_from', '$data->valid_until', NOW(), '$data->qty_redeem', '$data->start_hour', '$data->end_hour', '$day', '$payment_method')";
                $insert = mysqli_query($db_conn, $query);
                if ($insert) {
                    $msg = "Berhasil tambah data";
                    $success = 1;
                    $status = 200;
                } else {
                    $msg = "Gagal tambah data";
                    $success = 0;
                    $status = 204;
                }
            } else if ($data->master_program_id == 3) {

                $menus = "";
                if (isset($data->menus)) {
                    $menus = json_encode($data->menus);
                    $menus = mysqli_real_escape_string($db_conn, $menus);
                }

                $categories = "";
                if (isset($data->categories)) {
                    $categories = json_encode($data->categories);
                    $categories = mysqli_real_escape_string($db_conn, $categories);
                }

                $is_multiple = 0;
                if (isset($data->is_multiple)) {
                    $is_multiple = (int)$data->is_multiple;
                }

                $prerequisite_menu = json_encode($data->prerequisite_menu);
                $prerequisite_menu = mysqli_real_escape_string($db_conn, $prerequisite_menu);
                if ($prerequisite_menu == "null") {
                    $prerequisite_menu = "";
                }


                $prerequisite_category = "";
                if (isset($prerequisite_category)) {
                    $prerequisite_category = json_encode($data->prerequisite_category);
                    $prerequisite_category = mysqli_real_escape_string($db_conn, $prerequisite_category);
                }

                if ($menus == "[]") {
                    $menus = "";
                }

                if ($categories == "[]") {
                    $categories = "";
                }

                if ($prerequisite_menu == "[]") {
                    $prerequisite_menu = "";
                }

                if ($prerequisite_category == "[]") {
                    $prerequisite_category = "";
                }

                $qInsert = "INSERT INTO `programs`(`master_program_id`, `master_id`, `partner_id`, `title`, `minimum_value`, `menus`, `categories`,`enabled`, `valid_from`, `valid_until`, `created_at`, `qty_redeem`, `start_hour`, `end_hour`, `day`, `payment_method`,`is_multiple`,`prerequisite_menu`,`prerequisite_category`) VALUES ('$data->master_program_id', '$data->master_id', '$data->partner_id', '$title', '$data->minimum_value', '$menus','$categories', '$data->enabled', '$data->valid_from', '$data->valid_until', NOW(), '$data->qty_redeem', '$data->start_hour', '$data->end_hour', '$day', '$payment_method','$is_multiple','$prerequisite_menu','$prerequisite_category')";
                $insert = mysqli_query($db_conn, $qInsert);

                if ($insert) {
                    $msg = "Berhasil tambah data";
                    $success = 1;
                    $status = 200;
                } else {
                    $msg = "Gagal tambah data";
                    $success = 0;
                    $status = 204;
                }
            } else {
                $prerequisite_menu = "";
                if (
                    isset($data->prerequisite_menu) && !empty($data->prerequisite_menu)
                ) {
                    $prerequisite_menu = json_encode($data->prerequisite_menu);
                    $prerequisite_menu = mysqli_real_escape_string($db_conn, $prerequisite_menu);
                } else {
                    $prerequisite_menu = null;
                }

                $prerequisite_category = "";
                if (
                    isset($data->prerequisite_category) && !empty($data->prerequisite_category)
                ) {
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
                } else {
                    $prerequisite_category = null;
                }

                $transaction_type = "";
                if (
                    isset($data->transaction_type) && !empty($data->transaction_type)
                ) {
                    $transaction_type = json_encode($data->transaction_type);
                } else {
                    $transaction_type = null;
                }

                $maximum_discount = NULL;
                if (
                    isset($data->maximum_discount) && !empty($data->maximum_discount)
                ) {
                    $query = "INSERT INTO `programs`(`master_program_id`, `partner_id`, `master_id`, `title`, `minimum_value`, `prerequisite_menu`, `prerequisite_category`, `payment_method`, `discount_type`, `transaction_type`, `start_hour`, `end_hour`, `day`, `discount_percentage`, `maximum_discount`, `enabled`, `valid_from`, `valid_until`, `created_at`) VALUES ('$data->master_program_id', '$data->partner_id', '$data->master_id', '$title', '$data->minimum_value', '$prerequisite_menu', '$prerequisite_category', '$payment_method', '$data->discount_type', '$transaction_type', '$data->start_hour', '$data->end_hour', '$day', '$data->discount_percentage', '$data->maximum_discount', '$data->enabled', '$data->valid_from', '$data->valid_until', NOW())";
                } else {
                    $query = "INSERT INTO `programs`(`master_program_id`, `partner_id`, `master_id`, `title`, `minimum_value`, `prerequisite_menu`, `prerequisite_category`, `payment_method`, `discount_type`, `transaction_type`, `start_hour`, `end_hour`, `day`, `discount_percentage`, `enabled`, `valid_from`, `valid_until`, `created_at`) VALUES ('$data->master_program_id', '$data->partner_id', '$data->master_id', '$title', '$data->minimum_value', '$prerequisite_menu', '$prerequisite_category', '$payment_method', '$data->discount_type', '$transaction_type', '$data->start_hour', '$data->end_hour', '$day', '$data->discount_percentage', '$data->enabled', '$data->valid_from', '$data->valid_until', NOW())";
                }
                $insert = mysqli_query($db_conn, $query);
                if ($insert) {
                    $msg = "Berhasil tambah data";
                    $success = 1;
                    $status = 200;
                } else {
                    $msg = "Gagal tambah data";
                    $success = 0;
                    $status = 200;
                }
            }
        }
    } else {
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 200;
    }
}
echo json_encode(["status" => $status, "success" => $success, "msg" => $msg, "id" => $idInsert]);
