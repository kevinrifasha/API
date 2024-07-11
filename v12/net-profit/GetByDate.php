<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';

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
$idMaster = $tokenDecoded->masterID;
$value = array();
$data = array();
$success=0;
$msg = 'Failed';
$all = "0";
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $id = $_GET['id'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    $query = "";
    
    if(strlen($dateTo) === 10 && strlen($dateFrom) === 10){
        if($all == "1"){
            $query = "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE opc.master_id = '$idMaster' AND op.deleted_at IS NULL AND op.created_at BETWEEN DATE('$dateFrom') AND DATE('$dateTo') ORDER BY op.id DESC";
        } else {
            $query = "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE p.id='$id'AND op.deleted_at IS NULL AND op.created_at BETWEEN DATE('$dateFrom') AND DATE('$dateTo') ORDER BY op.id DESC";
        }
    }
    else
    {
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        if($all == "1"){
            $query = "SELECT SUM(am.amount) as amount FROM (SELECT DISTINCT op.id, op.amount as amount FROM operational_expenses op LEFT JOIN operational_expense_categories opc ON op.category_id=opc.id LEFT JOIN partner p ON p.id_master=opc.master_id LEFT JOIN employees e ON e.id=op.created_by WHERE p.id_master='$idMaster' AND op.deleted_at IS NULL AND op.created_by != 0 AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo') am";
        } else {
            $query = "SELECT SUM(am.amount) as amount FROM (SELECT DISTINCT op.amount as amount, op.id FROM operational_expenses op LEFT JOIN operational_expense_categories opc ON op.category_id=opc.id LEFT JOIN partner p ON p.id_master=opc.master_id LEFT JOIN employees e ON e.id=op.created_by WHERE e.id_partner='$id' AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo') am";
        }
        
    }

    $sql = mysqli_query($db_conn, $query);
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }

}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "net"=>$data]);

?>