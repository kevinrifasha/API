<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

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
$result = array();

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
    if(isset($obj->cName) && isset($obj->cPhone)&&isset($obj->amount)){
        $shift = mysqli_query($db_conn, "SELECT MAX(id) AS id FROM shift WHERE partner_id='$token->id_partner'");
        if(mysqli_num_rows($shift)>0){
            $res = mysqli_fetch_all($shift, MYSQLI_ASSOC);
            $shiftID = $res[0]['id'];
            $query = "INSERT INTO down_payments SET partner_id='$token->id_partner', master_id='$token->id_master', customer_name='$obj->cName', customer_phone='$obj->cPhone', amount='$obj->amount', payment_method_id='$obj->paymentMethodID', shift_id='$shiftID', notes='$obj->notes', created_by='$token->id'";
            $q = mysqli_query($db_conn, $query);
            if ($q) {
                $success =1;
                $status =200;
                $msg = "Berhasil tambah data";
            } else {
                $success =0;
                $status =204;
                $msg = "Gagal tambah data";
            }
        }else{
            $success =0;
            $status =204;
            $msg = "Gagal ambil shift. Mohon coba lagi";
        }
    }else{
        $success =0;
        $status =400;
        $msg = "Data tidak lengkap";
    }

}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>