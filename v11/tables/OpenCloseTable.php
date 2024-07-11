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
    $obj = json_decode(file_get_contents('php://input'));

    if(
        isset($obj->to) && !empty($obj->tableID)
    ){
      $oct=0;
      $validateSettings = mysqli_query($db_conn, "SELECT open_close_table FROM partner WHERE id='$token->partnerID'");
      while($row=mysqli_fetch_assoc($validateSettings)){
        $oct = (int)$row['open_close_table'];
      }
        if($oct==1){
          $update = mysqli_query($db_conn,"UPDATE meja SET is_seated = '$obj->to' WHERE id='$obj->tableID'");
          if($update){
              if((int)$obj->to==1){
                  $msg = "Berhasil tutup meja";
              }else{
                  $msg = "Berhasil buka meja";
              }
              
              $success = 1;
              $status=200;
          }else{
              $msg = "Gagal ubah data";
              $success = 0;
              $status=204;
          }
        }else{
          $success = 0;
          $msg = "Merchant ini belum mengaktifkan buka tutup meja manual. Mohon hubungi admin";
          $status = 400;
        }
    }else{
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 400;
    }

}
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]);

?>