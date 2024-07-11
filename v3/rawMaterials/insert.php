<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require_once("./../rawMaterialModels/rawMaterialManager.php");
require_once("./../rawMaterialStockModels/rawMaterialStockManager.php");
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
$today = date("Y-m-d H:i:s");

$json = file_get_contents('php://input');
$obj = json_decode($json,true);
$res=array();

$success=0;
$msg = "failed";
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{

    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    $data = json_decode(file_get_contents('php://input'));
    $rmName = mysqli_real_escape_string($db_conn, $obj['name']);
    $stock = $obj['stock'];
    $sql = mysqli_query($db_conn, "INSERT INTO raw_material SET id_master='$data->id_master', id_partner='$data->id_partner', name='$rmName', reminder_allert='$data->reminder_allert', id_metric='$data->id_metric', unit_price='$data->unit_price', id_metric_price='$data->id_metric_price', category_id='$data->categoryID', yield='$data->yield'");
    if($sql){
        $add = mysqli_insert_id($db_conn);
        $movement = mysqli_query($db_conn, "INSERT INTO stock_movements SET master_id='$data->id_master', partner_id='$data->id_partner', raw_id='$add', metric_id='$data->id_metric', type=0, initial='$stock', remaining=$stock");
        $rawManagerS = new RawMaterialStockManager($db);
        $rawMaterialS = new RawMaterialStock(array("id_raw_material"=>$add,"id_metric"=>$obj["id_metric"],"stock"=>$obj['stock'],"exp_date"=>"2030-12-12 00:00:00"));
        $add = $rawManagerS->insertInit($rawMaterialS);
        $success=1;
        $msg = "Success";
        $status = 200;
    }else{
        $success=0;
        $msg = "Failed";
        $status = 400;
    }

}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);

?>