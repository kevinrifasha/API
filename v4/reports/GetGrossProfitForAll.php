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
$res = array();
$resQ = array();
$hpp = 0;
$res['sales']=0;
$res['diskon_spesial']=0;
$res['employee_discount']=0;
$res['program_discount']=0;
$res['promo']=0;
$res['clean_sales']=0;
$res['gross_profit']=0;
$all = "0";
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
            $res = $cf->getSubTotal($id, $dateFrom, $dateTo);
            $res['hpp']=0;
            $res['gross_profit']=$res['clean_sales'];
            $query =  " SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu  WHERE ". $addQuery1 ." AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4";
                    $hppQ = mysqli_query(
                $db_conn,
                $query
            );
                if (mysqli_num_rows($hppQ) > 0){
                $resQ = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
                $hpp = (double) $resQ[0]['hpp'];
                $res['gross_profit'] = $res['gross_profit']-$hpp;
                $success =1;
                $status =200;
                $msg = "Success";
            } else {
                $success =0;
                $status =200;
                $msg = "Data Not Found";
            }
            $partner['cogs'] = $hpp;

            $partner['sales'] = $res['sales'];
            $partner['special_discount'] = $res['diskon_spesial'];
            $partner['employee_discount'] = $res['employee_discount'];
            $partner['promo'] = $res['promo'];
            $partner['program_discount'] = $res['program_discount'];
            $partner['net_sales'] = $res['clean_sales'];
            $partner['gross_profit'] = $res['gross_profit'];
           
            
            if(count($res) > 0) {
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