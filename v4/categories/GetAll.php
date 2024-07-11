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
    $is_program = 0;
    if(isset($_GET['is_program']) && $_GET['is_program'] != 0){
        $is_program = 1;
    }
    
    $promoQuery = "";
    if($is_program == 1){
        $promoQuery = " AND categories.name !='Promo'";
    }
    
    if($_GET['partner_type'] == 7){
        $page = $_GET['page'];
        $load = $_GET['load'];
        $finish = $load * $page;
        $start = $finish - $load;    
        $q = mysqli_query($db_conn, "SELECT categories.`id`, categories.`name`, categories.`sequence`, `categories`.`department_id`, departments.name AS department_name FROM `categories` LEFT JOIN departments ON departments.id=categories.department_id JOIN partner ON partner.id_master = categories.id_master WHERE partner.id='$token->id_partner' AND categories.deleted_at IS NULL" . $promoQuery . " ORDER BY categories.sequence ASC LIMIT $start,$load");
    } else {
        $q = mysqli_query($db_conn, "SELECT categories.`id`, categories.`name`, categories.`sequence`, `categories`.`department_id`, departments.name AS department_name FROM `categories` LEFT JOIN departments ON departments.id=categories.department_id JOIN partner ON partner.id_master = categories.id_master WHERE partner.id='$token->id_partner' AND categories.deleted_at IS NULL" . $promoQuery . " ORDER BY categories.sequence ASC");
    }
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "categories"=>$res]);
?>