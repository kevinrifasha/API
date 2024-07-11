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
    }

    if($newDateFormat == 1){
        if($all == "1") {
            $q = "SELECT rm.id, rm.name, rm.id_partner, rm.id_master, c.name AS categoryName, CASE WHEN rm.level=1 THEN 'Setengah Jadi' ELSE 'Bahan Baku' END AS type, m.name AS metricName, SUM(sm.gr) AS gr, SUM(sm.qty) AS qty, SUM(sm.adjustment) AS adjustment, IFNULL(SUM(sm.returned),0) AS qtyReturned, IFNULL(SUM(sm.produced),0) AS qtyReturned2 FROM raw_material rm JOIN rm_categories c ON c.id=rm.category_id JOIN metric m ON m.id=rm.id_metric LEFT JOIN stock_movements sm ON sm.raw_id = rm.id WHERE rm.deleted_at IS NULL AND rm.id_master='$idMaster' AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo' GROUP BY rm.id ORDER BY c.id ASC";
        } else {
            $q = "SELECT rm.id, rm.name, rm.id_partner, rm.id_master, c.name AS categoryName, CASE WHEN rm.level=1 THEN 'Setengah Jadi' ELSE 'Bahan Baku' END AS type, m.name AS metricName, SUM(sm.gr) AS gr, SUM(sm.qty) AS qty, SUM(sm.adjustment) AS adjustment, IFNULL(SUM(sm.returned),0) AS qtyReturned, IFNULL(SUM(sm.produced),0) AS qtyReturned2 FROM raw_material rm JOIN rm_categories c ON c.id=rm.category_id JOIN metric m ON m.id=rm.id_metric LEFT JOIN stock_movements sm ON sm.raw_id = rm.id  WHERE rm.deleted_at IS NULL AND rm.id_partner='$partnerID' AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo' GROUP BY rm.id ORDER BY c.id ASC"; 
        }
        
        $getNames = mysqli_query($db_conn, $q);
        if (mysqli_num_rows($getNames) > 0) {
            $success = 1;
            $status = 200;
            $msg = "Data ditemukan";
            $i = 0;
            $namesRM = mysqli_fetch_all($getNames, MYSQLI_ASSOC);
            foreach ($namesRM as $x) {
                $rawID = $x['id'];
                $partner_id = $x['id_partner'];
                $masterID = $x['id_master'];
                $res[$i]['id'] = $x['id'];
                $res[$i]['categoryName'] = $x['categoryName'];
                $res[$i]['type'] = $x['type'];
                $res[$i]['name'] = $x['name'];
                $res[$i]['metricName'] = $x['metricName'];
                $res[$i]["receivedQty"] = $x["gr"];
                $res[$i]["salesQty"] = $x["qty"];
                $res[$i]["adjustedQty"] = $x["adjustment"];
                $res[$i]["initialQty"] = $x["remaining"];
                $getInitial = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL  AND sm.created_at <= '$dateFrom' ORDER BY id DESC LIMIT 1");
                if (mysqli_num_rows($getInitial) > 0) {
                    $resInitial = mysqli_fetch_all($getInitial, MYSQLI_ASSOC);
                    if ($resInitial[0]['remaining'] == null) {
                        $res[$i]['initialQty'] = "0";
                    } else {
                        $res[$i]['initialQty'] = $resInitial[0]['remaining'];
                    }
                } else {
                     $res[$i]['initialQty'] = "0";
                }
                $getFinal = mysqli_query($db_conn, "SELECT IFNULL(remaining,0) AS remaining FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND sm.created_at <='$dateTo' ORDER BY id DESC LIMIT 1");
    
                if (mysqli_num_rows($getFinal) > 0) {
                    $resFinal = mysqli_fetch_all($getFinal, MYSQLI_ASSOC);
                    $res[$i]['finalQty'] = $resFinal[0]['remaining'];
                } else {
                    $res[$i]['finalQty'] = "0";
                }
                
                $res[$i]['returnedQty'] = $x['qtyReturned'];
                $res[$i]['producedQty'] = $x['qtyReturned2'];
                $i++;
            }
        }
        
        if($all == "1") {
            $query = "SELECT m.id, m.nama, m.id_partner, p.id_master, c.name AS categoryName, m.stock, SUM(sm.gr) AS gr, SUM(sm.qty) AS qty, SUM(sm.adjustment) AS adjustment, SUM(sm.returned) AS returned FROM menu m JOIN categories c ON c.id=m.id_category JOIN partner p ON p.id = m.id_partner LEFT JOIN stock_movements sm ON sm.menu_id=m.id WHERE m.deleted_at IS NULL AND p.id_master='$idMaster' AND m.is_recipe=0 AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo' GROUP BY m.id ORDER BY c.sequence ASC";
        } else {
            $query = "SELECT m.id, m.nama, m.id_partner, p.id_master, c.name AS categoryName, m.stock, SUM(sm.gr) AS gr, SUM(sm.qty) AS qty, SUM(sm.adjustment) AS adjustment, SUM(sm.returned) AS returned  FROM menu m JOIN categories c ON c.id=m.id_category JOIN partner p ON p.id = m.id_partner LEFT JOIN stock_movements sm ON sm.menu_id = m.id WHERE m.deleted_at IS NULL AND m.id_partner='$partnerID' AND m.is_recipe=0 AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo' GROUP BY m.id ORDER BY c.sequence ASC";
        }
        
        $getNames = mysqli_query($db_conn, $query);
        
        $j = $i+1;
        
        if (mysqli_num_rows($getNames) > 0) {
            $names = mysqli_fetch_all($getNames, MYSQLI_ASSOC);
            foreach ($names as $x) {
                $menuID = $x['id'];
                $partner_id = $x['id_partner'];
                $masterID = $x['id_master'];
                $res[$j]['id'] = $x['id'];
                $res[$j]['categoryName'] = $x['categoryName'];
                $res[$j]['type'] = "Bahan Jadi";
                $res[$j]['name'] = $x['nama'];
                $res[$j]['metricName'] = "PCS";
                $res[$j]['initialQty'] = "0";
                $res[$j]["receivedQty"] = $x["gr"];
                $res[$j]["salesQty"] = $x["qty"];
                $res[$j]["adjustedQty"] = $x["adjustment"];
                $getInitial = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.created_at <= '$dateFrom' ORDER BY id ASC LIMIT 1");
                if (mysqli_num_rows($getInitial) > 0) {
                    $resInitial = mysqli_fetch_all($getInitial, MYSQLI_ASSOC);
    
                    if ($resInitial[0]['remaining'] == null) {
                        $res[$j]['initialQty'] = "0";
                    } else {
                        $res[$j]['initialQty'] = $resInitial[0]['remaining'];
                    }
                } else {
                    $res[$j]['initialQty'] = "0";
                }
                $getFinal = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.created_at <='$dateTo' ORDER BY id DESC LIMIT 1");
                if (mysqli_num_rows($getFinal) > 0) {
                    $resFinal = mysqli_fetch_all($getFinal, MYSQLI_ASSOC);
                    if ($resFinal[0]['remaining'] == null) {
                        $res[$j]['finalQty'] = "0";
                    } else {
                        $res[$j]['finalQty'] = $resFinal[0]['remaining'];
                    }
                } else {
                    $res[$j]['finalQty'] = "0";
                }

                $res[$j]['returnedQty'] = $x['returned'];
                $res[$j]['producedQty'] = 0;
                
                $j++;
            }
        }
        
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
    else 
    {
        if($all == "1") {
            $q = "SELECT rm.id, rm.name, rm.id_partner, rm.id_master, c.name AS categoryName, CASE WHEN rm.level=1 THEN 'Setengah Jadi' ELSE 'Bahan Baku' END AS type, m.name AS metricName FROM raw_material rm JOIN rm_categories c ON c.id=rm.category_id JOIN metric m ON m.id=rm.id_metric WHERE rm.deleted_at IS NULL AND rm.id_master='$idMaster'  ORDER BY c.id ASC";
        } else {
            $q = "SELECT rm.id, rm.name, rm.id_partner, rm.id_master, c.name AS categoryName, CASE WHEN rm.level=1 THEN 'Setengah Jadi' ELSE 'Bahan Baku' END AS type, m.name AS metricName FROM raw_material rm JOIN rm_categories c ON c.id=rm.category_id JOIN metric m ON m.id=rm.id_metric WHERE rm.deleted_at IS NULL AND rm.id_partner='$partnerID'  ORDER BY c.id ASC";
        }
        
        $getNames = mysqli_query($db_conn, $q);
        if (mysqli_num_rows($getNames) > 0) {
            $success = 1;
            $status = 200;
            $msg = "Data ditemukan";
            $i = 0;
            $names = mysqli_fetch_all($getNames, MYSQLI_ASSOC);
            foreach ($names as $x) {
                $rawID = $x['id'];
                $partner_id = $x['id_partner'];
                $masterID = $x['id_master'];
                $res[$i]['id'] = $x['id'];
                $res[$i]['categoryName'] = $x['categoryName'];
                $res[$i]['type'] = $x['type'];
                $res[$i]['name'] = $x['name'];
                $res[$i]['metricName'] = $x['metricName'];
                
                $qGR = "SELECT SUM(sm.gr) AS qty FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND master_id='$masterID' AND partner_id='$partner_id'";
                
                $getGR = mysqli_query($db_conn, $qGR);
                
                $resGR = mysqli_fetch_all($getGR, MYSQLI_ASSOC);
                if ($resGR[0]['qty'] == null) {
                    $res[$i]['receivedQty'] = "0";
                } else {
                    $res[$i]['receivedQty'] = $resGR[0]['qty'];
                }
                $getSales = mysqli_query($db_conn, "SELECT SUM(sm.qty) AS qty FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo'");
                $resSales = mysqli_fetch_all($getSales, MYSQLI_ASSOC);
                if ($resSales[0]['qty'] == null) {
                    $res[$i]['salesQty'] = "0";
                } else {
                    $res[$i]['salesQty'] = $resSales[0]['qty'];
                }
                
                $getAdjustment = mysqli_query($db_conn, "SELECT SUM(sm.adjustment) AS qty FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND master_id='$masterID' AND partner_id='$partner_id'");
                
                $resAdjustment = mysqli_fetch_all($getAdjustment, MYSQLI_ASSOC);
                if ($resAdjustment[0]['qty'] == null) {
                    $res[$i]['adjustedQty'] = "0";
                } else {
                    $res[$i]['adjustedQty'] = $resAdjustment[0]['qty'];
                }
                $getInitial = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL  AND DATE(sm.created_at) <= '$dateFrom' ORDER BY id DESC LIMIT 1");
                if (mysqli_num_rows($getInitial) > 0) {
                    $resInitial = mysqli_fetch_all($getInitial, MYSQLI_ASSOC);
    
                    if ($resInitial[0]['remaining'] == null) {
                        $res[$i]['initialQty'] = "0";
                    } else {
                        $res[$i]['initialQty'] = $resInitial[0]['remaining'];
                    }
                } else {
                    // $getInitial = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL ORDER BY id ASC LIMIT 1");
                    // if (mysqli_num_rows($getInitial) > 0) {
                    //     $resInitial = mysqli_fetch_all($getInitial, MYSQLI_ASSOC);
                    //     $res[$i]['initialQty'] = $resInitial[0]['remaining'];
                    // } else {
                    //     $res[$i]['initialQty'] = "0";
                    // }
                     $res[$i]['initialQty'] = "0";
                }
                $getFinal = mysqli_query($db_conn, "SELECT IFNULL(remaining,0) AS remaining FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND DATE(sm.created_at)<='$dateTo' ORDER BY id DESC LIMIT 1");
    
                if (mysqli_num_rows($getFinal) > 0) {
                    $resFinal = mysqli_fetch_all($getFinal, MYSQLI_ASSOC);
                    $res[$i]['finalQty'] = $resFinal[0]['remaining'];
                } else {
                    $res[$i]['finalQty'] = "0";
                }
    
                $getReturned = mysqli_query($db_conn, "SELECT IFNULL(SUM(sm.returned),0) AS qty, IFNULL(SUM(sm.produced),0) AS qty2 FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo'");
                $resReturned = mysqli_fetch_all($getReturned, MYSQLI_ASSOC);
                $res[$i]['returnedQty'] = $resReturned[0]['qty'];
                $res[$i]['producedQty'] = $resReturned[0]['qty2'];
                $i++;
            }
        }
        
        if($all == "1") {
            $query = "SELECT m.id, m.nama, m.id_partner, p.id_master, c.name AS categoryName, m.stock FROM menu m JOIN categories c ON c.id=m.id_category JOIN partner p ON p.id = m.id_partner WHERE m.deleted_at IS NULL AND p.id_master='$idMaster' AND m.is_recipe=0 ORDER BY c.sequence ASC";
        } else {
            $query = "SELECT m.id, m.nama, m.id_partner, p.id_master, c.name AS categoryName, m.stock FROM menu m JOIN categories c ON c.id=m.id_category JOIN partner p ON p.id = m.id_partner WHERE m.deleted_at IS NULL AND m.id_partner='$partnerID' AND m.is_recipe=0 ORDER BY c.sequence ASC";
        }
        
        $getNames = mysqli_query($db_conn, $query);
        
        if (mysqli_num_rows($getNames) > 0) {
            $names = mysqli_fetch_all($getNames, MYSQLI_ASSOC);
            $j = 0;
            foreach ($names as $x) {
                $menuID = $x['id'];
                $partner_id = $x['id_partner'];
                $masterID = $x['id_master'];
                $array[$j]['id'] = $x['id'];
                $array[$j]['categoryName'] = $x['categoryName'];
                $array[$j]['type'] = "Bahan Jadi";
                $array[$j]['name'] = $x['nama'];
                $array[$j]['metricName'] = "PCS";
                $array[$j]['initialQty'] = "0";
                $getGR = mysqli_query($db_conn, "SELECT SUM(sm.gr) AS qty FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND master_id='$masterID' AND partner_id='$partner_id'");
                $resGR = mysqli_fetch_all($getGR, MYSQLI_ASSOC);
                if ($resGR[0]['qty'] == null) {
                    $array[$j]['receivedQty'] = "0";
                } else {
                    $array[$j]['receivedQty'] = $resGR[0]['qty'];
                }
                $getSales = mysqli_query($db_conn, "SELECT SUM(sm.qty) AS qty FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND sm.partner_id='$partner_id'");
                $resSales = mysqli_fetch_all($getSales, MYSQLI_ASSOC);
                if ($resSales[0]['qty'] == null) {
                    $array[$j]['salesQty'] = "0";
                } else {
                    $array[$j]['salesQty'] = $resSales[0]['qty'];
                }
                $getAdjustment = mysqli_query($db_conn, "SELECT SUM(sm.adjustment) AS qty FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND master_id='$masterID' AND partner_id='$partner_id'");
                $resAdjustment = mysqli_fetch_all($getAdjustment, MYSQLI_ASSOC);
                if ($resAdjustment[0]['qty'] == null) {
                    $array[$j]['adjustedQty'] = "0";
                } else {
                    $array[$j]['adjustedQty'] = $resAdjustment[0]['qty'];
                }
                $getInitial = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY id ASC LIMIT 1");
                if (mysqli_num_rows($getInitial) > 0) {
                    $resInitial = mysqli_fetch_all($getInitial, MYSQLI_ASSOC);
    
                    if ($resInitial[0]['remaining'] == null) {
                        $array[$j]['initialQty'] = "0";
                    } else {
                        $array[$j]['initialQty'] = $resInitial[0]['remaining'];
                    }
                } else {
                    $array[$j]['initialQty'] = "0";
                }
                $getFinal = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND DATE(sm.created_at)<='$dateTo' ORDER BY id DESC LIMIT 1");
                if (mysqli_num_rows($getFinal) > 0) {
                    $resFinal = mysqli_fetch_all($getFinal, MYSQLI_ASSOC);
                    if ($resFinal[0]['remaining'] == null) {
                        $array[$j]['finalQty'] = "0";
                    } else {
                        $array[$j]['finalQty'] = $resFinal[0]['remaining'];
                    }
                } else {
                    $array[$j]['finalQty'] = "0";
                }
                $getReturned = mysqli_query($db_conn, "SELECT SUM(sm.returned) AS qty FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND partner_id='$partner_id'");
                $resReturned = mysqli_fetch_all($getReturned, MYSQLI_ASSOC);
                if ($resReturned[0]['qty'] == null) {
                    $array[$j]['returnedQty'] = "0";
                } else {
                    $array[$j]['returnedQty'] = $resReturned[0]['qty'];
                }
                $array[$j]['producedQty'] = "0";
                $j++;
            }
        }
        
        if(count($res) > 0) {
            $res = array_merge($res, $array);
            
            $prefix = ' ';
            echo '{"source":[';
            foreach($res as $source) {
              if(json_encode($source)){
                echo $prefix, json_encode($source);
                $prefix = ',';
              }
            }
            echo '],"msg":"Success","status":200,"success":1}';

        } else {
            $success = 0;
            $msg = 'Data not found';
            $status = 203;
            echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "source" => $res]);
        }
    }
    
}


