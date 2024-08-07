<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
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
$res = array();
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
}else{
    if(isset($_GET['partnerId']) && !empty($_GET['partnerId'])){
        // $manager = new RawMaterialManager($db);
        $partnerID = $_GET['partnerId'];
        $sql = mysqli_query($db_conn, "SELECT rm.id, rm.id_master, rm.id_partner, rm.category_id, rm.name, rm.reminder_allert, rm.id_metric, rm.unit_price, rm.id_metric_price, rm.yield, rm.level, rm.created_at, rm.deleted_at, rmc.name AS categoryName FROM raw_material rm JOIN rm_categories rmc ON rm.category_id=rmc.id WHERE rm.id_partner='$partnerID' AND rm.deleted_at IS NULL AND rm.level=0 ORDER BY rm.id DESC");
        $donnees = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $raws = array();
        foreach($donnees as $donnee){
          $rawMaterial = new RawMaterial($donnee);
          array_push($raws, $rawMaterial);
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
                    $rawd['metricName']= "wrong";
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
                array_push($res, $rawd);
            }
            $success = 1;
            $msg = "Success";
            $status = 200;
        }else{
            $success = 0;
            $msg = "Data Not Found";
            $status = 204;
        }
    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;

    }

}


echo json_encode(["msg"=>$msg, "status"=>$status,"success"=>$success,"rawMaterials"=>$res]);

?>