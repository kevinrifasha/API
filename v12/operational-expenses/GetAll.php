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
$partnerID = $tokenDecoded->partnerID;
if(isset($_GET['partnerID'])){
    $partnerID = $_GET['partnerID'];
}

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    // $sql = mysqli_query($db_conn, "SELECT e.id, e.name, e.amount, e.created_at, em.nama AS employeeName, ec.name AS categoryName, ec.id AS categoryID FROM operational_expenses e JOIN operational_expense_categories ec ON e.category_id = ec.id JOIN employees em ON e.created_by = em.id WHERE ec.master_id = '$tokenDecoded->masterID' AND e.deleted_at IS NULL ORDER BY e.id DESC");
    $sql = mysqli_query($db_conn, "SELECT e.id, e.name, e.amount, e.created_at, em.nama AS employeeName, ec.name AS categoryName, ec.id AS categoryID FROM operational_expenses e JOIN operational_expense_categories ec ON e.category_id = ec.id JOIN employees em ON e.created_by = em.id WHERE ec.partner_id = '$partnerID' AND e.deleted_at IS NULL ORDER BY e.id DESC");
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