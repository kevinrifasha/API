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
// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
//     $status = $tokens['status'];
//     $msg = $tokens['msg']; 
//     $success = 0;
    
// }else{
    $id = $_GET["id"];
    $sql = mysqli_query($db_conn, "SELECT `id`, `name` FROM `partner_types` WHERE deleted_at IS NULL");
    $sqlValidator = mysqli_query($db_conn, "SELECT `id`, `category_id`, `subcategory_id` FROM `partner_subcategory_assignments` WHERE deleted_at IS NULL AND partner_id='$id'");
    $sql2 = mysqli_query($db_conn, "SELECT partner_subcategories.id, category_id as partner_type_id, pt.name as partner_type_name, partner_subcategories.name FROM partner_subcategories LEFT JOIN partner_types pt ON pt.id=partner_subcategories.category_id WHERE pt.deleted_at IS NULL AND  partner_subcategories.deleted_at IS NULL ORDER BY id ASC");
    
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $data2 = mysqli_fetch_all($sql2, MYSQLI_ASSOC);
        $data3 = [];
        if(mysqli_num_rows($sqlValidator) > 0){
            $data3 = mysqli_fetch_all($sqlValidator, MYSQLI_ASSOC);
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
    
// }
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "types"=>$data, "partner_subcategory"=>$data3, "subcategory"=>$data2, "test"=>$id]);  

?>