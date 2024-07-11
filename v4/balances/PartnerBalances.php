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
$vals = [];
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

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$values = array();
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    http_response_code($status);
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $id = $token->id_partner;
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);
    $query = "SELECT transaksi.total, transaksi.tax, transaksi.service, transaksi.charge_ur, transaksi.promo, transaksi.point, transaksi.tipe_bayar FROM transaksi WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
   
    $transaksi = mysqli_query(
        $db_conn,
        $query
    );
    $values = array();
    $tot=0;
    $sumCharge_ur=0;
    $sumPoint=0;
    $sumPromo=0;
    $sumTotal=0;
    $sumService=0;
    $sumTax=0;

    while($row=mysqli_fetch_assoc($transaksi)){
        $countService=0;
        $withService=0;
        $countTax=0;

        $tax = $row['tax'];
        $service = $row['service'];
        $charge_ur = $row['charge_ur'];
        $total = $row['total'];
        $promo = $row['promo'];
        $point = $row['point'];

        $sumPromo+=$promo;
        $sumPoint+=$point;
        $sumTotal+=$total;
        $sumCharge_ur+=$charge_ur;

        $countService = ceil((($total-$promo)*$service)/100);
        $sumService += $countService;
        $countTax = ceil(((($total-$promo)+$countService+$charge_ur)*$tax)/100) + $charge_ur;
        $sumTax += $countTax;
        $totTemp=$countService+$countTax+$total-$promo;
        $tot+=$totTemp;
        $values["value"]=$tot;
        $values["charge_ur"]=$sumCharge_ur;
        $values["point"]=$sumPoint;
        // array_push($values, array("value" => $tot, "prom" => $sumCharge_ur));; 
    }
    
    
    $vals1 = $cf->getGroupPaymentMethod($id, $dateFrom, $dateTo);
    $vals = array();
    $i=0;
    foreach($vals1 as $v){
        $vals[$i]['paymentMethodName']=$v['payment_method_name'];
        $vals[$i]['value']=$v['value'];
        $vals[$i]['charge_ur']=$v['charge_ur'];
        $vals[$i]['point']=$v['point'];
        $vals[$i]['tipe']=$v['tipe'];
        $i+=1;
    }
    $success=1;
    $status=200;
    http_response_code($status);
    $msg="Success";
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$values, "paymentMethod"=>$vals]);  
// Echo the message.
echo $signupJson;
