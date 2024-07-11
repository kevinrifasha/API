<?php
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
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$dataT = array();
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $tokenDecoded->masterID;
$value = array();
$success=0;
$msg = 'Failed';
$array = [];

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $dateFrom = $_GET['dateFrom'];
    $dateTo =  $_GET['dateTo'];

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
                $data=array();
                $voidTrx=0;
                $trxCount=0;
                $trxPercentage=0;
                $voidItem=0;
                $itemCount=0;
                $itemPercentage=0;
                $mostVoid="-";
                
                $query = "SELECT
                    tc.id,
                    m.nama,
                    tc.qty,
                    tc.notes,
                    e.nama AS eName,
                    dt.id_transaksi AS transactionID,
                    t.id_partner
                  FROM
                    transaction_cancellation tc
                    JOIN detail_transaksi dt ON dt.id = tc.detail_transaction_id
                    JOIN menu m ON m.id = dt.id_menu
                    JOIN employees e ON e.id = tc.created_by
                    JOIN shift s ON s.id = tc.shift_id
                    JOIN transaksi t ON t.id = dt.id_transaksi
                  WHERE
                    tc.deleted_at IS NULL
                    AND tc.transaction_id IS NULL
                    AND  tc.created_at BETWEEN '$dateFrom'
                    AND '$dateTo'
                    AND s.partner_id = '$partnerID'
                  UNION ALL
                  SELECT
                    tc.id,
                    m.nama,
                    dt.qty,
                    tc.notes,
                    e.nama AS eName,
                    dt.id_transaksi AS transactionID,
                    t.id_partner
                  FROM
                    transaction_cancellation tc
                    JOIN detail_transaksi dt ON dt.id_transaksi = tc.transaction_id
                    JOIN menu m ON m.id = dt.id_menu
                    JOIN employees e ON e.id = tc.created_by
                    JOIN shift s ON s.id = tc.shift_id
                    JOIN transaksi t ON t.id = dt.id_transaksi
                  WHERE
                    tc.deleted_at IS NULL
                    AND  tc.created_at BETWEEN '$dateFrom'
                    AND '$dateTo'
                    AND s.partner_id = '$partnerID'";
                
                $sql = mysqli_query($db_conn, $query);
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $success = 1;
                    $status = 200;
                    $msg = "Success";
                    $qVT = "";
                    
                    $qVT = "SELECT COUNT(tc.id) AS count FROM transaction_cancellation tc JOIN shift s ON s.id=tc.shift_id WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NOT NULL AND  tc.created_at BETWEEN '$dateFrom' AND '$dateTo' AND s.partner_id='$partnerID'";
                    
                    $sqlVT = mysqli_query($db_conn, $qVT);
                    $resVT = mysqli_fetch_all($sqlVT, MYSQLI_ASSOC);
                    $voidTrx=$resVT[0]['count'];
                    $qTC = "";
                    
                    $qTC = "SELECT COUNT(id) AS count FROM transaksi WHERE  jam BETWEEN '$dateFrom' AND '$dateTo' AND id_partner='$partnerID'";
            
                    $sqlTC = mysqli_query($db_conn, $qTC);
                    $resTC = mysqli_fetch_all($sqlTC, MYSQLI_ASSOC);
                    $trxCount = $resTC[0]['count'];
                    $trxPercentage = (int)$voidTrx/(int)$trxCount*100;
            
                    $qVI = "";
                    
                    $qVI = "SELECT SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN shift s ON s.id=tc.shift_id WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NULL AND  tc.created_at BETWEEN '$dateFrom' AND '$dateTo' AND s.partner_id='$partnerID'";
                    
                    $sqlVI = mysqli_query($db_conn, $qVI);
                    $resVI = mysqli_fetch_all($sqlVI, MYSQLI_ASSOC);
                    $voidItem=$resVI[0]['count'];
                    
                    $qIC = "";
                    
                    $qIC = "SELECT SUM(dt.qty) AS count FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id WHERE  dt.created_at BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$partnerID'";
                    
                    $sqlIC = mysqli_query($db_conn, $qIC);
                    $resIC = mysqli_fetch_all($sqlIC, MYSQLI_ASSOC);
                    $itemCount = $resIC[0]['count'];
            
                    $itemPercentage = (int)$voidItem/(int)$itemCount*100;
                    $qMV = "";
            
                    $qMV = "SELECT m.nama AS menuName,SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id=tc.detail_transaction_id JOIN shift s ON s.id=tc.shift_id JOIN menu m ON m.id=dt.id_menu WHERE tc.deleted_at IS NULL AND  tc.created_at BETWEEN '$dateFrom' AND '$dateTo' AND s.partner_id='$partnerID' GROUP BY dt.id_menu
                      UNION ALL 
                      SELECT m.nama AS menuName,SUM(dt.qty) AS count FROM transaction_cancellation tc JOIN transaksi t ON t.id = tc.transaction_id JOIN detail_transaksi dt ON dt.id_transaksi=t.id JOIN shift s ON s.id=tc.shift_id JOIN menu m ON m.id=dt.id_menu WHERE tc.deleted_at IS NULL AND  tc.created_at BETWEEN '$dateFrom' AND '$dateTo' AND s.partner_id='$partnerID' GROUP BY dt.id_menu ORDER BY `count` DESC LIMIT 1";
                    
                    $sqlMV = mysqli_query($db_conn, $qMV);
                    $resMV = mysqli_fetch_all($sqlMV, MYSQLI_ASSOC);
                    // $mostVoid = $resMV[0]['menuName'];
                    $mostVoid = $resMV;
                    // $mvc = $resMV[0]['count'];
                    $mvc = $resMV;
                }
                
                $partner['voids'] = $data;
                $partner['voidTrx'] = $voidTrx;
                $partner['trxCount'] = $trxCount;
                $partner['trxPercentage'] = $trxPercentage;
                $partner['voidItem'] = $voidItem ?? 0;
                $partner['itemCount'] = $itemCount;
                $partner['itemPercentage'] = $itemPercentage;
                $partner['mostVoid'] = $mostVoid;
                $partner['mostVoidCount'] = $mvc;
                
                if(count($data) > 0) {
                    array_push($array, $partner);
                } 
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
    else 
    {
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $partnerID = $partner['partner_id'];
                $data=array();
                $voidTrx=0;
                $trxCount=0;
                $trxPercentage=0;
                $voidItem=0;
                $itemCount=0;
                $itemPercentage=0;
                $mostVoid="-";
                
                $query = "SELECT
                    tc.id,
                    m.nama,
                    tc.qty,
                    tc.notes,
                    e.nama AS eName,
                    dt.id_transaksi AS transactionID,
                    t.id_partner
                  FROM
                    transaction_cancellation tc
                    JOIN detail_transaksi dt ON dt.id = tc.detail_transaction_id
                    JOIN menu m ON m.id = dt.id_menu
                    JOIN employees e ON e.id = tc.created_by
                    JOIN shift s ON s.id = tc.shift_id
                    JOIN transaksi t ON t.id = dt.id_transaksi
                  WHERE
                    tc.deleted_at IS NULL
                    AND tc.transaction_id IS NULL
                    AND DATE(tc.created_at) BETWEEN '$dateFrom'
                    AND '$dateTo'
                    AND s.partner_id = '$partnerID'
                  UNION ALL
                  SELECT
                    tc.id,
                    m.nama,
                    dt.qty,
                    tc.notes,
                    e.nama AS eName,
                    dt.id_transaksi AS transactionID,
                    t.id_partner
                  FROM
                    transaction_cancellation tc
                    JOIN detail_transaksi dt ON dt.id_transaksi = tc.transaction_id
                    JOIN menu m ON m.id = dt.id_menu
                    JOIN employees e ON e.id = tc.created_by
                    JOIN shift s ON s.id = tc.shift_id
                    JOIN transaksi t ON t.id = dt.id_transaksi
                  WHERE
                    tc.deleted_at IS NULL
                    AND DATE(tc.created_at) BETWEEN '$dateFrom'
                    AND '$dateTo'
                    AND s.partner_id = '$partnerID'";
                
                $sql = mysqli_query($db_conn, $query);
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $success = 1;
                    $status = 200;
                    $msg = "Success";
                    $qVT = "";
                    
                    $qVT = "SELECT COUNT(tc.id) AS count FROM transaction_cancellation tc JOIN shift s ON s.id=tc.shift_id WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NOT NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND s.partner_id='$partnerID'";
                    
                    $sqlVT = mysqli_query($db_conn, $qVT);
                    $resVT = mysqli_fetch_all($sqlVT, MYSQLI_ASSOC);
                    $voidTrx=$resVT[0]['count'];
                    $qTC = "";
                    
                    $qTC = "SELECT COUNT(id) AS count FROM transaksi WHERE DATE(jam) BETWEEN '$dateFrom' AND '$dateTo' AND id_partner='$partnerID'";
            
                    $sqlTC = mysqli_query($db_conn, $qTC);
                    $resTC = mysqli_fetch_all($sqlTC, MYSQLI_ASSOC);
                    $trxCount = $resTC[0]['count'];
                    $trxPercentage = (int)$voidTrx/(int)$trxCount*100;
            
                    $qVI = "";
                    
                    $qVI = "SELECT SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN shift s ON s.id=tc.shift_id WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND s.partner_id='$partnerID'";
                    
                    $sqlVI = mysqli_query($db_conn, $qVI);
                    $resVI = mysqli_fetch_all($sqlVI, MYSQLI_ASSOC);
                    $voidItem=$resVI[0]['count'];
                    
                    $qIC = "";
                    
                    $qIC = "SELECT SUM(dt.qty) AS count FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id WHERE DATE(dt.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$partnerID'";
                    
                    $sqlIC = mysqli_query($db_conn, $qIC);
                    $resIC = mysqli_fetch_all($sqlIC, MYSQLI_ASSOC);
                    $itemCount = $resIC[0]['count'];
            
                    $itemPercentage = (int)$voidItem/(int)$itemCount*100;
                    $qMV = "";
            
                    $qMV = "SELECT m.nama AS menuName,SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id=tc.detail_transaction_id JOIN shift s ON s.id=tc.shift_id JOIN menu m ON m.id=dt.id_menu WHERE tc.deleted_at IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND s.partner_id='$partnerID' GROUP BY dt.id_menu
                      UNION ALL 
                      SELECT m.nama AS menuName,SUM(dt.qty) AS count FROM transaction_cancellation tc JOIN transaksi t ON t.id = tc.transaction_id JOIN detail_transaksi dt ON dt.id_transaksi=t.id JOIN shift s ON s.id=tc.shift_id JOIN menu m ON m.id=dt.id_menu WHERE tc.deleted_at IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND s.partner_id='$partnerID' GROUP BY dt.id_menu ORDER BY `count` DESC LIMIT 1";
                    
                    $sqlMV = mysqli_query($db_conn, $qMV);
                    $resMV = mysqli_fetch_all($sqlMV, MYSQLI_ASSOC);
                    // $mostVoid = $resMV[0]['menuName'];
                    $mostVoid = $resMV;
                    // $mvc = $resMV[0]['count'];
                    $mvc = $resMV;
                }
                
                $partner['voids'] = $data;
                $partner['voidTrx'] = $voidTrx;
                $partner['trxCount'] = $trxCount;
                $partner['trxPercentage'] = $trxPercentage;
                $partner['voidItem'] = $voidItem ?? 0;
                $partner['itemCount'] = $itemCount;
                $partner['itemPercentage'] = $itemPercentage;
                $partner['mostVoid'] = $mostVoid;
                $partner['mostVoidCount'] = $mvc;
                
                if(count($data) > 0) {
                    array_push($array, $partner);
                } 
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
}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$array]);

?>