<?php
error_reporting(E_ALL);
ini_set(‘display_errors’, 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';

$headers = apache_request_headers();
$token = '';

foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$data = array();
$dataT = array();
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt', $token));
$idMaster = $tokenDecoded->masterID;
$value = array();
$success = 0;
$msg = 'Failed';
$all = "0";

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
} else {
    $voidTrx = 0;
    $trxCount = 0;
    $trxPercentage = 0;
    $voidItem = 0;
    $itemCount = 0;
    $itemPercentage = 0;
    $mostVoid = "-";
    $partnerID = $_GET['partnerID'];
    $dateFrom = $_GET['dateFrom'];
    $dateTo =  $_GET['dateTo'];
    
    $newDateFormat = 0;
    
    $dataNotes = "";
    
    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }
    
    if (isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($newDateFormat == 1){
        if($all != "1") {
            $query = "SELECT
                dt.harga_satuan, dt.qty, t.status as trxStatus, dt.status, dt.harga, t.id, t.id_partner, m.nama, e.nama as eName, tc.notes as tcnotes, dt.notes as dtnotes, t.notes as tnotes
                FROM detail_transaksi dt
                LEFT JOIN
                    transaksi t ON t.id = dt.id_transaksi
                LEFT JOIN 
                    transaction_cancellation tc ON tc.detail_transaction_id = dt.id
                LEFT JOIN
                    employees e ON e.id = tc.created_by
                LEFT JOIN
                    menu m ON m.id = dt.id_menu
                WHERE
                    t.id_partner = '$partnerID'
                    AND
                    (dt.status = 4 OR t.status = 4 OR t.status = 3)
                    AND t.jam BETWEEN '$dateFrom'
                    AND '$dateTo'
                ORDER BY t.jam DESC
            ";

        } else {
            $query = "SELECT tc.id, vb.nama as vbName, tc.notes as tcnotes, dt.notes as dtnotes, t.notes as tnotes,
            dt.harga_satuan, dt.qty, t.status as trxStatus, dt.status, dt.harga, t.id as transactionID, t.id_partner, m.nama, e.nama as eName
            FROM detail_transaksi dt
            LEFT JOIN
                transaksi t ON t.id = dt.id_transaksi
            LEFT JOIN 
                transaction_cancellation tc ON tc.detail_transaction_id = dt.id
            LEFT JOIN
                employees e ON e.id = tc.created_by
            LEFT JOIN
                employees vb ON vb.id = tc.acc_by
            LEFT JOIN
                menu m ON m.id = dt.id_menu
            LEFT JOIN 
                partner p ON p.id = t.id_partner
            WHERE
                p.id_master = '$idMaster'
                AND
                (dt.status = 4 OR t.status = 4 OR t.status = 3)
                AND t.jam BETWEEN '$dateFrom'
                AND '$dateTo'
            ORDER BY t.jam DESC";
            
            
            
            // $query = "SELECT
            //         tc.id,
            //         m.nama,
            //         tc.qty,
            //         tc.notes,
            //         e.nama AS eName,
            //         dt.id_transaksi AS transactionID,
            //         t.id_partner
            //     FROM
            //         transaction_cancellation tc
            //         JOIN detail_transaksi dt ON dt.id = tc.detail_transaction_id
            //         JOIN menu m ON m.id = dt.id_menu
            //         JOIN employees e ON e.id = tc.created_by
            //         JOIN shift s ON s.id = tc.shift_id
            //         JOIN transaksi t ON t.id = dt.id_transaksi
            //         JOIN partner p ON p.id = t.id_partner
            //     WHERE
            //         tc.deleted_at IS NULL
            //         AND tc.transaction_id IS NULL
            //         AND tc.created_at BETWEEN '$dateFrom'
            //         AND '$dateTo'
            //         AND p.id_master = '$idMaster'
            //     UNION ALL
            //     SELECT
            //         tc.id,
            //         m.nama,
            //         dt.qty,
            //         tc.notes,
            //         e.nama AS eName,
            //         dt.id_transaksi AS transactionID,
            //         t.id_partner
            //     FROM
            //         transaction_cancellation tc
            //         JOIN detail_transaksi dt ON dt.id_transaksi = tc.transaction_id
            //         JOIN menu m ON m.id = dt.id_menu
            //         JOIN employees e ON e.id = tc.created_by
            //         JOIN shift s ON s.id = tc.shift_id
            //         JOIN transaksi t ON t.id = dt.id_transaksi
            //         JOIN partner p ON p.id = t.id_partner
            //     WHERE
            //         tc.deleted_at IS NULL
            //         AND tc.created_at BETWEEN '$dateFrom'
            //         AND '$dateTo'
            //         AND p.id_master = '$idMaster'";
        }
    
        $sql = mysqli_query($db_conn, $query);
        if (mysqli_num_rows($sql) > 0) {
            $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
            
            $i = 0;
            
            $data[$i]['notes'] = "";
            foreach($data as $val){
                if($data[$i]['tcnotes']){
                    $data[$i]['notes'] = $val['tcnotes'];
                } else if(!$data[$i]['tcnotes']){
                    $data[$i]['notes'] = $val['dtnotes'];
                } else if (!$data[$i]['tcnotes'] && !$data[$i]['dtnotes']){
                    $data[$i]['notes'] = $val['tnotes'];
                }
            
                $query = "";
                
                $notes_data = "";
                $current_id_trx = $data[$i]['id'];
                
                if(!$data[$i]['notes']){
                    $query = "SELECT notes, e.nama as eName from transaction_cancellation LEFT JOIN employees e ON e.id = transaction_cancellation.created_by WHERE transaction_id = '$current_id_trx'";
                    $sql = mysqli_query($db_conn, $query);
                    $notes_data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $data[$i]['notes'] = $notes_data[0]['notes'];
                    if($data[$i]['trxStatus'] == "3" || $data[$i]['trxStatus'] == 3){
                        $data[$i]['notes'] = "Dibatalkan Customer";    
                    }
                    $data[$i]['eName'] = $notes_data[0]['eName'];
                    $dataNotes = $notes_data;
                }
                
                $i++;
            }
            
            $success = 1;
            $status = 200;
            $msg = "Success";
            $qVT = "";
            if ($all == "1") {
            $qVT = "SELECT COUNT(t.id) AS count FROM transaksi t JOIN partner p ON p.id = t.id_partner WHERE t.jam  BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' AND (t.status = 4 OR t.status = 3)";
            } else {
            $qVT = "SELECT COUNT(id) AS count FROM transaksi WHERE jam BETWEEN '$dateFrom' AND '$dateTo' AND (status = 4 OR status = 3) AND id_partner='$partnerID'";
            }
            $sqlVT = mysqli_query($db_conn, $qVT);
            $resVT = mysqli_fetch_all($sqlVT, MYSQLI_ASSOC);
            $voidTrx = $resVT[0]['count'];
            $qTC = "";
    
            if ($all == "1") {
            $qTC = "SELECT COUNT(t.id) AS count FROM transaksi t JOIN partner p ON p.id = t.id_partner WHERE jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster'";
            } else {
            $qTC = "SELECT COUNT(id) AS count FROM transaksi WHERE jam BETWEEN '$dateFrom' AND '$dateTo' AND id_partner='$partnerID'";
            }
            $sqlTC = mysqli_query($db_conn, $qTC);
            $resTC = mysqli_fetch_all($sqlTC, MYSQLI_ASSOC);
            $trxCount = $resTC[0]['count'];
            $trxPercentage = (int)$voidTrx / (int)$trxCount * 100;
    
            $qVI = "";
            if ($all == "1") {
            $qVI = "SELECT dt.harga_satuan, dt.qty, dt.status, dt.notes, dt.harga, t.id, t.id_partner, m.nama, SUM(dt.qty) AS count FROM detail_transaksi dt LEFT JOIN transaksi t ON t.id = dt.id_transaksi LEFT JOIN menu m ON m.id = dt.id_menu LEFT JOIN partner p ON p.id = t.id_partner WHERE p.id_master = '$idMaster' AND (dt.status = 4 OR t.status = 4 OR t.status = 3) AND t.jam BETWEEN '$dateFrom' AND '$dateTo'";
            } else {
            $qVI = "SELECT
                dt.harga_satuan, dt.qty, dt.status, dt.notes, dt.harga, t.id, t.id_partner, m.nama, SUM(dt.qty) AS count
                FROM detail_transaksi dt
                LEFT JOIN
                    transaksi t ON t.id = dt.id_transaksi
                LEFT JOIN
                    menu m ON m.id = dt.id_menu
                WHERE
                    t.id_partner = '$partnerID'
                    AND
                    (dt.status = 4 OR t.status = 4 OR t.status = 3)
                    AND t.jam BETWEEN '$dateFrom'
                    AND '$dateTo'
            ";
            }
            $sqlVI = mysqli_query($db_conn, $qVI);
            $resVI = mysqli_fetch_all($sqlVI, MYSQLI_ASSOC);
            $voidItem = $resVI[0]['count'] ?? 0;
    
            $qIC = "";
            if ($all == "1") {
            $qIC = "SELECT SUM(dt.qty) AS count FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster'";
            } else {
            $qIC = "SELECT SUM(dt.qty) AS count FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$partnerID'";
            }
            $sqlIC = mysqli_query($db_conn, $qIC);
            $resIC = mysqli_fetch_all($sqlIC, MYSQLI_ASSOC);
            $itemCount = $resIC[0]['count'];
    
            $itemPercentage = (int)$voidItem / (int)$itemCount * 100;
            $qMV = "";
    
            if ($all == "1") {
            $qMV = "SELECT dt.harga_satuan, dt.qty, dt.status, dt.notes, dt.harga, t.id, t.id_partner, m.nama as menuName, SUM(dt.qty) AS count FROM detail_transaksi dt LEFT JOIN transaksi t ON t.id = dt.id_transaksi LEFT JOIN menu m ON m.id = dt.id_menu LEFT JOIN partner p ON p.id = t.id_partner WHERE p.id_master = '$idMaster' AND (dt.status = 4 OR t.status = 4 OR t.status = 3) AND t.jam BETWEEN '$dateFrom' AND '$dateTo' GROUP BY dt.id_menu ORDER BY `count` DESC LIMIT 1";
            } else {
            
            $qMV = "SELECT
                dt.harga_satuan, dt.qty, dt.status, dt.notes, dt.harga, t.id, t.id_partner, m.nama as menuName, SUM(dt.qty) AS count
                FROM detail_transaksi dt
                LEFT JOIN
                    transaksi t ON t.id = dt.id_transaksi
                LEFT JOIN
                    menu m ON m.id = dt.id_menu
                WHERE
                    t.id_partner = '$partnerID'
                    AND
                    (dt.status = 4 OR t.status = 4 OR t.status = 3)
                    AND t.jam BETWEEN '$dateFrom'
                    AND '$dateTo'
                GROUP BY dt.id_menu
                ORDER BY `count` DESC LIMIT 1
            ";
            }
            $sqlMV = mysqli_query($db_conn, $qMV);
            if (mysqli_num_rows($sqlMV) > 0) {
            $resMV = mysqli_fetch_all($sqlMV, MYSQLI_ASSOC);
            // $mostVoid = $resMV[0]['menuName'];
            $mostVoid = $resMV;
            // $mvc = $resMV[0]['count'];
            $mvc = $resMV;
            } else {
            $mostVoid = null;
            $mvc = null;
            }
        } else {
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }

    } else {
        if ($all !== "1") {
            $query = "SELECT
                dt.harga_satuan, dt.qty, t.status as trxStatus, dt.status, dt.harga, t.id, t.id_partner, m.nama, e.nama as eName, tc.notes as tcnotes, dt.notes as dtnotes, t.notes as tnotes
                FROM detail_transaksi dt
                LEFT JOIN
                    transaksi t ON t.id = dt.id_transaksi
                LEFT JOIN 
                    transaction_cancellation tc ON tc.detail_transaction_id = dt.id
                LEFT JOIN
                    employees e ON e.id = tc.created_by
                LEFT JOIN
                    menu m ON m.id = dt.id_menu
                WHERE
                    t.id_partner = '$partnerID'
                    AND
                    (dt.status = 4 OR t.status = 4 OR t.status = 3)
                    AND DATE(t.jam) BETWEEN '$dateFrom'
                    AND '$dateTo'
                ORDER BY t.jam DESC
            ";
        } else {
            $query = "SELECT
                dt.harga_satuan, dt.qty, t.status as trxStatus, dt.status, dt.harga, t.id, t.id_partner, m.nama, e.nama as eName, tc.notes as tcnotes, dt.notes as dtnotes, t.notes as tnotes
                FROM detail_transaksi dt
                LEFT JOIN
                    transaksi t ON t.id = dt.id_transaksi
                LEFT JOIN 
                    transaction_cancellation tc ON tc.detail_transaction_id = dt.id
                LEFT JOIN
                    employees e ON e.id = tc.created_by
                LEFT JOIN
                    menu m ON m.id = dt.id_menu
                LEFT JOIN 
                    partner p ON p.id = t.id_partner
                WHERE
                    p.id_master = '$idMaster'
                    AND
                    (dt.status = 4 OR t.status = 4 OR t.status = 3)
                    AND DATE(t.jam) BETWEEN '$dateFrom'
                    AND '$dateTo'
                ORDER BY t.jam DESC";
            
            // $query = "SELECT
            //         tc.id,
            //         m.nama,
            //         tc.qty,
            //         tc.notes,
            //         e.nama AS eName,
            //         dt.id_transaksi AS transactionID,
            //         t.id_partner
            //     FROM
            //         transaction_cancellation tc
            //         JOIN detail_transaksi dt ON dt.id = tc.detail_transaction_id
            //         JOIN menu m ON m.id = dt.id_menu
            //         JOIN employees e ON e.id = tc.created_by
            //         JOIN shift s ON s.id = tc.shift_id
            //         JOIN transaksi t ON t.id = dt.id_transaksi
            //         JOIN partner p ON p.id = t.id_partner
            //     WHERE
            //         tc.deleted_at IS NULL
            //         AND tc.transaction_id IS NULL
            //         AND DATE(tc.created_at) BETWEEN '$dateFrom'
            //         AND '$dateTo'
            //         AND p.id_master = '$idMaster'
            //     UNION ALL
            //     SELECT
            //         tc.id,
            //         m.nama,
            //         dt.qty,
            //         tc.notes,
            //         e.nama AS eName,
            //         dt.id_transaksi AS transactionID,
            //         t.id_partner
            //     FROM
            //         transaction_cancellation tc
            //         JOIN detail_transaksi dt ON dt.id_transaksi = tc.transaction_id
            //         JOIN menu m ON m.id = dt.id_menu
            //         JOIN employees e ON e.id = tc.created_by
            //         JOIN shift s ON s.id = tc.shift_id
            //         JOIN transaksi t ON t.id = dt.id_transaksi
            //         JOIN partner p ON p.id = t.id_partner
            //     WHERE
            //         tc.deleted_at IS NULL
            //         AND DATE(tc.created_at) BETWEEN '$dateFrom'
            //         AND '$dateTo'
            //         AND p.id_master = '$idMaster'";
        }
    
        $sql = mysqli_query($db_conn, $query);
                if (mysqli_num_rows($sql) > 0) {
            $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
            
            $i = 0;
            
            $data[$i]['notes'] = "";
            foreach($data as $val){
                if($data[$i]['tcnotes']){
                    $data[$i]['notes'] = $val['tcnotes'];
                } else if(!$data[$i]['tcnotes']){
                    $data[$i]['notes'] = $val['dtnotes'];
                } else if (!$data[$i]['tcnotes'] && !$data[$i]['dtnotes']){
                    $data[$i]['notes'] = $val['tnotes'];
                }
            
                $query = "";
                
                $notes_data = "";
                $current_id_trx = $data[$i]['id'];
                
                if(!$data[$i]['notes']){
                    $query = "SELECT notes, e.nama as eName from transaction_cancellation LEFT JOIN employees e ON e.id = transaction_cancellation.created_by WHERE transaction_id = '$current_id_trx'";
                    $sql = mysqli_query($db_conn, $query);
                    $notes_data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    if($data[$i]['trxStatus'] == "3" || $data[$i]['trxStatus'] == 3){
                        $data[$i]['notes'] = "Dibatalkan Customer";    
                    }
                    $data[$i]['notes'] = $notes_data[0]['notes'];
                    $data[$i]['eName'] = $notes_data[0]['eName'];
                    $dataNotes = $notes_data;
                }
                
                $i++;
            }
            
            $success = 1;
            $status = 200;
            $msg = "Success";
            $qVT = "";
            if ($all == "1") {
            $qVT = "SELECT COUNT(t.id) AS count FROM transaction_cancellation tc JOIN shift s ON s.id=tc.shift_id JOIN partner p ON p.id = s.partner_id WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NOT NULL AND tc.created_at BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster'";
            } else {
            $qVT = "SELECT COUNT(id) AS count FROM transaksi WHERE jam BETWEEN '$dateFrom' AND '$dateTo' AND (status = 4 OR status = 3) AND id_partner='$partnerID'";
            }
            $sqlVT = mysqli_query($db_conn, $qVT);
            $resVT = mysqli_fetch_all($sqlVT, MYSQLI_ASSOC);
            $voidTrx = $resVT[0]['count'];
            $qTC = "";
    
            if ($all == "1") {
            $qTC = "SELECT COUNT(t.id) AS count FROM transaksi t JOIN partner p ON p.id = t.id_partner WHERE jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster'";
            } else {
            $qTC = "SELECT COUNT(id) AS count FROM transaksi WHERE jam BETWEEN '$dateFrom' AND '$dateTo' AND id_partner='$partnerID'";
            }
            $sqlTC = mysqli_query($db_conn, $qTC);
            $resTC = mysqli_fetch_all($sqlTC, MYSQLI_ASSOC);
            $trxCount = $resTC[0]['count'];
            $trxPercentage = (int)$voidTrx / (int)$trxCount * 100;
    
            $qVI = "";
            if ($all == "1") {
            $qVI = "SELECT SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN shift s ON s.id=tc.shift_id JOIN partner p ON p.id = s.partner_id WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NULL AND tc.created_at BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster'";
            } else {
            $qVI = "SELECT
                dt.harga_satuan, dt.qty, dt.status, dt.notes, dt.harga, t.id, t.id_partner, m.nama, SUM(dt.qty) AS count
                FROM detail_transaksi dt
                LEFT JOIN
                    transaksi t ON t.id = dt.id_transaksi
                LEFT JOIN
                    menu m ON m.id = dt.id_menu
                WHERE
                    t.id_partner = '$partnerID'
                    AND
                    (dt.status = 4 OR t.status = 4 OR t.status = 3)
                    AND t.jam BETWEEN '$dateFrom'
                    AND '$dateTo'
            ";
            }
            $sqlVI = mysqli_query($db_conn, $qVI);
            $resVI = mysqli_fetch_all($sqlVI, MYSQLI_ASSOC);
            $voidItem = $resVI[0]['count'] ?? 0;
    
            $qIC = "";
            if ($all == "1") {
            $qIC = "SELECT SUM(dt.qty) AS count FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id JOIN partner p ON p.id = t.id_partner WHERE dt.created_at BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster'";
            } else {
            $qIC = "SELECT SUM(dt.qty) AS count FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$partnerID'";
            }
            $sqlIC = mysqli_query($db_conn, $qIC);
            $resIC = mysqli_fetch_all($sqlIC, MYSQLI_ASSOC);
            $itemCount = $resIC[0]['count'];
    
            $itemPercentage = (int)$voidItem / (int)$itemCount * 100;
            $qMV = "";
    
            if ($all == "1") {
            $qMV = "SELECT m.nama AS menuName,SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id=tc.detail_transaction_id JOIN shift s ON s.id=tc.shift_id JOIN menu m ON m.id=dt.id_menu JOIN partner p ON p.id = s.partner_id WHERE tc.deleted_at IS NULL AND tc.created_at BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' GROUP BY dt.id_menu
                UNION ALL
                SELECT m.nama AS menuName,SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id=tc.detail_transaction_id JOIN shift s ON s.id=tc.shift_id JOIN menu m ON m.id=dt.id_menu JOIN partner p ON p.id = s.partner_id WHERE tc.deleted_at IS NULL AND tc.created_at BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' GROUP BY dt.id_menu ORDER BY `count` DESC LIMIT 1";
            } else {
            
            $qMV = "SELECT
                dt.harga_satuan, dt.qty, dt.status, dt.notes, dt.harga, t.id, t.id_partner, m.nama as menuName, SUM(dt.qty) AS count
                FROM detail_transaksi dt
                LEFT JOIN
                    transaksi t ON t.id = dt.id_transaksi
                LEFT JOIN
                    menu m ON m.id = dt.id_menu
                WHERE
                    t.id_partner = '$partnerID'
                    AND
                    (dt.status = 4 OR t.status = 4 OR t.status = 3)
                    AND t.jam BETWEEN '$dateFrom'
                    AND '$dateTo'
                GROUP BY dt.id_menu
                ORDER BY `count` DESC LIMIT 1
            ";
            }
            $sqlMV = mysqli_query($db_conn, $qMV);
            if (mysqli_num_rows($sqlMV) > 0) {
            $resMV = mysqli_fetch_all($sqlMV, MYSQLI_ASSOC);
            // $mostVoid = $resMV[0]['menuName'];
            $mostVoid = $resMV;
            // $mvc = $resMV[0]['count'];
            $mvc = $resMV;
            } else {
            $mostVoid = null;
            $mvc = null;
            }
        } else {
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
        // if (mysqli_num_rows($sql) > 0) {
        //     $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        //     $success = 1;
        //     $status = 200;
        //     $msg = "Success";
        //     $qVT = "";
        //     if ($all == "1") {
        //     $qVT = "SELECT COUNT(tc.id) AS count FROM transaction_cancellation tc JOIN shift s ON s.id=tc.shift_id JOIN partner p ON p.id = s.partner_id WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NOT NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster'";
        //     } else {
        //     $qVT = "SELECT COUNT(tc.id) AS count FROM transaction_cancellation tc JOIN shift s ON s.id=tc.shift_id WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NOT NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND s.partner_id='$partnerID'";
        //     }
        //     $sqlVT = mysqli_query($db_conn, $qVT);
        //     $resVT = mysqli_fetch_all($sqlVT, MYSQLI_ASSOC);
        //     $voidTrx = $resVT[0]['count'];
        //     $qTC = "";
    
        //     if ($all == "1") {
        //     $qTC = "SELECT COUNT(t.id) AS count FROM transaksi t JOIN partner p ON p.id = t.id_partner WHERE DATE(jam) BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster'";
        //     } else {
        //     $qTC = "SELECT COUNT(id) AS count FROM transaksi WHERE DATE(jam) BETWEEN '$dateFrom' AND '$dateTo' AND id_partner='$partnerID'";
        //     }
        //     $sqlTC = mysqli_query($db_conn, $qTC);
        //     $resTC = mysqli_fetch_all($sqlTC, MYSQLI_ASSOC);
        //     $trxCount = $resTC[0]['count'];
        //     $trxPercentage = (int)$voidTrx / (int)$trxCount * 100;
    
        //     $qVI = "";
        //     if ($all == "1") {
        //     $qVI = "SELECT SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN shift s ON s.id=tc.shift_id JOIN partner p ON p.id = s.partner_id WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster'";
        //     } else {
        //     $qVI = "SELECT SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN shift s ON s.id=tc.shift_id WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND s.partner_id='$partnerID'";
        //     }
        //     $sqlVI = mysqli_query($db_conn, $qVI);
        //     $resVI = mysqli_fetch_all($sqlVI, MYSQLI_ASSOC);
        //     $voidItem = $resVI[0]['count'] ?? 0;
    
        //     $qIC = "";
        //     if ($all == "1") {
        //     $qIC = "SELECT SUM(dt.qty) AS count FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id JOIN partner p ON p.id = t.id_partner WHERE DATE(dt.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster'";
        //     } else {
        //     $qIC = "SELECT SUM(dt.qty) AS count FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id WHERE DATE(dt.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$partnerID'";
        //     }
        //     $sqlIC = mysqli_query($db_conn, $qIC);
        //     $resIC = mysqli_fetch_all($sqlIC, MYSQLI_ASSOC);
        //     $itemCount = $resIC[0]['count'];
    
        //     $itemPercentage = (int)$voidItem / (int)$itemCount * 100;
        //     $qMV = "";
    
        //     if ($all == "1") {
        //     $qMV = "SELECT m.nama AS menuName,SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id=tc.detail_transaction_id JOIN shift s ON s.id=tc.shift_id JOIN menu m ON m.id=dt.id_menu JOIN partner p ON p.id = s.partner_id WHERE tc.deleted_at IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' GROUP BY dt.id_menu
        //         UNION ALL
        //         SELECT m.nama AS menuName,SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id=tc.detail_transaction_id JOIN shift s ON s.id=tc.shift_id JOIN menu m ON m.id=dt.id_menu JOIN partner p ON p.id = s.partner_id WHERE tc.deleted_at IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' GROUP BY dt.id_menu ORDER BY `count` DESC LIMIT 1";
        //     } else {
        //     $qMV = "SELECT m.nama AS menuName,SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id=tc.detail_transaction_id JOIN shift s ON s.id=tc.shift_id JOIN menu m ON m.id=dt.id_menu WHERE tc.deleted_at IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND s.partner_id='$partnerID' GROUP BY dt.id_menu
        //         UNION ALL
        //         SELECT m.nama AS menuName,SUM(dt.qty) AS count FROM transaction_cancellation tc JOIN transaksi t ON t.id = tc.transaction_id JOIN detail_transaksi dt ON dt.id_transaksi=t.id JOIN shift s ON s.id=tc.shift_id JOIN menu m ON m.id=dt.id_menu WHERE tc.deleted_at IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND s.partner_id='$partnerID' GROUP BY dt.id_menu ORDER BY `count` DESC LIMIT 1";
        //     }
        //     $sqlMV = mysqli_query($db_conn, $qMV);
        //     if (mysqli_num_rows($sqlMV) > 0) {
        //     $resMV = mysqli_fetch_all($sqlMV, MYSQLI_ASSOC);
        //     // $mostVoid = $resMV[0]['menuName'];
        //     $mostVoid = $resMV;
        //     // $mvc = $resMV[0]['count'];
        //     $mvc = $resMV;
        //     } else {
        //     $mostVoid = null;
        //     $mvc = null;
        //     }
        // } else {
        //     $success = 0;
        //     $status = 204;
        //     $msg = "Data Not Found";
        // }
    }
}

echo json_encode([
        "success" => $success, 
        "status" => $status, 
        "msg" => $msg, 
        "voids" => $data, 
        "voidTrx" => $voidTrx, 
        "trxCount" => $trxCount, 
        "trxPercentage" => $trxPercentage, 
        "voidItem" => $voidItem, 
        "itemCount" => $itemCount, 
        "itemPercentage" => $itemPercentage, 
        "mostVoid" => $mostVoid, 
        "mostVoidCount" => $mvc,
        "dataNotes" => $dataNotes
    ]);

// echo json_encode(["dateFrom"=>$dateFrom, "dateTo" => $dateTo])
// ?>
