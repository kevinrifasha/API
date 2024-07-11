<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../tokenModels/tokenManager.php");
require_once("./../rawMaterialModels/rawMaterialManager.php");
require_once("./../metricModels/metricManager.php");
require_once("./../rawMaterialStockModels/rawMaterialStockManager.php");
require_once("./../metricConvertModels/metricConvertManager.php");

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
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->masterID;
$today = date("Y-m-d H:i:s");
$all_raw1=[];

// $json = file_get_contents('php://input');
// $obj = json_decode($json,true);
$res=array();
$status = 200;
$success=0;
$msg = "failed";
$all = "0";
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $msg = $tokens['msg'];
    $success = 0;
}else{

    $id = $_GET['id_partner'];
    $now = date("Y-m-d H:i:s");
    $date = date("Y-m-d");
    $date1 = $date;
    $before = date('Y-m-d',strtotime($date1 . "+3 days"));
    $query = "";
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    if($all !== "1") {
        $query = "SELECT raw_material_stock.id, raw_material_stock.id_raw_material, raw_material_stock.stock, raw_material_stock.id_metric, raw_material_stock.exp_date, raw_material_stock.id_goods_receipt_detail, raw_material.name AS rmname, metric.name as mname FROM raw_material_stock JOIN raw_material ON raw_material_stock.id_raw_material=raw_material.id JOIN metric ON raw_material_stock.id_metric = metric.id WHERE raw_material_stock.exp_date<='$before' AND raw_material.id_partner='$id' AND raw_material.deleted_at IS NULL"; 
    } else {
        $query = "SELECT raw_material_stock.id, raw_material_stock.id_raw_material, raw_material_stock.stock, raw_material_stock.id_metric, raw_material_stock.exp_date, raw_material_stock.id_goods_receipt_detail, raw_material.name AS rmname, metric.name as mname FROM raw_material_stock JOIN raw_material ON raw_material_stock.id_raw_material=raw_material.id JOIN metric ON raw_material_stock.id_metric = metric.id WHERE raw_material_stock.exp_date<='$before' AND raw_material.id_master ='$idMaster' AND raw_material.deleted_at IS NULL";
    }

    $allRaw1 = mysqli_query($db_conn, $query);
    if (mysqli_num_rows($allRaw1) > 0) {
        $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
    }
    $arr = array();
    $index = 0;
    $j = 0;
    $note = valid;
    $manager = new RawMaterialManager($db);
        $partnerId = $_GET['id_partner'];
        $raws = [];
        
        if($all == "1") {
            $raws = $manager->getByMasterId($idMaster);
        } else {
            $raws = $manager->getByPartnerId($partnerId);
        }
        
        if($raws!=false){
            $res = array();
            foreach ($raws as $raw) {
                $rawd = $raw->getDetails();
                $metricManager = new MetricManager($db);
                $metric = $metricManager->getById($rawd['id_metric']);
                if($metric!=false){
                    $dataM = $metric->getDetails();
                    $rawd['metricName'] = $dataM['name'];
                }else{
                    $rawd['metricName'] = "wrong";
                }
                $metricManager = new MetricManager($db);
                $metric = $metricManager->getById($rawd['id_metric_price']);
                if($metric!=false){
                    $dataM = $metric->getDetails();
                    $rawd['priceMetricName'] = $dataM['name'];
                }else{
                    $rawd['priceMetricName'] = "wrong";
                }
                $RMSmanager = new RawMaterialStockManager($db);
                $rawMaterialStocks = $RMSmanager->getByRawId($rawd['id']);
                $stock=0;
                $idm=0;
                if($rawMaterialStocks!=false){
                    foreach ($rawMaterialStocks as $valueRMS) {
                        if($idm ==$valueRMS->getId_metric() || $idm == 0){
                            $stock += $valueRMS->getStock();
                            $idm = $valueRMS->getId_metric();
                        }else{
                            $MCmanager = new MetricConvertManager($db);
                            $mcVal = $MCmanager->getByMetricsId($idm,$valueRMS->getId_metric());
                            if($mcVal==false){
                                $mcVal = $MCmanager->getByMetricsId($valueRMS->getId_metric(),$idm);
                                if($mcVal==false){
                                    $stockMC = ($valueRMS->getStock()*1) ;

                                }else{
                                    $stockMC = ($valueRMS->getStock()*$mcVal->getValue()) ;
                                }

                                $stock += $stockMC;

                            }else{
                                $stockMC= $stock*$mcVal->getValue();
                                $stock = $valueRMS->getStock()+$stockMC;
                                $idm=$valueRMS->getId_metric();
                            }
                        }
                    }
                }
                $rawd['stock']=$stock;
                $rawd['stockMetricId']=$idm;
                $metric = $metricManager->getById($idm);
                if($metric!=false){
                    $dataM = $metric->getDetails();
                    $rawd['stockMetricName'] = $dataM['name'];
                }else{
                    $rawd['stockMetricName'] = $rawd['metricName'];
                    $rawd['stockMetricId'] = $rawd['id_metric'];
                }
                if($rawd['stockMetricId']==$rawd['id_metric']){
                    
                    if((int)$rawd['reminder_allert']>=(int)$rawd['stock']){
                        array_push($res, $rawd);
                    }
                }else{
                    $MCmanager = new MetricConvertManager($db);
                    $mcVal = $MCmanager->getByMetricsId($rawd['stockMetricId'],$rawd['id_metric']);

                    if($mcVal==false){
                        $MCmanager = new MetricConvertManager($db);
                        $mcVal = $MCmanager->getByMetricsId($rawd['id_metric'], $rawd['stockMetricId']);
                        if($mcVal==false){
                            $note = "invalid";
                        }else{
                            $st = (int)$rawd['reminder_allert']*(int)$mcVal->getValue();
                            if((int)$st>=(int)$rawd['stock']){
                                $rawd['reminder_allert']=$st;
                                array_push($res, $rawd);
                            }
                        }
                    }else{
                        $st = (int)$rawd['stock']*(int)$mcVal->getValue();
                        
                        if((int)$rawd['reminder_allert']>=(int)$st){
                            $rawd['stock']=$st;
                            array_push($res, $rawd);
                        }
                    }
                }
            }
        }
    // if (count($res) > 0) {
        $success=1;
        $status=200;
        if($note == "valid"){
            $msg="Success";
        } else {
            $msg="Ada Id Stock Metric dan Id Metric yang kurang tepat";
        }
    // } else {
    //     $success=0;
    //     $status=204;
    //     $msg="Data Not Found";
    // }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "raw"=>$res, "expired"=>$all_raw1]);
?>