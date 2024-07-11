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
//     $i=0;
//     $id = $_GET['id'];
//     $dateTo = $_GET['dateTo'];
//     $dateFrom = $_GET['dateFrom'];
//     $total = 0;
//     // $rows = "";
//     $query = "";
//     if(isset($_GET['all'])) {
//         $all = $_GET['all'];
//     }
//     if($all !== "1") {
//         $query = "SELECT SUM(detail_transaksi.harga) AS qty, categories.name AS nama, COUNT(menu.id_category) AS cat_qty FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 GROUP BY categories.id ORDER BY qty DESC";
//     } else {
//         $query = "SELECT SUM(detail_transaksi.harga) AS qty, categories.name AS nama, COUNT(menu.id_category) AS cat_qty FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 GROUP BY categories.name ORDER BY qty DESC";
//     }
    
//     $sqlGetSales = mysqli_query($db_conn, $query);
//         while($row2=mysqli_fetch_assoc($sqlGetSales)){
//             $namaMenu2 = $row2['nama'];
//             $qty1 =(int) $row2['cat_qty'];
//             $qty2 =(int) $row2['qty'];
//             array_push($arr, array("name" => "$namaMenu2", "sales" => $qty2, "category_qty" => $qty1));
//             $total += $qty2;
//         }

//         // if(mysqli_num_rows($sqlGetSales) > 0) {
//         //     $data = mysqli_fetch_all($sqlGetSales, MYSQLI_ASSOC);
//         //     $rows = $data;
//         // }

//         // $dateFromStr = str_replace("-","", $dateFrom);
//         // $dateToStr = str_replace("-","", $dateTo);
//         // $queryTrans = "SELECT table_name FROM information_schema.tables
//         // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//         // $transaksi = mysqli_query($db_conn, $queryTrans);
//         // while($row=mysqli_fetch_assoc($transaksi)){
//         //     $table_name = explode("_",$row['table_name']);
//         //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//         //         $sqlGetSales = mysqli_query($db_conn, "SELECT SUM(`$detail_transactions`.harga) AS qty, categories.name AS nama FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN categories ON categories.id=menu.id_category
//         //         WHERE `$transactions`.id_partner='$id' AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY categories.id ORDER BY qty DESC ");
//         //         if(mysqli_num_rows($sqlGetSales) > 0) {
//         //             while($row2=mysqli_fetch_assoc($sqlGetSales)){
//         //                 $namaMenu2 = $row2['nama'];
//         //                 $qty2 =(int) $row2['qty'];
//         //                 $add =true;
//         //                 $arI = 0;
//         //                 foreach($arr as $ar){
//         //                     if($ar['name']==$namaMenu2){
//         //                         $add = false;
//         //                         $arr[$arI]['sales']+=$qty2;
//         //                         $total+= $qty2;
//         //                     }
//         //                     $arI +=1;
//         //                 }
//         //                 if($add==true){
//         //                     array_push($arr, array("name" => "$namaMenu2", "sales" => $qty2));
//         //                     $total+= $qty2;
//         //                 }
//         //             }
//         //         }
//         //     }
//         // }

//     $sorted = array();
//     $sorted = array_column($arr, 'sales');
//     array_multisort($sorted, SORT_DESC, $arr);

//     if(count($sorted)>0){

//         $success = 1;
//         $status = 200;
//         $msg = "Success";
//     }else{
//         $success = 0;
//         // $status = 204;
//         $msg = "Data Not Found";
//     }

// }

// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "categorySales"=>$arr, "total"=>$total]);


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
    $i=0;
    $id = $_GET['id'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];

    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }
    
    $total = 0;
    // $rows = "";

    $query = "";

    if($newDateFormat == 1)
    {
        if(isset($_GET['all'])) {
            $all = $_GET['all'];
        }
        if($all !== "1") {
            $query = "SELECT SUM(detail_transaksi.harga) AS qty, categories.name AS nama, COUNT(menu.id_category) AS cat_qty FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 GROUP BY categories.id ORDER BY qty DESC";
        } else {
            $query = "SELECT SUM(detail_transaksi.harga) AS qty, categories.name AS nama, COUNT(menu.id_category) AS cat_qty FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 GROUP BY categories.name ORDER BY qty DESC";
        }
        
        $sqlGetSales = mysqli_query($db_conn, $query);
            while($row2=mysqli_fetch_assoc($sqlGetSales)){
                $namaMenu2 = $row2['nama'];
                $qty1 =(int) $row2['cat_qty'];
                $qty2 =(int) $row2['qty'];
                array_push($arr, array("name" => "$namaMenu2", "sales" => $qty2, "category_qty" => $qty1));
                $total += $qty2;
            }
    
            // if(mysqli_num_rows($sqlGetSales) > 0) {
            //     $data = mysqli_fetch_all($sqlGetSales, MYSQLI_ASSOC);
            //     $rows = $data;
            // }
    
            // $dateFromStr = str_replace("-","", $dateFrom);
            // $dateToStr = str_replace("-","", $dateTo);
            // $queryTrans = "SELECT table_name FROM information_schema.tables
            // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
            // $transaksi = mysqli_query($db_conn, $queryTrans);
            // while($row=mysqli_fetch_assoc($transaksi)){
            //     $table_name = explode("_",$row['table_name']);
            //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            //         $sqlGetSales = mysqli_query($db_conn, "SELECT SUM(`$detail_transactions`.harga) AS qty, categories.name AS nama FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN categories ON categories.id=menu.id_category
            //         WHERE `$transactions`.id_partner='$id' AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.deleted_at IS NULL AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY categories.id ORDER BY qty DESC ");
            //         if(mysqli_num_rows($sqlGetSales) > 0) {
            //             while($row2=mysqli_fetch_assoc($sqlGetSales)){
            //                 $namaMenu2 = $row2['nama'];
            //                 $qty2 =(int) $row2['qty'];
            //                 $add =true;
            //                 $arI = 0;
            //                 foreach($arr as $ar){
            //                     if($ar['name']==$namaMenu2){
            //                         $add = false;
            //                         $arr[$arI]['sales']+=$qty2;
            //                         $total+= $qty2;
            //                     }
            //                     $arI +=1;
            //                 }
            //                 if($add==true){
            //                     array_push($arr, array("name" => "$namaMenu2", "sales" => $qty2));
            //                     $total+= $qty2;
            //                 }
            //             }
            //         }
            //     }
            // }
    
        $sorted = array();
        $sorted = array_column($arr, 'sales');
        array_multisort($sorted, SORT_DESC, $arr);
    
        if(count($sorted)>0){
    
            $success = 1;
            $status = 200;
            $msg = "Success";
        }else{
            $success = 0;
            // $status = 204;
            $msg = "Data Not Found";
        }
    
    }

    else
    {
        if(isset($_GET['all'])) {
            $all = $_GET['all'];
        }
        if($all !== "1") {
            $query = "SELECT SUM(detail_transaksi.harga) AS qty, categories.name AS nama, COUNT(menu.id_category) AS cat_qty FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 GROUP BY categories.id ORDER BY qty DESC";
        } else {
            $query = "SELECT SUM(detail_transaksi.harga) AS qty, categories.name AS nama, COUNT(menu.id_category) AS cat_qty FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 GROUP BY categories.name ORDER BY qty DESC";
        }
        
        $sqlGetSales = mysqli_query($db_conn, $query);
            while($row2=mysqli_fetch_assoc($sqlGetSales)){
                $namaMenu2 = $row2['nama'];
                $qty1 =(int) $row2['cat_qty'];
                $qty2 =(int) $row2['qty'];
                array_push($arr, array("name" => "$namaMenu2", "sales" => $qty2, "category_qty" => $qty1));
                $total += $qty2;
            }
    
            // if(mysqli_num_rows($sqlGetSales) > 0) {
            //     $data = mysqli_fetch_all($sqlGetSales, MYSQLI_ASSOC);
            //     $rows = $data;
            // }
    
            // $dateFromStr = str_replace("-","", $dateFrom);
            // $dateToStr = str_replace("-","", $dateTo);
            // $queryTrans = "SELECT table_name FROM information_schema.tables
            // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
            // $transaksi = mysqli_query($db_conn, $queryTrans);
            // while($row=mysqli_fetch_assoc($transaksi)){
            //     $table_name = explode("_",$row['table_name']);
            //     $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            //     $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            //     if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            //         $sqlGetSales = mysqli_query($db_conn, "SELECT SUM(`$detail_transactions`.harga) AS qty, categories.name AS nama FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN categories ON categories.id=menu.id_category
            //         WHERE `$transactions`.id_partner='$id' AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY categories.id ORDER BY qty DESC ");
            //         if(mysqli_num_rows($sqlGetSales) > 0) {
            //             while($row2=mysqli_fetch_assoc($sqlGetSales)){
            //                 $namaMenu2 = $row2['nama'];
            //                 $qty2 =(int) $row2['qty'];
            //                 $add =true;
            //                 $arI = 0;
            //                 foreach($arr as $ar){
            //                     if($ar['name']==$namaMenu2){
            //                         $add = false;
            //                         $arr[$arI]['sales']+=$qty2;
            //                         $total+= $qty2;
            //                     }
            //                     $arI +=1;
            //                 }
            //                 if($add==true){
            //                     array_push($arr, array("name" => "$namaMenu2", "sales" => $qty2));
            //                     $total+= $qty2;
            //                 }
            //             }
            //         }
            //     }
            // }
    
        $sorted = array();
        $sorted = array_column($arr, 'sales');
        array_multisort($sorted, SORT_DESC, $arr);
    
        if(count($sorted)>0){
    
            $success = 1;
            $status = 200;
            $msg = "Success";
        }else{
            $success = 0;
            // $status = 204;
            $msg = "Data Not Found";
        }
    
    }

}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "categorySales"=>$arr, "total"=>$total]);

