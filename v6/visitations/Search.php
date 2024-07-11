<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
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
    $showAll = $_GET['showAll'];
    $teamID = $_GET['teamID'];
    $search = $_GET['search'];
    if($showAll=="1"){
        $extraQuery="";
    }else{
        $extraQuery = " AND created_by='$token->id'";
    }
    $sql = mysqli_query($db_conn, "SELECT v.id, v.merchant_name, v.business_type, v.package_type, v.address, v.pic_name, v.pic_phone, v.visit_no, v.customer_input, v.latitude, v.longitude, v.is_mock, v.image, u.name AS salesName, v.created_at, v.current_pos, v.other_pos FROM `sa_visitations` v JOIN sa_users u ON u.id=v.created_by WHERE v.deleted_at IS NULL".$extraQuery." AND (CONVERT(v.`id` USING utf8) LIKE '%$search%' OR CONVERT(v.`merchant_name` USING utf8) LIKE '%$search%' OR CONVERT(v.`business_type` USING utf8) LIKE '%$search%' OR CONVERT(v.`address` USING utf8) LIKE '%$search%' OR CONVERT(v.`pic_name` USING utf8) LIKE '%$search%' OR CONVERT(v.`pic_phone` USING utf8) LIKE '%$search%' OR CONVERT(v.`visit_no` USING utf8) LIKE '%$search%' OR CONVERT(v.`customer_input` USING utf8) LIKE '%$search%' OR CONVERT(v.`latitude` USING utf8) LIKE '%$search%' OR CONVERT(v.`longitude` USING utf8) LIKE '%$search%' OR CONVERT(v.`is_mock` USING utf8) LIKE '%$search%' OR CONVERT(v.`image` USING utf8) LIKE '%$search%' OR CONVERT(v.`package_type` USING utf8) LIKE '%$search%' OR CONVERT(v.`current_pos` USING utf8) LIKE '%$search%' OR CONVERT(v.`other_pos` USING utf8) LIKE '%$search%' OR CONVERT(v.`created_by` USING utf8) LIKE '%$search%' OR CONVERT(v.`created_at` USING utf8) LIKE '%$search%' OR CONVERT(v.`updated_at` USING utf8) LIKE '%$search%' OR CONVERT(v.`deleted_at` USING utf8) LIKE '%$search%') ORDER BY v.id DESC");
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data, "sql"=>$sql]);

?>
