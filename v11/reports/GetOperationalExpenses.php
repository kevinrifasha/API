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
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    $sql = ""; 
    
    if($all == "1") {
        $sql = "SELECT SUM(op.amount) as amount FROM operational_expenses op LEFT JOIN operational_expense_categories opc ON op.category_id=opc.id LEFT JOIN partner p ON p.id_master=opc.master_id WHERE p.id_master=$idMaster AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC";

        $sql1 = "SELECT SUM(op.amount) as amount, op.name FROM operational_expenses op LEFT JOIN operational_expense_categories opc ON op.category_id=opc.id LEFT JOIN partner p ON p.id_master=opc.master_id WHERE p.id_master=$idMaster AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY name ORDER BY op.id DESC";
        
        $sql2 = "SELECT SUM(op.amount) as amount, opc.name FROM operational_expenses op LEFT JOIN operational_expense_categories opc ON op.category_id=opc.id LEFT JOIN partner p ON p.id_master=opc.master_id WHERE p.id_master=$idMaster AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY name ORDER BY op.id DESC";
    } else {
        $getPartnerQuery = "SELECT opc.master_id, opc.partner_id, p.parent_id FROM operational_expense_categories opc LEFT JOIN partner p ON p.id=opc.partner_id WHERE master_id='$idMaster' group by partner_id";
        $getParent = "SELECT p.parent_id FROM operational_expense_categories opc LEFT JOIN partner p ON p.id=opc.partner_id WHERE opc.master_id='$idMaster' AND p.parent_id IS NOT NULL group by parent_id";
        $gPQExecute = mysqli_query($db_conn, $getPartnerQuery);
        $gPExecute = mysqli_query($db_conn, $getParent);
        $getPartnerData = mysqli_fetch_all($gPQExecute, MYSQLI_ASSOC);
        $getParentData = mysqli_fetch_all($gPExecute, MYSQLI_ASSOC);
        $parent_id = $getParentData[0]["parent_id"];
        
        // $test = "hit all 0";
        
        // $appendQuery = "";
        
        // if(count($getPartnerData) == 1 && ($getPartnerData[0]["partner_id"] == null || $getPartnerData[0]["partner_id"] == "")){
            
        //     $appendQuery = "p.id_master = '$idMaster'";
            
        //     $test = "hti 1";
        // }else if(count($getPartnerData) == 2 && (($getPartnerData[0]["partner_id"] == null || $getPartnerData[0]["partner_id"] == "") && ($getPartnerData[1]["partner_id"] == null || $getPartnerData[1]["partner_id"] == ""))){
            
        //     $appendQuery = "p.id = '$idPartner'";
            
        //     $test = "hit 2";
        // }else if(count($getPartnerData) == 1 && str_len($getPartnerData[0]["partner_id"]) != 6){
            
        //     $numZeros = 6 - strlen($getPartnerData[0]["partner_id"]);
            
        //     $idPartner = $getPartnerData[0]["partner_id"];
            
        //     for ($x = 0; $x < $numZeros; $x++) {
        //       $idPartner = "0" . $idPartner;
        //     }
            
        //     $appendQuery = "p.id = '$idPartner'";
        //     $test = "hti 3";
        // } else {
        //     $idCut = str_replace("0",'',$id);
        //     $idPartner = 0;
        //     $test = "hit else";
        //     foreach($getPartnerData as $partner){
                
        //         if($partner["partner_id"] == $id){
        //             $idPartner = $id;
        //             $test = "hit 4";
        //             break;
        //         }
                
        //         if($partner["partner_id"] == $idCut){
        //             $idPartner = $id;
        //             $test = "hit 5";
        //             break;
        //         }
                
        //     }
            
        //     if($idPartner != 0){
        //         if($partner["partner_id"] == $parent_id){
        //             $appendQuery = "p.id = '$idPartner'";
        //         } else {
                    
        //         }
        //     }
        // }

        $sql = "SELECT SUM(e.amount) as amount FROM operational_expenses e JOIN operational_expense_categories ec ON e.category_id = ec.id JOIN employees em ON e.created_by = em.id WHERE ec.master_id = '$idMaster' AND em.id_partner='$id' AND e.deleted_at IS NULL AND DATE(e.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY e.id DESC";
        
        $sql1 = "SELECT SUM(e.amount) as amount, e.name FROM operational_expenses e JOIN operational_expense_categories ec ON e.category_id = ec.id JOIN employees em ON e.created_by = em.id WHERE ec.master_id = '$idMaster' AND em.id_partner='$id' AND e.deleted_at IS NULL AND DATE(e.created_at) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY name";
        
        $sql2 = "SELECT SUM(e.amount) as amount, ec.name FROM operational_expenses e JOIN operational_expense_categories ec ON e.category_id = ec.id JOIN employees em ON e.created_by = em.id WHERE ec.master_id = '$idMaster' AND em.id_partner='$id' AND e.deleted_at IS NULL AND DATE(e.created_at) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY name";
    }
    
    $getOperationalExpenses = mysqli_query($db_conn, $sql);
    $fetchOpex = mysqli_fetch_all($getOperationalExpenses, MYSQLI_ASSOC);
    $opexAmount = $fetchOpex[0]["amount"] ?? "0";

    $getOperationalExpensesByName = mysqli_query($db_conn, $sql1);
    $fetchOpexByName = mysqli_fetch_all($getOperationalExpensesByName, MYSQLI_ASSOC);
    $opexName = $fetchOpexByName;

    $getOperationalExpensesByCat = mysqli_query($db_conn, $sql2);
    $fetchOpexByCat = mysqli_fetch_all($getOperationalExpensesByCat, MYSQLI_ASSOC);
    $opexCat = $fetchOpexByCat;
    
    $success = 1;
    $status = 200;
    $msg = "Get Operational Expenses Data Success";
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "opex_amount"=>$opexAmount, "opex_name"=>$opexName, "opex_cat"=>$opexCat]);

echo $signupJson;
?>