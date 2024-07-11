<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
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
$status = 400;
$msg = "error";
$success = 0;

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $obj = json_decode(file_get_contents('php://input'));
    if(
        isset($obj->from) && !empty($obj->from)
        && isset($obj->to) && !empty($obj->to)
    ){
        $q = mysqli_query($db_conn, "UPDATE transaksi SET no_meja='$obj->to' WHERE no_meja='$obj->from' AND deleted_at IS NULL AND status NOT IN(2,3,4,7)");
        if ($q) {
            $success =1;
            $status =200;
            $msg = "Berhasil pindah meja";
        } else {
            $success =0;
            $status =204;
            $msg = "Gagal pindah meja. Mohon coba lagi";
        }
    }else{
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 400;
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>