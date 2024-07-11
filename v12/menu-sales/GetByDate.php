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
// $value = array();
// $success=0;
// $msg = 'Failed';
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
//     $totalS = 0;
//     $query = "SELECT SUM(detail_transaksi.harga) AS sales, SUM(detail_transaksi.qty) AS qty, menu.nama AS nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu   WHERE transaksi.id_partner='$id' AND detail_transaksi.deleted_at IS NULL AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY menu.id ORDER BY qty DESC";
//     $sqlGetSales = mysqli_query($db_conn, $query);
//     if(mysqli_num_rows($sqlGetSales) > 0) {
//         while($row2=mysqli_fetch_assoc($sqlGetSales)){
//             $namaMenu2 = $row2['nama'];
//             $qty2 = $row2['qty'];
//             $sales2 = $row2['sales'];
//             array_push($arr, array("name" => "$namaMenu2", "qty" => $qty2, "sales"=>$sales2));
//             $total+= $qty2;
//             $totalS+= $sales2;
//         }
//         $success = 1;
//         $status = 200;
//         $msg = "Success";
//         $sorted = array();
//         $sorted = array_column($arr, 'qty');
//         array_multisort($sorted, SORT_DESC, $arr);
//     }else{
//         $success = 0;
//         $status = 204;
//         $msg = "Data Not Found";
//     }

// }

// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "categorySales"=>$arr, "total"=>$totalS, "totalQty"=>$total]);

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
$value = array();
$success=0;
$msg = 'Failed';
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
    $totalS = 0;

    if($newDateFormat == 1)
    {
        $query = "SELECT SUM(detail_transaksi.harga) AS sales, SUM(detail_transaksi.qty) AS qty, menu.nama AS nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu   WHERE transaksi.id_partner='$id' AND detail_transaksi.deleted_at IS NULL AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY menu.id ORDER BY qty DESC";
        $sqlGetSales = mysqli_query($db_conn, $query);
        if(mysqli_num_rows($sqlGetSales) > 0) {
            while($row2=mysqli_fetch_assoc($sqlGetSales)){
                $namaMenu2 = $row2['nama'];
                $qty2 = $row2['qty'];
                $sales2 = $row2['sales'];
                array_push($arr, array("name" => "$namaMenu2", "qty" => $qty2, "sales"=>$sales2));
                $total+= $qty2;
                $totalS+= $sales2;
            }
            $success = 1;
            $status = 200;
            $msg = "Success";
            $sorted = array();
            $sorted = array_column($arr, 'qty');
            array_multisort($sorted, SORT_DESC, $arr);
        }else{
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
        
    }
    else
    {
        $query = "SELECT SUM(detail_transaksi.harga) AS sales, SUM(detail_transaksi.qty) AS qty, menu.nama AS nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu   WHERE transaksi.id_partner='$id' AND detail_transaksi.deleted_at IS NULL AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY menu.id ORDER BY qty DESC";
        $sqlGetSales = mysqli_query($db_conn, $query);
        if(mysqli_num_rows($sqlGetSales) > 0) {
            while($row2=mysqli_fetch_assoc($sqlGetSales)){
                $namaMenu2 = $row2['nama'];
                $qty2 = $row2['qty'];
                $sales2 = $row2['sales'];
                array_push($arr, array("name" => "$namaMenu2", "qty" => $qty2, "sales"=>$sales2));
                $total+= $qty2;
                $totalS+= $sales2;
            }
            $success = 1;
            $status = 200;
            $msg = "Success";
            $sorted = array();
            $sorted = array_column($arr, 'qty');
            array_multisort($sorted, SORT_DESC, $arr);
        }else{
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    }


}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "categorySales"=>$arr, "total"=>$totalS, "totalQty"=>$total]);

?>