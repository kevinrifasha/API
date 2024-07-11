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
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    if(
        isset($obj['code']) && !empty($obj['code'])
        && isset($obj['title']) && !empty($obj['title'])
        && isset($obj['type_id']) 
        && isset($obj['title']) && !empty($obj['title'])
    ){

        $code = $obj['code'];
        $title= $obj['title'];
        $description= $obj['description'];
        $type_id= $obj['type_id'];
        $is_percent= $obj['is_percent'];
        $discount= $obj['discount'];
        $enabled= $obj['enabled'];
        $valid_from= $obj['valid_from'];
        $valid_until= $obj['valid_until'];
        $total_usage= $obj['total_usage'];
        $img= $obj['img'];
        $prerequisite=$obj['prerequisite'];
        $q = mysqli_query($db_conn, "SELECT id FROM `redeemable_voucher` WHERE code='$code' AND deleted_at IS NULL");
        
        if (mysqli_num_rows($q) > 0) {
            $success =0;
            $status =204;
            $msg = "Kode voucher sudah terpakai";
        } else {
            $sqlInsert = "INSERT redeemable_voucher SET code='{$code}', title='{$title}', description='{$description}', type_id='{$type_id}', is_percent='{$is_percent}', discount='{$discount}', enabled='{$enabled}', valid_from='{$valid_from}', valid_until='{$valid_until}', total_usage='{$total_usage}', img='{$img}', created_at= NOW(), prerequisite='{$prerequisite}'";
            if(isset($obj['master_id']) && !empty($obj['master_id'])){
                $master_id=$obj['master_id'];
                $sqlInsert = $sqlInsert.",master_id='{$master_id}' ";
            }
            if(isset($obj['partner_id']) && !empty($obj['partner_id'])){
                $partner_id=$obj['partner_id'];
                $sqlInsert = $sqlInsert.",partner_id='{$partner_id}' ";
            }
            
            $insert = mysqli_query($db_conn, $sqlInsert);
            if($insert){

                $success =1;
                $status =200;
                $msg = "Success";
            }else{
                $success =0;
                $status =204;
                $msg = "System Failed";
            }
        }
    }else{
        $success =0;
        $status =204;
        $msg = "Missing Required Field";
    }
}


echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>
     