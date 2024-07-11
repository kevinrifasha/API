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
$arr = [];
$totalSales = 0;
$totalQty = 0;
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
    $id = $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $id = $_GET['partnerID']; 
    }
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($all == "1") {
        $addQuery1 = "p.id_master='$idMaster'";
        $addQuery2 = "JOIN partner p ON p.id = trx.id_partner";
    } else {
        $addQuery1 = "trx.id_partner='$id'";
        $addQuery2 = "";
    }
    
    $i = 0;
    $where = "". $addQuery1 ." AND trx.deleted_at IS NULL AND DATE(trx.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND trx.status in(1,2) ";
    
    $query = "SELECT 'Diskon Pelanggan Spesial' AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.diskon_spesial),0) as sales FROM transaksi as trx ". $addQuery2 ."  WHERE IFNULL(trx.diskon_spesial,0) > 0 AND ".$where."
    UNION ALL
    SELECT 'Diskon Karyawan' AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.employee_discount),0) as sales FROM transaksi as trx ". $addQuery2 ." WHERE IFNULL(trx.employee_discount,0) > 0 AND ".$where."
    UNION ALL
    SELECT 'Diskon Program' AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.program_discount),0) as sales FROM transaksi as trx ". $addQuery2 ." WHERE IFNULL(trx.program_discount,0) > 0 AND ".$where."
    UNION ALL
    SELECT v.title AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.promo),0) as sales FROM transaksi as trx JOIN voucher AS v ON trx.id_voucher = v.code ". $addQuery2 ." WHERE IFNULL(trx.promo,0) > 0 AND ".$where." GROUP BY trx.id_voucher
    UNION ALL
    SELECT v.title AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.promo),0) as sales FROM transaksi as trx JOIN redeemable_voucher AS v ON trx.id_voucher_redeemable = v.code ". $addQuery2 ." WHERE IFNULL(trx.promo,0) > 0 AND ".$where." GROUP BY trx.id_voucher_redeemable
    UNION ALL
    SELECT v.title AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.promo),0) as sales FROM transaksi as trx JOIN membership_voucher AS v ON trx.id_voucher_redeemable = v.code ". $addQuery2 ." WHERE IFNULL(trx.promo,0) > 0 AND ".$where." GROUP BY trx.id_voucher_redeemable";
    
    $q = mysqli_query($db_conn, $query);
    while($row = mysqli_fetch_assoc($q)){
        if($row['sales']>0){
            $arr[$i]['name']=$row['name'];
            $arr[$i]['sales']=(int)$row['sales'];
            $arr[$i]['qty']=(int)$row['qty'];
            $totalSales += $arr[$i]['sales'];
            $totalQty += $arr[$i]['qty'];
            $i++;
        }
    }
    $index = 0;
    $tps = 0;
    $tpq = 0;
    foreach($arr as $v){
        $arr[$index]['percentage_sales']=round(($v['sales']/$totalSales)*100,2);
        if(is_infinite( $arr[$index]['percentage_sales']) || is_nan($arr[$index]['percentage_sales'])){
            $arr[$index]['percentage_sales']=0;
        }
        $arr[$index]['percentage_qty']=round(($v['qty']/$totalQty)*100,2);
        if(is_nan($arr[$index]['percentage_qty']) || is_infinite($arr[$index]['percentage_qty'])){
            $arr[$index]['percentage_qty']=0;
        }
        $tps += round(($v['sales']/$totalSales)*100,2);
        $tpq += round(($v['qty']/$totalQty)*100,2);
        $index+=1;
    }
    $index-=1;
    if($tps>100){
        $tps = 100;
    }
    if($tpq>100){
        $tpq = 100;
    }
    if($index>0){
        $arr[$index]['percentage_sales']=100 - ($tps-$arr[$index]['percentage_sales']);
        if(is_infinite( $arr[$index]['percentage_sales']) || is_nan($arr[$index]['percentage_sales'])){
            $arr[$index]['percentage_sales']=0;
        }
        $arr[$index]['percentage_qty']=100 - ($tpq-$arr[$index]['percentage_qty']);
        if(is_nan($arr[$index]['percentage_qty']) || is_infinite($arr[$index]['percentage_qty'])){
            $arr[$index]['percentage_qty']=0;
        }
    }
    $success =1;
    $status =200;
    $msg = "Success";
}
// if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
//         http_response_code(200);
//     }else{
//         http_response_code($status);
//     }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "discount"=>$arr, "total"=>$totalSales, "totalQty"=>$totalQty]);  
?>