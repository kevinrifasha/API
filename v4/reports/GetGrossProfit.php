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
$res = array();
$resQ = array();
$hpp = 0;
$res['sales']=0;
$res['diskon_spesial']=0;
$res['employee_discount']=0;
$res['program_discount']=0;
$res['promo']=0;
$res['clean_sales']=0;
$res['gross_profit']=0;
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
    
    if($all == "1") {
        $addQuery1 = "p.id_master='$idMaster'";
        $addQuery2 = "JOIN partner p ON p.id = transaksi.id_partner";
        $res = $cf->getSubTotalMaster($idMaster, $dateFrom, $dateTo);
    } else {
        $addQuery1 = "transaksi.id_partner='$id'";
        $addQuery2 = "";
        $res = $cf->getSubTotal($id, $dateFrom, $dateTo);
    }

    $res['hpp']=0;
    $res['gross_profit']=$res['clean_sales'];

    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);
    
    $query =  "SELECT SUM(hpp) hpp FROM ( SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu ". $addQuery2 ." WHERE ". $addQuery1 ." AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4";


    $query .= " ) AS tmp ";
    $hppQ = mysqli_query(
        $db_conn,
        $query
    );

    if (mysqli_num_rows($hppQ) > 0){
        $resQ = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
        $hpp = (double) $resQ[0]['hpp'];
        $res['gross_profit'] = $res['gross_profit']-$hpp;
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =200;
        $msg = "Data Not Found";
    }
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "sales"=>$res['sales'], "special_discount"=>$res['diskon_spesial'], "employee_discount"=>$res['employee_discount'],"promo"=>$res['promo'], "program_discount"=> $res["program_discount"],"net_sales"=>$res['clean_sales'], "cogs"=>$hpp, "gross_profit"=>$res['gross_profit']]);

echo $signupJson;
?>