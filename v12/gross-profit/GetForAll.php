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
// $all = "1";
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

    if($newDateFormat == 1)
    {
        $res = $cf->getSubTotalAllWithHour($idMaster, $dateFrom, $dateTo);
        $data = [];
        
        if(count($res) > 0) {
            foreach($res as $val) {
                $id = $val['id_partner'];
                $charge_ewallet = 0;
                $shiftTrx = $cf->getShiftTransactionWithHour($id, $dateFrom, $dateTo, null);
                
                $val['income'] = $shiftTrx['debit'];
                $val['expense'] = $shiftTrx['credit'];
            
                $payments = $cf->getGroupPaymentMethodWithHour($id, $dateFrom, $dateTo, null);
                foreach($payments as $x){
                    $charge_ewallet += (int)$x['charge_ewallet'];
                }

                $val['charge_ewallet'] = $charge_ewallet;
                $val['hpp']=0;
                $val['gross_profit']=$val['clean_sales'];
                $val['gross_profit_afterincome'] = $val['gross_profit'] + $val['income'];
                $val['gross_profit_afterexpense'] = $val['gross_profit_afterincome'] - $val['expense'];
                $val['gross_profit_afterservice']=$val['gross_profit_afterexpense']-$val['service'];
                $val['gross_profit_aftertax']=$val['gross_profit_afterservice']-$val['tax'];
                $val['gross_profit_aftercharge']=$val['gross_profit_aftertax']-$val['charge_ewallet'];
        
                $query =  "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
            
                $hppQ = mysqli_query($db_conn,$query);
            
            if (mysqli_num_rows($hppQ) > 0) {
                $valQ1 = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
                $valQ[0]['hpp']=0;
                foreach ($valQ1 as $value) {
                    $valQ[0]['hpp']+=(double) $value['hpp'];
                }
                $val['hpp']=(double)$valQ[0]['hpp'];
                $val['gross_profit'] = $val['gross_profit'] - $val['hpp'];
                $val['gross_profit_afterincome'] = $val['gross_profit'] + $val['income'];
                $val['gross_profit_afterexpense'] = $val['gross_profit_afterincome'] - $val['expense'];
                $val['gross_profit_afterservice']=$val['gross_profit_afterexpense']-$val['service'];
                $val['gross_profit_aftertax']=$val['gross_profit_afterservice']-$val['tax'];
                $val['gross_profit_aftercharge']=$val['gross_profit_aftertax']-$val['charge_ewallet'];
            }
                array_push($data, $val);
            }
            
            $success=1;
            $status=200;
            $msg="Success";
        } else {
            $success=0;
            $status=203;
            $msg="Data not found";
        }
    }
    else
    {
    $res = $cf->getSubTotalAll($idMaster, $dateFrom, $dateTo);
    $data = [];
    
    if(count($res) > 0) {
        foreach($res as $val) {
            $id = $val['id_partner'];
            $charge_ewallet = 0;
            $shiftTrx = $cf->getShiftTransaction($id, $dateFrom, $dateTo, null);
            
            $val['income'] = $shiftTrx['debit'];
            $val['expense'] = $shiftTrx['credit'];
        
            $payments = $cf->getGroupPaymentMethod($id, $dateFrom, $dateTo, null);
            foreach($payments as $x){
                $charge_ewallet += (int)$x['charge_ewallet'];
            }

            $val['charge_ewallet'] = $charge_ewallet;
            $val['hpp']=0;
            $val['gross_profit']=$val['clean_sales'];
            $val['gross_profit_afterincome'] = $val['gross_profit'] + $val['income'];
            $val['gross_profit_afterexpense'] = $val['gross_profit_afterincome'] - $val['expense'];
            $val['gross_profit_afterservice']=$val['gross_profit_afterexpense']-$val['service'];
            $val['gross_profit_aftertax']=$val['gross_profit_afterservice']-$val['tax'];
            $val['gross_profit_aftercharge']=$val['gross_profit_aftertax']-$val['charge_ewallet'];
    
            $query =  "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
        
            $hppQ = mysqli_query($db_conn,$query);
        
        if (mysqli_num_rows($hppQ) > 0) {
            $valQ1 = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
            $valQ[0]['hpp']=0;
            foreach ($valQ1 as $value) {
                $valQ[0]['hpp']+=(double) $value['hpp'];
            }
            $val['hpp']=(double)$valQ[0]['hpp'];
            $val['gross_profit'] = $val['gross_profit'] - $val['hpp'];
            $val['gross_profit_afterincome'] = $val['gross_profit'] + $val['income'];
            $val['gross_profit_afterexpense'] = $val['gross_profit_afterincome'] - $val['expense'];
            $val['gross_profit_afterservice']=$val['gross_profit_afterexpense']-$val['service'];
            $val['gross_profit_aftertax']=$val['gross_profit_afterservice']-$val['tax'];
            $val['gross_profit_aftercharge']=$val['gross_profit_aftertax']-$val['charge_ewallet'];
        }
            array_push($data, $val);
        }
        
        $success=1;
        $status=200;
        $msg="Success";
    } else {
        $success=0;
        $status=203;
        $msg="Data not found";
    }
}


    
    
}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data, "test"=>$res]);

echo $signupJson;
