<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../tokenModels/tokenManager.php");
require_once("./../menuModels/menuManager.php");
require_once("./../categoryModels/categoryManager.php");
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
    $res = [];

    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
        $status = $tokens['status'];
        $signupMsg = $tokens['msg'];
        $success = 0;
    }else{
        $partnerId = $_GET['partnerId'];
        
        $query = "SELECT m.id, m.nama AS menu, m.is_recipe, c.name AS category FROM `menu` m JOIN `categories` c ON c.id=m.id_category WHERE m.id_partner = '$partnerId' AND m.deleted_at IS NULL ORDER BY id DESC";
        $sql = mysqli_query($db_conn, $query);
        
        if(mysqli_num_rows($sql) > 0) {
            $dataGet = mysqli_fetch_all($sql, MYSQLI_ASSOC);
            
            foreach ($dataGet as $val) {
                $menuID = $val['id'];
                $queryRawMaterial = "SELECT r.id_raw, rm.name AS raw, r.qty, r.id_metric AS id_metric_recipe, m.name AS metric_recipe, rm.id_metric AS id_metric_stock, mt.name AS metric_stock FROM `recipe` r JOIN `raw_material` rm ON rm.id = r.id_raw JOIN `metric` m ON m.id=r.id_metric JOIN `metric` mt ON mt.id=rm.id_metric WHERE id_menu = '$menuID' ORDER BY r.id DESC";
                $sqlRawMaterial = mysqli_query($db_conn, $queryRawMaterial);
                
                $dataRaw = mysqli_fetch_all($sqlRawMaterial, MYSQLI_ASSOC);
                
                $raws = array();
                
                if(count($dataRaw) > 0) {
                    foreach($dataRaw as $data) {
                        // get stock raw material nya disini
                        $RMSmanager = new RawMaterialStockManager($db);
                        $rawMaterialStocks = $RMSmanager->getByRawId($data['id_raw']);
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
                        
                        $data['stock'] = $stock;

                        array_push($raws, $data);
                    }
                    $val['rawMaterials'] = $raws;
                } else {
                    $val['rawMaterials'] = "";
                }
                
                if($val['is_recipe'] == 0) {
                    $val['rawMaterials'] = "";
                }
                
                array_push($res, $val);
            }
            
            $success = 1;
            $signupMsg = "Success";
            $status=200;
        } else {
            $success = 0;
            $signupMsg = "Data not found";
            $status=400;
        }
    }
    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$signupMsg, "menus"=>$res]);

    echo $signupJson;
 ?>
