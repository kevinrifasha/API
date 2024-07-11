 <?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: access");
// header("Access-Control-Allow-Methods: GET");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// require_once("./../tokenModels/tokenManager.php");
// require_once("../connection.php");
// require '../../db_connection.php';
// require_once '../../includes/CalculateFunctions.php';
// require  __DIR__ . '/../../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
// $dotenv->load();

// $cf = new CalculateFunction();
// $id = $_GET['id'];
// $dateTo = $_GET['dateTo'];
// $dateFrom = $_GET['dateFrom'];

// $dateTo = str_replace("%20"," ",$dateTo);
// $dateFrom = str_replace("%20"," ",$dateFrom);

// $all = "0";
// $res = array();
// $resQ = array();
// $tot = [];
// $shiftTrx = [];
// $charge_ewallet = 0;

// $headers = array();
//     $rx_http = '/\AHTTP_/';
//     foreach($_SERVER as $key => $val) {
//       if( preg_match($rx_http, $key) ) {
//         $arh_key = preg_replace($rx_http, '', $key);
//         $rx_matches = array();
//         // do some nasty string manipulations to restore the original letter case
//         // this should work in most cases
//         $rx_matches = explode('_', $arh_key);
//         if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
//           foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
//           $arh_key = implode('-', $rx_matches);
//         }
//         $headers[$arh_key] = $val;
//       }
//     }
// $token = '';

// foreach ($headers as $header => $value) {
//     if($header=="Authorization" || $header=="AUTHORIZATION"){
//         $token=substr($value,7);
//     }
// }

// $db = connectBase();
// $tokenizer = new TokenManager($db);
// $tokens = $tokenizer->validate($token);
// $values = array();
// $token = json_decode($tokenizer->stringEncryption('decrypt',$token));
// $idMaster = $token->masterID;
// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

//     $status = $tokens['status'];
//     $msg = $tokens['msg'];
//     $success = 0;

// }else{
//     if(isset($_GET['all'])) {
//         $all = $_GET['all'];
//     }
//     if($all !== "1") {
//         $idMaster = null;
//     }
    
//     if($all == "1") {
//         $res = $cf->getSubTotalMasterWithHour($idMaster, $dateFrom, $dateTo);
//     } else {
//         $res = $cf->getSubTotalWithHour($id, $dateFrom, $dateTo);
//     }
    
//     $shiftTrx = $cf->getShiftTransactionWithHour($id, $dateFrom, $dateTo, $idMaster);
//     $res['income'] = $shiftTrx['debit'];
//     $res['expense'] = $shiftTrx['credit'];
//     $payments = $cf->getGroupPaymentMethodWithHour($id, $dateFrom, $dateTo, $idMaster);
//     foreach($payments as $x){
//         $charge_ewallet += (int)$x['charge_ewallet'];
//     }
//     $res['charge_ewallet'] = $charge_ewallet;
//     $res['hpp']=0;
//     $res['gross_profit']=$res['clean_sales'];
//     $res['gross_profit_afterincome'] = $res['gross_profit'] + $res['income'];
//     $res['gross_profit_afterexpense'] = $res['gross_profit_afterincome'] - $res['expense'];
//     $res['gross_profit_afterservice']=$res['gross_profit_afterexpense']-$res['service'];
//     $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
//     $res['gross_profit_aftercharge']=$res['gross_profit_aftertax']-$res['charge_ewallet'];
    

//     $query = "";
//     if($all == "1") {
//         $query =  "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories c ON c.id = menu.id_category WHERE c.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
//     } else {
//         $query =  "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
//     }
    
//     $hppQ = mysqli_query($db_conn,$query);
    
//     if (mysqli_num_rows($hppQ) > 0) {
//         $resQ1 = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
//         $resQ[0]['hpp']=0;
//         foreach ($resQ1 as $value) {
//             $resQ[0]['hpp']+=(double) $value['hpp'];
//         }
//         $res['hpp']=(double)$resQ[0]['hpp'];
//         $res['gross_profit'] = $res['gross_profit'] - $res['hpp'];
//         $res['gross_profit_afterincome'] = $res['gross_profit'] + $res['income'];
//         $res['gross_profit_afterexpense'] = $res['gross_profit_afterincome'] - $res['expense'];
//         $res['gross_profit_afterservice']=$res['gross_profit_afterexpense']-$res['service'];
//         $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
//         $res['gross_profit_aftercharge']=$res['gross_profit_aftertax']-$res['charge_ewallet'];
//         $success=1;
//         $status=200;
//         $msg="Success";
//     }else{
//         $success=0;
//         $status=401;
//         $msg="Not Found";
//     }
// }
// $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$res, "hpp"=>$resQ]);

// echo $signupJson;


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
$shiftTrx = [];
$charge_ewallet = 0;

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
    if($all !== "1") {
        $idMaster = null;
    }
    
    if($newDateFormat == 1){
        if($all == "1") {
            $res = $cf->getSubTotalMasterWithHour($idMaster, $dateFrom, $dateTo);
        } else {
            $res = $cf->getSubTotalWithHour($id, $dateFrom, $dateTo);
        }
        
        $shiftTrx = $cf->getShiftTransactionWithHour($id, $dateFrom, $dateTo, $idMaster);
        $res['income'] = $shiftTrx['debit'];
        $res['expense'] = $shiftTrx['credit'];
        $payments = $cf->getGroupPaymentMethodWithHour($id, $dateFrom, $dateTo, $idMaster);
        
        foreach($payments as $x){
            $charge_ewallet += (int)$x['charge_ewallet'];
        }
        $res['charge_ewallet'] = $charge_ewallet;
        $res['hpp']=0;
        $res['gross_profit']=$res['clean_sales'];
        $res['gross_profit_afterincome'] = $res['gross_profit'] + $res['income'];
        $res['gross_profit_afterexpense'] = $res['gross_profit_afterincome'] - $res['expense'];
        $res['gross_profit_afterservice']=$res['gross_profit_afterexpense']-$res['service'];
        $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
        $res['gross_profit_aftercharge']=$res['gross_profit_aftertax']-$res['charge_ewallet'];
        
    
        $query = "";
        
        if($all == "1") {
            $query =  "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories c ON c.id = menu.id_category WHERE c.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.deleted_at IS NULL ";
        } else {
            $query =  "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.deleted_at IS NULL ";
        }
        
        $hppQ = mysqli_query($db_conn,$query);
        
        if (mysqli_num_rows($hppQ) > 0) {
            $resQ1 = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
            $resQ[0]['hpp']=0;
            foreach ($resQ1 as $value) {
                $resQ[0]['hpp']+=(double) $value['hpp'];
            }
            $res['hpp']=(double)$resQ[0]['hpp'];
            $res['gross_profit'] = $res['gross_profit'] - $res['hpp'];
            $res['gross_profit_afterincome'] = $res['gross_profit'] + $res['income'];
            $res['gross_profit_afterexpense'] = $res['gross_profit_afterincome'] - $res['expense'];
            $res['gross_profit_afterservice']=$res['gross_profit_afterexpense']-$res['service'];
            $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
            $res['gross_profit_aftercharge']=$res['gross_profit_aftertax']-$res['charge_ewallet'];
            $success=1;
            $status=200;
            $msg="Success";
        }else{
            $success=0;
            $status=401;
            $msg="Not Found";
        }
    }
    
    else {
        if($all == "1") {
            $res = $cf->getSubTotalMaster($idMaster, $dateFrom, $dateTo);
        } else {
            $res = $cf->getSubTotal($id, $dateFrom, $dateTo);
        }
        
        $shiftTrx = $cf->getShiftTransaction($id, $dateFrom, $dateTo, $idMaster);
        $res['income'] = $shiftTrx['debit'];
        $res['expense'] = $shiftTrx['credit'];
        $payments = $cf->getGroupPaymentMethod($id, $dateFrom, $dateTo, $idMaster);
        
        foreach($payments as $x){
            $charge_ewallet += (int)$x['charge_ewallet'];
        }
        $res['charge_ewallet'] = $charge_ewallet;
        $res['hpp']=0;
        $res['gross_profit']=$res['clean_sales'];
        $res['gross_profit_afterincome'] = $res['gross_profit'] + $res['income'];
        $res['gross_profit_afterexpense'] = $res['gross_profit_afterincome'] - $res['expense'];
        $res['gross_profit_afterservice']=$res['gross_profit_afterexpense']-$res['service'];
        $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
        $res['gross_profit_aftercharge']=$res['gross_profit_aftertax']-$res['charge_ewallet'];
        
    
        $query = "";
        
        if($all == "1") {
            $query =  "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories c ON c.id = menu.id_category WHERE c.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') ";
        } else {
            $query =  "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND transaksi.paid_date BETWEEN DATE('$dateFrom') AND DATE('$dateTo') ";
        }
        
        $hppQ = mysqli_query($db_conn,$query);
        
        if (mysqli_num_rows($hppQ) > 0) {
            $resQ1 = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
            $resQ[0]['hpp']=0;
            foreach ($resQ1 as $value) {
                $resQ[0]['hpp']+=(double) $value['hpp'];
            }
            $res['hpp']=(double)$resQ[0]['hpp'];
            $res['gross_profit'] = $res['gross_profit'] - $res['hpp'];
            $res['gross_profit_afterincome'] = $res['gross_profit'] + $res['income'];
            $res['gross_profit_afterexpense'] = $res['gross_profit_afterincome'] - $res['expense'];
            $res['gross_profit_afterservice']=$res['gross_profit_afterexpense']-$res['service'];
            $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
            $res['gross_profit_aftercharge']=$res['gross_profit_aftertax']-$res['charge_ewallet'];
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
