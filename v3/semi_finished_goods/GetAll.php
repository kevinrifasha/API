<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';
$headers = apache_request_headers();
$token = '';

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $tokenDecoded->masterID;
$value = array();
$success=0;
$msg = 'Failed';
$all = "0";
$partnerID = $_GET['partnerID'];

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($all == "1") {
            $addQuery1 = "r.id_master='$idMaster'";
        } else {
            $addQuery1 = "r.id_partner='$partnerID'";
        }
    
    $q = mysqli_query($db_conn, "SELECT r.id, m.id as mID, r.name, r.reminder_allert, r.yield, m.name AS mName, r.unit_price, r.id_metric_price, m1.name AS name_metric_price, CASE WHEN COUNT(recipe.id) >0 THEN 1 ELSE 0 END AS is_recipe, r.category_id, rmc.name AS categoryName FROM `raw_material` r JOIN metric m ON r.id_metric = m.id JOIN metric m1 ON m1.id=r.id_metric_price LEFT JOIN recipe ON r.id=recipe.id_raw JOIN rm_categories rmc ON rmc.id=r.category_id LEFT JOIN menu ON menu.id=recipe.id_menu AND menu.deleted_at IS NULL WHERE ". $addQuery1 ." AND r.deleted_at IS NULL AND r.level=1 GROUP BY r.id ORDER BY r.id DESC");
    
    $res1 = array();
    if(mysqli_num_rows($q) > 0){
        // $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $stock=0;
        $idm=0;
        while($value = mysqli_fetch_assoc($q)) {
            $rawd = $value;
            $find = $value['id'];
            $today = date("Y-m-d");
            $qS = mysqli_query($db_conn, "SELECT raw_material_stock.id, raw_material_stock.id_raw_material, raw_material_stock.stock, raw_material_stock.id_metric, raw_material_stock.id_goods_receipt_detail, metric.name AS metricName FROM `raw_material_stock` JOIN metric on metric.id=raw_material_stock.id_metric WHERE raw_material_stock.id_raw_material='{$find}' ORDER BY raw_material_stock.id DESC");
            $resQs = mysqli_fetch_all($qS, MYSQLI_ASSOC);
            $rawd['rawMaterialStocks']=$resQs;
            $arr = array();
            foreach ($resQs as $valueRMS) {
                if($idm ==$valueRMS['id_metric'] || $idm == 0){
                    $stock += $valueRMS['stock'];
                    $idm = $valueRMS['id_metric'];
                    $id = $idm;
                    $allRaw = mysqli_query($db_conn, "SELECT metric_convert.id_metric2, metric.name FROM metric_convert
                    JOIN metric on metric.id=metric_convert.id_metric2 WHERE metric_convert.id_metric1='$id'");
                    $all_raw = mysqli_fetch_all($allRaw, MYSQLI_ASSOC);
                    foreach($all_raw as $key){
                        array_push($arr,array(
                            'id'=>$key['id_metric2'],
                            'name'=>$key['name'],
                        ));
                    }
                    $allRaw1 = mysqli_query($db_conn, "SELECT id,name FROM `metric` WHERE id='$id'");
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                    foreach($all_raw1 as $key){
                        array_push($arr,array(
                            'id'=>$key['id'],
                            'name'=>$key['name'],
                        ));
                    }
                }else{
                    $findMC = $valueRMS['id_metric'];
                    $id = $findMC;
                    $allRaw = mysqli_query($db_conn, "SELECT metric_convert.id_metric2, metric.name FROM metric_convert
                    JOIN metric on metric.id=metric_convert.id_metric2 WHERE metric_convert.id_metric1='$id'");
                    $all_raw = mysqli_fetch_all($allRaw, MYSQLI_ASSOC);
                    foreach($all_raw as $key){
                        array_push($arr,array(
                            'id'=>$key['id_metric2'],
                            'name'=>$key['name'],
                        ));
                    }
                    $allRaw1 = mysqli_query($db_conn, "SELECT metric_convert.id_metric1, metric.name FROM metric_convert
                    JOIN metric on metric.id=metric_convert.id_metric1 WHERE metric_convert.id_metric2='$id'");
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                    foreach($all_raw1 as $key){
                        array_push($arr,array(
                            'id'=>$key['id_metric1'],
                            'name'=>$key['name'],
                        ));
                    }
                    $allRaw1 = mysqli_query($db_conn, "SELECT id,name FROM `metric` WHERE id='$id'");
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                    foreach($all_raw1 as $key){
                        array_push($arr,array(
                            'id'=>$key['id'],
                            'name'=>$key['name'],
                        ));
                    }
                    $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$idm}' AND `id_metric2`='{$findMC}' ");
                    if($mcVal==false){
                        $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric2='{$idm}' AND `id_metric1`='{$findMC}' ");
                        $mcVal = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                        $stockMC = $valueRMS['stock']*$mcVal['value'] ;
                        $stock += $stockMC;
                    }else{
                        $mcVal = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                        $stockMC= $minStock*$mcVal['value'];
                        $stock += $stockMC;
                        $idm=$valueRMS['id_metric'];
                    }
                }
            }
            $item = array(
                'id'=>$key['id_metric2'],
                'name'=>$key['name'],
            );

            $rawd['stock']=$stock;
            if($idm==0){
                $rawd['stockMetricId']="0";
            }else{
                $rawd['stockMetricId']=$idm;
            }
            $qMN = mysqli_query($db_conn, "SELECT name FROM `metric` WHERE id='$idm'");
            $dataM = mysqli_fetch_all($qMN, MYSQLI_ASSOC);
            if($dataM!=false){
                $rawd['stockMetricName'] = $dataM[0]['name'];
            }else{
                $rawd['stockMetricName'] = "wrong";
            }
            array_push($res1, $rawd);
            $stock=0;
        }
        $prefix = ' ';
        echo '{"rawmaterials":[';
        foreach($res1 as $rawd) {
          if(json_encode($rawd)){
            echo $prefix, json_encode($rawd);
            $prefix = ',';
          }
        }
        echo '],"msg":"success","status":200,"success":1,';
        echo '"id":';
        echo '"',$tokenDecoded->partnerID,'"';
        echo "}";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
        echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "rawmaterials"=>$res1, "id"=>$tokenDecoded->partnerID]);
    }
}

?>
