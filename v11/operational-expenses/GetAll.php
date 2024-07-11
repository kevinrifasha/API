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
$data = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;
$all = "0";

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    $id = $token->id_partner;
    if (isset($_GET['partnerID'])) {
        $id = $_GET['partnerID'];
    }
    if (isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    $sql = "";
    if($all == "1"){
        $sql = mysqli_query($db_conn, "SELECT e.id, e.name, e.amount, e.created_at, em.nama AS employeeName, ec.name AS categoryName, ec.id AS categoryID FROM operational_expenses e JOIN operational_expense_categories ec ON e.category_id = ec.id JOIN employees em ON e.created_by = em.id WHERE ec.master_id = '$idMaster' AND e.created_by != 0 AND e.deleted_at IS NULL ORDER BY e.id DESC");
    } else {
        $sql = mysqli_query($db_conn, "SELECT e.id, e.name, e.amount, e.created_at, em.nama AS employeeName, ec.name AS categoryName, ec.id AS categoryID FROM operational_expenses e JOIN operational_expense_categories ec ON e.category_id = ec.id JOIN employees em ON e.created_by = em.id WHERE ec.master_id = '$idMaster' AND em.id_partner='$id' AND e.deleted_at IS NULL ORDER BY e.id DESC");
    }
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
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "expenses"=>$data]);  

?>