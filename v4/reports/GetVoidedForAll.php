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

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;
$array = [];

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $dateFrom = $_GET['dateFrom'];
    $dateTo =  $_GET['dateTo'];
    
    
    $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
    if(mysqli_num_rows($sqlPartner) > 0) {
        $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
        foreach($getPartners as $partner) {
            $partnerID = $partner['partner_id'];
            $voidTrx=0;
            $trxCount=0;
            $trxPercentage=0;
            $voidItem=0;
            $itemCount=0;
            $itemPercentage=0;
            $mostVoid="-";
            
            $addQuery1 = "s.partner_id='$partnerID'";
            $addQuery2 = "";
            
            $sql = mysqli_query($db_conn, "SELECT tc.id, m.nama, tc.qty, tc.notes, e.nama AS eName, vb.nama AS vbName, dt.id_transaksi AS transactionID FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id=tc.detail_transaction_id JOIN menu m ON m.id=dt.id_menu JOIN employees e ON e.id=tc.created_by JOIN shift s ON s.id=tc.shift_id LEFT JOIN employees vb ON vb.id=tc.acc_by ". $addQuery2 ." WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND ". $addQuery1 ." UNION ALL SELECT tc.id, m.nama, dt.qty, tc.notes, e.nama AS eName, vb.nama AS vbName, dt.id_transaksi AS transactionID FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id_transaksi=tc.transaction_id JOIN menu m ON m.id=dt.id_menu JOIN employees e ON e.id=tc.created_by JOIN shift s ON s.id=tc.shift_id LEFT JOIN employees vb ON vb.id=tc.acc_by ". $addQuery2 ." WHERE tc.deleted_at IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND ". $addQuery1 ."");
            
            if(mysqli_num_rows($sql) > 0) {
                $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
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
                
                $qVT = mysqli_query($db_conn, "SELECT COUNT(tc.id) AS count FROM transaction_cancellation tc JOIN shift s ON s.id=tc.shift_id ". $addQuery2 ." WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NOT NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND ". $addQuery1 ."");
                $resVT = mysqli_fetch_all($qVT, MYSQLI_ASSOC);
                $voidTrx=$resVT[0]['count']??0;
        
                $qVI = mysqli_query($db_conn, "SELECT SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN shift s ON s.id=tc.shift_id ". $addQuery2 ." WHERE tc.deleted_at IS NULL AND tc.transaction_id IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND ". $addQuery1 ."");
                $resVI = mysqli_fetch_all($qVI, MYSQLI_ASSOC);
                $voidItem=$resVI[0]['count']??0;
                
                $qMV = mysqli_query($db_conn, "SELECT m.nama AS menuName,SUM(tc.qty) AS count FROM transaction_cancellation tc JOIN detail_transaksi dt ON dt.id=tc.detail_transaction_id JOIN shift s ON s.id=tc.shift_id JOIN menu m ON m.id=dt.id_menu ". $addQuery2 ." WHERE tc.deleted_at IS NULL AND DATE(tc.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND ". $addQuery1 ." GROUP BY dt.id_menu ORDER BY SUM(tc.qty) DESC LIMIT 1");
                $resMV = mysqli_fetch_all($qMV, MYSQLI_ASSOC);
                $mostVoid = $resMV[0]['menuName']??"-";
                $mvc = $resMV[0]['count']??0;
                
                $addQuery1 = "t.id_partner='$partnerID'";
                $addQuery2 = "";
                
                $qTC = mysqli_query($db_conn, "SELECT COUNT(t.id) AS count FROM transaksi t ". $addQuery2 ." WHERE DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' AND ". $addQuery1 ."");
                $resTC = mysqli_fetch_all($qTC, MYSQLI_ASSOC);
                $trxCount = $resTC[0]['count']??0;
        
                $trxPercentage = (int)$voidTrx/(int)$trxCount*100;
        
                $qIC = mysqli_query($db_conn, "SELECT SUM(dt.qty) AS count FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id ". $addQuery2 ." WHERE DATE(dt.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND ". $addQuery1 ."");
                $resIC = mysqli_fetch_all($qIC, MYSQLI_ASSOC);
                $itemCount = $resIC[0]['count']??0;
        
                $itemPercentage = (int)$voidItem/(int)$itemCount*100;
            } else{
                $data = [];
            }
            
            $partner['voids'] = $data;
            $partner['voidTrx'] = $voidTrx;
            $partner['trxCount'] = $trxCount;
            $partner['trxPercentage'] = $trxPercentage;
            $partner['voidItem'] = $voidItem;
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

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$array]);

?>