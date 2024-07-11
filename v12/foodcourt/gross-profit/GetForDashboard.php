<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../tokenModels/tokenManager.php");
require_once("../../connection.php");
require '../../../db_connection.php';
require_once '../../../includes/CalculateFunctions.php';
require  __DIR__ . '/../../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
$dotenv->load();


$cf = new CalculateFunction();

$id = $_GET['id'];
$dateTo = $_GET['dateTo'];
$dateFrom = $_GET['dateFrom'];
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
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $res = $cf->getSubTotalTenant($id, $dateFrom, $dateTo);
    $res['hpp']=0;
    $res['gross_profit']=$res['clean_sales'];
    $res['gross_profit_afterservice']=$res['gross_profit']-$res['service'];
    $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];


    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);
    $query = "SELECT SUM(hpp) AS hpp FROM ( SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= " UNION ALL " ;
                $query .= " SELECT SUM(`$detail_transactions`.qty * menu.hpp) AS hpp FROM `$detail_transactions` JOIN `$transactions` ON `$transactions`.id=`$detail_transactions`.id_transaksi JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE `menu`.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.status<=2 and `$transactions`.status>=1 AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $query .= " ) AS tmp ";
    $hppQ = mysqli_query(
        $db_conn,
        $query
    );

    $sqlOpex = mysqli_query($db_conn, "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE p.id='$id'AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC");
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
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$res, "hpp"=>$resQ]);
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
