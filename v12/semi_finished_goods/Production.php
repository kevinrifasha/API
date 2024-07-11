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
$value = array();
$success=0;
$msg = 'Failed';
$obj = json_decode(file_get_contents('php://input'));
function reduce_recipe_until_minus($db_conn,$recipes,$qtyOrder, $tokenDecoded){
    $irms=0;
    foreach ($recipes as $valueR) {
        $rawID = $valueR['id_raw'];
        $addStock = $qtyOrder*$valueR['qty'];
        $metricID = $valueR['id_metric'];
        $partnerID = $obj->partnerID;
        $remainingStock = mysqli_query($db_conn, "SELECT remaining FROM stock_movements WHERE raw_id='$rawID' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
        if(mysqli_num_rows($remainingStock)>0){
            $resRS =  mysqli_fetch_all($remainingStock, MYSQLI_ASSOC);
            $remaining = (double)$resRS[0]['remaining'];
        }else{
            $remaining = 0;
        }
        $remaining = $remaining-$addStock;
        // $track = mysqli_query($db_conn, "INSERT INTO stock_movements SET master_id='$tokenDecoded->masterID', partner_id='$tokenDecoded->partnerID', raw_id='$rawID', metric_id='$metricID', qty='$addStock', remaining='$remaining'");
        $track = mysqli_query($db_conn, "INSERT INTO stock_movements SET master_id='$tokenDecoded->masterID', partner_id='$partnerID', raw_id='$rawID', metric_id='$metricID', qty='$addStock', remaining='$remaining'");
        $qTemp = mysqli_query($db_conn,"SELECT * FROM `raw_material_stock` WHERE id_raw_material='$rawID' AND DATE(exp_date)>NOW() AND deleted_at IS NULL");
        if(mysqli_num_rows($qTemp) > 0){
            $resTemp = mysqli_fetch_all($qTemp, MYSQLI_ASSOC);
            $idRMS = $resTemp[0]['id'];
            if($valueR['id_metric']==$resTemp[0]['id_metric']){
                $qRc = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`=`stock`-'$addStock' WHERE `id`='$idRMS'");
            }else{
                $idm = $valueR['id_metric'];
                $findMC = $resTemp[0]['id_metric'];
                $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$idm}' AND `id_metric2`='{$findMC}' ");
                if(mysqli_num_rows($qMC)==0){
                    $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$findMC}' AND `id_metric2`='{$idm}'");
                    $mcVal  = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                    $mcVal = $mcVal[0];
                    $stockMC = ($resTemp[0]['stock']*$mcVal['value']) - ($qtyOrder*$valueR['$qty']);
                    $rmsMetricID = $valueR['id_metric'];
                    $updateStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`='$stockMC', `id_metric`='$rmsMetricID' WHERE id='$idRMS'");
                }else{
                    $mcVal  = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                    $mcVal = $mcVal[0];
                    $valueR['id_metric'] = $resTemp[0]['id_metric'];
                    $minStock= $qtyOrder*$mcVal['value'];
                    $resTemp[0]['stock']=($resTemp[0]['stock']) - $minStock;
                    $rmsStock = $resTemp[0]['stock'];
                    $updateStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`='$rmsStock', `id_metric`='$findMC' WHERE id='$idRMS'");
                }
            }
        }
    }
}
$idInsert = "";
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $today = date("Y-m-d");
    // POST DATA
    $obj = json_decode(file_get_contents('php://input'));
    $productionQty = $obj->qty;
    $sfgID = $obj->sfgID;
    $metricID = $obj->metricID;
    $partnerID = $obj->partnerID;
    // $insertProduction = mysqli_query($db_conn, "INSERT INTO sfg_productions SET master_id='$tokenDecoded->masterID', partner_id='$tokenDecoded->partnerID', sfg_id='$sfgID', qty='$productionQty', metric_id='$metricID', created_by='$tokenDecoded->id'");
    $insertProduction = mysqli_query($db_conn, "INSERT INTO sfg_productions SET master_id='$tokenDecoded->masterID', partner_id='$partnerID', sfg_id='$sfgID', qty='$productionQty', metric_id='$metricID', created_by='$tokenDecoded->id'");
    if($insertProduction){
        $getRecipes = mysqli_query($db_conn, "SELECT id_raw, qty, id_metric FROM recipe WHERE deleted_at IS NULL AND sfg_id='$sfgID'");
        $recipes = mysqli_fetch_all($getRecipes, MYSQLI_ASSOC);
        reduce_recipe_until_minus($db_conn,$recipes,$productionQty, $tokenDecoded);
        $insertStock = mysqli_query($db_conn,"INSERT INTO raw_material_stock SET id_raw_material='$sfgID', stock=stock+'$productionQty', id_metric='$metricID'");
        if($insertStock){
            $remainingStock = mysqli_query($db_conn, "SELECT remaining FROM stock_movements WHERE raw_id='$sfgID' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
            if(mysqli_num_rows($remainingStock)>0){
                $resRS =  mysqli_fetch_all($remainingStock, MYSQLI_ASSOC);
                $remaining = (double)$resRS[0]['remaining'];
            }else{
                $remaining = 0;
            }
            $remaining = $remaining+$productionQty;
            $track = mysqli_query($db_conn, "INSERT INTO stock_movements SET raw_id='$sfgID', metric_id='$metricID', produced='$productionQty', remaining='$remaining'");
            $success=1;
            $status=200;
            $msg="Berhasil produksi";
        }else{
            $success=0;
            $status=204;
            $msg="Gagal produksi. Mohon coba lagi";
        }
    }else{
        $success=0;
        $status=204;
        $msg="Gagal produksi. Mohon coba lagi";
    }

}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "id"=>$idInsert]);
?>