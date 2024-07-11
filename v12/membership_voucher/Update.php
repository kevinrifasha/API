<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';

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
$token = '';
    
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$res = array();
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    if(
        isset($obj['code']) && !empty($obj['code'])
        && isset($obj['id']) && !empty($obj['id'])
        && isset($obj['title']) && !empty($obj['title'])
        && isset($obj['type_id']) 
        && isset($obj['title']) && !empty($obj['title'])
    ){

        $id = $obj['id'];
        $code = str_replace("'","''",$obj['code']);
        $title=str_replace("'","''",$obj['title']);
        $description= str_replace("'","''",$obj['description']);
        $type_id= $obj['type_id'];
        $is_percent= $obj['is_percent'];
        $discount= $obj['discount'];
        $enabled= $obj['enabled'];
        $valid_from= $obj['valid_from'];
        $valid_until= $obj['valid_until'];
        $total_usage= $obj['total_usage'];
        $img= $obj['img'];
        $point= $obj['point'];
        $prerequisite=json_encode($obj['prerequisite']);
            $sqlInsert = "UPDATE membership_voucher SET code='{$code}', title='{$title}', description='{$description}', type_id='{$type_id}', is_percent='{$is_percent}', discount='{$discount}', enabled='{$enabled}', valid_from='{$valid_from}', valid_until='{$valid_until}', total_usage='{$total_usage}', img='{$img}', created_at= NOW(), prerequisite='{$prerequisite}', point='{$point}'";
            if(isset($obj['master_id']) && !empty($obj['master_id'])){
                $master_id=$obj['master_id'];
                $sqlInsert = $sqlInsert.",master_id='{$master_id}' ";
            }
            if(isset($obj['partner_id']) && !empty($obj['partner_id'])){
                $partner_id=$obj['partner_id'];
                $sqlInsert = $sqlInsert.",partner_id='{$partner_id}' ";
            }
            $sqlInsert = $sqlInsert." WHERE id ='{$id}' ";
            
            $insert = mysqli_query($db_conn, $sqlInsert);
            if($insert){

                $success =1;
                $status =200;
                $msg = "Success";
            }else{
                // var_dump($sqlInsert);
                // var_dump($insert);
                $success =0;
                $status =204;
                $msg = "System Failed";
            }
    }else{
        $success =0;
        $status =204;
        $msg = "Missing Required Field";
    }
}

if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>
