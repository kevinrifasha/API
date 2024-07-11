<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
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

$idInsert = 0;
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$success=0;
$signupMsg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $signupMsg = $tokens['msg'];
}else{
    $data = json_decode(file_get_contents('php://input'));
    $name = mysqli_real_escape_string($db_conn, $data->name);
    if(
        isset($data->id) && !empty($data->id)
        &&isset($data->name) && !empty($data->name)
    ){
                $insert = mysqli_query($db_conn,"UPDATE `rm_categories` SET `name`='$name', sequence='$data->sequence', updated_at=NOW() WHERE id='$data->id'");
                if($insert){
                    $msg = "Berhasil ubah data";
                    $success = 1;
                    $status=200;
                }else{
                    $msg = "Gagal ubah data";
                    $success = 0;
                    $status=204;
                }
    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);

?>