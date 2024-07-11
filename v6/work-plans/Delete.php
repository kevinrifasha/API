<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
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

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $obj = json_decode(file_get_contents('php://input'));
    if(
        !empty($obj->id)
    ){
        $qInsert = "UPDATE sa_work_plans SET deleted_at=NOW() WHERE id='$obj->id'";
        $insert = mysqli_query($db_conn,$qInsert);
        if($insert){
          $i=0;
          $dataLength = 0;
          $deleteExisting = mysqli_query($db_conn, "DELETE FROM sa_work_plan_details WHERE work_plan_id='$obj->id'");
          if($deleteExisting){
            $msg = "Berhasil hapus data";
            $success = 1;
            $status=200;
          }else{
            $msg = "Gagal menghapus data existing";
              $success = 0;
              $status=204;
          }
        }else{
            $msg = "Gagal hapus data";
            $success = 0;
            $status=204;
        }
    }else{
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 204;
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);

?>