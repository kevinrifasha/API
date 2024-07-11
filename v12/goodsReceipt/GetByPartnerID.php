<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
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
$res = [];
$all = "0";

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
    $idMaster = $tokenDcrpt->masterID;
    $status=200;
    
    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
        $status = $tokens['status'];
        $msg = $tokens['msg'];
        $success = 0;
    }else{
        $i=0;
        $partnerID = $_GET['partnerID'];
        $dateFrom = $_GET['dateFrom'];
        $dateTo = $_GET['dateTo'];

        if(isset($_GET['all'])) {
            $all = $_GET['all'];
        }
        
        if($newDateFormat == 1){
          if($all == "1") {
            $addQuery1 = "gr.id_master='$idMaster'";
        } else {
            $addQuery1 = "gr.id_partner='$partnerID'";
        }
        
        $getGR=mysqli_query($db_conn,"SELECT gr.id, gr.recieve_date, gr.delivery_order_number, gr.sender, po.no AS poNo, s.name AS supplierName, e.nama AS employeeName, gr.notes FROM goods_receipt gr JOIN purchase_orders po ON gr.purchase_order_id=po.id JOIN suppliers s ON s.id=po.supplier_id JOIN employees e ON e.id=gr.receiver_id WHERE gr.deleted_at IS NULL AND ". $addQuery1 ." AND gr.recieve_date BETWEEN '$dateFrom' AND '$dateTo' ORDER BY gr.id DESC");
        if(mysqli_num_rows($getGR)>0){
            $success=1;
            $status=200;
            $msg="Data ditemukan";
            $resGR = mysqli_fetch_all($getGR, MYSQLI_ASSOC);
            foreach($resGR as $x){
                $id=$x['id'];
                $getGRD=mysqli_query($db_conn,"SELECT grd.id, grd.qty, CASE WHEN id_raw_material=0 THEN 'pcs' ELSE (SELECT name FROM metric m  WHERE grd.id_metric=m.id) END AS metricName, CASE WHEN id_raw_material=0 THEN (SELECT nama FROM menu WHERE menu.id=grd.id_menu) ELSE (SELECT name FROM raw_material rm WHERE rm.id=grd.id_raw_material) END AS itemName FROM goods_receipt_detail grd WHERE grd.id_gr='$id' AND grd.deleted_at IS NULL ORDER BY grd.id ASC");
                $details = mysqli_fetch_all($getGRD, MYSQLI_ASSOC);
                $res[$i]=$x;
                $res[$i]['details']=$details;
                $i++;
            }

        }else{
            $success=1;
            $status=200;
            $msg="Data tidak ditemukan";
        }
        } 
        else 
        {
          if($all == "1") {
            $addQuery1 = "gr.id_master='$idMaster'";
          } else {
              $addQuery1 = "gr.id_partner='$partnerID'";
          }
          
          $getGR=mysqli_query($db_conn,"SELECT gr.id, gr.recieve_date, gr.delivery_order_number, gr.sender, po.no AS poNo, s.name AS supplierName, e.nama AS employeeName, gr.notes FROM goods_receipt gr JOIN purchase_orders po ON gr.purchase_order_id=po.id JOIN suppliers s ON s.id=po.supplier_id JOIN employees e ON e.id=gr.receiver_id WHERE gr.deleted_at IS NULL AND ". $addQuery1 ." AND DATE(gr.recieve_date) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY gr.id DESC");
          if(mysqli_num_rows($getGR)>0){
              $success=1;
              $status=200;
              $msg="Data ditemukan";
              $resGR = mysqli_fetch_all($getGR, MYSQLI_ASSOC);
              foreach($resGR as $x){
                  $id=$x['id'];
                  $getGRD=mysqli_query($db_conn,"SELECT grd.id, grd.qty, CASE WHEN id_raw_material=0 THEN 'pcs' ELSE (SELECT name FROM metric m  WHERE grd.id_metric=m.id) END AS metricName, CASE WHEN id_raw_material=0 THEN (SELECT nama FROM menu WHERE menu.id=grd.id_menu) ELSE (SELECT name FROM raw_material rm WHERE rm.id=grd.id_raw_material) END AS itemName FROM goods_receipt_detail grd WHERE grd.id_gr='$id' AND grd.deleted_at IS NULL ORDER BY grd.id ASC");
                  $details = mysqli_fetch_all($getGRD, MYSQLI_ASSOC);
                  $res[$i]=$x;
                  $res[$i]['details']=$details;
                  $i++;
              }

          }else{
              $success=1;
              $status=200;
              $msg="Data tidak ditemukan";
          }
        }


    }
    echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "goodsReceipt"=>$res]);
 ?>

