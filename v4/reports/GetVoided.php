<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

//init var
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
$tokenizer = new Token();
$token = '';
$total = 0;
$arr = [];
$arr2 = [];
$all = "0";
//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $voidTrx=0;
    $trxCount=0;
    $trxPercentage=0;
    $voidItem=0;
    $itemCount=0;
    $itemPercentage=0;
    $mostVoid="-";
    
    $partnerID = $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $partnerID = $_GET['partnerID']; 
    }
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    $dateFrom = $_GET['dateFrom'];
    $dateTo =  $_GET['dateTo'];
    
    if($all == "1") {
        $addQuery1 = "p.id_master='$idMaster'";
        $addQuery2 = "JOIN partner p ON p.id = m.id_partner";
    } else {
        $addQuery1 = "s.partner_id='$partnerID'";
        $addQuery2 = "";
    }
    
    // $sql = mysqli_query($db_conn, "SELECT tc.id, m.nama, tc.qty, tc.notes, e.nama AS eName, vb.nama AS vbName, dt.id_transaksi AS transactionID FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id=tc.detail_transaction_id JOIN menu m ON m.id=dt.id_menu JOIN employees e ON e.id=tc.created_by JOIN shift s ON s.id=tc.shift_id LEFT JOIN employees vb ON vb.id=tc.acc_by ". $addQuery2 ." WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND ". $addQuery1 ." UNION ALL SELECT tc.id, m.nama, dt.qty, tc.notes, e.nama AS eName, vb.nama AS vbName, dt.id_transaksi AS transactionID FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id_transaksi=tc.transaction_id JOIN menu m ON m.id=dt.id_menu JOIN employees e ON e.id=tc.created_by JOIN shift s ON s.id=tc.shift_id LEFT JOIN employees vb ON vb.id=tc.acc_by ". $addQuery2 ." WHERE tc.deleted_at IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND ". $addQuery1 ."");

    if($all != "1") {
        $sql = mysqli_query($db_conn, "SELECT tc.id, vb.nama as vbName, tc.notes as tcnotes, dt.notes as dtnotes, t.notes as tnotes,
            dt.harga_satuan, dt.qty, t.status as trxStatus, dt.status, dt.harga, t.id as transactionID, t.id_partner, m.nama, e.nama as eName
        FROM detail_transaksi dt
        LEFT JOIN
            transaksi t ON t.id = dt.id_transaksi
        LEFT JOIN 
            transaction_cancellation tc ON (tc.detail_transaction_id = dt.id OR tc.transaction_id = t.id)
        LEFT JOIN
            employees e ON e.id = tc.created_by
        LEFT JOIN
            employees vb ON vb.id = tc.acc_by
        LEFT JOIN
            menu m ON m.id = dt.id_menu
        WHERE
            t.id_partner = '$partnerID'
            AND
            (dt.status = 4 OR t.status = 4 OR t.status = 3)
            AND DATE(t.jam) BETWEEN '$dateFrom'
            AND '$dateTo'
        GROUP BY tc.id
        ORDER BY t.jam DESC");
    } else {
        $sql = mysqli_query($db_conn, "SELECT tc.id, vb.nama as vbName, tc.notes as tcnotes, dt.notes as dtnotes, t.notes as tnotes,
            dt.harga_satuan, dt.qty, t.status as trxStatus, dt.status, dt.harga, t.id as transactionID, t.id_partner, m.nama, e.nama as eName
            FROM detail_transaksi dt
            LEFT JOIN
                transaksi t ON t.id = dt.id_transaksi
            LEFT JOIN 
                transaction_cancellation tc ON (tc.detail_transaction_id = dt.id OR tc.transaction_id = t.id)
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
                AND DATE(t.jam) BETWEEN '$dateFrom'
                AND '$dateTo'
            GROUP BY tc.id
            ORDER BY t.jam DESC");
    }
    
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        
        $i = 0;
        
        $dataVoid = [];
            
        $data[$i]['notes'] = "";
        foreach($data as $val){
            // $id = $data[$i]['id']; 
            if($data[$i]['tcnotes']){
                $dataVoid[$i]['notes'] = $val['tcnotes'];
            } else if(!$data[$i]['tcnotes']){
                $dataVoid[$i]['notes'] = $val['dtnotes'];
            } else if (!$data[$i]['tcnotes'] && !$data[$i]['dtnotes']){
                $dataVoid[$i]['notes'] = $val['tnotes'];
            }
        
            $query = "";
            
            $notes_data = "";
            $current_id_trx = $data[$i]['transactionID'];
            $dataVoid[$i]['transactionID'] = $current_id_trx;
            
            if(!$data[$i]['notes']){
                $query = "SELECT notes, e.nama as eName from transaction_cancellation LEFT JOIN employees e ON e.id = transaction_cancellation.created_by WHERE transaction_id = '$current_id_trx'";
                $sql = mysqli_query($db_conn, $query);
                $notes_data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                $dataVoid[$i]['notes'] = $notes_data[0]['notes'];
                if(!$dataVoid[0]['notes']){
                    $dataVoid[$i]['notes'] = $data[$i]['tcnotes'];
                } else if($data[$i]['trxStatus'] == "3" || $data[$i]['trxStatus'] == 3){
                    $dataVoid[$i]['notes'] = "Dibatalkan Customer";    
                }
                
                $dataVoid[$i]['eName'] = $notes_data[0]['eName'];
                if($notes_data[0]['eName'] === null){
                    $dataVoid[$i]['eName'] = "-";
                }
                $dataVoid[$i]['qty'] = $data[$i]['qty'];
                $dataVoid[$i]['vbName'] = $data[$i]['vbName'];
                if($data[$i]['vbName'] == null){
                    $dataVoid[$i]['vbName'] = $notes_data[0]['eName'];
                } 
                if($dataVoid[$i]['vbName'] == null){
                    $dataVoid[$i]['vbName'] = "-";
                } 
                $dataVoid[$i]['nama'] = $data[$i]['nama'];
                $dataVoid[$i]['id'] = $data[$i]['id'];
                $dataNotes = $notes_data;
            }
            
            $i++;
        }
        
        $success = 1;
        $status = 200;
        $msg = "Success";
        
        if($all == "1") {
            $addQuery1 = "p.id_master='$idMaster'";
            $addQuery2 = "JOIN partner p ON p.id = s.partner_id";
        } else {
            $addQuery1 = "s.partner_id='$partnerID'";
            $addQuery2 = "";
        }

        $qVT = "";
        if ($all == "1") {
        $qVT = "SELECT COUNT(t.id) AS count FROM transaksi t JOIN partner p ON p.id = t.id_partner WHERE t.deleted_at IS NULL AND DATE(t.jam)  BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster' AND (t.status = 4 OR t.status = 3)";
        } else {
        $qVT = "SELECT COUNT(id) AS count FROM transaksi WHERE DATE(jam) BETWEEN '$dateFrom' AND '$dateTo' AND (status = 4 OR status = 3) AND id_partner='$partnerID'";
        }
        $sqlVT = mysqli_query($db_conn, $qVT);
        $resVT = mysqli_fetch_all($sqlVT, MYSQLI_ASSOC);
        $voidTrx = $resVT[0]['count'];
        
        $qVI = "";
        if ($all == "1") {
        $qVI = "SELECT dt.harga_satuan, dt.qty, dt.status, dt.notes, dt.harga, t.id, t.id_partner, m.nama, SUM(dt.qty) AS count FROM detail_transaksi dt LEFT JOIN transaksi t ON t.id = dt.id_transaksi LEFT JOIN menu m ON m.id = dt.id_menu LEFT JOIN partner p ON p.id = t.id_partner WHERE p.id_master = '$idMaster' AND (dt.status = 4 OR t.status = 4 OR t.status = 3) AND DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo'";
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
                AND DATE(t.jam) BETWEEN '$dateFrom'
                AND '$dateTo'
        ";
        }
        $sqlVI = mysqli_query($db_conn, $qVI);
        $resVI = mysqli_fetch_all($sqlVI, MYSQLI_ASSOC);
        $voidItem = $resVI[0]['count'] ?? 0;
        
        if ($all == "1") {
            $qMV = "SELECT dt.harga_satuan, dt.qty, dt.status, dt.notes, dt.harga, t.id, t.id_partner, m.nama as menuName, SUM(dt.qty) AS count FROM detail_transaksi dt LEFT JOIN transaksi t ON t.id = dt.id_transaksi LEFT JOIN menu m ON m.id = dt.id_menu LEFT JOIN partner p ON p.id = t.id_partner WHERE p.id_master = '$idMaster' AND (dt.status = 4 OR t.status = 4 OR t.status = 3) AND DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY dt.id_menu ORDER BY `count` DESC LIMIT 1";
            
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
                    AND DATE(t.jam) BETWEEN '$dateFrom'
                    AND '$dateTo'
                GROUP BY dt.id_menu
                ORDER BY `count` DESC LIMIT 1
            ";
            }
            $sqlMV = mysqli_query($db_conn, $qMV);
            if (mysqli_num_rows($sqlMV) > 0) {
            $resMV = mysqli_fetch_all($sqlMV, MYSQLI_ASSOC);
            $mostVoid = $resMV[0]['menuName'];
            // $mostVoid = $resMV;
            $mvc = $resMV[0]['count'];
            // $mvc = $resMV;
            } else {
            $mostVoid = null;
            $mvc = null;
            }
        
            if($all == "1") {
                $addQuery1 = "p.id_master='$idMaster'";
                $addQuery2 = "JOIN partner p ON p.id = t.id_partner";
            } else {
                $addQuery1 = "t.id_partner='$partnerID'";
                $addQuery2 = "";
            }
            
            $qTC = mysqli_query($db_conn, "SELECT COUNT(t.id) AS count FROM transaksi t ". $addQuery2 ." WHERE DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' AND ". $addQuery1 ."");
            $resTC = mysqli_fetch_all($qTC, MYSQLI_ASSOC);
            $trxCount = $resTC[0]['count']??0;

        $trxPercentage = (int)$voidTrx/(int)$trxCount*100;

        $qIC = "";
        if ($all == "1") {
            $qIC = mysqli_query($db_conn, "SELECT SUM(dt.qty) AS count FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id JOIN partner p ON p.id = t.id_partner WHERE DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$idMaster'");
            $resIC = mysqli_fetch_all($qIC, MYSQLI_ASSOC);
            $itemCount = $resIC[0]['count']??0;
        } else {
            $qIC = mysqli_query($db_conn, "SELECT SUM(dt.qty) AS count FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id WHERE DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$partnerID'");
            $resIC = mysqli_fetch_all($qIC, MYSQLI_ASSOC);
            $itemCount = $resIC[0]['count']??0;
        }

        $itemPercentage = (int)$voidItem/(int)$itemCount*100;
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
 
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "voids"=>$dataVoid, "voidTrx"=>$voidTrx, "voidItem"=>$voidItem, "mostVoid"=>$mostVoid, "mostVoidCount"=>$mvc, "trxCount" => $trxCount, "trxPercentage"=>$trxPercentage, "itemCount"=>$itemCount, "itemPercentage"=>$itemPercentage
    ]);

?>