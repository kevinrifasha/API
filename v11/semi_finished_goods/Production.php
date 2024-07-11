<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');


//init var
$headers = array();
$rx_http = '/\AHTTP_/';
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
            $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
    }
}
$tokenizer = new Token();
$token = '';
$res = array();
//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}
function reduce_recipe($db_conn, $recipes, $qtyOrder)
{
    //Raw Material Stock
    $rawMaterialStocks = array();
    $irms = 0;
    foreach ($recipes as $valueR) {
        $rawID = $valueR['id_raw'];
        $qTemp = mysqli_query($db_conn, "SELECT * FROM `raw_material_stock` WHERE id_raw_material='$rawID' AND DATE(exp_date)>NOW() AND deleted_at IS NULL");
        $rawMaterialStocks[$irms] = mysqli_fetch_all($qTemp, MYSQLI_ASSOC);
        $irms += 1;
    }
    //update stock
    //cek Resep
    foreach ($recipes as $valueR) {
        $minStock = ($valueR['qty'] * $qtyOrder);
        foreach ($rawMaterialStocks as $valueLRMS) {
            foreach ($valueLRMS as $valueRMS) {
                if ($minStock > 0) {
                    if ($valueR['id_raw'] == $valueRMS['id_raw_material']) {
                        if ($valueR['id_metric'] == $valueRMS['id_metric']) {
                            $stockMC = $valueRMS['stock'] - $minStock;
                            if ($stockMC >= 0) {
                                $minMCStock = $minStock;
                            } else {
                                $minMCStock = $valueRMS['stock'];
                            }
                            $minStock = $minStock - $minMCStock;
                            $rmsID = $valueRMS['id'];
                            $rmsStock = $valueRMS['stock'] - $minMCStock;
                            $updateStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`='$rmsStock' WHERE id='$rmsID'");
                        } else {
                            $idm = $valueR['id_metric'];
                            $findMC = $valueRMS['id_metric'];
                            $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$idm}' AND `id_metric2`='{$findMC}' ");
                            if (mysqli_num_rows($qMC) == 0) {
                                $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$findMC}' AND `id_metric2`='{$idm}' ");
                                $mcVal  = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                                $mcVal = $mcVal[0];
                                $stockMC = $valueRMS['stock'] * $mcVal['value'] - $minStock;
                                if ($stockMC >= 0) {
                                    $minMCStock = $minStock;
                                } else {
                                    $minMCStock = $valueRMS['stock'];
                                }
                                $valueRMS['id_metric'] = $valueR['id_metric'];
                                $valueRMS['stock'] = ($valueRMS['stock'] * $mcVal['value']) - $minMCStock;
                                $rmsID = $valueRMS['id'];
                                $rmsMetricID = $valueRMS['id_metric'];
                                $rmsStock = $valueRMS['stock'];
                                $updateStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`='$rmsStock', `id_metric`='$rmsMetricID' WHERE id='$rmsID'");
                            } else {
                                $mcVal  = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                                $mcVal = $mcVal[0];
                                $valueR['id_metric'] = $valueRMS['id_metric'];
                                $minStock = $minStock * $mcVal['value'];
                                $stockMC = ($minStock - $valueRMS['stock']);
                                if ($stockMC >= 0) {
                                    $minMCStock = $minStock;
                                } else {
                                    $minMCStock = $valueRMS['stock'];
                                }
                                $valueRMS['id_metric'] = $valueR['id_metric'];
                                $valueRMS['stock'] = ($valueRMS['stock']) - $minStock;
                                $minStock =  $stockMC;
                                $rmsID = $valueRMS['id'];
                                $rmsMetricID = $valueRMS['id_metric'];
                                $rmsStock = $valueRMS['stock'];
                                $updateStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`='$rmsStock', `id_metric`='$findMC' WHERE id='$rmsID'");
                            }
                        }
                    }
                }
            }
        }
    }
}
$idInsert = "";
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $today = date("Y-m-d");
    // POST DATA
    $obj = json_decode(file_get_contents('php://input'));
    $productionQty = $obj->qty;
    $sfgID = $obj->sfgID;
    $metricID = $obj->metricID;
    $getRecipes = mysqli_query($db_conn, "SELECT id_raw, qty, id_metric FROM recipe WHERE deleted_at IS NULL AND sfg_id='$sfgID'");
    $recipes = mysqli_fetch_all($getRecipes, MYSQLI_ASSOC);
    reduce_recipe($db_conn, $recipes, $productionQty);
    $insertStock = mysqli_query($db_conn, "INSERT INTO raw_material_stock SET id_raw_material='$sfgID', stock=stock+'$productionQty', id_metric='$metricID'");
    if ($insertStock) {
        $success = 1;
        $status = 200;
        $msg = "Berhasil produksi";
    } else {
        $success = 0;
        $status = 204;
        $msg = "Gagal produksi. Mohon coba lagi";
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "id" => $idInsert]);
