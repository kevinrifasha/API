<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once '../../includes/CalculateFunctions.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$cf = new CalculateFunction();

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
$gross_sales = 0;
$service = 0;
$amountAfterService = 0;
$tax = 0;
$amountAfterTax = 0;
$operational = 0;
$charge_ur = 0;
$net_profit = 0;

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
}else{
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    
    $array = [];
    
    $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
    if(mysqli_num_rows($sqlPartner) > 0) {
        $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
        foreach($getPartners as $partner) {
            $id = $partner['partner_id'];

            $addQuery1 = "p.id='$id'";
            $addQueryA = "transaksi.id_partner='$id'";
            $addQueryB = "";
            $res = $cf->getSubTotal($id, $dateFrom, $dateTo);
        
            $sql = mysqli_query($db_conn, "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE ". $addQuery1 ." AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC");
        
            $dateFromStr = str_replace("-","", $dateFrom);
            $dateToStr = str_replace("-","", $dateTo);
            
            $query = "SELECT SUM(hpp) hpp FROM( SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu $addQueryB WHERE ". $addQueryA ." AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 ";
            $query .= " ) AS tmp ";
            $hppQ = mysqli_query(
                $db_conn,
                $query
            );
        
            if (mysqli_num_rows($hppQ) > 0 || mysqli_num_rows($sql) > 0) {
                $resQ = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
                $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                
                $partner['net_sales'] = $res['clean_sales'];
                $partner['hpp'] = (double) $resQ[0]['hpp'];
                $partner['gross_profit'] = $partner['net_sales'] - $partner['hpp'];
                $partner['service'] = (int) $res['service'];
                $partner['amountAfterService'] = $partner['gross_profit']-$partner['service'];
                $partner['tax'] = (int) $res['tax'];
                $partner['amountAfterTax'] = $partner['amountAfterService']-$partner['tax'];
                if(isset($data[0]['amount']) && !empty($data[0]['amount'])){
                    $partner['operational'] = (int)$data[0]['amount'];
                }
                $partner['charge_ur'] = (int) $res['charge_ur'];
                
                if(isset($partner['operational'])) {
                    $partner['net_profit'] = $partner['amountAfterTax'] - $partner['operational'] - $partner['charge_ur'];
                } else {
                    $partner['net_profit'] = $partner['amountAfterTax'] - $partner['charge_ur'];
                }
            }
            
            if($partner['gross_profit'] > 0) {
                array_push($array, $partner);
            }
        }
        
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =203;
        $msg = "Data not found";
    }
}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$array]);

echo $signupJson;
?>