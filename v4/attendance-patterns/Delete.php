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
    $obj = json_decode(file_get_contents('php://input'));
    if(!empty($obj->id) && !empty($obj->id) ){
        
    $sql = mysqli_query($db_conn, "UPDATE attendance_patterns SET deleted_at=NOW() WHERE id='$obj->id'");
    $updateEmp = mysqli_query($db_conn, "UPDATE employees SET pattern_id=0 WHERE pattern_id='$obj->id' AND deleted_at IS NULL AND id_master='$token->id_master' AND id_partner ='$token->id_partner'");
   
    if($sql) {

        $success = 1;
        $status = 200;
        $msg = "Berhasil hapus data";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Gagal hapus data. mohon coba lagi";
    }
    }else{
        $success = 0;
        $status = 204;
        $msg = "Mohon lengkapi form";
    }
    
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);  

?>