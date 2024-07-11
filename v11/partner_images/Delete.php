<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../db_connection.php';
require_once('../auth/Token.php');$headers = array();
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
    $data = json_decode(file_get_contents('php://input'));
    if(
        isset($data->id) && !empty($data->id)
    ){
                $insert = mysqli_query($db_conn,"UPDATE `partner_images` SET `deleted_at`=NOW() WHERE `id`='$data->id'");
                if($insert){
                    $msg = "Berhasil menghapus data";
                    $success = 1;
                    $status=200;
                }else{
                    $msg = "Gagal menghapus data";
                    $success = 0;
                    $status=204;
                }
    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;  
    }

}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
    http_response_code(200);
  }else{
    http_response_code($status);
  }   
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]); 
    
?>
     