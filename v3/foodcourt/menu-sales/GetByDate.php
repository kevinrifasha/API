// <?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: access");
// header("Access-Control-Allow-Methods: GET");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// require_once("../../tokenModels/tokenManager.php"); 
// require_once("../../connection.php");
// require '../../../db_connection.php';
// require  __DIR__ . '/../../../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
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
// $total = 0;
// $totalS = 0;
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
//     $query = "SELECT SUM(sales) AS sales, nama, name, id, SUM(qty) AS qty FROM ( SELECT SUM(detail_transaksi.qty) AS qty, SUM(detail_transaksi.harga_satuan *detail_transaksi.qty) AS sales, menu.nama AS name, menu.nama, menu.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
//     WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY detail_transaksi.id  ";
//     $dateFromStr = str_replace("-","", $dateFrom);
//     $dateToStr = str_replace("-","", $dateTo);
//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(
//             ($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])
//             ){
//             $query .= "UNION ALL " ;
//             $query .= "SELECT SUM(`$detail_transactions`.qty) AS qty, SUM(`$detail_transactions`.harga_satuan *`$detail_transactions`.qty) AS sales, menu.nama AS name, menu.nama, menu.id FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
//             WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1 AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY `$detail_transactions`.id ";
//         }
//     }
//     $query .= ") AS tmp GROUP BY id ORDER BY sales DESC";
//     $arrQ = mysqli_query($db_conn, $query);
//     if(mysqli_num_rows($arrQ)>0) {
//         $arr = mysqli_fetch_all($arrQ, MYSQLI_ASSOC);
//     }
//     foreach ($arr as $value) {
//         $totalS += (int) $value['sales'];
//         $total += (int) $value['qty'];
//     }

//     if(count($arr)>0){

//         $success = 1;
//         $status = 200;
//         $msg = "Success";
//     }else{
//         $success = 0;
//         // $status = 204;
//         $msg = "Data Not Found";
//     }
    
// }
// if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
//         http_response_code(200);
//     }else{
//         http_response_code($status);
//     }
// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "categorySales"=>$arr, "total"=>$totalS, "totalQty"=>$total]);  

// <?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../tokenModels/tokenManager.php"); 
require_once("../../connection.php");
require '../../../db_connection.php';
require  __DIR__ . '/../../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
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
$total = 0;
$totalS = 0;
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

    if($newDateFormat == 1)
    {
        $query = "SELECT SUM(sales) AS sales, nama, name, id, SUM(qty) AS qty FROM ( SELECT SUM(detail_transaksi.qty) AS qty, SUM(detail_transaksi.harga_satuan *detail_transaksi.qty) AS sales, menu.nama AS name, menu.nama, menu.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY detail_transaksi.id  ";
        $dateFromStr = str_replace("-","", $dateFrom);
        $dateToStr = str_replace("-","", $dateTo);
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(
                ($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])
                ){
                $query .= "UNION ALL " ;
                $query .= "SELECT SUM(`$detail_transactions`.qty) AS qty, SUM(`$detail_transactions`.harga_satuan *`$detail_transactions`.qty) AS sales, menu.nama AS name, menu.nama, menu.id FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1 AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY `$detail_transactions`.id ";
            }
        }
        $query .= ") AS tmp GROUP BY id ORDER BY sales DESC";
        $arrQ = mysqli_query($db_conn, $query);
        if(mysqli_num_rows($arrQ)>0) {
            $arr = mysqli_fetch_all($arrQ, MYSQLI_ASSOC);
        }
        foreach ($arr as $value) {
            $totalS += (int) $value['sales'];
            $total += (int) $value['qty'];
        }
    
        if(count($arr)>0){
    
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
        $query = "SELECT SUM(sales) AS sales, nama, name, id, SUM(qty) AS qty FROM ( SELECT SUM(detail_transaksi.qty) AS qty, SUM(detail_transaksi.harga_satuan *detail_transaksi.qty) AS sales, menu.nama AS name, menu.nama, menu.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY detail_transaksi.id  ";
        $dateFromStr = str_replace("-","", $dateFrom);
        $dateToStr = str_replace("-","", $dateTo);
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(
                ($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])
                ){
                $query .= "UNION ALL " ;
                $query .= "SELECT SUM(`$detail_transactions`.qty) AS qty, SUM(`$detail_transactions`.harga_satuan *`$detail_transactions`.qty) AS sales, menu.nama AS name, menu.nama, menu.id FROM `$detail_transactions` JOIN `$transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1 AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY `$detail_transactions`.id ";
            }
        }
        $query .= ") AS tmp GROUP BY id ORDER BY sales DESC";
        $arrQ = mysqli_query($db_conn, $query);
        if(mysqli_num_rows($arrQ)>0) {
            $arr = mysqli_fetch_all($arrQ, MYSQLI_ASSOC);
        }
        foreach ($arr as $value) {
            $totalS += (int) $value['sales'];
            $total += (int) $value['qty'];
        }
    
        if(count($arr)>0){
    
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
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "categorySales"=>$arr, "total"=>$totalS, "totalQty"=>$total]);  

?>