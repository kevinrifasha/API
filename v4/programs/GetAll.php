<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
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
$data = array();

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
    
    $partner_id = $_GET['partner_id'];
    $sql = mysqli_query($db_conn, "SELECT `programs`.`id`, `master_program_id`, `master_programs`.`name` AS `master_program_name`,`master_id`, `partner_id`, `title`, `minimum_value`, `menus`,`categories`,`is_multiple`, `enabled`, `valid_from`, `valid_until`, `discount_percentage`, `prerequisite_menu`, `prerequisite_category`, `payment_method`, `discount_type`, `transaction_type`, `start_hour`, `end_hour`, `day`,`maximum_discount`, 
    voucher_types.name AS voucher_types_name, `programs`.`qty_redeem`,`programs`.`is_sf_only`,`programs`.`and_or`
    FROM `programs`  JOIN `master_programs` ON `master_programs`.`id`=`programs`.`master_program_id` LEFT JOIN voucher_types ON voucher_types.id=programs.discount_type WHERE `partner_id`='$partner_id' AND programs.`deleted_at` IS NULL");
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $i=0;
        foreach($data as $value){
            $menus = json_decode($value['menus']);
            $categories = json_decode($value['categories']);
            $j = 0;
            
            if(is_array($menus)){
                foreach($menus as $value1){
                    $mid = $value1->id;
                    $sqlM = mysqli_query($db_conn, "SELECT nama as name FROM menu WHERE id='$mid'");
                    if(mysqli_num_rows($sqlM) > 0) {
                        $dataM = mysqli_fetch_all($sqlM, MYSQLI_ASSOC);
                        $menus[$j]->name=$dataM[0]['name'];
                    }
                    $j+=1;
                }
                $data[$i]['arr_menu']=$menus;
            } else {
                $data[$i]['arr_menu']=[];
            }
            
            
            $k = 0;
            if(is_array($categories)){
                foreach($categories as $value2){
                    $cid = $value2->id;
                    $sqlC = mysqli_query($db_conn, "SELECT name FROM categories WHERE id='$cid'");
                    if(mysqli_num_rows($sqlC) > 0) {
                        $dataC = mysqli_fetch_all($sqlC, MYSQLI_ASSOC);
                        $categories[$k]->name=$dataC[0]['name'];
                    }
                    $k+=1;
                }
                $data[$i]['arr_category']=$categories;
            } else {
                $data[$i]['arr_category']=[];
            }
            
            $prerequisite_menu = array();
            if(isset($value['prerequisite_menu']) && !empty($value['prerequisite_menu'])){
                $prerequisite_menu = json_decode($value['prerequisite_menu']);
            }
            $data[$i]['arr_prerequisite_menu']=$prerequisite_menu;
            
            $prerequisite_category = array();
            if(isset($value['prerequisite_category']) && !empty($value['prerequisite_category'])){
                $prerequisite_category = json_decode($value['prerequisite_category']);
            }
            $data[$i]['arr_prerequisite_category']=$prerequisite_category;
            
            $day = array();
            if(isset($value['day']) && !empty($value['day'])){
                $day = json_decode($value['day']);
            }
            $data[$i]['arr_day']=$day;

            $payment_method = array();
            if(isset($value['payment_method']) && !empty($value['payment_method'])){
                $payment_method = json_decode($value['payment_method']);
            }
            $data[$i]['arr_payment_method']=$payment_method;

            $transaction_type = array();
            if(isset($value['transaction_type']) && !empty($value['transaction_type'])){
                $transaction_type = json_decode($value['transaction_type']);
            }
            $data[$i]['arr_transaction_type']=$transaction_type;

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
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "programs"=>$data]);
?>