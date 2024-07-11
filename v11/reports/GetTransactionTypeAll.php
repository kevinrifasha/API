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
$array = [];

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
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    
    $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
    if(mysqli_num_rows($sqlPartner) > 0) {
        $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
        foreach($getPartners as $partner) {
            $id = $partner['partner_id'];
            $res = array();
            $totalQty =0;
            $totalSales = 0;
            
            $addQuery1 = "t.id_partner='$id'";
            $addQuery2 = "";
            
            $query = "SELECT trx.name, trx.type, COUNT(trx.id) as qty, SUM( trx.total - trx.promo - trx.program_discount - trx.diskon_spesial - trx.point + trx.service + trx.tax + trx.charge_ur + trx.ongkir ) as sales FROM ( SELECT t.id, CASE WHEN (t.no_meja != null or t.no_meja != '') THEN 'Dine In' WHEN t.takeaway = 1 THEN 'Takeaway' WHEN t.pre_order_id != 0 THEN 'Preorder' WHEN d.id IS NOT NULL THEN 'Delivery' ELSE 'none' END as name, CASE WHEN (t.no_meja != null or t.no_meja != '') THEN 'dinein' WHEN t.takeaway = 1 THEN 'takeaway' WHEN t.pre_order_id != 0 THEN 'preorder' WHEN d.id IS NOT NULL THEN 'delivery' ELSE 'none' END as type, t.id_partner, t.jam, d.rate_id, t.total, t.promo, t.program_discount, t.diskon_spesial, t.point, ( t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point )* t.service / 100 AS service, ( ( ( t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point )* t.service / 100 )+ t.total - t.promo - t.program_discount - t.diskon_spesial - t.employee_discount - t.point + t.charge_ur )* t.tax / 100 AS tax, t.charge_ur, IFNULL( CASE WHEN d.rate_id = 0 THEN d.ongkir ELSE 0 END, 0 ) as ongkir FROM transaksi AS t LEFT JOIN delivery AS d ON t.id = d.transaksi_id ". $addQuery2 ." WHERE t.deleted_at IS NULL AND t.status IN (1, 2) AND ". $addQuery1 ." AND DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo') AS trx GROUP BY trx.type";
            $sqlCountType = mysqli_query($db_conn, $query);
            if(mysqli_num_rows($sqlCountType) > 0) {
                $allData = mysqli_fetch_all($sqlCountType, MYSQLI_ASSOC);
                foreach($allData as $v){
                    $totalQty += (int) $v['qty'];
                    $totalSales += (int) $v['sales'];
                }
                foreach($allData as $v){
                    $data = array(
                        "name" => $v['name'],
                        "type" => $v['type'],
                        "qty" => (int) $v['qty'],
                        "sales" => (int) $v['sales'],
                        "percentage_sales" => round(($v['sales']/$totalSales)*100,2),
                        "percentage_qty" => round(($v['qty']/$totalQty)*100, 2),
                    );
                   array_push($res,$data);
                }
            }
            
            $partner['data'] = $res;
            $partner['totalSales'] = $totalSales;
            $partner['totalQty'] = $totalQty;
            
            if(count($res) > 0) {
                array_push($array, $partner);
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

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$array]);

?>