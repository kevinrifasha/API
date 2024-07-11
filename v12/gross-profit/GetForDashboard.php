<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';
require_once '../../includes/CalculateFunctions.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$cf = new CalculateFunction();
$id = $_GET['id'];
$dateTo = $_GET['dateTo'];
$dateFrom = $_GET['dateFrom'];

$newDateFormat = 0;

if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
    $dateTo = str_replace("%20"," ",$dateTo);
    $dateFrom = str_replace("%20"," ",$dateFrom);
    $newDateFormat = 1;
}

$all = "0";
$res = array();
$resQ = array();
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
$values = array();
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->masterID;

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($newDateFormat == 1){
      if($all == "1") {
        $res = $cf->getSubTotalMasterWithHour($idMaster, $dateFrom, $dateTo);
    } else {
        $res = $cf->getSubTotalWithHour($id, $dateFrom, $dateTo);
    }

    $res['hpp']=0;
    $res['gross_profit']=$res['clean_sales'];
    $res['gross_profit_afterservice']=$res['gross_profit']-$res['service'];
    $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];

    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);
    $query = "";
    if($all == "1") {
        $query = "SELECT SUM(hpp) AS hpp FROM (SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories c ON c.id = menu.id_category WHERE c.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo'";
    } else {
        $query = "SELECT SUM(hpp) AS hpp FROM ( SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
    }
    $query .= " ) AS tmp ";

    $hppQ = mysqli_query($db_conn, $query);
    
    $qOpex = "";
    if($all == "1") {
        // $qOpex = "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE opc.master_id = '$idMaster' AND op.deleted_at IS NULL AND op.created_at BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC";
        $qOpex = "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id WHERE opc.master_id='$idMaster' AND op.deleted_at IS NULL AND op.created_at BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC";
      } else {
          // $qOpex = "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE opc.partner_id='$id' AND op.deleted_at IS NULL AND op.created_at BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC";
          $qOpex = "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id WHERE opc.partner_id='$id' AND op.deleted_at IS NULL AND op.created_at BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC";
      }
      $sqlOpex = mysqli_query($db_conn,$qOpex);
      
      while($row = mysqli_fetch_assoc($sqlOpex)){
          $opex = $row['amount']==null?0:(int)$row['amount'];
          $res['opex'] = $opex;
      }
      if (mysqli_num_rows($hppQ) > 0) {
          $resQ = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
          $res['hpp']=(double)$resQ[0]['hpp'];

          $res['gross_profit'] = $res['gross_profit'] - $res['hpp'];
          $res['gross_profit_afterservice']=$res['gross_profit']-$res['service'];
          $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
          $res['net_profit_before_charge']= $res['gross_profit_aftertax']-$opex;
          
          $success=1;
          $status=200;
          $msg="Success";
      }else{
          $success=0;
          $status=401;
          $msg="Not Found";
      }
    } 
    else 
    {
      if($all == "1") {
        $res = $cf->getSubTotalMaster($idMaster, $dateFrom, $dateTo);
      } else {
          $res = $cf->getSubTotal($id, $dateFrom, $dateTo);
      }
      $res['hpp']=0;
      $res['gross_profit']=$res['clean_sales'];
      $res['gross_profit_afterservice']=$res['gross_profit']-$res['service'];
      $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];

      $dateFromStr = str_replace("-","", $dateFrom);
      $dateToStr = str_replace("-","", $dateTo);
      $query = "";
      if($all == "1") {
          $query = "SELECT SUM(hpp) AS hpp FROM (SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories c ON c.id = menu.id_category WHERE c.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'";
      } else {
          $query = "SELECT SUM(hpp) AS hpp FROM ( SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
      }
      $query .= " ) AS tmp ";

      $hppQ = mysqli_query($db_conn, $query);
      
      $qOpex = "";
      if($all == "1") {
          // $qOpex = "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE opc.master_id = '$idMaster' AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC";
          $qOpex = "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id WHERE opc.master_id='$idMaster' AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC";
      } else {
          // $qOpex = "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE opc.partner_id='$id' AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC";
          $qOpex = "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id WHERE opc.partner_id='$id' AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC";
      }
      $sqlOpex = mysqli_query($db_conn,$qOpex);
      
      while($row = mysqli_fetch_assoc($sqlOpex)){
          $opex = $row['amount']==null?0:(int)$row['amount'];
          $res['opex'] = $opex;
      }
      if (mysqli_num_rows($hppQ) > 0) {
          $resQ = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
          $res['hpp']=(double)$resQ[0]['hpp'];

          $res['gross_profit'] = $res['gross_profit'] - $res['hpp'];
          $res['gross_profit_afterservice']=$res['gross_profit']-$res['service'];
          $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
          $res['net_profit_before_charge']= $res['gross_profit_aftertax']-$opex;
          
          $success=1;
          $status=200;
          $msg="Success";
      }else{
          $success=0;
          $status=401;
          $msg="Not Found";
      }
    }

}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$res, "hpp"=>$resQ]);

echo $signupJson;
?>