<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once '../../includes/CalculateFunctions.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$cf = new CalculateFunction();
$values = [];
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
$tokenizer = new Token();
$token = '';

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;
$array = [];

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    http_response_code($status);
    $success = 0;

}else{
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    
    $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
    if(mysqli_num_rows($sqlPartner) > 0) {
        $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
        foreach($getPartners as $partner) {
            $id = $partner['partner_id'];
            $data = array();
            $totals = array();
            $mdrTax=0;
          
            $vals = $cf->getGroupPaymentMethod($id, $dateFrom, $dateTo, null);
            $getMDRTax = mysqli_query($db_conn, "SELECT value FROM settings WHERE id=24");
            while($row=mysqli_fetch_assoc($getMDRTax)){
                $mdrTax = (int)$row['value'];
              }
            $i=0;
            $totalIncome = 0;
            $totalMDR = 0;
            $totalTax = 0;
            $totalValue = 0;
            foreach($vals as $x){
                $data[$i]=$x;
                $intType = (int)$x['tipe'];
                if($intType==1||$intType==3||$intType==4||$intType==10){
                    $data[$i]['mdr']=1.5;
                    $data[$i]['tax']=$mdrTax;
                }else if($intType==2){
                    $data[$i]['mdr']=2;
                    $data[$i]['tax']=$mdrTax;
                }else{
                    $data[$i]['mdr']=0;
                    $data[$i]['tax']=0;
                }
                $data[$i]['mdr_rupiah']=ceil((int)$data[$i]['value']*$data[$i]['mdr']/100);
                $data[$i]['tax_rupiah']=ceil((int)$data[$i]['mdr_rupiah']*$data[$i]['tax']/100);
                $data[$i]['income']= $data[$i]['value']-$data[$i]['mdr_rupiah']-$data[$i]['tax_rupiah'];
                $totalIncome += (int)$data[$i]['income'];
                $totalMDR += (int)$data[$i]['mdr_rupiah'];
                $totalTax += (int)$data[$i]['tax_rupiah'];
                $totalValue += (int)$data[$i]['value'];
                $i++;
            }
            $totals['total_income']=$totalIncome;
            $totals['total_mdr']=$totalMDR;
            $totals['total_tax']=$totalTax;
            $totals['total_value']=$totalValue;
            
            $partner['data'] = $data;
            $partner['totals'] = $totals;
            
            if(count($data) > 0) {
                array_push($array, $partner);
            }
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

http_response_code($status);
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "list"=>$array]);

echo $signupJson;
