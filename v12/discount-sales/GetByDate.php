<?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: access");
// header("Access-Control-Allow-Methods: GET");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// require_once("./../tokenModels/tokenManager.php"); 
// require_once("../connection.php");
// require '../../db_connection.php';
// require  __DIR__ . '/../../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
// $dotenv->load();

// $headers = array();
//     $rx_http = '/\AHTTP_/';
//     foreach($_SERVER as $key => $val) {
//       if( preg_match($rx_http, $key) ) {
//         $arh_key = preg_replace($rx_http, '', $key);
//         $rx_matches = array();
//         // do some nasty string manipulations to restore the original letter case
//         // this should work in most cases
//         $rx_matches = explode('_', $arh_key);
//         if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
//           foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
//           $arh_key = implode('-', $rx_matches);
//         }
//         $headers[$arh_key] = $val;
//       }
//     }
// $token = '';
    
// foreach ($headers as $header => $value) {
//     if($header=="Authorization" || $header=="AUTHORIZATION"){
//         $token=substr($value,7);
//     }
// }

// $db = connectBase();
// $tokenizer = new TokenManager($db);
// $tokens = $tokenizer->validate($token);
// $tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
// $idMaster = $tokenDecoded->masterID;
// $value = array();
// $success=0;
// $msg = 'Failed';
// $all = "0";
// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
//     $status = $tokens['status'];
//     $msg = $tokens['msg']; 
//     $success = 0;
    
// }else{
//     $arr = [];
//     $i=3;
//     $totalVoucher = 0;
//     $id = $_GET['id'];
//     $dateTo = $_GET['dateTo'];
//     $dateFrom = $_GET['dateFrom'];
//     $totalSales = 0;
//     $totalQty = 0;
//     $i = 0;
//     $where = "";
//     if(isset($_GET['all'])) {
//         $all = $_GET['all'];
//     }
//     if($all !== "1") {
//         $idMaster = null;
//         $where = "trx.id_partner='$id' AND trx.deleted_at IS NULL AND DATE(trx.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND trx.status in(1,2) ";
//     } else {
//         $where = "p.id_master = '$idMaster' AND trx.deleted_at IS NULL AND DATE(trx.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND trx.status in(1,2) ";
//     }
    
//     $query = "SELECT 'Diskon Pelanggan Spesial' AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.diskon_spesial),0) as sales FROM transaksi as trx JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.diskon_spesial,0) > 0 AND ".$where."
//     UNION ALL
//     SELECT CONCAT(trx.employee_discount_percent,'%') AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.employee_discount),0) as sales FROM transaksi as trx  JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.employee_discount,0) > 0 AND trx.employee_discount_percent!=0 AND ".$where." GROUP BY trx.employee_discount_percent
//     UNION ALL
//     SELECT 'Diskon Program' AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.program_discount),0) as sales FROM transaksi as trx JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.program_discount,0) > 0 AND ".$where."
//     UNION ALL
//     SELECT v.title AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.promo),0) as sales FROM transaksi as trx JOIN voucher AS v ON trx.id_voucher = v.code JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.promo,0) > 0 AND ".$where." GROUP BY trx.id_voucher
//     UNION ALL
//     SELECT v.title AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.promo),0) as sales FROM transaksi as trx JOIN redeemable_voucher AS v ON trx.id_voucher_redeemable = v.code JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.promo,0) > 0 AND ".$where." GROUP BY trx.id_voucher_redeemable
//     UNION ALL
//     SELECT v.title AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.promo),0) as sales FROM transaksi as trx JOIN membership_voucher AS v ON trx.id_voucher_redeemable = v.code JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.promo,0) > 0 AND ".$where." GROUP BY trx.id_voucher_redeemable";
//     $q = mysqli_query($db_conn, $query);
//     while($row = mysqli_fetch_assoc($q)){
//         if($row['sales']>0){
//             $arr[$i]['name']=$row['name'];
//             $arr[$i]['sales']=(int)$row['sales'];
//             $arr[$i]['qty']=(int)$row['qty'];
//             // $totalSales += $arr[$i]['sales'];
//             ($totalSales ?? $totalSales = 0) ? $totalSales += $arr[$i]['sales'] : $totalSales = $arr[$i]['sales'];
//             // $totalQty += $arr[$i]['qty'];
//             ($totalQty ?? $totalQty = 0) ? $totalQty += $arr[$i]['qty'] : $totalQty = $arr[$i]['qty'];
//             $i++;
//         }
//     }
//     $index = 0;
//     $tps = 0;
//     $tpq = 0;
//     foreach($arr as $v){
//         $arr[$index]['percentage_sales']=ceil(($v['sales']/$totalSales)*100);
//         if(is_infinite( $arr[$index]['percentage_sales']) || is_nan($arr[$index]['percentage_sales'])){
//             $arr[$index]['percentage_sales']=0;
//         }
//         $arr[$index]['percentage_qty']=ceil(($v['qty']/$totalQty)*100);
//         if(is_nan($arr[$index]['percentage_qty']) || is_infinite($arr[$index]['percentage_qty'])){
//             $arr[$index]['percentage_qty']=0;
//         }
//         $tps += ceil(($v['sales']/$totalSales)*100);
//         $tpq += ceil(($v['qty']/$totalQty)*100);
//         $index+=1;
//     }
//     $index-=1;
//     if($tps>100){
//         $tps = 100;
//     }
//     if($tpq>100){
//         $tpq = 100;
//     }
//     if($index>0){
//         $arr[$index]['percentage_sales']=100 - ($tps-$arr[$index]['percentage_sales']);
//         if(is_infinite( $arr[$index]['percentage_sales']) || is_nan($arr[$index]['percentage_sales'])){
//             $arr[$index]['percentage_sales']=0;
//         }
//         $arr[$index]['percentage_qty']=100 - ($tpq-$arr[$index]['percentage_qty']);
//         if(is_nan($arr[$index]['percentage_qty']) || is_infinite($arr[$index]['percentage_qty'])){
//             $arr[$index]['percentage_qty']=0;
//         }
//     }
//     $success =1;
//     $status =200;
//     $msg = "Success";
// }
// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "discount"=>$arr, "total"=>$totalSales, "totalQty"=>$totalQty, "q"=>$query]);  

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

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
$all = "0";
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $arr = [];
    $i=3;
    $totalVoucher = 0;
    $id = $_GET['id'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];


    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    $totalSales = 0;
    $totalQty = 0;
    $i = 0;
    $where = "";

    if($newDateFormat == 1)
    {  
        if(isset($_GET['all'])) {
            $all = $_GET['all'];
        }
    
        if($all !== "1") {
            $idMaster = null;
            $where = "trx.id_partner='$id' AND trx.deleted_at IS NULL AND trx.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND trx.status in(1,2) ";
        } else {
            $where = "p.id_master = '$idMaster' AND trx.deleted_at IS NULL AND trx.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND trx.status in(1,2) ";
        }
        
        $query = "SELECT 'Diskon Pelanggan Spesial' AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.diskon_spesial),0) as sales FROM transaksi as trx JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.diskon_spesial,0) > 0 AND ".$where."
        UNION ALL
        SELECT CONCAT(trx.employee_discount_percent,'%') AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.employee_discount),0) as sales FROM transaksi as trx  JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.employee_discount,0) > 0 AND trx.employee_discount_percent!=0 AND ".$where." GROUP BY trx.employee_discount_percent
        UNION ALL
        SELECT 'Diskon Program' AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.program_discount),0) as sales FROM transaksi as trx JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.program_discount,0) > 0 AND ".$where."
        UNION ALL
        SELECT v.title AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.promo),0) as sales FROM transaksi as trx JOIN voucher AS v ON trx.id_voucher = v.code JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.promo,0) > 0 AND ".$where." GROUP BY trx.id_voucher
        UNION ALL
        SELECT v.title AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.promo),0) as sales FROM transaksi as trx JOIN redeemable_voucher AS v ON trx.id_voucher_redeemable = v.code JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.promo,0) > 0 AND ".$where." GROUP BY trx.id_voucher_redeemable
        UNION ALL
        SELECT v.title AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.promo),0) as sales FROM transaksi as trx JOIN membership_voucher AS v ON trx.id_voucher_redeemable = v.code JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.promo,0) > 0 AND ".$where." GROUP BY trx.id_voucher_redeemable";
        $q = mysqli_query($db_conn, $query);
        while($row = mysqli_fetch_assoc($q)){
            if($row['sales']>0){
                $arr[$i]['name']=$row['name'];
                $arr[$i]['sales']=(int)$row['sales'];
                $arr[$i]['qty']=(int)$row['qty'];
                // $totalSales += $arr[$i]['sales'];
                ($totalSales ?? $totalSales = 0) ? $totalSales += $arr[$i]['sales'] : $totalSales = $arr[$i]['sales'];
                // $totalQty += $arr[$i]['qty'];
                ($totalQty ?? $totalQty = 0) ? $totalQty += $arr[$i]['qty'] : $totalQty = $arr[$i]['qty'];
                $i++;
            }
        }
        $index = 0;
        $tps = 0;
        $tpq = 0;
        foreach($arr as $v){
            $arr[$index]['percentage_sales']=ceil(($v['sales']/$totalSales)*100);
            if(is_infinite( $arr[$index]['percentage_sales']) || is_nan($arr[$index]['percentage_sales'])){
                $arr[$index]['percentage_sales']=0;
            }
            $arr[$index]['percentage_qty']=ceil(($v['qty']/$totalQty)*100);
            if(is_nan($arr[$index]['percentage_qty']) || is_infinite($arr[$index]['percentage_qty'])){
                $arr[$index]['percentage_qty']=0;
            }
            $tps += ceil(($v['sales']/$totalSales)*100);
            $tpq += ceil(($v['qty']/$totalQty)*100);
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
    else
    {
        if(isset($_GET['all'])) {
            $all = $_GET['all'];
        }
    
        if($all !== "1") {
            $idMaster = null;
            $where = "trx.id_partner='$id' AND trx.deleted_at IS NULL AND DATE(trx.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND trx.status in(1,2) ";
        } else {
            $where = "p.id_master = '$idMaster' AND trx.deleted_at IS NULL AND DATE(trx.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND trx.status in(1,2) ";
        }
        
        $query = "SELECT 'Diskon Pelanggan Spesial' AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.diskon_spesial),0) as sales FROM transaksi as trx JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.diskon_spesial,0) > 0 AND ".$where."
        UNION ALL
        SELECT CONCAT(trx.employee_discount_percent,'%') AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.employee_discount),0) as sales FROM transaksi as trx  JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.employee_discount,0) > 0 AND trx.employee_discount_percent!=0 AND ".$where." GROUP BY trx.employee_discount_percent
        UNION ALL
        SELECT 'Diskon Program' AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.program_discount),0) as sales FROM transaksi as trx JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.program_discount,0) > 0 AND ".$where."
        UNION ALL
        SELECT v.title AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.promo),0) as sales FROM transaksi as trx JOIN voucher AS v ON trx.id_voucher = v.code JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.promo,0) > 0 AND ".$where." GROUP BY trx.id_voucher
        UNION ALL
        SELECT v.title AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.promo),0) as sales FROM transaksi as trx JOIN redeemable_voucher AS v ON trx.id_voucher_redeemable = v.code JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.promo,0) > 0 AND ".$where." GROUP BY trx.id_voucher_redeemable
        UNION ALL
        SELECT v.title AS name, COUNT(trx.id) AS qty, IFNULL(SUM(trx.promo),0) as sales FROM transaksi as trx JOIN membership_voucher AS v ON trx.id_voucher_redeemable = v.code JOIN partner p ON p.id = trx.id_partner WHERE IFNULL(trx.promo,0) > 0 AND ".$where." GROUP BY trx.id_voucher_redeemable";
        $q = mysqli_query($db_conn, $query);
        while($row = mysqli_fetch_assoc($q)){
            if($row['sales']>0){
                $arr[$i]['name']=$row['name'];
                $arr[$i]['sales']=(int)$row['sales'];
                $arr[$i]['qty']=(int)$row['qty'];
                // $totalSales += $arr[$i]['sales'];
                ($totalSales ?? $totalSales = 0) ? $totalSales += $arr[$i]['sales'] : $totalSales = $arr[$i]['sales'];
                // $totalQty += $arr[$i]['qty'];
                ($totalQty ?? $totalQty = 0) ? $totalQty += $arr[$i]['qty'] : $totalQty = $arr[$i]['qty'];
                $i++;
            }
        }
        $index = 0;
        $tps = 0;
        $tpq = 0;
        foreach($arr as $v){
            $arr[$index]['percentage_sales']=ceil(($v['sales']/$totalSales)*100);
            if(is_infinite( $arr[$index]['percentage_sales']) || is_nan($arr[$index]['percentage_sales'])){
                $arr[$index]['percentage_sales']=0;
            }
            $arr[$index]['percentage_qty']=ceil(($v['qty']/$totalQty)*100);
            if(is_nan($arr[$index]['percentage_qty']) || is_infinite($arr[$index]['percentage_qty'])){
                $arr[$index]['percentage_qty']=0;
            }
            $tps += ceil(($v['sales']/$totalSales)*100);
            $tpq += ceil(($v['qty']/$totalQty)*100);
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


}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "discount"=>$arr, "total"=>$totalSales, "totalQty"=>$totalQty, "q"=>$query]);  

?>