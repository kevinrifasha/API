<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require_once '../../includes/CalculateFunctions.php';
require '../../db_connection.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();
$cf = new CalculateFunction();
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
$success=0;
$msg = 'Failed';
$array = array();

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }

    $idMaster = $tokenDecoded->masterID;

    if($newDateFormat == 1){
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $id = $partner['partner_id'];
                $name = $partner['partner_name'];
                $queryRepeater = "SELECT COUNT(trx.phone) as monthly_repeater FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.is_repeater='1') trx";
                $queryNonRepeater = "SELECT COUNT(trx.phone) as monthly_non_repeater FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.is_repeater='0') trx";
                
                $sqlRepeater = mysqli_query($db_conn, $queryRepeater);
                $fetchRepeater = mysqli_fetch_all($sqlRepeater, MYSQLI_ASSOC);
                $repeater = $fetchRepeater[0]['monthly_repeater'];
                $sqlNonRepeater = mysqli_query($db_conn, $queryNonRepeater);
                $fetchNonRepeater = mysqli_fetch_all($sqlNonRepeater, MYSQLI_ASSOC);
                $nonRepeater = $fetchNonRepeater[0]['monthly_non_repeater'];
                
                $data = ["partner_name"=>$name, "partner_id"=>$id, "monthly_repeater"=>$repeater,"monthly_non_repeater"=>$nonRepeater ];
                
                array_push($array, $data);
                    
            } 
            $success=1;
            $status=200;
            $msg = 'Succeed';
        } else {
            $success=0;
            $status=203;
            $msg = 'Failed';
        }
    }else{
                $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $id = $partner['partner_id'];
                $name = $partner['partner_name'];
                $queryRepeater = "SELECT COUNT(trx.phone) as monthly_repeater FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner='$id' AND t.is_repeater='1') trx";
                $queryNonRepeater = "SELECT COUNT(trx.phone) as monthly_non_repeater FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner='$id' AND t.is_repeater='0') trx";
                
                $sqlRepeater = mysqli_query($db_conn, $queryRepeater);
                $fetchRepeater = mysqli_fetch_all($sqlRepeater, MYSQLI_ASSOC);
                $repeater = $fetchRepeater[0]['monthly_repeater'];
                $sqlNonRepeater = mysqli_query($db_conn, $queryNonRepeater);
                $fetchNonRepeater = mysqli_fetch_all($sqlNonRepeater, MYSQLI_ASSOC);
                $nonRepeater = $fetchNonRepeater[0]['monthly_non_repeater'];
                
                $data = ["partner_name"=>$name, "partner_id"=>$id, "monthly_repeater"=>$repeater,"monthly_non_repeater"=>$nonRepeater ];
                
                array_push($array, $data);
                    
            }
            $success=1;
            $status=200;
            $msg = 'Succeed';
        } else {
            $success=0;
            $status=203;
            $msg = 'Failed';
        }
        

    }
    


}
echo json_encode([
  "success"=>$success,
  "status"=>$status,
  "msg"=>$msg,
  "data"=>$array
]);
