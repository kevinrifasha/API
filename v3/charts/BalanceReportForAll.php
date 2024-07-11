<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once '../../includes/CalculateFunctions.php';
require_once("../connection.php");
require '../../db_connection.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

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

$cf = new CalculateFunction();
$res = array();
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->masterID;
$value = array();
$success=0;
$msg = 'Failed';
$array = [];

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];

    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    $status = 200;
    $success=1;
    $msg="Success";

    if($newDateFormat == 1){
      $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
      if(mysqli_num_rows($sqlPartner) > 0) {
          $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
          
          foreach($getPartners as $partner) {
              $id = $partner['partner_id'];
              $res = array();
              
              // graph payment method percentage
              $query = "SELECT DISTINCT 
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS Total,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=1 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS OvoCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=2 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS GopayCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=3 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS DanaCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=4 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS LinkAjaCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=5 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS TunaiCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=6 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS SakukuCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=7 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS CreditCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=8 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS DebitCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=9 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS QrisCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=10 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS ShopeeCount
                  FROM transaksi WHERE paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
              
              $transaksi = mysqli_query($db_conn, $query);
              $values = array();
          
              $total = 0;
              $ovo = 0;
              $gopay = 0;
              $dana = 0;
              $linkaja = 0;
              $tunai = 0;
              $sakuku = 0;
              $credit = 0;
              $debit = 0;
              $qris = 0;
              $shopee = 0;
              while ($row = mysqli_fetch_assoc($transaksi)) {
                  $total = (int) $row['Total'];
                  $ovo = (int) $row['OvoCount'];
                  $gopay = (int) $row['GopayCount'];
                  $dana = (int) $row['DanaCount'];
                  $linkaja = (int) $row['LinkAjaCount'];
                  $tunai = (int) $row['TunaiCount'];
                  $sakuku = (int) $row['SakukuCount'];
                  $credit = (int) $row['CreditCount'];
                  $debit = (int) $row['DebitCount'];
                  $qris = (int) $row['QrisCount'];
                  $shopee = (int) $row['ShopeeCount'];
              }
              
              if($total > 0) {
                  $pOvo =round(($ovo/$total)*100);
                  $pGopay =round(($gopay/$total)*100);
                  $pDana =round(($dana/$total)*100);
                  $pLinkaja =round(($linkaja/$total)*100);
                  $pTunai =round(($tunai/$total)*100);
                  $pSakuku =round(($sakuku/$total)*100);
                  $pCredit =round(($credit/$total)*100);
                  $pDebit =round(($debit/$total)*100);
                  $pQris =round(($qris/$total)*100);
                  $pShopee = round(($shopee/$total)*100);
                  array_push($values, array("label" => 'OVO', "value" => $pOvo, "value1"=>$ovo, "color"=>"#c128c9"));
                  array_push($values, array("label" => 'Gopay', "value" => $pGopay, "value1"=>$gopay, "color"=>"#0000FF"));
                  array_push($values, array("label" => 'Dana', "value" => $pDana,  "value1"=>$dana, "color"=>"#00D8FF"));
                  array_push($values, array("label" => 'LinkAja', "value" => $pLinkaja,  "value1"=>$linkaja, "color"=>"#DD1B16"));
                  array_push($values, array("label" => 'Tunai', "value" => $pTunai,  "value1"=>$tunai, "color"=>"#11f215"));
                  // array_push($values, array("label" => 'Sakuku', "value" => $pSakuku));
                  array_push($values, array("label" => 'Kartu Kredit', "value" => $pCredit, "value1"=>$credit, "color"=>"#1ca3a1"));
                  array_push($values, array("label" => 'Kartu Debit', "value" => $pDebit,  "value1"=>$debit,"color"=>"#808080"));
                  array_push($values, array("label" => 'QRIS', "value" => $pQris,  "value1"=>$qris, "color"=>"#e9f241"));
                  array_push($values, array("label" => 'Shopee', "value" => $pShopee,  "value1"=>$shopee, "color"=>"#f59342"));
                  
                  foreach ($values as $key => $row) {
                      $value[$key]  = $row['value'];
                      $label[$key] = $row['label'];
                  }
                  
                  $value  = array_column($values, 'value');
                  $label = array_column($values, 'label');
                  
                  array_multisort($value, SORT_DESC, $label, SORT_ASC, $values);
                  $res['paymentMethodPercentage']=$values;
              } else {
                  $res = [];
              }
              // graph payment method percentage
              
              // partner payment method balances
              $mdrTax=0;
              $data = [];
              $totals = [];
              $dateFrom = $_GET['dateFrom'];
              $dateTo = $_GET['dateTo'];
      
              $vals = $cf->getGroupPaymentMethod($id, $dateFrom, $dateTo, null);
              $getMDRTax = mysqli_query($db_conn, "SELECT value FROM settings WHERE id=24");
              while($row=mysqli_fetch_assoc($getMDRTax)){
                $mdrTax = (int)$row['value'];
              }
              $y=0;
              $totalIncome = 0;
              $totalMDR = 0;
              $totalTax = 0;
              $totalValue = 0;
              foreach($vals as $x){
                  $data[$y]=$x;
                  $intType = (int)$x['tipe'];
                  if($intType==1||$intType==3||$intType==4||$intType==10){
                      $data[$y]['mdr']=1.5;
                      $data[$y]['tax']=$mdrTax;
                  }else if($intType==2){
                      $data[$y]['mdr']=2;
                      $data[$y]['tax']=$mdrTax;
                  }else{
                      $data[$y]['mdr']=0;
                      $data[$y]['tax']=0;
                  }
                  
                  $data[$y]['mdr_rupiah']=ceil((int)$data[$y]['value']*$data[$y]['mdr']/100);
                  
                  $data[$y]['tax_rupiah']=ceil((int)$data[$y]['mdr_rupiah']*$data[$y]['tax']/100);
                  
                  $data[$y]['income']= $data[$y]['value']-$data[$y]['mdr_rupiah']-$data[$y]['tax_rupiah'];
                  
                  $totalIncome += (int)$data[$y]['income'];
                  $totalMDR += (int)$data[$y]['mdr_rupiah'];
                  $totalTax += (int)$data[$y]['tax_rupiah'];
                  $totalValue += (int)$data[$y]['value'];
                  $y++;
              }
              $totals['total_income']=$totalIncome;
              $totals['total_mdr']=$totalMDR;
              $totals['total_tax']=$totalTax;
              $totals['total_value']=$totalValue;
              
              $totalIncome = 0;
              $totalMDR = 0;
              $totalTax = 0;
              $totalValue = 0;
              
              $paymentMethodBalances = [];
              $paymentMethodBalances['data'] = $data;
              $paymentMethodBalances['totals'] = $totals;
              $paymentMethodBalances['mdrTax'] = $mdrTax;
              // partner payment method balances end
              
              // partner income daily
              $valuesDaily = array();
      
              $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(charge_ur) AS charge_ur,SUM(point) AS point, SUM(service) AS service, SUM(tax) AS tax,
                  SUM(charge_ewallet) AS charge_ewallet, created_at FROM ( SELECT SUM(transaksi.program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+`transaksi`.charge_ur)*transaksi.tax/100) AS tax,
                  SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet,  transaksi.paid_date created_at FROM transaksi JOIN partner ON partner.id = transaksi.id_partner WHERE id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.paid_date ";
              
              $query .= ") as tmp GROUP BY created_at ";
              $transaksi = mysqli_query($db_conn,$query);
              
              if(mysqli_num_rows($transaksi) > 0) {
                  $j = 0;
                  $valuesDaily[0]['value']=0;
                  while ($row = mysqli_fetch_assoc($transaksi)) {
                      ($valuesDaily[$j]['value'] ?? $valuesDaily[$j]['value'] = 0) ? $valuesDaily[$j]['value'] += ceil($row['total'])-ceil($row['promo'])-ceil($row['program_discount'])-ceil($row['diskon_spesial'])-ceil($row['employee_discount'])-ceil($row['point'])+ceil($row['service'])+ceil($row['tax'])+ceil($row['charge_ur']) : $valuesDaily[$j]['value'] = ceil($row['total'])-ceil($row['promo'])-ceil($row['program_discount'])-ceil($row['diskon_spesial'])-ceil($row['employee_discount'])-ceil($row['point'])+ceil($row['service'])+ceil($row['tax'])+ceil($row['charge_ur']);
                      
                      $valuesDaily[$j]['date'] = date('d-m-Y',strtotime($row['created_at']));
                      $j+=1;
                  }
              } else {
                  $valuesDaily = array();
              }
              // partner income daily end
              
              // partner income monthly
              $year = date("Y");
              $valuesMonthly = array();
              
              $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount ,SUM(total) AS total, SUM(point) AS point,  SUM(charge_ur) AS charge_ur, SUM(service) AS service, SUM(tax) AS tax, 
                  SUM(charge_ewallet) AS charge_ewallet, month FROM ( SELECT SUM(program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount ,SUM(transaksi.total) AS total, SUM(transaksi.point) AS point,  SUM(transaksi.charge_ur) AS charge_ur, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+transaksi.charge_ur)*transaksi.tax/100) AS tax, 
                  SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet, MONTH(transaksi.paid_date) AS month FROM transaksi JOIN partner ON partner.id = transaksi.id_partner WHERE id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND YEAR(paid_date)='$year' GROUP BY MONTH(transaksi.paid_date) ";
              
              $dateFrom = $year."-01-01";
              $dateTo = $year."-12-31";
              
              $query .= ") AS tmp GROUP BY month";
              $transaksi = mysqli_query(
                  $db_conn,
                  $query
              );
          
              for ($i = 1; $i <= 12; $i++) {
                  array_push($valuesMonthly, array("month" => $i, "value" => 0));
              }
          
              while ($row = mysqli_fetch_assoc($transaksi)) {
                  $valuesMonthly[$row['month']-1]['value']=ceil($row['total'])-ceil($row['promo'])-ceil($row['program_discount'])-ceil($row['diskon_spesial'])-ceil($row['employee_discount'])-ceil($row['point'])+ceil($row['service'])+ceil($row['tax'])+ceil($row['charge_ur']);
              }
          
              for ($k = 0; $k < 12; $k++) {
                  $monthNum = $valuesMonthly[$k]['month'];
                  $valuesMonthly[$k]['month'] = date('F', mktime(0, 0, 0, $monthNum, 10));
              }
              // partner income monthly end
          
              $partner['paymentMethodPercentage'] = $res;
              $partner['paymentMethodBalances'] = $paymentMethodBalances;
              $partner['partnerIncomeDaily'] = $valuesDaily;
              $partner['partnerIncomeMonthly'] = $valuesMonthly;
              
              if(count($res) > 0) {
                  array_push($array, $partner);
              }
              
          }
          
          $success = 1;
          $status = 200;
          $msg = "Success";
      } else {
          $success = 0;
          $status = 203;
          $msg = "Data not found";
      }
    } 
    else 
    {
      $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
      if(mysqli_num_rows($sqlPartner) > 0) {
          $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
          
          foreach($getPartners as $partner) {
              $id = $partner['partner_id'];
              $res = array();
              
              // graph payment method percentage
              $query = "SELECT DISTINCT 
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS Total,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=1 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS OvoCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=2 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS GopayCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=3 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS DanaCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=4 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS LinkAjaCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=5 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS TunaiCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=6 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS SakukuCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=7 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS CreditCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=8 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS DebitCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=9 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS QrisCount,
                  (SELECT DISTINCT COUNT(id) FROM transaksi  WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND tipe_bayar=10 AND id_partner='$id' AND deleted_at IS NULL AND status IN (1,2)) AS ShopeeCount
                  FROM transaksi WHERE DATE(paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
              
              $transaksi = mysqli_query($db_conn, $query);
              $values = array();
          
              $total = 0;
              $ovo = 0;
              $gopay = 0;
              $dana = 0;
              $linkaja = 0;
              $tunai = 0;
              $sakuku = 0;
              $credit = 0;
              $debit = 0;
              $qris = 0;
              $shopee = 0;
              while ($row = mysqli_fetch_assoc($transaksi)) {
                  $total = (int) $row['Total'];
                  $ovo = (int) $row['OvoCount'];
                  $gopay = (int) $row['GopayCount'];
                  $dana = (int) $row['DanaCount'];
                  $linkaja = (int) $row['LinkAjaCount'];
                  $tunai = (int) $row['TunaiCount'];
                  $sakuku = (int) $row['SakukuCount'];
                  $credit = (int) $row['CreditCount'];
                  $debit = (int) $row['DebitCount'];
                  $qris = (int) $row['QrisCount'];
                  $shopee = (int) $row['ShopeeCount'];
              }
              
              if($total > 0) {
                  $pOvo =round(($ovo/$total)*100);
                  $pGopay =round(($gopay/$total)*100);
                  $pDana =round(($dana/$total)*100);
                  $pLinkaja =round(($linkaja/$total)*100);
                  $pTunai =round(($tunai/$total)*100);
                  $pSakuku =round(($sakuku/$total)*100);
                  $pCredit =round(($credit/$total)*100);
                  $pDebit =round(($debit/$total)*100);
                  $pQris =round(($qris/$total)*100);
                  $pShopee = round(($shopee/$total)*100);
                  array_push($values, array("label" => 'OVO', "value" => $pOvo, "value1"=>$ovo, "color"=>"#c128c9"));
                  array_push($values, array("label" => 'Gopay', "value" => $pGopay, "value1"=>$gopay, "color"=>"#0000FF"));
                  array_push($values, array("label" => 'Dana', "value" => $pDana,  "value1"=>$dana, "color"=>"#00D8FF"));
                  array_push($values, array("label" => 'LinkAja', "value" => $pLinkaja,  "value1"=>$linkaja, "color"=>"#DD1B16"));
                  array_push($values, array("label" => 'Tunai', "value" => $pTunai,  "value1"=>$tunai, "color"=>"#11f215"));
                  // array_push($values, array("label" => 'Sakuku', "value" => $pSakuku));
                  array_push($values, array("label" => 'Kartu Kredit', "value" => $pCredit, "value1"=>$credit, "color"=>"#1ca3a1"));
                  array_push($values, array("label" => 'Kartu Debit', "value" => $pDebit,  "value1"=>$debit,"color"=>"#808080"));
                  array_push($values, array("label" => 'QRIS', "value" => $pQris,  "value1"=>$qris, "color"=>"#e9f241"));
                  array_push($values, array("label" => 'Shopee', "value" => $pShopee,  "value1"=>$shopee, "color"=>"#f59342"));
                  
                  foreach ($values as $key => $row) {
                      $value[$key]  = $row['value'];
                      $label[$key] = $row['label'];
                  }
                  
                  $value  = array_column($values, 'value');
                  $label = array_column($values, 'label');
                  
                  array_multisort($value, SORT_DESC, $label, SORT_ASC, $values);
                  $res['paymentMethodPercentage']=$values;
              } else {
                  $res = [];
              }
              // graph payment method percentage
              
              // partner payment method balances
              $mdrTax=0;
              $data = [];
              $totals = [];
              $dateFrom = $_GET['dateFrom'];
              $dateTo = $_GET['dateTo'];
      
              $vals = $cf->getGroupPaymentMethod($id, $dateFrom, $dateTo, null);
              $getMDRTax = mysqli_query($db_conn, "SELECT value FROM settings WHERE id=24");
              while($row=mysqli_fetch_assoc($getMDRTax)){
                $mdrTax = (int)$row['value'];
              }
              $y=0;
              $totalIncome = 0;
              $totalMDR = 0;
              $totalTax = 0;
              $totalValue = 0;
              foreach($vals as $x){
                  $data[$y]=$x;
                  $intType = (int)$x['tipe'];
                  if($intType==1||$intType==3||$intType==4||$intType==10){
                      $data[$y]['mdr']=1.5;
                      $data[$y]['tax']=$mdrTax;
                  }else if($intType==2){
                      $data[$y]['mdr']=2;
                      $data[$y]['tax']=$mdrTax;
                  }else{
                      $data[$y]['mdr']=0;
                      $data[$y]['tax']=0;
                  }
                  
                  $data[$y]['mdr_rupiah']=ceil((int)$data[$y]['value']*$data[$y]['mdr']/100);
                  
                  $data[$y]['tax_rupiah']=ceil((int)$data[$y]['mdr_rupiah']*$data[$y]['tax']/100);
                  
                  $data[$y]['income']= $data[$y]['value']-$data[$y]['mdr_rupiah']-$data[$y]['tax_rupiah'];
                  
                  $totalIncome += (int)$data[$y]['income'];
                  $totalMDR += (int)$data[$y]['mdr_rupiah'];
                  $totalTax += (int)$data[$y]['tax_rupiah'];
                  $totalValue += (int)$data[$y]['value'];
                  $y++;
              }
              $totals['total_income']=$totalIncome;
              $totals['total_mdr']=$totalMDR;
              $totals['total_tax']=$totalTax;
              $totals['total_value']=$totalValue;
              
              $totalIncome = 0;
              $totalMDR = 0;
              $totalTax = 0;
              $totalValue = 0;
              
              $paymentMethodBalances = [];
              $paymentMethodBalances['data'] = $data;
              $paymentMethodBalances['totals'] = $totals;
              $paymentMethodBalances['mdrTax'] = $mdrTax;
              // partner payment method balances end
              
              // partner income daily
              $valuesDaily = array();
      
              $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(charge_ur) AS charge_ur,SUM(point) AS point, SUM(service) AS service, SUM(tax) AS tax,
                  SUM(charge_ewallet) AS charge_ewallet, created_at FROM ( SELECT SUM(transaksi.program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+`transaksi`.charge_ur)*transaksi.tax/100) AS tax,
                  SUM((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-transaksi.program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet,  DATE(transaksi.paid_date) AS created_at FROM transaksi JOIN partner ON partner.id = transaksi.id_partner WHERE id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.paid_date ";
              
              $query .= ") as tmp GROUP BY created_at ";
              $transaksi = mysqli_query($db_conn,$query);
              
              if(mysqli_num_rows($transaksi) > 0) {
                  $j = 0;
                  $valuesDaily[0]['value']=0;
                  while ($row = mysqli_fetch_assoc($transaksi)) {
                      ($valuesDaily[$j]['value'] ?? $valuesDaily[$j]['value'] = 0) ? $valuesDaily[$j]['value'] += ceil($row['total'])-ceil($row['promo'])-ceil($row['program_discount'])-ceil($row['diskon_spesial'])-ceil($row['employee_discount'])-ceil($row['point'])+ceil($row['service'])+ceil($row['tax'])+ceil($row['charge_ur']) : $valuesDaily[$j]['value'] = ceil($row['total'])-ceil($row['promo'])-ceil($row['program_discount'])-ceil($row['diskon_spesial'])-ceil($row['employee_discount'])-ceil($row['point'])+ceil($row['service'])+ceil($row['tax'])+ceil($row['charge_ur']);
                      
                      $valuesDaily[$j]['date'] = date('d-m-Y',strtotime($row['created_at']));
                      $j+=1;
                  }
              } else {
                  $valuesDaily = array();
              }
              // partner income daily end
              
              // partner income monthly
              $year = date("Y");
              $valuesMonthly = array();
              
              $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount ,SUM(total) AS total, SUM(point) AS point,  SUM(charge_ur) AS charge_ur, SUM(service) AS service, SUM(tax) AS tax, 
                  SUM(charge_ewallet) AS charge_ewallet, month FROM ( SELECT SUM(program_discount) AS program_discount, SUM(transaksi.promo) AS promo, SUM(transaksi.diskon_spesial) AS diskon_spesial, SUM(transaksi.employee_discount) AS employee_discount ,SUM(transaksi.total) AS total, SUM(transaksi.point) AS point,  SUM(transaksi.charge_ur) AS charge_ur, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+transaksi.charge_ur)*transaksi.tax/100) AS tax, 
                  SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.tax/100))*transaksi.charge_ewallet/100) AS charge_ewallet, MONTH(transaksi.paid_date) AS month FROM transaksi JOIN partner ON partner.id = transaksi.id_partner WHERE id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status<=2 and transaksi.status>=1 AND YEAR(paid_date)='$year' GROUP BY MONTH(transaksi.paid_date) ";
              
              $dateFrom = $year."-01-01";
              $dateTo = $year."-12-31";
              
              $query .= ") AS tmp GROUP BY month";
              $transaksi = mysqli_query(
                  $db_conn,
                  $query
              );
          
              for ($i = 1; $i <= 12; $i++) {
                  array_push($valuesMonthly, array("month" => $i, "value" => 0));
              }
          
              while ($row = mysqli_fetch_assoc($transaksi)) {
                  $valuesMonthly[$row['month']-1]['value']=ceil($row['total'])-ceil($row['promo'])-ceil($row['program_discount'])-ceil($row['diskon_spesial'])-ceil($row['employee_discount'])-ceil($row['point'])+ceil($row['service'])+ceil($row['tax'])+ceil($row['charge_ur']);
              }
          
              for ($k = 0; $k < 12; $k++) {
                  $monthNum = $valuesMonthly[$k]['month'];
                  $valuesMonthly[$k]['month'] = date('F', mktime(0, 0, 0, $monthNum, 10));
              }
              // partner income monthly end
          
              $partner['paymentMethodPercentage'] = $res;
              $partner['paymentMethodBalances'] = $paymentMethodBalances;
              $partner['partnerIncomeDaily'] = $valuesDaily;
              $partner['partnerIncomeMonthly'] = $valuesMonthly;
              
              if(count($res) > 0) {
                  array_push($array, $partner);
              }
              
          }
          
          $success = 1;
          $status = 200;
          $msg = "Success";
      } else {
          $success = 0;
          $status = 203;
          $msg = "Data not found";
      }
    } 
}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$array]);  

echo $signupJson;