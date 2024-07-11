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
    $res=array();
    $status=200;
    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
        $status = $tokens['status'];
        $msg = $tokens['msg'];
        $success = 0;
    }else{
        $partnerID = $_GET['partnerID'];
        $dateFrom = date("Y-m-01");
        $dateTo = date("Y-m-d", strtotime("last day of this month"));
        $q="SELECT rm.id, rm.name, c.name AS categoryName, rm.unit_price, CASE WHEN rm.level=1 THEN 'Setengah Jadi' ELSE 'Bahan Baku' END AS type, m.name AS metricName FROM raw_material rm JOIN rm_categories c ON c.id=rm.category_id JOIN metric m ON m.id=rm.id_metric WHERE rm.deleted_at IS NULL AND rm.id_partner='$partnerID'  ORDER BY c.id ASC";
        $getNames=mysqli_query($db_conn,$q);
        if(mysqli_num_rows($getNames)>0){
            $success=1;
            $status=200;
            $msg="Data ditemukan";
            $i=0;
            $names = mysqli_fetch_all($getNames, MYSQLI_ASSOC);
            foreach($names AS $x){
                $rawID = $x['id'];
                $res[$i]['id']=$x['id'];
                $res[$i]['categoryName']=$x['categoryName'];
                $res[$i]['type']=$x['type'];
                $res[$i]['name']=$x['name'];
                $res[$i]['metricName']=$x['metricName'];
                $res[$i]['unitPrice']=$x['unit_price'];
                $getFinal = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND DATE(sm.created_at)<='$dateTo' ORDER BY id DESC LIMIT 1");
                $resFinal = mysqli_fetch_all($getFinal, MYSQLI_ASSOC);
                if($resFinal[0]['remaining']==null){
                    $res[$i]['finalQty']="0";
                }else{
                    $res[$i]['finalQty']= $resFinal[0]['remaining'];
                }
                $i++;
            }
        }
        $getNames = mysqli_query($db_conn, "SELECT m.id, m.nama, c.name AS categoryName, m.stock, m.hpp FROM menu m JOIN categories c ON c.id=m.id_category WHERE m.deleted_at IS NULL AND m.id_partner='$partnerID' AND m.is_recipe=0 ORDER BY c.sequence ASC");
        if(mysqli_num_rows($getNames)>0){
            $names = mysqli_fetch_all($getNames, MYSQLI_ASSOC);
            foreach($names AS $x){
                $menuID = $x['id'];
                $res[$i]['id']=$x['id'];
                $res[$i]['categoryName']=$x['categoryName'];
                $res[$i]['type']="Bahan Jadi";
                $res[$i]['name']=$x['nama'];
                $res[$i]['unitPrice']=$x['hpp'];
                $res[$i]['metricName']="PCS";
                $res[$i]['initialQty']="0";
                $getFinal = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND DATE(sm.created_at)<='$dateTo' ORDER BY id DESC LIMIT 1");
                $resFinal = mysqli_fetch_all($getFinal, MYSQLI_ASSOC);
                if($resFinal[0]['remaining']==null){
                    $res[$i]['finalQty']="0";
                }else{
                    $res[$i]['finalQty']= $resFinal[0]['remaining'];
                }
                $i++;
            }
            echo '{"source":[';
            foreach($res as $source) {
              if(json_encode($source)){
                echo $prefix, json_encode($source);
                $prefix = ',';
              }
            }
            echo '],"msg":"Success","status":200,"success":1}';
            
        } else {
            echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "source"=>$res]);
        }
        
    }
?>