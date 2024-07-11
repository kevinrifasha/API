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
$res = array();

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
    if ($_GET['partner_type'] == 7) {
        $page = $_GET['page'];
        $load = $_GET['load'];
        $finish = $load * $page;
        $start = $finish - $load;
        $q = mysqli_query($db_conn, "SELECT r.id, m.id as mID, r.name, r.reminder_allert, m.name AS mName, r.unit_price, r.id_metric_price, m1.name AS name_metric_price, CASE WHEN COUNT(recipe.id) >0 THEN 1 ELSE 0 END AS is_recipe FROM `raw_material` r JOIN metric m ON r.id_metric = m.id JOIN metric m1 ON m1.id=r.id_metric_price LEFT JOIN recipe ON r.id=recipe.id_raw LEFT JOIN menu ON menu.id=recipe.id_menu AND menu.deleted_at IS NULL WHERE r.id_partner='$token->id_partner' AND r.deleted_at IS NULL GROUP BY r.id LIMIT $start,$load");
    } else {
        $q = mysqli_query($db_conn, "SELECT r.id, m.id as mID, r.name, r.reminder_allert, r.yield, m.name AS mName, r.unit_price, r.id_metric_price, m1.name AS name_metric_price, CASE WHEN COUNT(recipe.id) >0 THEN 1 ELSE 0 END AS is_recipe FROM `raw_material` r JOIN metric m ON r.id_metric = m.id JOIN metric m1 ON m1.id=r.id_metric_price LEFT JOIN recipe ON r.id=recipe.id_raw LEFT JOIN menu ON menu.id=recipe.id_menu AND menu.deleted_at IS NULL WHERE r.id_partner='$token->id_partner' AND r.deleted_at IS NULL GROUP BY r.id ORDER BY r.id DESC");
    }
    $res1 = array();
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $stock = 0;
        $idm = 0;
        foreach ($res as $value) {
            $rawd = $value;
            $find = $value['id'];
            $today = date("Y-m-d");
            $qS = mysqli_query($db_conn, "SELECT raw_material_stock.id, raw_material_stock.id_raw_material, raw_material_stock.stock, raw_material_stock.id_metric, raw_material_stock.exp_date, raw_material_stock.id_goods_receipt_detail, metric.name AS metricName FROM `raw_material_stock` JOIN metric on metric.id=raw_material_stock.id_metric WHERE raw_material_stock.id_raw_material='{$find}' ORDER BY raw_material_stock.id DESC");
            $resQs = mysqli_fetch_all($qS, MYSQLI_ASSOC);
            $arr = array();
            $rawd['rawMaterialStocks'] = $resQs;
            foreach ($resQs as $valueRMS) {
                if ($idm == $valueRMS['id_metric'] || $idm == 0) {
                    $stock += $valueRMS['stock'];
                    $idm = $valueRMS['id_metric'];
                    $id = $idm;
                    $allRaw = mysqli_query($db_conn, "SELECT metric_convert.id_metric2, metric.name FROM metric_convert
                    JOIN metric on metric.id=metric_convert.id_metric2 WHERE metric_convert.id_metric1='$id'");
                    $all_raw = mysqli_fetch_all($allRaw, MYSQLI_ASSOC);
                    foreach ($all_raw as $key) {
                        array_push($arr, array(
                            'id' => $key['id_metric2'],
                            'name' => $key['name'],
                        ));
                    }
                    $allRaw1 = mysqli_query($db_conn, "SELECT metric_convert.id_metric1, metric.name FROM metric_convert
                    JOIN metric on metric.id=metric_convert.id_metric1 WHERE metric_convert.id_metric2='$id'");
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                    foreach ($all_raw1 as $key) {
                        array_push($arr, array(
                            'id' => $key['id_metric1'],
                            'name' => $key['name'],
                        ));
                    }
                    $allRaw1 = mysqli_query($db_conn, "SELECT id,name FROM `metric` WHERE id='$id'");
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                    foreach ($all_raw1 as $key) {
                        array_push($arr, array(
                            'id' => $key['id'],
                            'name' => $key['name'],
                        ));
                    }
                } else {
                    $findMC = $valueRMS['id_metric'];
                    $id = $findMC;
                    $allRaw = mysqli_query($db_conn, "SELECT metric_convert.id_metric2, metric.name FROM metric_convert
                    JOIN metric on metric.id=metric_convert.id_metric2 WHERE metric_convert.id_metric1='$id'");
                    $all_raw = mysqli_fetch_all($allRaw, MYSQLI_ASSOC);
                    foreach ($all_raw as $key) {
                        array_push($arr, array(
                            'id' => $key['id_metric2'],
                            'name' => $key['name'],
                        ));
                    }
                    $allRaw1 = mysqli_query($db_conn, "SELECT metric_convert.id_metric1, metric.name FROM metric_convert
                    JOIN metric on metric.id=metric_convert.id_metric1 WHERE metric_convert.id_metric2='$id'");
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                    foreach ($all_raw1 as $key) {
                        array_push($arr, array(
                            'id' => $key['id_metric1'],
                            'name' => $key['name'],
                        ));
                    }
                    $allRaw1 = mysqli_query($db_conn, "SELECT id,name FROM `metric` WHERE id='$id'");
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                    foreach ($all_raw1 as $key) {
                        array_push($arr, array(
                            'id' => $key['id'],
                            'name' => $key['name'],
                        ));
                    }
                    $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$idm}' AND `id_metric2`='{$findMC}' ");
                    if (mysqli_num_rows($qMC) <= 0) {
                        $qMC2 = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric2='{$idm}' AND `id_metric1`='{$findMC}'");

                        if (mysqli_num_rows($qMC2) > 0) {
                            $mcVal = mysqli_fetch_all($qMC2, MYSQLI_ASSOC);
                            $stockMC = $valueRMS['stock'] * ((int)$mcVal['value'] ?? 1);
                            $stock += $stockMC;
                        } else {
                            $stock += $valueRMS['stock'];
                        }
                    } else {
                        $mcVal = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                        $stockMC = $minStock * ((int)$mcVal['value'] ?? 1);
                        $stock += $stockMC;
                        $idm = $valueRMS['id_metric'];
                    }
                }
            }
            // $item = array(
            //     'id'=>$key['id_metric2'],
            //     'name'=>$key['name'],
            // );
            $arrNew = array();
            foreach ($arr as $value) {
                if (!in_array($value, $arrNew, true)) {
                    array_push($arrNew, $value);
                }
            }
            $rawd['relevantMetrics'] = $arrNew;
            $rawd['stock'] = $stock;
            $rawd['stockMetricId'] = $idm;
            $qMN = mysqli_query($db_conn, "SELECT name FROM `metric` WHERE id='$idm'");
            $dataM = mysqli_fetch_all($qMN, MYSQLI_ASSOC);
            if ($dataM != false) {
                $rawd['stockMetricName'] = $dataM[0]['name'];
            } else {
                $rawd['stockMetricName'] = "wrong";
            }
            array_push($res1, $rawd);
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
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "rawmaterials" => $res1]);
