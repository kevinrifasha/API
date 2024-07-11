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
$all = "0";
$data = array();

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
    $id= $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $id = $_GET['partnerID']; 
    }
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    
    $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
    
    if(mysqli_num_rows($sqlPartner) > 0) {
        $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
        foreach($getPartners as $partner) {
            $idPerPartner = $partner['partner_id'];
            $partnerName = $partner['partner_name'];
            $dataPartner = array();
            
            $sql = "SELECT SUM(e.amount) as amount FROM operational_expenses e JOIN operational_expense_categories ec ON e.category_id = ec.id JOIN employees em ON e.created_by = em.id WHERE ec.master_id = '$idMaster' AND em.id_partner='$idPerPartner' AND e.deleted_at IS NULL AND DATE(e.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY e.id DESC";
            
            $sql1 = "SELECT SUM(e.amount) as amount, e.name FROM operational_expenses e JOIN operational_expense_categories ec ON e.category_id = ec.id JOIN employees em ON e.created_by = em.id WHERE ec.master_id = '$idMaster' AND em.id_partner='$idPerPartner' AND e.deleted_at IS NULL AND DATE(e.created_at) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY name";
            
            $sql2 = "SELECT SUM(e.amount) as amount, ec.name FROM operational_expenses e JOIN operational_expense_categories ec ON e.category_id = ec.id JOIN employees em ON e.created_by = em.id WHERE ec.master_id = '$idMaster' AND em.id_partner='$idPerPartner' AND e.deleted_at IS NULL AND DATE(e.created_at) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY name";
            
            
            $getOperationalExpenses = mysqli_query($db_conn, $sql);
            $fetchOpex = mysqli_fetch_all($getOperationalExpenses, MYSQLI_ASSOC);
            $opexAmount = $fetchOpex[0]["amount"] ?? "0";
        
            $getOperationalExpensesByName = mysqli_query($db_conn, $sql1);
            $fetchOpexByName = mysqli_fetch_all($getOperationalExpensesByName, MYSQLI_ASSOC);
            $opexName = $fetchOpexByName;
        
            $getOperationalExpensesByCat = mysqli_query($db_conn, $sql2);
            $fetchOpexByCat = mysqli_fetch_all($getOperationalExpensesByCat, MYSQLI_ASSOC);
            $opexCat = $fetchOpexByCat;
            
            $dataPartner = ["partner_id"=>$idPerPartner, "partner_name"=>$partnerName,"opex_amount"=>$opexAmount, "opex_name"=>$opexName, "opex_cat"=>$opexCat]; 
    
            array_push($data, $dataPartner);
        }

        $success = 1;
        $status = 200;
        $msg = "Get Operational Expenses Data Success";
    
    } else {
        $success = 0;
        $status = 204;
        $msg = "Get Operational Expenses Data Not Found";
    }
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data]);

echo $signupJson;
?>