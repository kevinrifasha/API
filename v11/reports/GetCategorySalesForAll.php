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
$total = 0;

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;
$data = [];

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $i=0;
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    
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
            $addQuery2 = "categories.id";
        
            $dateFromStr = str_replace("-","", $dateFrom);
            $dateToStr = str_replace("-","", $dateTo);
          
            $query =  "SELECT SUM(detail_transaksi.harga) AS harga, SUM(detail_transaksi.qty) AS qty, categories.id AS categoryID, categories.name AS nama, categories.id, transaksi.id_partner, p.name AS partner_name FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN partner p ON p.id = transaksi.id_partner
             WHERE ". $addQuery1 ." AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 GROUP BY ". $addQuery2 ." ORDER BY harga, qty DESC";
            $sqlGetSales = mysqli_query($db_conn, $query);
            
            if(mysqli_num_rows($sqlGetSales) > 0) {
                
                $getSales = mysqli_fetch_all($sqlGetSales, MYSQLI_ASSOC);
                
                foreach($getSales as $sales){
                    $totalSales+= $sales['harga'];
                    $totalQty+= $sales['qty'];
                }
                
                $tps = 0;
                $tpq = 0;
                foreach($getSales as $row2){
                    $namaMenu = $row2['nama'];
                    $qty = $row2['harga'];
                    $id = $row2['categoryID'];
                    $id_partner = $row2['id_partner'];
                    $partner_name = $row2['partner_name'];
                    $namaMenu2 = $row2['nama'];
                    $percentage1=round(($row2['harga']/$totalSales)*100,2);
                    $tps += $percentage1;
                    
                    $qty2 = $row2['qty'];
                    $percentage2=round(($row2['qty']/$totalQty)*100,2);
                    $tpq += $percentage2;
                    
                    array_push($arr, array("name" => "$namaMenu", "sales" => $qty, "id"=>$id, "partnerID"=>$id_partner, "partner_name"=>$partner_name, "percentage"=>$percentage1));
                    
                    array_push($arr2, array("name" => "$namaMenu2", "qty" => $qty2, "id" => $id, "partnerID"=>$id_partner, "partner_name"=>$partner_name, "percentage"=>$percentage2));
                }

                $i-=1;
                if($tps>100){
                    $tps=100;
                }
                if($tpq>100){
                    $tpq=100;
                }
                if($i>0){
                    $tps = $tps-$arr[$i]['percentage'];
                    $arr[$i]['percentage']=100-($tps);
                    $tpq = $tpq-$arr2[$i]['percentage'];
                    $arr2[$i]['percentage']=100-$tpq;
                }
                
                $success = 1;
                $status = 200;
                $msg = "Success";
                
                $sorted = array();
                $sorted = array_column($arr, 'sales', 'id');
                array_multisort($sorted, SORT_DESC, $arr);
                
                $sorted2 = array();
                $sorted2 = array_column($arr2, 'qty', 'id');
                array_multisort($sorted2, SORT_DESC, $arr2);
            }
            
            $partner['categorySales'] = $arr;
            $partner['categoryQty'] = $arr2;
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
        $msg = "Data not found";
    }
}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data]);

?>