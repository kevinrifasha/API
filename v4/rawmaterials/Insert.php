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
$idInsert = "";
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $today = date("Y-m-d");
    // POST DATA
    $obj = json_decode(file_get_contents('php://input'));
    // $expiredDate = date("Y-m-d", $obj->exp_date);
    $obj->name = mysqli_real_escape_string($db_conn, $obj->name);
    if($obj->exp_date>$today){
        $add1 = mysqli_query($db_conn,"INSERT INTO raw_material SET id_master = '$token->id_master', id_partner = '$token->id_partner', name = '$obj->name', reminder_allert = '$obj->reminder_allert', id_metric = '$obj->id_metric', unit_price='$obj->unit_price', id_metric_price = '$obj->id_metric_price', yield='$obj->yield'");
    $idInsert = mysqli_insert_id($db_conn);
    if($add1!=false){
        $finalStock=0;
        if((int)$obj->yield==100){
            $finalStock = $obj->stock;
        }else{
            $finalStock = (int)$obj->stock*(int)$obj->yield/100;
        }
        $movement = mysqli_query($db_conn, "INSERT INTO stock_movements SET master_id='$token->id_master', partner_id='$token->id_partner', raw_id='$idInsert', metric_id='$obj->id_metric', type=0, initial='$finalStock', remaining='$finalStock'");

        $add = mysqli_query($db_conn,"INSERT INTO raw_material_stock SET id_raw_material = '$idInsert', stock = '$finalStock', id_metric = '$obj->id_metric', exp_date = '$obj->exp_date'");
        $success=1;
        $msg = "Berhasil tambah bahan baku";
        $status = 200;
    }else{
        $success=0;
        $msg = "Gagal tambah data. Mohon coba lagi";
        $status = 400;
    }
    }else{
        $success=0;
        $msg = "Tanggal kadaluarsa tidak boleh kurang dari atau sama dengan hari ini";
        $status = 400;
    }

}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "id"=>$idInsert]);
?>