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
$value = array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $partnerID = $_GET['partnerID'];
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    if(isset($_GET['status'])){
        $status = $_GET['status'];
        $status = " AND ar.status='$status'";
    }else{
        $status="";
    }

        $query = "SELECT ar.id, ar.user_name, ar.user_phone, ar.company, ar.deadline, ar.status, e.nama AS employeeName, ar.transaction_id, ar.group_id FROM account_receivables ar JOIN employees e ON ar.created_by=e.id WHERE ar.partner_id='$partnerID' AND ar.deleted_at IS NULL".$status." AND DATE(ar.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY id DESC";


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
                    $service = ceil($subtotal*$y['service']/100);
                    $serviceandCharge = $service + $y['charge_ur'];
                    $tax = ceil(($subtotal+$serviceandCharge)*$y['tax']/100);
                    $grandTotal = $subtotal+$serviceandCharge+$tax;
                    $arr[$i]['total']=$grandTotal;
                    $j++;
                }
            }
            $i++;
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "accountReceivables"=>$arr]);

?>