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
$data = [];

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
} else {
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];

    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    if($newDateFormat == 1){
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $partnerID = $partner['partner_id'];
                $res = [];
                
                $q = "SELECT rm.id, rm.name, c.name AS categoryName, CASE WHEN rm.level=1 THEN 'Setengah Jadi' ELSE 'Bahan Baku' END AS type, m.name AS metricName FROM raw_material rm JOIN rm_categories c ON c.id=rm.category_id JOIN metric m ON m.id=rm.id_metric WHERE rm.deleted_at IS NULL AND rm.id_partner='$partnerID' ORDER BY c.id ASC";
                $getNames = mysqli_query($db_conn, $q);
                if (mysqli_num_rows($getNames) > 0) {
                    $success = 1;
                    $status = 200;
                    $msg = "Data ditemukan";
                    $i = 0;
                    $names = mysqli_fetch_all($getNames, MYSQLI_ASSOC);
                    foreach ($names as $x) {
                        $rawID = $x['id'];
                        $res[$i]['id'] = $x['id'];
                        $res[$i]['categoryName'] = $x['categoryName'];
                        $res[$i]['type'] = $x['type'];
                        $res[$i]['name'] = $x['name'];
                        $res[$i]['metricName'] = $x['metricName'];
                        $getGR = mysqli_query($db_conn, "SELECT SUM(sm.gr) AS qty FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND sm.type=0 AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo' AND master_id='$idMaster' AND partner_id='$partnerID'");
                        $resGR = mysqli_fetch_all($getGR, MYSQLI_ASSOC);
                        if ($resGR[0]['qty'] == null) {
                            $res[$i]['receivedQty'] = "0";
                        } else {
                            $res[$i]['receivedQty'] = $resGR[0]['qty'];
                        }
                        $getSales = mysqli_query($db_conn, "SELECT SUM(sm.qty) AS qty FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND sm.type=0 AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo'");
                        $resSales = mysqli_fetch_all($getSales, MYSQLI_ASSOC);
                        if ($resSales[0]['qty'] == null) {
                            $res[$i]['salesQty'] = "0";
                        } else {
                            $res[$i]['salesQty'] = $resSales[0]['qty'];
                        }
                        $getAdjustment = mysqli_query($db_conn, "SELECT SUM(sm.adjustment) AS qty FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND sm.type=0 AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo' AND master_id='$idMaster' AND partner_id='$partnerID'");
                        $resAdjustment = mysqli_fetch_all($getAdjustment, MYSQLI_ASSOC);
                        if ($resAdjustment[0]['qty'] == null) {
                            $res[$i]['adjustedQty'] = "0";
                        } else {
                            $res[$i]['adjustedQty'] = $resAdjustment[0]['qty'];
                        }
                        $getInitial = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL  AND sm.created_at <='$dateFrom' ORDER BY id DESC LIMIT 1");
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
                        $getFinal = mysqli_query($db_conn, "SELECT IFNULL(remaining,0) AS remaining FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND sm.created_at<='$dateTo' ORDER BY id DESC LIMIT 1");
            
                        if (mysqli_num_rows($getFinal) > 0) {
                            $resFinal = mysqli_fetch_all($getFinal, MYSQLI_ASSOC);
                            $res[$i]['finalQty'] = $resFinal[0]['remaining'];
                        } else {
                            $res[$i]['finalQty'] = "0";
                        }
            
                        $getReturned = mysqli_query($db_conn, "SELECT IFNULL(SUM(sm.returned),0) AS qty, IFNULL(SUM(sm.produced),0) AS qty2 FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND sm.type=0 AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo'");
                        $resReturned = mysqli_fetch_all($getReturned, MYSQLI_ASSOC);
                        $res[$i]['returnedQty'] = $resReturned[0]['qty'];
                        $res[$i]['producedQty'] = $resReturned[0]['qty2'];
                        $i++;
                    }
                }
                $getNames = mysqli_query($db_conn, "SELECT m.id, m.nama, c.name AS categoryName, m.stock FROM menu m JOIN categories c ON c.id=m.id_category WHERE m.deleted_at IS NULL AND m.id_partner='$partnerID' AND m.is_recipe=0 ORDER BY c.sequence ASC");
                if (mysqli_num_rows($getNames) > 0) {
                    $names = mysqli_fetch_all($getNames, MYSQLI_ASSOC);
                    foreach ($names as $x) {
                        $menuID = $x['id'];
                        $res[$i]['id'] = $x['id'];
                        $res[$i]['categoryName'] = $x['categoryName'];
                        $res[$i]['type'] = "Bahan Jadi";
                        $res[$i]['name'] = $x['nama'];
                        $res[$i]['metricName'] = "PCS";
                        $res[$i]['initialQty'] = "0";
                        $getGR = mysqli_query($db_conn, "SELECT SUM(sm.gr) AS qty FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.type=0 AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo' AND master_id='$idMaster' AND partner_id='$partnerID'");
                        $resGR = mysqli_fetch_all($getGR, MYSQLI_ASSOC);
                        if ($resGR[0]['qty'] == null) {
                            $res[$i]['receivedQty'] = "0";
                        } else {
                            $res[$i]['receivedQty'] = $resGR[0]['qty'];
                        }
                        $getSales = mysqli_query($db_conn, "SELECT SUM(sm.qty) AS qty FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.type=0 AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo' AND sm.partner_id='$partnerID'");
                        $resSales = mysqli_fetch_all($getSales, MYSQLI_ASSOC);
                        if ($resSales[0]['qty'] == null) {
                            $res[$i]['salesQty'] = "0";
                        } else {
                            $res[$i]['salesQty'] = $resSales[0]['qty'];
                        }
                        $getAdjustment = mysqli_query($db_conn, "SELECT SUM(sm.adjustment) AS qty FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.type=0 AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo' AND master_id='$idMaster' AND partner_id='$partnerID'");
                        $resAdjustment = mysqli_fetch_all($getAdjustment, MYSQLI_ASSOC);
                        if ($resAdjustment[0]['qty'] == null) {
                            $res[$i]['adjustedQty'] = "0";
                        } else {
                            $res[$i]['adjustedQty'] = $resAdjustment[0]['qty'];
                        }
                        $getInitial = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo' ORDER BY id ASC LIMIT 1");
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
                        $getFinal = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.created_at<='$dateTo' ORDER BY id DESC LIMIT 1");
                        if (mysqli_num_rows($getFinal) > 0) {
                            $resFinal = mysqli_fetch_all($getFinal, MYSQLI_ASSOC);
                            if ($resFinal[0]['remaining'] == null) {
                                $res[$i]['finalQty'] = "0";
                            } else {
                                $res[$i]['finalQty'] = $resFinal[0]['remaining'];
                            }
                        } else {
                            $res[$i]['finalQty'] = "0";
                        }
                        $getReturned = mysqli_query($db_conn, "SELECT SUM(sm.returned) AS qty FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.type=0 AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo' AND partner_id='$partnerID'");
                        $resReturned = mysqli_fetch_all($getReturned, MYSQLI_ASSOC);
                        if ($resReturned[0]['qty'] == null) {
                            $res[$i]['returnedQty'] = "0";
                        } else {
                            $res[$i]['returnedQty'] = $resReturned[0]['qty'];
                        }
                        $res[$i]['producedQty'] = "0";
                        $i++;
                    }
                }
                
                $partner['source'] = $res;
             
                if(count($res) > 0) {
                    array_push($data, $partner);
                }   
            }
            
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 203;
            $msg = "Data not found";
        }
    } 
    else
    {
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $partnerID = $partner['partner_id'];
                $res = [];
                
                $q = "SELECT rm.id, rm.name, c.name AS categoryName, CASE WHEN rm.level=1 THEN 'Setengah Jadi' ELSE 'Bahan Baku' END AS type, m.name AS metricName FROM raw_material rm JOIN rm_categories c ON c.id=rm.category_id JOIN metric m ON m.id=rm.id_metric WHERE rm.deleted_at IS NULL AND rm.id_partner='$partnerID' ORDER BY c.id ASC";
                $getNames = mysqli_query($db_conn, $q);
                if (mysqli_num_rows($getNames) > 0) {
                    $success = 1;
                    $status = 200;
                    $msg = "Data ditemukan";
                    $i = 0;
                    $names = mysqli_fetch_all($getNames, MYSQLI_ASSOC);
                    foreach ($names as $x) {
                        $rawID = $x['id'];
                        $res[$i]['id'] = $x['id'];
                        $res[$i]['categoryName'] = $x['categoryName'];
                        $res[$i]['type'] = $x['type'];
                        $res[$i]['name'] = $x['name'];
                        $res[$i]['metricName'] = $x['metricName'];
                        $getGR = mysqli_query($db_conn, "SELECT SUM(sm.gr) AS qty FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND master_id='$idMaster' AND partner_id='$partnerID'");
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
                        $getAdjustment = mysqli_query($db_conn, "SELECT SUM(sm.adjustment) AS qty FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND master_id='$idMaster' AND partner_id='$partnerID'");
                        $resAdjustment = mysqli_fetch_all($getAdjustment, MYSQLI_ASSOC);
                        if ($resAdjustment[0]['qty'] == null) {
                            $res[$i]['adjustedQty'] = "0";
                        } else {
                            $res[$i]['adjustedQty'] = $resAdjustment[0]['qty'];
                        }
                        $getInitial = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL  AND DATE(sm.created_at) <='$dateFrom' ORDER BY id DESC LIMIT 1");
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
                $getNames = mysqli_query($db_conn, "SELECT m.id, m.nama, c.name AS categoryName, m.stock FROM menu m JOIN categories c ON c.id=m.id_category WHERE m.deleted_at IS NULL AND m.id_partner='$partnerID' AND m.is_recipe=0 ORDER BY c.sequence ASC");
                if (mysqli_num_rows($getNames) > 0) {
                    $names = mysqli_fetch_all($getNames, MYSQLI_ASSOC);
                    foreach ($names as $x) {
                        $menuID = $x['id'];
                        $res[$i]['id'] = $x['id'];
                        $res[$i]['categoryName'] = $x['categoryName'];
                        $res[$i]['type'] = "Bahan Jadi";
                        $res[$i]['name'] = $x['nama'];
                        $res[$i]['metricName'] = "PCS";
                        $res[$i]['initialQty'] = "0";
                        $getGR = mysqli_query($db_conn, "SELECT SUM(sm.gr) AS qty FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND master_id='$idMaster' AND partner_id='$partnerID'");
                        $resGR = mysqli_fetch_all($getGR, MYSQLI_ASSOC);
                        if ($resGR[0]['qty'] == null) {
                            $res[$i]['receivedQty'] = "0";
                        } else {
                            $res[$i]['receivedQty'] = $resGR[0]['qty'];
                        }
                        $getSales = mysqli_query($db_conn, "SELECT SUM(sm.qty) AS qty FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND sm.partner_id='$partnerID'");
                        $resSales = mysqli_fetch_all($getSales, MYSQLI_ASSOC);
                        if ($resSales[0]['qty'] == null) {
                            $res[$i]['salesQty'] = "0";
                        } else {
                            $res[$i]['salesQty'] = $resSales[0]['qty'];
                        }
                        $getAdjustment = mysqli_query($db_conn, "SELECT SUM(sm.adjustment) AS qty FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND master_id='$idMaster' AND partner_id='$partnerID'");
                        $resAdjustment = mysqli_fetch_all($getAdjustment, MYSQLI_ASSOC);
                        if ($resAdjustment[0]['qty'] == null) {
                            $res[$i]['adjustedQty'] = "0";
                        } else {
                            $res[$i]['adjustedQty'] = $resAdjustment[0]['qty'];
                        }
                        $getInitial = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY id ASC LIMIT 1");
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
                        $getFinal = mysqli_query($db_conn, "SELECT remaining FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND DATE(sm.created_at)<='$dateTo' ORDER BY id DESC LIMIT 1");
                        if (mysqli_num_rows($getFinal) > 0) {
                            $resFinal = mysqli_fetch_all($getFinal, MYSQLI_ASSOC);
                            if ($resFinal[0]['remaining'] == null) {
                                $res[$i]['finalQty'] = "0";
                            } else {
                                $res[$i]['finalQty'] = $resFinal[0]['remaining'];
                            }
                        } else {
                            $res[$i]['finalQty'] = "0";
                        }
                        $getReturned = mysqli_query($db_conn, "SELECT SUM(sm.returned) AS qty FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.type=0 AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND partner_id='$partnerID'");
                        $resReturned = mysqli_fetch_all($getReturned, MYSQLI_ASSOC);
                        if ($resReturned[0]['qty'] == null) {
                            $res[$i]['returnedQty'] = "0";
                        } else {
                            $res[$i]['returnedQty'] = $resReturned[0]['qty'];
                        }
                        $res[$i]['producedQty'] = "0";
                        $i++;
                    }
                }
                
                $partner['source'] = $res;
             
                if(count($res) > 0) {
                    array_push($data, $partner);
                }   
            }
            
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 203;
            $msg = "Data not found";
        }
    }
    

}
// echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "source" => $res]);
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "data" => $data]);
