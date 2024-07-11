<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';

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
$token = '';

foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt', $token));
$idMaster = $tokenDecoded->masterID;
$value = array();
$success = 0;
$msg = 'Failed';
$status = 0;
$all = "0";
$array = [];

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
} else {
    $res = [];
    $partnerID = $_GET['partnerID'];
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    
    $newDateFormat = 0;

    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    } else {
        $dateTo = $dateTo . " 23:59:59";
        $dateFrom = $dateFrom . " 00:00:00";
        $newDateFormat = 1;
    } 

    if($newDateFormat == 1){
        if($all == "1") {
            $q = "WITH InitStock AS (
                    SELECT * 
                    FROM( SELECT raw_id AS item_id, remaining as initStock
                            FROM
                                stock_movements
                            WHERE
                                deleted_at IS NULL AND created_at <= '$dateFrom' 
                            ORDER BY id DESC
                            LIMIT 18446744073709551615
                     ) as tmp
                    GROUP BY item_id
                ), InitMenuStock AS (
                    SELECT * 
                    FROM( SELECT menu_id AS item_id, remaining as initStock
                            FROM
                                stock_movements
                            WHERE
                                deleted_at IS NULL AND created_at <= '$dateFrom' 
                            ORDER BY id DESC
                            LIMIT 18446744073709551615
                     ) as tmp
                    GROUP BY item_id
                ), FinalStock AS (
                    SELECT * 
                    FROM( SELECT raw_id AS item_id, remaining as finalStock
                            FROM
                                stock_movements
                            WHERE
                                deleted_at IS NULL AND created_at <= '$dateTo' 
                            ORDER BY id DESC
                            LIMIT 18446744073709551615
                     ) as tmp
                    GROUP BY item_id
                ), FinalMenuStock AS (
                    SELECT * 
                    FROM( SELECT menu_id AS item_id, remaining as finalStock
                            FROM
                                stock_movements
                            WHERE
                                deleted_at IS NULL AND created_at <= '$dateTo' 
                            ORDER BY id DESC
                            LIMIT 18446744073709551615
                     ) as tmp
                    GROUP BY item_id
                )
                
                
                SELECT
                    rm.id,
                    c.name AS categoryName,
                    CASE WHEN rm.level = 1 THEN 'Setengah Jadi' ELSE 'Bahan Baku' END AS type,
                    rm.name,
                    m.name AS metricName,
                    SUM(sm.gr) AS receivedQty,
                    SUM(sm.qty) AS salesQty,
                    SUM(sm.adjustment) AS adjustedQty,
                    IFNULL(latest_init.initStock, 0) + SUM(sm.initial) AS initialQty,
                    IFNULL(latest_final.finalStock, 0) AS finalQty,
                    IFNULL(SUM(sm.returned), 0) AS returnedQty,
                    IFNULL(SUM(sm.produced), 0) AS producedQty
                FROM
                    raw_material rm
                JOIN rm_categories c ON
                    c.id = rm.category_id
                JOIN metric m ON
                    m.id = rm.id_metric
                LEFT JOIN stock_movements sm ON
                    sm.raw_id = rm.id
                LEFT JOIN InitStock AS latest_init ON
                    latest_init.item_id = rm.id
                LEFT JOIN FinalStock AS latest_final ON
                    latest_final.item_id = rm.id
                WHERE
                    rm.deleted_at IS NULL AND rm.id_master = '$idMaster' AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo'
                GROUP BY
                    rm.id
                
                UNION
                
                SELECT
                    m.id,
                    c.name AS categoryName,
                    'Bahan Jadi' AS type,
                    m.nama,
                    'PCS' AS metricName,
                    SUM(sm.gr) AS receivedQty,
                    SUM(sm.qty) AS salesQty,
                    SUM(sm.adjustment) AS adjustedQty,
                    IFNULL(latest_init.initStock, 0) + SUM(sm.initial) AS initialQty,
                    IFNULL(latest_final.finalStock, 0) AS finalQty,
                    SUM(sm.returned) AS returnedQty,
                    0 AS producedQty
                FROM
                    menu m
                JOIN categories c ON
                    c.id = m.id_category
                LEFT JOIN stock_movements sm ON
                    sm.menu_id = m.id
                LEFT JOIN InitMenuStock AS latest_init ON
                    latest_init.item_id = m.id
                LEFT JOIN FinalMenuStock AS latest_final ON
                    latest_final.item_id = m.id
                WHERE
                    m.deleted_at IS NULL AND m.id_master = '$idMaster' AND m.is_recipe = 0
                    AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo'
                GROUP BY
                    m.id
                ORDER BY
                    categoryName";
        } else {
            $q = "WITH InitStock AS (
                    SELECT * 
                    FROM( SELECT raw_id AS item_id, remaining as initStock
                            FROM
                                stock_movements
                            WHERE
                                deleted_at IS NULL AND created_at <= '$dateFrom' 
                            ORDER BY id DESC
                            LIMIT 18446744073709551615
                     ) as tmp
                    GROUP BY item_id
                ), InitMenuStock AS (
                    SELECT * 
                    FROM( SELECT menu_id AS item_id, remaining as initStock
                            FROM
                                stock_movements
                            WHERE
                                deleted_at IS NULL AND created_at <= '$dateFrom' 
                            ORDER BY id DESC
                            LIMIT 18446744073709551615
                     ) as tmp
                    GROUP BY item_id
                ), FinalStock AS (
                    SELECT * 
                    FROM( SELECT raw_id AS item_id, remaining as finalStock
                            FROM
                                stock_movements
                            WHERE
                                deleted_at IS NULL AND created_at <= '$dateTo' 
                            ORDER BY id DESC
                            LIMIT 18446744073709551615
                     ) as tmp
                    GROUP BY item_id
                ), FinalMenuStock AS (
                    SELECT * 
                    FROM( SELECT menu_id AS item_id, remaining as finalStock
                            FROM
                                stock_movements
                            WHERE
                                deleted_at IS NULL AND created_at <= '$dateTo' 
                            ORDER BY id DESC
                            LIMIT 18446744073709551615
                     ) as tmp
                    GROUP BY item_id
                )
                
                
                SELECT
                    rm.id,
                    c.name AS categoryName,
                    CASE WHEN rm.level = 1 THEN 'Setengah Jadi' ELSE 'Bahan Baku' END AS type,
                    rm.name,
                    m.name AS metricName,
                    SUM(sm.gr) AS receivedQty,
                    SUM(sm.qty) AS salesQty,
                    SUM(sm.adjustment) AS adjustedQty,
                    IFNULL(latest_init.initStock, 0) + SUM(sm.initial) AS initialQty,
                    IFNULL(latest_final.finalStock, 0) AS finalQty,
                    IFNULL(SUM(sm.returned), 0) AS returnedQty,
                    IFNULL(SUM(sm.produced), 0) AS producedQty
                FROM
                    raw_material rm
                JOIN rm_categories c ON
                    c.id = rm.category_id
                JOIN metric m ON
                    m.id = rm.id_metric
                LEFT JOIN stock_movements sm ON
                    sm.raw_id = rm.id
                LEFT JOIN InitStock AS latest_init ON
                    latest_init.item_id = rm.id
                LEFT JOIN FinalStock AS latest_final ON
                    latest_final.item_id = rm.id
                WHERE
                    rm.deleted_at IS NULL AND rm.id_partner = '$partnerID' AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo'
                GROUP BY
                    rm.id
                
                UNION
                
                SELECT
                    m.id,
                    c.name AS categoryName,
                    'Bahan Jadi' AS type,
                    m.nama,
                    'PCS' AS metricName,
                    SUM(sm.gr) AS receivedQty,
                    SUM(sm.qty) AS salesQty,
                    SUM(sm.adjustment) AS adjustedQty,
                    IFNULL(latest_init.initStock, 0) + SUM(sm.initial) AS initialQty,
                    IFNULL(latest_final.finalStock, 0) AS finalQty,
                    SUM(sm.returned) AS returnedQty,
                    0 AS producedQty
                FROM
                    menu m
                JOIN categories c ON
                    c.id = m.id_category
                LEFT JOIN stock_movements sm ON
                    sm.menu_id = m.id
                LEFT JOIN InitMenuStock AS latest_init ON
                    latest_init.item_id = m.id
                LEFT JOIN FinalMenuStock AS latest_final ON
                    latest_final.item_id = m.id
                WHERE
                    m.deleted_at IS NULL AND m.id_partner = '$partnerID' AND m.is_recipe = 0
                    AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo'
                GROUP BY
                    m.id
                ORDER BY
                    categoryName;"; 
        }
        
        $res = mysqli_query($db_conn, $q);
        $success = 1;
        $status = 200;
        $msg = "Data ditemukan";
        $res = mysqli_fetch_all($res, MYSQLI_ASSOC);
        
        if(count($res) > 0) {
            $res = array_values($res);
            
            $prefix = ' ';
            echo '{"source":[';
            foreach($res as $rawd) {
              if(json_encode($rawd)){
                echo $prefix, json_encode($rawd);
                $prefix = ',';
              }
            }
            echo '],"msg":"Success","status":200,"success":1';
            // echo ',"count":';
            // echo count($res);
            echo '}'; 
            
            $success = 1;
            $msg = 'Success';
            $status = 200;
        } else {
            $success = 0;
            $msg = 'Data not found';
            $status = 203;
            echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "source" => $res]);
        }
    } 
    
}

