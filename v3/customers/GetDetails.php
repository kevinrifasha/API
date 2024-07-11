<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
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

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$count=array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $master_id = $_GET['master_id'];
    $phone = $_GET['phone'];
    $sql = mysqli_query($db_conn, "SELECT name, phone, email, TglLahir, Gender FROM users WHERE phone='$phone'");

    $query = "SELECT t.jam AS first FROM transaksi t JOIN partner p ON p.id = t.id_partner JOIN master m ON m.id = p.id_master WHERE t.phone='$phone' AND m.id='$master_id' AND t.deleted_at IS NULL ";
    
    // $queryTrans = "SELECT table_name FROM information_schema.tables
    // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    // $transaksi = mysqli_query($db_conn, $queryTrans);
    // while($row=mysqli_fetch_assoc($transaksi)){
    //         $table_name = explode("_",$row['table_name']);
    //         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
    //         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
    //         $query .= "UNION ALL " ;
    //         $query .= "SELECT t.jam AS first FROM `$transactions` t JOIN partner p ON p.id = t.id_partner JOIN master m ON m.id = p.id_master WHERE t.phone='$phone' AND m.id='$master_id' AND t.deleted_at IS NULL " ;
    // }
    $query .= "ORDER BY first ASC LIMIT 1";
    $sqlFirstTrx = mysqli_query($db_conn, $query);
    
    $query = "SELECT t.jam AS last FROM transaksi t JOIN partner p ON p.id = t.id_partner JOIN master m ON m.id = p.id_master WHERE t.phone='$phone' AND m.id='$master_id' AND t.deleted_at IS NULL ";
    
    // $queryTrans = "SELECT table_name FROM information_schema.tables
    // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    // $transaksi = mysqli_query($db_conn, $queryTrans);
    // while($row=mysqli_fetch_assoc($transaksi)){
    //         $table_name = explode("_",$row['table_name']);
    //         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
    //         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
    //         $query .= "UNION ALL " ;
    //         $query .= "SELECT t.jam AS last FROM `$transactions` t JOIN partner p ON p.id = t.id_partner JOIN master m ON m.id = p.id_master WHERE t.phone='$phone' AND m.id='$master_id' AND t.deleted_at IS NULL " ;
    // }
    $query .= "ORDER BY last DESC LIMIT 1";
    $sqlLastTrx = mysqli_query($db_conn, $query);
    
    $query = "SELECT COUNT(t.id) AS count FROM transaksi t JOIN partner p ON p.id = t.id_partner JOIN master m ON m.id = p.id_master WHERE t.phone='$phone' AND m.id='$master_id' AND t.deleted_at IS NULL ";
    
    // $queryTrans = "SELECT table_name FROM information_schema.tables
    // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    // $transaksi = mysqli_query($db_conn, $queryTrans);
    // while($row=mysqli_fetch_assoc($transaksi)){
    //         $table_name = explode("_",$row['table_name']);
    //         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
    //         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
    //         $query .= "UNION ALL " ;
    //         $query .= "SELECT COUNT(t.id) AS count FROM `$transactions` t JOIN partner p ON p.id = t.id_partner JOIN master m ON m.id = p.id_master WHERE t.phone='$phone' AND m.id='$master_id' AND t.deleted_at IS NULL " ;
    // }
    $sqlCountrTx = mysqli_query($db_conn, $query);

    $query =  "SELECT t.id, t.jam, t.shift_id, t.no_meja, t.status, t.total, t.id_voucher, t.tipe_bayar, t.promo, t.diskon_spesial, t.employee_discount,t.point, t.queue, t.takeaway, t.tax, t.service, t.charge_ur, t.partner_note, p.name AS partner_name, pm.nama AS payment_method_name FROM transaksi t JOIN users u ON t.phone=u.phone JOIN partner p ON p.id=t.id_partner JOIN payment_method pm ON t.tipe_bayar = pm.id WHERE p.id_master='$master_id' AND u.phone='$phone' AND t.deleted_at IS NULL  ";
    // $queryTrans = "SELECT table_name FROM information_schema.tables
    // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    // $transaksi = mysqli_query($db_conn, $queryTrans);
    // while($row=mysqli_fetch_assoc($transaksi)){
    //         $table_name = explode("_",$row['table_name']);
    //         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
    //         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
    //         $query .= "UNION ALL " ;
    //         $query .= "SELECT t.id, t.jam, t.shift_id, t.no_meja, t.status, t.total, t.id_voucher, t.tipe_bayar, t.promo, t.diskon_spesial, t.employee_discount,t.point, t.queue, t.takeaway, t.tax, t.service, t.charge_ur, t.partner_note, p.name AS partner_name, pm.nama AS payment_method_name FROM `$transactions` t JOIN users u ON t.phone=u.phone JOIN partner p ON p.id=t.id_partner JOIN payment_method pm ON t.tipe_bayar = pm.id WHERE p.id_master='$master_id' AND u.phone='$phone' AND t.deleted_at IS NULL ";
    // }
    $query .= "ORDER BY jam DESC";
    $sqlTransactionList = mysqli_query($db_conn, $query);
    
    if(mysqli_num_rows($sql) > 0) {
        $details = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $first = mysqli_fetch_all($sqlFirstTrx, MYSQLI_ASSOC);
        $last = mysqli_fetch_all($sqlLastTrx, MYSQLI_ASSOC);
        $count1 = mysqli_fetch_all($sqlCountrTx, MYSQLI_ASSOC);
        $count[0]['count']=0;
        foreach($count1 as $c){
            $count[0]['count'] += (int) $c['count'];
        }
        $transactions = mysqli_fetch_all($sqlTransactionList, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
    
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "details"=>$details, "first"=>$first, "last"=>$last, "count"=>$count, "transactions"=>$transactions]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
?>