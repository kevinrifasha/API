<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

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
$total = 0;
$totalS = 0;
$sorted = [];
$arr = [];

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $arr = [];
    $i=0;
    // $id = $token->id_partner;
    $id = $_GET['partnerID'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    $categoryID = $_GET['categoryID'];
    $type = $_GET['type'];
    $total = 0;
    
    $sqlGetSales = mysqli_query($db_conn, "SELECT SUM(detail_transaksi.harga) AS sales, SUM(detail_transaksi.qty) AS qty, menu.nama AS nama FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND menu.id_category='$categoryID' GROUP BY menu.id ORDER BY sales DESC");
    
    if(mysqli_num_rows($sqlGetSales) > 0) {
        while($row2=mysqli_fetch_assoc($sqlGetSales)){
            $namaMenu2 = $row2['nama'];
            $qty2 = $row2['qty'];
            $sales2 = $row2['sales'];
            array_push($arr, array("name" => "$namaMenu2", "qty" => $qty2, "sales"=>$sales2));
            $total+= $qty2;
            $totalS+= $sales2;
        }
        $arrQty=$arr;
        $sorted = array();
        $sorted = array_column($arr, 'sales');
        array_multisort($sorted, SORT_DESC, $arr);
        $sortedQty = array();
        $sortedQty = array_column($arrQty, 'qty');
        array_multisort($sortedQty, SORT_DESC, $arrQty);

        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 203;
        $msg = "Data Not Found";
    }

}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "sales"=>$arr, "qty"=>$arrQty, "total"=>$total, "totalSales"=>$totalS]);

?>