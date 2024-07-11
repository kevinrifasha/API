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
        $partnerID = $_GET['partnerID'];
        $dateFrom = $_GET['dateFrom'];
        $dateTo = $_GET['dateTo'];
        $getPO=mysqli_query($db_conn,"SELECT po.id, po.no, po.notes, po.total, po.received, po.created_at, s.name AS supplierName, e.nama AS employeeName FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id JOIN employees e ON e.id = po.created_by WHERE po.partner_id='$partnerID' AND DATE(po.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND po.deleted_at IS NULL ORDER BY po.id DESC");
        if(mysqli_num_rows($getPO)>0){
            $success=1;
            $status=200;
            $msg="Data ditemukan";
            $res = mysqli_fetch_all($getPO, MYSQLI_ASSOC);

        }else{
            $success=1;
            $status=200;
            $msg="Data tidak ditemukan";
        }
    }
    echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "purchaseOrder"=>$res]);


 ?>

