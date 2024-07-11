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

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $tokenDecoded->masterID;
$value = array();
$success=0;
$msg = 'Failed';
$arr = [];
$all = "0";

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $partnerID = $_GET['partnerID'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    
    $newDateFormat = 0;
    
    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }
    
    $query = "";
     if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($newDateFormat == 1){
        if($all !== "1") {
            $idMaster = null;
            $query = "SELECT ar.id, ar.user_name, ar.user_phone, ar.company, ar.deadline, ar.status, e.nama AS employeeName, em.nama AS receiverName, ar.transaction_id, ar.group_id, pm.nama AS pmName FROM account_receivables ar JOIN payment_method pm ON pm.id=ar.payment_method_id JOIN employees e ON ar.created_by=e.id JOIN employees em ON ar.received_by=em.id WHERE ar.partner_id='$partnerID' AND ar.deleted_at IS NULL AND ar.status=1 AND ar.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND ar.received_by!=0 ORDER BY id DESC";
        } else {
            $query = "SELECT ar.id, ar.user_name, ar.user_phone, ar.company, ar.deadline, ar.status, e.nama AS employeeName, em.nama AS receiverName, ar.transaction_id, ar.group_id, pm.nama AS pmName FROM account_receivables ar JOIN payment_method pm ON pm.id=ar.payment_method_id JOIN employees e ON ar.created_by=e.id JOIN employees em ON ar.received_by=em.id WHERE ar.master_id = '$idMaster' AND ar.deleted_at IS NULL AND ar.status=1 AND ar.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND ar.received_by!=0 ORDER BY id DESC";
        }
        
        $res = mysqli_query($db_conn, $query);
        if(mysqli_num_rows($res) > 0) {
            $i=0;
            $res = mysqli_fetch_all($res, MYSQLI_ASSOC);
            foreach($res as $x){
                $arr[$i]=$x;
                $j=0;
                if($x['group_id']==0||$x['group_id']=="0"){
                    $transactionID=$x['transaction_id'];
                    $getTransactions = mysqli_query($db_conn,"SELECT t.total,
                    t.program_discount,
                    t.promo,
                    t.diskon_spesial,
                    t.employee_discount,
                    t.service,
                    t.tax,
                    t.charge_ur  FROM transaksi t WHERE t.id='$transactionID'");
                    $resTrx = mysqli_fetch_all($getTransactions, MYSQLI_ASSOC);
                    foreach($resTrx as $y){
                        $subtotal = $y['total']-$y['program_discount']-$y['promo']-$y['diskon_spesial']-$y['employee_discount'];
                        $service = ceil($subtotal*$y['service']/100);
                        $serviceandCharge = $service + $y['charge_ur'];
                        $tax = ceil(($subtotal+$serviceandCharge)*$y['tax']/100);
                        $grandTotal = $subtotal+$serviceandCharge+$tax;
                        $arr[$i]['total']=$grandTotal;
                        $j++;
                    }
                }else{
                    $groupID = $x['group_id'];
                    $getTransactions = mysqli_query($db_conn,"SELECT SUM(t.total) AS total,
                    SUM(t.program_discount) AS program_discount,
                    SUM(t.promo) AS promo,
                    SUM(t.diskon_spesial) AS diskon_spesial,
                    SUM(t.employee_discount) AS employee_discount,
                    t.service,
                    t.tax,
                    SUM(t.charge_ur) AS charge_ur  FROM transaksi t WHERE t.group_id='$groupID'");
                    $resTrx = mysqli_fetch_all($getTransactions, MYSQLI_ASSOC);
                    foreach($resTrx as $y){
                        $subtotal = $y['total']-$y['program_discount']-$y['promo']-$y['diskon_spesial']-$y['employee_discount'];
                        $service = floor($subtotal*$y['service']/100);
                        $serviceandCharge = $service + $y['charge_ur'];
                        $tax = floor(($subtotal+$serviceandCharge)*$y['tax']/100);
                        $grandTotal = $subtotal+$serviceandCharge+$tax;
                        $arr[$i]['total']=$grandTotal;
                        $j++;
                    }
                }
                $i++;
            }
            // foreach($res as $x){
            //     $subtotal = $x['total']-$x['program_discount']-$x['promo']-$x['diskon_spesial']-$x['employee_discount'];
            //     $service = ceil($subtotal*$x['service']/100);
            //     $serviceandCharge = $service + $x['charge_ur'];
            //     $tax = ceil(($subtotal+$serviceandCharge)*$x['tax']/100);
            //     $grandTotal = $subtotal+$serviceandCharge+$tax;
            //     $arr[$i]=$x;
            //     $arr[$i]['total']=$grandTotal;
            //     $i++;
            // }
            $success = 1;
            $status = 200;
            $msg = "Success";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    }
    else{
        if($all != "1") {
            $idMaster = null;
            $query = "SELECT ar.id, ar.user_name, ar.user_phone, ar.company, ar.deadline, ar.status, e.nama AS employeeName, em.nama AS receiverName, ar.transaction_id, ar.group_id, pm.nama AS pmName FROM account_receivables ar JOIN payment_method pm ON pm.id=ar.payment_method_id JOIN employees e ON ar.created_by=e.id JOIN employees em ON ar.received_by=em.id WHERE ar.partner_id='$partnerID' AND ar.deleted_at IS NULL AND ar.status=1 AND ar.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND ar.received_by!=0 ORDER BY id DESC";
        } else {
            $query = "SELECT ar.id, ar.user_name, ar.user_phone, ar.company, ar.deadline, ar.status, e.nama AS employeeName, em.nama AS receiverName, ar.transaction_id, ar.group_id, pm.nama AS pmName FROM account_receivables ar JOIN payment_method pm ON pm.id=ar.payment_method_id JOIN employees e ON ar.created_by=e.id JOIN employees em ON ar.received_by=em.id WHERE ar.master_id = '$idMaster' AND ar.deleted_at IS NULL AND ar.status=1 AND ar.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND ar.received_by!=0 ORDER BY id DESC";
        }
        
        $res = mysqli_query($db_conn, $query);
        if(mysqli_num_rows($res) > 0) {
            $i=0;
            $res = mysqli_fetch_all($res, MYSQLI_ASSOC);
            foreach($res as $x){
                $arr[$i]=$x;
                $j=0;
                if($x['group_id']==0||$x['group_id']=="0"){
                    $transactionID=$x['transaction_id'];
                    $getTransactions = mysqli_query($db_conn,"SELECT t.total,
                    t.program_discount,
                    t.promo,
                    t.diskon_spesial,
                    t.employee_discount,
                    t.service,
                    t.tax,
                    t.charge_ur  FROM transaksi t WHERE t.id='$transactionID'");
                    $resTrx = mysqli_fetch_all($getTransactions, MYSQLI_ASSOC);
                    foreach($resTrx as $y){
                        $subtotal = $y['total']-$y['program_discount']-$y['promo']-$y['diskon_spesial']-$y['employee_discount'];
                        $service = ceil($subtotal*$y['service']/100);
                        $serviceandCharge = $service + $y['charge_ur'];
                        $tax = ceil(($subtotal+$serviceandCharge)*$y['tax']/100);
                        $grandTotal = $subtotal+$serviceandCharge+$tax;
                        $arr[$i]['total']=$grandTotal;
                        $j++;
                    }
                }else{
                    $groupID = $x['group_id'];
                    $getTransactions = mysqli_query($db_conn,"SELECT SUM(t.total) AS total,
                    SUM(t.program_discount) AS program_discount,
                    SUM(t.promo) AS promo,
                    SUM(t.diskon_spesial) AS diskon_spesial,
                    SUM(t.employee_discount) AS employee_discount,
                    t.service,
                    t.tax,
                    SUM(t.charge_ur) AS charge_ur  FROM transaksi t WHERE t.group_id='$groupID'");
                    $resTrx = mysqli_fetch_all($getTransactions, MYSQLI_ASSOC);
                    foreach($resTrx as $y){
                        $subtotal = $y['total']-$y['program_discount']-$y['promo']-$y['diskon_spesial']-$y['employee_discount'];
                        $service = floor($subtotal*$y['service']/100);
                        $serviceandCharge = $service + $y['charge_ur'];
                        $tax = floor(($subtotal+$serviceandCharge)*$y['tax']/100);
                        $grandTotal = $subtotal+$serviceandCharge+$tax;
                        $arr[$i]['total']=$grandTotal;
                        $j++;
                    }
                }
                $i++;
            }
            // foreach($res as $x){
            //     $subtotal = $x['total']-$x['program_discount']-$x['promo']-$x['diskon_spesial']-$x['employee_discount'];
            //     $service = ceil($subtotal*$x['service']/100);
            //     $serviceandCharge = $service + $x['charge_ur'];
            //     $tax = ceil(($subtotal+$serviceandCharge)*$x['tax']/100);
            //     $grandTotal = $subtotal+$serviceandCharge+$tax;
            //     $arr[$i]=$x;
            //     $arr[$i]['total']=$grandTotal;
            //     $i++;
            // }
            $success = 1;
            $status = 200;
            $msg = "Success";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    }
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "accountReceivables"=>$arr]);

?>