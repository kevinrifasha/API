<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
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
$arr = array();
$find = $_GET['text'];

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $q = mysqli_query($db_conn, "SELECT m.id, m.id_partner, m.nama, m.harga, m.Deskripsi, m.category, m.id_category, m.img_data, m.enabled, m.stock, m.hpp, m.harga_diskon, m.is_variant, m.is_recommended, m.is_recipe, m.is_auto_cogs, m.thumbnail , m.nama AS name, c.name as category_name FROM menu m JOIN categories c ON m.id_category=c.id WHERE m.id_partner='$token->id_partner' AND m.is_recipe=0 AND m.deleted_at IS NULL AND m.nama LIKE '%$find%' AND m.deleted_at IS NULL");

    $q1 = mysqli_query($db_conn, "SELECT r.id, r.name, r.reminder_allert, m.name AS mName FROM `raw_material` r JOIN metric m ON r.id_metric = m.id  WHERE r.id_partner='$token->id_partner' AND r.name LIKE '%$find%' AND r.deleted_at IS NULL");

    if (mysqli_num_rows($q) > 0 || mysqli_num_rows($q1) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $i = 0;
        foreach ($res as $r) {
            $arr[$i] = $r;
            $arr[$i]['type'] = "menu";
            $i += 1;
        }

        $res = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        $stock = 0;
        $idm = 0;
        foreach ($res as $value) {
            $rawd = $value;
            $findID = $value['id'];
            $qS = mysqli_query($db_conn, "SELECT raw_material_stock.id, raw_material_stock.id_raw_material, raw_material_stock.stock, raw_material_stock.id_metric, raw_material_stock.exp_date, raw_material_stock.id_goods_receipt_detail, metric.name AS metricName FROM `raw_material_stock` JOIN metric on metric.id=raw_material_stock.id_metric WHERE raw_material_stock.id_raw_material='{$findID}' ORDER BY raw_material_stock.exp_date ASC");
            $resQs = mysqli_fetch_all($qS, MYSQLI_ASSOC);
            $rawd['rawMaterialStocks'] = $resQs;
            foreach ($resQs as $valueRMS) {
                if ($idm == $valueRMS['id_metric'] || $idm == 0) {
                    $stock += $valueRMS['stock'];
                    $idm = $valueRMS['id_metric'];
                    $id = $idm;
                    $allRaw = mysqli_query($db_conn, "SELECT metric_convert.id_metric2, metric.name FROM metric_convert
                    JOIN metric on metric.id=metric_convert.id_metric2 WHERE metric_convert.id_metric1='$id'");
                    $all_raw = mysqli_fetch_all($allRaw, MYSQLI_ASSOC);
                    $arr1 = array();
                    $i = 0;
                    foreach ($all_raw as $key) {
                        $arr1[$i]['id'] = $key['id_metric2'];
                        $arr1[$i]['name'] = $key['name'];
                        $i += 1;
                    }
                    $allRaw1 = mysqli_query($db_conn, "SELECT id,name FROM `metric` WHERE id='$id'");
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                    foreach ($all_raw1 as $key) {
                        $arr1[$i]['id'] = $key['id'];
                        $arr1[$i]['name'] = $key['name'];
                        $i += 1;
                    }
                } else {
                    $findMC = $valueRMS['id_metric'];

                    $id = $findMC;
                    $allRaw = mysqli_query($db_conn, "SELECT metric_convert.id_metric2, metric.name FROM metric_convert
                    JOIN metric on metric.id=metric_convert.id_metric2 WHERE metric_convert.id_metric1='$id'");
                    $all_raw = mysqli_fetch_all($allRaw, MYSQLI_ASSOC);
                    $arr1 = array();
                    $i = 0;
                    foreach ($all_raw as $key) {
                        $arr1[$i]['id'] = $key['id_metric2'];
                        $arr1[$i]['name'] = $key['name'];
                        $i += 1;
                    }
                    $allRaw1 = mysqli_query($db_conn, "SELECT metric_convert.id_metric1, metric.name FROM metric_convert
                    JOIN metric on metric.id=metric_convert.id_metric1 WHERE metric_convert.id_metric2='$id'");
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                    foreach ($all_raw1 as $key) {
                        $arr1[$i]['id'] = $key['id_metric1'];
                        $arr1[$i]['name'] = $key['name'];
                        $i += 1;
                    }

                    $allRaw1 = mysqli_query($db_conn, "SELECT id,name FROM `metric` WHERE id='$id'");
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                    foreach ($all_raw1 as $key) {
                        $arr1[$i]['id'] = $key['id'];
                        $arr1[$i]['name'] = $key['name'];
                        $i += 1;
                    }
                    $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$idm}' AND `id_metric2`='{$findMC}' ");
                    
                    // Yang ini mungkin maksdunya dari $qMC bukan dari $mcVal
                    // soalnya $mcVal itu di diatas tidak ada
                    // if ($mcVal == false) {
                    if ($qMC == false) {
                        $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric2='{$idm}' AND `id_metric1`='{$findMC}' ");
                        if (mysqli_num_rows($qMC) > 0) {
                            $mcVal = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                            $stockMC = $valueRMS['stock'] * ($mcVal['value'] ?? 1);
                        } else {
                            $stockMC = $valueRMS['stock'];
                        }

                        $stock += $stockMC;
                    } else {
                        $mcVal = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                        $stockMC = $minStock * $mcVal['value'];
                        $stock += $stockMC;
                        $idm = $valueRMS['id_metric'];
                    }
                }
            }
            $rawd['relevantMetrics'] = $arr1;
            $rawd['stock'] = $stock;
            $rawd['stockMetricId'] = $idm;
            $qMN = mysqli_query($db_conn, "SELECT name FROM `metric` WHERE id='$idm'");
            $dataM = mysqli_fetch_all($qMN, MYSQLI_ASSOC);
            if ($dataM != false) {
                $rawd['stockMetricName'] = $dataM[0]['name'];
            } else {
                $rawd['stockMetricName'] = "wrong";
            }
            $rawd['type'] = "raw_material";
            array_push($arr, $rawd);
        }


        $success = 1;
        $status = 200;
        $msg = "Success";
    } else {
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "menus" => $arr]);
