<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

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

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    
    // created_at, name, phone, id_partner, no_meja, total, id_voucher,
    //     paymentName, promo, notes, tax, service, charge_ur, customer_name, status, diskon_spesial
    $dateFrom = $_GET['dateFrom'];
    $dateUntil = $_GET['dateUntil'];
    $partnerID = $token->id_partner;
    if(isset($_GET['partnerID'])){
        $partnerID = $_GET['partnerID'];
    }
    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateUntil);

    $query = "SELECT id, transaction_id, review, rating, attributes, anonymous, created_at, name, phone, id_partner, no_meja, total, id_voucher, promo, notes, tax, service, charge_ur, customer_name, status, diskon_spesial, employee_discount FROM ( SELECT r.id, r.transaction_id, r.review, r.rating, r.attributes, r.anonymous, r.created_at, u.name, t.phone, t.id_partner, t.no_meja, t.total, t.id_voucher, t.promo, t.notes, t.tax, t.service, t.charge_ur, u.name AS customer_name, t.status, t.diskon_spesial, t.employee_discount FROM reviews r JOIN transaksi t ON r.transaction_id=t.id JOIN users u ON u.phone=t.phone WHERE r.deleted_at IS NULL AND t.id_partner='$partnerID' AND DATE(r.created_at) BETWEEN '$dateFrom' AND '$dateUntil' AND t.deleted_at IS NULL ";
    
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
    while($row=mysqli_fetch_assoc($transaksi)){
        $table_name = explode("_",$row['table_name']);
        $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            $query .= "UNION ALL " ;
            $query .=  "SELECT r.id, r.transaction_id, r.review, r.rating, r.attributes, r.anonymous, r.created_at, u.name, t.phone, t.id_partner, t.no_meja, t.total, t.id_voucher, t.promo, t.notes, t.tax, t.service, t.charge_ur, u.name AS customer_name, t.status, t.diskon_spesial, t.employee_discount FROM reviews r JOIN `$transactions` t ON r.transaction_id=t.id JOIN users u ON u.phone=t.phone WHERE r.deleted_at IS NULL AND t.id_partner='$token->id_partner' AND DATE(r.created_at) BETWEEN '$dateFrom' AND '$dateUntil' AND t.deleted_at IS NULL ";
        }
    }
    $query .= " ) AS tmp ORDER BY id DESC ";
    $sql = mysqli_query($db_conn, $query);
    // $sql = mysqli_query($db_conn, "SELECT id, partner_id, radius, price FROM custom_deliveries WHERE deleted_at IS NULL AND master_id = '$tokenDecoded->masterID' ORDER BY id DESC");
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 200;
        $msg = "Data Not Found";
    }
    
}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "reviews"=>$data]);  

?>