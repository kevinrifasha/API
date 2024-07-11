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

// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

//     $status = $tokens['status'];
//     $msg = $tokens['msg'];
//     $success = 0;

// }else{
//     $i=0;
//     $dateFromStr = str_replace("-","", $dateFrom);
//     $dateToStr = str_replace("-","", $dateTo);
//     $query = "";
    
//     $data = [];
    
//     $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
//     if(mysqli_num_rows($sqlPartner) > 0) {
//         $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
//         foreach($getPartners as $partner) {
//             $id = $partner['partner_id'];
//             $arr=[];
//             $arr2=[];
//             $totalSales = 0;
//             $totalQty = 0;
            
//             $query = "SELECT SUM(transaksi.total) AS sales, transaksi.no_meja FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.no_meja ORDER BY total DESC";
            
//             $sqlGetSales = mysqli_query($db_conn, $query);
            
//             $query = "SELECT COUNT(transaksi.id) AS qty, transaksi.no_meja FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL GROUP BY transaksi.no_meja ORDER BY qty DESC ";
            
//             $sqlGetQty = mysqli_query($db_conn, $query);
        
//             if(mysqli_num_rows($sqlGetSales) > 0 && mysqli_num_rows($sqlGetQty) > 0){
//                 while($row=mysqli_fetch_assoc($sqlGetSales)){
//                     $tableName = $row['no_meja'];
//                     $sales = $row['sales'];
//                     array_push($arr, array("name" => "$tableName", "sales" => $sales));
//                     $totalSales += $sales;
//                 }
//                 while($row=mysqli_fetch_assoc($sqlGetQty)){
//                     $tableName = $row['no_meja'];
//                     $qty = $row['qty'];
//                     array_push($arr2, array("name" => "$tableName", "qty" => $qty));
//                     $totalQty += $qty;
//                 }
//             }
            
//             $partner['tableSales'] = $arr;
//             $partner['tableQty'] = $arr2;
//             $partner['totalSales'] = $totalSales;
//             $partner['totalQty'] = $totalQty;
            
//             if($totalSales > 0 && $totalQty > 0) {
//                 array_push($data, $partner);
//             }
//         }
        
//         $success=1;
//         $msg="Success";
//         $status=200;
        
//     } else {
//         $success = 0;
//         $status = 400;
//         $msg = "Data tidak ditemukan";
//     }
// }

// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data]);


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

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $i=0;
    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);
    $query = "";
    
    $data = [];
    
    if($newDateFormat == 1)
    {
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $id = $partner['partner_id'];
                $arr=[];
                $arr2=[];
                $totalSales = 0;
                $totalQty = 0;
                
                $query = "SELECT SUM(transaksi.total) AS sales, transaksi.no_meja FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.no_meja ORDER BY total DESC";
                
                $sqlGetSales = mysqli_query($db_conn, $query);
                
                $query = "SELECT COUNT(transaksi.id) AS qty, transaksi.no_meja FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL GROUP BY transaksi.no_meja ORDER BY qty DESC ";
                
                $sqlGetQty = mysqli_query($db_conn, $query);
            
                if(mysqli_num_rows($sqlGetSales) > 0 && mysqli_num_rows($sqlGetQty) > 0){
                    while($row=mysqli_fetch_assoc($sqlGetSales)){
                        $tableName = $row['no_meja'];
                        $sales = $row['sales'];
                        array_push($arr, array("name" => "$tableName", "sales" => $sales));
                        $totalSales += $sales;
                    }
                    while($row=mysqli_fetch_assoc($sqlGetQty)){
                        $tableName = $row['no_meja'];
                        $qty = $row['qty'];
                        array_push($arr2, array("name" => "$tableName", "qty" => $qty));
                        $totalQty += $qty;
                    }
                }
                
                $partner['tableSales'] = $arr;
                $partner['tableQty'] = $arr2;
                $partner['totalSales'] = $totalSales;
                $partner['totalQty'] = $totalQty;
                
                if($totalSales > 0 && $totalQty > 0) {
                    array_push($data, $partner);
                }
            }
            
            $success=1;
            $msg="Success";
            $status=200;
            
        } else {
            $success = 0;
            $status = 400;
            $msg = "Data tidak ditemukan";
        }
    }
    else
    {
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $id = $partner['partner_id'];
                $arr=[];
                $arr2=[];
                $totalSales = 0;
                $totalQty = 0;
                
                $query = "SELECT SUM(transaksi.total) AS sales, transaksi.no_meja FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.no_meja ORDER BY total DESC";
                
                $sqlGetSales = mysqli_query($db_conn, $query);
                
                $query = "SELECT COUNT(transaksi.id) AS qty, transaksi.no_meja FROM transaksi  WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL GROUP BY transaksi.no_meja ORDER BY qty DESC ";
                
                $sqlGetQty = mysqli_query($db_conn, $query);
            
                if(mysqli_num_rows($sqlGetSales) > 0 && mysqli_num_rows($sqlGetQty) > 0){
                    while($row=mysqli_fetch_assoc($sqlGetSales)){
                        $tableName = $row['no_meja'];
                        $sales = $row['sales'];
                        array_push($arr, array("name" => "$tableName", "sales" => $sales));
                        $totalSales += $sales;
                    }
                    while($row=mysqli_fetch_assoc($sqlGetQty)){
                        $tableName = $row['no_meja'];
                        $qty = $row['qty'];
                        array_push($arr2, array("name" => "$tableName", "qty" => $qty));
                        $totalQty += $qty;
                    }
                }
                
                $partner['tableSales'] = $arr;
                $partner['tableQty'] = $arr2;
                $partner['totalSales'] = $totalSales;
                $partner['totalQty'] = $totalQty;
                
                if($totalSales > 0 && $totalQty > 0) {
                    array_push($data, $partner);
                }
            }
            
            $success=1;
            $msg="Success";
            $status=200;
            
        } else {
            $success = 0;
            $status = 400;
            $msg = "Data tidak ditemukan";
        }
    }


}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data]);
