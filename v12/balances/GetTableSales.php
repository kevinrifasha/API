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

// $id = $_GET['partnerID'];
// $dateTo = $_GET['dateTo'];
// $dateFrom = $_GET['dateFrom'];
// $values = [];
// $tot = [];

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
// $token = json_decode($tokenizer->stringEncryption('decrypt',$token));
// $idMaster = $token->masterID;
// $values = array();
// $all = "0";
// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

//     $status = $tokens['status'];
//     $msg = $tokens['msg'];
//     $success = 0;

// }else{
//     $i=0;
//     $arr=[];
//     $arr2=[];
//     $dateFromStr = str_replace("-","", $dateFrom);
//     $dateToStr = str_replace("-","", $dateTo);
//     $query = "";
//     if(isset($_GET['all'])) {
//         $all = $_GET['all'];
//     }
//     if($all !== "1") {
//         $query = "SELECT SUM(transaksi.total) AS sales, transaksi.no_meja FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.no_meja ORDER BY total DESC";
//     } else {
//         $query = "SELECT SUM(transaksi.total) AS sales, transaksi.no_meja FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.no_meja ORDER BY total DESC";
//     }
//     $sqlGetSales = mysqli_query($db_conn, $query);
    
//     if($all == "1") {
//         $query = "SELECT COUNT(transaksi.id) AS qty, transaksi.no_meja FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL GROUP BY transaksi.no_meja ORDER BY qty DESC";
//     } else {
//         $query = "SELECT COUNT(transaksi.id) AS qty, transaksi.no_meja FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL GROUP BY transaksi.no_meja ORDER BY qty DESC ";
//     }
//     $sqlGetQty = mysqli_query($db_conn, $query);

//     if(mysqli_num_rows($sqlGetSales) > 0 && mysqli_num_rows($sqlGetQty) > 0){
//         while($row=mysqli_fetch_assoc($sqlGetSales)){
//             $tableName = $row['no_meja'];
//             $sales = $row['sales'];
//             array_push($arr, array("name" => "$tableName", "sales" => $sales));
//             ($totalSales ?? $totalSales = 0) ? $totalSales += $sales : $totalSales = $sales;
//         }
//         while($row=mysqli_fetch_assoc($sqlGetQty)){
//             $tableName = $row['no_meja'];
//             $qty = $row['qty'];
//             array_push($arr2, array("name" => "$tableName", "qty" => $qty));
//             ($totalQty ?? $totalQty = 0) ? $totalQty += $qty : $totalQty = $qty;
//         }
//         $success=1;
//         $status=200;
//         $msg="Data ditemukan";
//     }else{
//         $success=0;
//         $status=400;
//         $msg="Data tidak ditemukan";
//     }
//     // if(mysqli_num_rows($sqlGetSales) > 0) {
//     //     $a = array();
//     //     $i = 0;
//     //     while($row2=mysqli_fetch_assoc($sqlGetSales)){
//     //         $a[$i]=$row2;
//     //         $namaMenu = $row2['no_meja'];
//     //         $qty = $row2['qty'];
//     //         array_push($arr, array("name" => "$namaMenu", "sales" => $qty));
//     //         $totalSales+= $qty;
//     //         $i+=1;
//     //     }
//     //     $i=0;
//     //     $tps = 0;
//     //     foreach($a as $v){
//     //         $arr[$i]['percentage']=round(($arr[$i]['sales']/$totalSales)*100,2);
//     //         $tps += round(($arr[$i]['sales']/$totalSales)*100,2);
//     //         $i+=1;
//     //     }
//     //     $i-=1;
//     //     if($tps>100){
//     //         $tps=100;
//     //     }
//     //     if($i>0){
//     //         $tps = $tps-$arr[$i]['percentage'];
//     //         $arr[$i]['percentage']=100-($tps);
//     //     }

//     //     $b = array();
//     //     $j = 0;
//     //     while($row3=mysqli_fetch_assoc($sqlGetQty)){
//     //         $b[$j]=$row3;
//     //         $namaMenu2 = $row3['no_meja'];
//     //         $qty2 = $row3['qty'];
//     //         array_push($arr2, array("name" => "$namaMenu2", "qty" => $qty2));
//     //         $totalQty+= $qty2;
//     //         $j+=1;
//     //     }
//     //     $j=0;
//     //     $tpq = 0;
//     //     foreach($b as $v){
//     //         $arr2[$j]['percentage']=round(($arr2[$j]['qty']/$totalQty)*100,2);
//     //         $tpq += $arr2[$j]['percentage'];
//     //         $j+=1;
//     //     }
//     //     $j-=1;
//     //     if($tpq>100){
//     //         $tpq=100;
//     //     }
//     //     if($j>0){
//     //         $tpq = $tpq-$arr2[$j]['percentage'];
//     //         $arr2[$j]['percentage']=100-$tpq;
//     //     }
//     //     $success = 1;
//     //     $status = 200;
//     //     $msg = "Success";
//     //     $sorted = array();
//     //     $sorted = array_column($arr, 'qty');
//     //     array_multisort($sorted, SORT_DESC, $arr);
//     //     $sorted2 = array();
//     //     $sorted2 = array_column($arr2, 'qty');
//     //     array_multisort($sorted, SORT_DESC, $arr2);
//     // }else{
//     //     $success = 0;
//     //     $status = 200;
//     //     $msg = "Data Not Found";
//     // }
// }
// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "tableSales"=>$arr,"tableQty"=>$arr2, "totalSales"=>$totalSales, "totalQty"=>$totalQty]);

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

$id = $_GET['partnerID'];
$dateTo = $_GET['dateTo'];
$dateFrom = $_GET['dateFrom'];

$newDateFormat = 0;

if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
    $dateTo = str_replace("%20"," ",$dateTo);
    $dateFrom = str_replace("%20"," ",$dateFrom);
    $newDateFormat = 1;
}

$values = [];
$tot = [];

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
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->masterID;
$values = array();
$all = "0";
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $i=0;
    $arr=[];
    $arr2=[];
    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);

    if($newDateFormat == 1)
    {
        $query = "";
        if(isset($_GET['all'])) {
            $all = $_GET['all'];
        }
        if($all !== "1") {
            $query = "SELECT SUM(transaksi.total) AS sales, transaksi.no_meja FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.no_meja ORDER BY total DESC";
        } else {
            $query = "SELECT SUM(transaksi.total) AS sales, transaksi.no_meja FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.no_meja ORDER BY total DESC";
        }
        $sqlGetSales = mysqli_query($db_conn, $query);
        
        if($all == "1") {
            $query = "SELECT COUNT(transaksi.id) AS qty, transaksi.no_meja FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status IN(1,2) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL GROUP BY transaksi.no_meja ORDER BY qty DESC";
        } else {
            $query = "SELECT COUNT(transaksi.id) AS qty, transaksi.no_meja FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL GROUP BY transaksi.no_meja ORDER BY qty DESC ";
        }
        $sqlGetQty = mysqli_query($db_conn, $query);

        if(mysqli_num_rows($sqlGetSales) > 0 && mysqli_num_rows($sqlGetQty) > 0){
            while($row=mysqli_fetch_assoc($sqlGetSales)){
                $tableName = $row['no_meja'];
                $sales = $row['sales'];
                array_push($arr, array("name" => "$tableName", "sales" => $sales));
                ($totalSales ?? $totalSales = 0) ? $totalSales += $sales : $totalSales = $sales;
            }
            while($row=mysqli_fetch_assoc($sqlGetQty)){
                $tableName = $row['no_meja'];
                $qty = $row['qty'];
                array_push($arr2, array("name" => "$tableName", "qty" => $qty));
                ($totalQty ?? $totalQty = 0) ? $totalQty += $qty : $totalQty = $qty;
            }
            $success=1;
            $status=200;
            $msg="Data ditemukan";
        }else{
            $success=0;
            $status=400;
            $msg="Data tidak ditemukan";
        }
        // if(mysqli_num_rows($sqlGetSales) > 0) {
        //     $a = array();
        //     $i = 0;
        //     while($row2=mysqli_fetch_assoc($sqlGetSales)){
        //         $a[$i]=$row2;
        //         $namaMenu = $row2['no_meja'];
        //         $qty = $row2['qty'];
        //         array_push($arr, array("name" => "$namaMenu", "sales" => $qty));
        //         $totalSales+= $qty;
        //         $i+=1;
        //     }
        //     $i=0;
        //     $tps = 0;
        //     foreach($a as $v){
        //         $arr[$i]['percentage']=round(($arr[$i]['sales']/$totalSales)*100,2);
        //         $tps += round(($arr[$i]['sales']/$totalSales)*100,2);
        //         $i+=1;
        //     }
        //     $i-=1;
        //     if($tps>100){
        //         $tps=100;
        //     }
        //     if($i>0){
        //         $tps = $tps-$arr[$i]['percentage'];
        //         $arr[$i]['percentage']=100-($tps);
        //     }

        //     $b = array();
        //     $j = 0;
        //     while($row3=mysqli_fetch_assoc($sqlGetQty)){
        //         $b[$j]=$row3;
        //         $namaMenu2 = $row3['no_meja'];
        //         $qty2 = $row3['qty'];
        //         array_push($arr2, array("name" => "$namaMenu2", "qty" => $qty2));
        //         $totalQty+= $qty2;
        //         $j+=1;
        //     }
        //     $j=0;
        //     $tpq = 0;
        //     foreach($b as $v){
        //         $arr2[$j]['percentage']=round(($arr2[$j]['qty']/$totalQty)*100,2);
        //         $tpq += $arr2[$j]['percentage'];
        //         $j+=1;
        //     }
        //     $j-=1;
        //     if($tpq>100){
        //         $tpq=100;
        //     }
        //     if($j>0){
        //         $tpq = $tpq-$arr2[$j]['percentage'];
        //         $arr2[$j]['percentage']=100-$tpq;
        //     }
        //     $success = 1;
        //     $status = 200;
        //     $msg = "Success";
        //     $sorted = array();
        //     $sorted = array_column($arr, 'qty');
        //     array_multisort($sorted, SORT_DESC, $arr);
        //     $sorted2 = array();
        //     $sorted2 = array_column($arr2, 'qty');
        //     array_multisort($sorted, SORT_DESC, $arr2);
        // }else{
        //     $success = 0;
        //     $status = 200;
        //     $msg = "Data Not Found";
        // }
    }
    else
    {
        $query = "";
        if(isset($_GET['all'])) {
            $all = $_GET['all'];
        }
        if($all !== "1") {
            $query = "SELECT SUM(transaksi.total) AS sales, transaksi.no_meja FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.no_meja ORDER BY total DESC";
        } else {
            $query = "SELECT SUM(transaksi.total) AS sales, transaksi.no_meja FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.no_meja ORDER BY total DESC";
        }
        $sqlGetSales = mysqli_query($db_conn, $query);
        
        if($all == "1") {
            $query = "SELECT COUNT(transaksi.id) AS qty, transaksi.no_meja FROM transaksi JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL GROUP BY transaksi.no_meja ORDER BY qty DESC";
        } else {
            $query = "SELECT COUNT(transaksi.id) AS qty, transaksi.no_meja FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL GROUP BY transaksi.no_meja ORDER BY qty DESC ";
        }
        $sqlGetQty = mysqli_query($db_conn, $query);

        if(mysqli_num_rows($sqlGetSales) > 0 && mysqli_num_rows($sqlGetQty) > 0){
            while($row=mysqli_fetch_assoc($sqlGetSales)){
                $tableName = $row['no_meja'];
                $sales = $row['sales'];
                array_push($arr, array("name" => "$tableName", "sales" => $sales));
                ($totalSales ?? $totalSales = 0) ? $totalSales += $sales : $totalSales = $sales;
            }
            while($row=mysqli_fetch_assoc($sqlGetQty)){
                $tableName = $row['no_meja'];
                $qty = $row['qty'];
                array_push($arr2, array("name" => "$tableName", "qty" => $qty));
                ($totalQty ?? $totalQty = 0) ? $totalQty += $qty : $totalQty = $qty;
            }
            $success=1;
            $status=200;
            $msg="Data ditemukan";
        }else{
            $success=0;
            $status=400;
            $msg="Data tidak ditemukan";
        }
        // if(mysqli_num_rows($sqlGetSales) > 0) {
        //     $a = array();
        //     $i = 0;
        //     while($row2=mysqli_fetch_assoc($sqlGetSales)){
        //         $a[$i]=$row2;
        //         $namaMenu = $row2['no_meja'];
        //         $qty = $row2['qty'];
        //         array_push($arr, array("name" => "$namaMenu", "sales" => $qty));
        //         $totalSales+= $qty;
        //         $i+=1;
        //     }
        //     $i=0;
        //     $tps = 0;
        //     foreach($a as $v){
        //         $arr[$i]['percentage']=round(($arr[$i]['sales']/$totalSales)*100,2);
        //         $tps += round(($arr[$i]['sales']/$totalSales)*100,2);
        //         $i+=1;
        //     }
        //     $i-=1;
        //     if($tps>100){
        //         $tps=100;
        //     }
        //     if($i>0){
        //         $tps = $tps-$arr[$i]['percentage'];
        //         $arr[$i]['percentage']=100-($tps);
        //     }

        //     $b = array();
        //     $j = 0;
        //     while($row3=mysqli_fetch_assoc($sqlGetQty)){
        //         $b[$j]=$row3;
        //         $namaMenu2 = $row3['no_meja'];
        //         $qty2 = $row3['qty'];
        //         array_push($arr2, array("name" => "$namaMenu2", "qty" => $qty2));
        //         $totalQty+= $qty2;
        //         $j+=1;
        //     }
        //     $j=0;
        //     $tpq = 0;
        //     foreach($b as $v){
        //         $arr2[$j]['percentage']=round(($arr2[$j]['qty']/$totalQty)*100,2);
        //         $tpq += $arr2[$j]['percentage'];
        //         $j+=1;
        //     }
        //     $j-=1;
        //     if($tpq>100){
        //         $tpq=100;
        //     }
        //     if($j>0){
        //         $tpq = $tpq-$arr2[$j]['percentage'];
        //         $arr2[$j]['percentage']=100-$tpq;
        //     }
        //     $success = 1;
        //     $status = 200;
        //     $msg = "Success";
        //     $sorted = array();
        //     $sorted = array_column($arr, 'qty');
        //     array_multisort($sorted, SORT_DESC, $arr);
        //     $sorted2 = array();
        //     $sorted2 = array_column($arr2, 'qty');
        //     array_multisort($sorted, SORT_DESC, $arr2);
        // }else{
        //     $success = 0;
        //     $status = 200;
        //     $msg = "Data Not Found";
        // }
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "tableSales"=>$arr,"tableQty"=>$arr2, "totalSales"=>$totalSales, "totalQty"=>$totalQty]);
