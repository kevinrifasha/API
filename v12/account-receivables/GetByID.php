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
    $id=$_GET['id'];
    $query = "SELECT ar.transaction_id, ar.group_id FROM account_receivables ar WHERE ar.id='$id'";
    $res = mysqli_query($db_conn, $query);
    if(mysqli_num_rows($res) > 0) {
        $res = mysqli_fetch_all($res, MYSQLI_ASSOC);
        $x = $res[0];
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
                $arr = $resTrx[0];
                foreach($resTrx as $y){
                    $subtotal = $y['total']-$y['program_discount']-$y['promo']-$y['diskon_spesial']-$y['employee_discount'];
                    $service = ceil($subtotal*$y['service']/100);
                    $serviceandCharge = $service + $y['charge_ur'];
                    $tax = ceil(($subtotal+$serviceandCharge)*$y['tax']/100);
                    $grandTotal = $subtotal+$serviceandCharge+$tax;
                    $arr['grandTotal']=$grandTotal;
                    $getDetails = mysqli_query($db_conn, "SELECT dt.id, dt.id_menu, m.nama, dt.server_id, dt.harga_satuan, dt.qty, dt.harga FROM detail_transaksi dt JOIN menu m ON m.id=dt.id_menu WHERE dt.deleted_at IS NULL AND dt.status!=4 AND dt.id_transaksi='$transactionID'");
                    $details = mysqli_fetch_all($getDetails, MYSQLI_ASSOC);
                    $arr['details']=$details;
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
                SUM(t.charge_ur) AS charge_ur  FROM transaksi t WHERE t.group_id='$groupID' AND t.organization='Natta'");
                $resTrx = mysqli_fetch_all($getTransactions, MYSQLI_ASSOC);
                $arr = $resTrx[0];

                foreach($resTrx as $y){
                    $subtotal = $y['total']-$y['program_discount']-$y['promo']-$y['diskon_spesial']-$y['employee_discount'];
                    $service = ceil($subtotal*$y['service']/100);
                    $serviceandCharge = $service + $y['charge_ur'];
                    $tax = ceil(($subtotal+$serviceandCharge)*$y['tax']/100);
                    $grandTotal = $subtotal+$serviceandCharge+$tax;
                    $arr['grandTotal']=$grandTotal;
                    $getDetails = mysqli_query($db_conn, "SELECT dt.id, dt.id_menu, m.nama, dt.server_id, dt.harga_satuan, dt.qty, dt.harga FROM detail_transaksi dt JOIN menu m ON m.id=dt.id_menu JOIN transaksi t ON t.id=dt.id_transaksi WHERE dt.deleted_at IS NULL AND dt.status!=4 AND t.group_id='$groupID'");
                    $details = mysqli_fetch_all($getDetails, MYSQLI_ASSOC);
                    $arr['details']=$details;
                }
            }
        // foreach($res as $x){
        //     $subtotal = $x['total']-$x['program_discount']-$x['promo']-$x['diskon_spesial']-$x['employee_discount'];
        //     $service = ceil($subtotal*$x['service']/100);
        //     $serviceandCharge = $service + $x['charge_ur'];
        //     $tax = ceil(($subtotal+$serviceandCharge)*$x['tax']/100);
        //     $grandTotal = $subtotal+$serviceandCharge+$tax;
        //     $arr=$x;
        //     $arr['total']=$grandTotal;
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
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "details"=>$arr]);

?>