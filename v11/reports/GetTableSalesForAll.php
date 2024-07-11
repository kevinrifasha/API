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
$data = [];

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
    $i=0;
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];

    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);
    
    $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
    if(mysqli_num_rows($sqlPartner) > 0) {
        $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
        foreach($getPartners as $partner) {
            $id = $partner['partner_id'];
            $arr = [];
            $arr2 = [];
            $totalSales = 0;
            $totalQty = 0;
            
            $addQuery1 = "transaksi.id_partner='$id'";
            $addQuery2 = "";
            
            $query = "SELECT SUM(transaksi.total) AS qty, transaksi.no_meja FROM transaksi ". $addQuery2 ." WHERE ". $addQuery1 ." AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.no_meja ORDER BY total DESC";
            $sqlGetSales = mysqli_query($db_conn, $query);
            
            $query = "SELECT COUNT(transaksi.id) AS qty, transaksi.no_meja FROM transaksi ". $addQuery2 ." WHERE ". $addQuery1 ." AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL GROUP BY transaksi.no_meja ORDER BY qty DESC ";
        
            $sqlGetQty = mysqli_query($db_conn, $query);
            
            if(mysqli_num_rows($sqlGetSales) > 0) {
                $a = array();
                $i = 0;
                while($row2=mysqli_fetch_assoc($sqlGetSales)){
                    $a[$i]=$row2;
                    $namaMenu = $row2['no_meja'];
                    $qty = $row2['qty'];
                    array_push($arr, array("name" => "$namaMenu", "sales" => $qty));
                    // $totalSales += $qty;
                    ($totalSales ?? $totalSales = 0) ? $totalSales += $qty : $totalSales = $qty;
                    $i+=1;
                }
                $i=0;
                $tps = 0;
                foreach($a as $v){
                    $arr[$i]['percentage']=round(($arr[$i]['sales']/$totalSales)*100,2);
                    $tps += round(($arr[$i]['sales']/$totalSales)*100,2);
                    $i+=1;
                }
                $i-=1;
                if($tps>100){
                    $tps=100;
                }
                if($i>0){
                    $tps = $tps-$arr[$i]['percentage'];
                    $arr[$i]['percentage']=100-($tps);
                }
        
                $b = array();
                $j = 0;
                while($row3=mysqli_fetch_assoc($sqlGetQty)){
                    $b[$j]=$row3;
                    $namaMenu2 = $row3['no_meja'];
                    $qty2 = $row3['qty'];
                    array_push($arr2, array("name" => "$namaMenu2", "qty" => $qty2));
                    // $totalQty += $qty2;
                    ($totalQty ?? $totalQty = 0) ? $totalQty += $qty2 : $totalQty = $qty2;
                    $j+=1;
                }
                $j=0;
                $tpq = 0;
                foreach($b as $v){
                    $arr2[$j]['percentage']=round(($arr2[$j]['qty']/$totalQty)*100,2);
                    $tpq += $arr2[$j]['percentage'];
                    $j+=1;
                }
                $j-=1;
                if($tpq>100){
                    $tpq=100;
                }
                if($j>0){
                    $tpq = $tpq-$arr2[$j]['percentage'];
                    $arr2[$j]['percentage']=100-$tpq;
                }
                $success = 1;
                $status = 200;
                $msg = "Success";
                $sorted = array();
                $sorted = array_column($arr, 'sales');
                array_multisort($sorted, SORT_DESC, $arr);
                $sorted2 = array();
                $sorted2 = array_column($arr2, 'qty');
                array_multisort($sorted, SORT_DESC, $arr2);
            }
            
            $partner['tableSales'] = $arr;
            $partner['tableQty'] = $arr2;
            $partner['totalSales'] = $totalSales;
            $partner['totalQty'] = $totalQty;
            
            if(count($arr) > 0) {
                array_push($data, $partner);
            }
        }
        
        $success = 1;
        $status = 200;
        $msg = "Success";
    } else {
        $success = 0;
        $status = 203;
        $msg = "Data Not Found";
    }
}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data]);

?>