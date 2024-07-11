<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require_once("./../partnerModels/partnerManager.php");
require_once("./../purchaseOrdersModels/purchaseOrdersManager.php");
require_once("./../menuModels/menuManager.php");
require_once("./../purchaseOrderDetailsModels/purchaseOrderDetailsManager.php");
require_once("./../rawMaterialModels/rawMaterialManager.php");
require_once("./../metricModels/metricManager.php");
require_once("./../employeeModels/employeeManager.php");
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
    // $tokens = $tokenizer->validate($token);
    $tokens = $tokenizer->validate($token);
    $tokenDcrpt = json_decode($tokenizer->stringEncryption('decrypt',$token));

    $status=200;
    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
        $status = $tokens['status'];
        $msg = $tokens['msg'];
        $success = 0;
    }else{
        $id = $_GET['id'];
        $getGRD=mysqli_query($db_conn,"SELECT grd.id, grd.qty, CASE WHEN id_raw_material=0 THEN 'pcs' ELSE (SELECT name FROM metric m  WHERE grd.id_metric=m.id) END AS metricName, CASE WHEN id_raw_material=0 THEN (SELECT nama FROM menu WHERE menu.id=grd.id_menu) ELSE (SELECT name FROM raw_material rm WHERE rm.id=grd.id_raw_material) END AS itemName FROM goods_receipt_detail grd WHERE grd.id_gr='$id' AND grd.deleted_at IS NULL ORDER BY grd.id ASC");

        // $getPO=mysqli_query($db_conn,"SELECT pod.id, pod.qty FROM purchase_orders_details pod WHERE pod.purchase_order_id='$id' AND pod.deleted_at IS NULL ORDER BY pod.id ASC");
        if(mysqli_num_rows($getGRD)>0){
            $success=1;
            $status=200;
            $msg="Data ditemukan";
            $res = mysqli_fetch_all($getGRD, MYSQLI_ASSOC);
            // $resPO = mysqli_fetch_all($getPO, MYSQLI_ASSOC);

        }else{
            $success=1;
            $status=200;
            $msg="Data tidak ditemukan";
        }
    }
    echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "details"=>$res, "po"=>$resPO]);


 ?>

