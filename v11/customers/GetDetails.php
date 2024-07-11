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
$point=array();
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
    $master_id = $token->id_master;
    $phone = $_GET['phone'];
    $query = "SELECT first FROM ( SELECT t.jam AS first FROM transaksi t JOIN partner p ON p.id = t.id_partner JOIN master m ON m.id = p.id_master WHERE t.phone='$phone' AND t.deleted_at IS NULL AND m.id='$master_id' ";
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
    while($row=mysqli_fetch_assoc($transaksi)){
        $table_name = explode("_",$row['table_name']);
        $transactions = "transactions_".$table_name[1]."_".$table_name[2];
        $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
        // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            $query .= "UNION ALL " ;
            $query .= "SELECT t.jam AS first FROM `$transactions` t JOIN partner p ON p.id = t.id_partner JOIN master m ON m.id = p.id_master WHERE t.phone='$phone' AND t.deleted_at IS NULL AND m.id='$master_id' ";
            // }
        }
        $query .= " ) AS tmp ORDER BY first ASC LIMIT 1 ";
        $sqlFirstTrx = mysqli_query($db_conn, $query);

        $query = "SELECT last FROM ( SELECT t.jam AS last FROM transaksi t JOIN partner p ON p.id = t.id_partner JOIN master m ON m.id = p.id_master WHERE t.phone='$phone' AND m.id='$master_id' ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT t.jam AS last FROM `$transactions` t JOIN partner p ON p.id = t.id_partner JOIN master m ON m.id = p.id_master WHERE t.phone='$phone' AND t.deleted_at IS NULL AND m.id='$master_id' ";
                // }
            }
        $query .= " ) AS tmp ORDER BY last DESC LIMIT 1 ";
        $sqlLastTrx = mysqli_query($db_conn, $query);

        $query = "SELECT SUM(count) count FROM ( SELECT COUNT(t.id) AS count FROM transaksi t JOIN partner p ON p.id = t.id_partner JOIN master m ON m.id = p.id_master WHERE t.phone='$phone' AND t.deleted_at IS NULL AND m.id='$master_id' ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            $query .= "UNION ALL " ;
            $query .= "SELECT COUNT(t.id) AS count FROM `$transactions` t JOIN partner p ON p.id = t.id_partner JOIN master m ON m.id = p.id_master WHERE t.phone='$phone' AND t.deleted_at IS NULL AND m.id='$master_id' ";
                // }
        }
        $query .= " ) AS tmp ";
        $sqlCountTrx = mysqli_query($db_conn, $query);
        
        $query = "SELECT id, jam, shift_id, no_meja, status, total, id_voucher, tipe_bayar, promo, diskon_spesial, employee_discount,point, queue, takeaway, tax, service, charge_ur, partner_note, partner_name,  payment_method_name FROM( SELECT t.id, t.jam, t.shift_id, t.no_meja, t.status, t.total, t.id_voucher, t.tipe_bayar, t.promo, t.diskon_spesial, t.employee_discount,t.point, t.queue, t.takeaway, t.tax, t.service, t.charge_ur, t.partner_note, p.name AS partner_name, pm.nama AS payment_method_name FROM transaksi t JOIN users u ON t.phone=u.phone JOIN partner p ON p.id=t.id_partner JOIN payment_method pm ON t.tipe_bayar = pm.id WHERE p.id_master='$master_id' AND t.deleted_at IS NULL AND u.phone='$phone' ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            $query .= "UNION ALL " ;
            $query .= "SELECT t.id, t.jam, t.shift_id, t.no_meja, t.status, t.total, t.id_voucher, t.tipe_bayar, t.promo, t.diskon_spesial, t.employee_discount,t.point, t.queue, t.takeaway, t.tax, t.service, t.charge_ur, t.partner_note, p.name AS partner_name, pm.nama AS payment_method_name FROM `$transactions` t JOIN users u ON t.phone=u.phone JOIN partner p ON p.id=t.id_partner JOIN payment_method pm ON t.tipe_bayar = pm.id WHERE p.id_master='$master_id' AND t.deleted_at IS NULL AND u.phone='$phone' ";
                // }
        }
        $query .= " ) AS tmp ORDER BY jam DESC ";
        $sqlTransactionList = mysqli_query($db_conn, $query);
        $query = "SELECT id, masterID, owner_name, SUM(point) point, name, img  FROM ( SELECT m.id, ma.id AS masterID, ma.name AS owner_name, m.point, ma.name, ma.img FROM memberships m JOIN master ma ON ma.id = m.master_id WHERE m.user_phone = '$phone' AND m.master_id='$master_id' ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
            $query .= "UNION ALL " ;
            $query .= "SELECT m.id, ma.id AS masterID, ma.name AS owner_name, m.point, ma.name, ma.img FROM memberships m JOIN master ma ON ma.id = m.master_id WHERE m.user_phone = '$phone' AND m.master_id='$master_id' ";
                // }
        }
        $query .= " ) AS tmp GROUP BY masterID";
        $qP = mysqli_query($db_conn, $query);
        $sqlGetUser =  "SELECT name, phone, email, TglLahir, Gender FROM `users` WHERE phone='$phone' AND deleted_at IS NULL";
        $user = mysqli_query($db_conn,$sqlGetUser);
    if(mysqli_num_rows($user) > 0) {
        $details = mysqli_fetch_all($user, MYSQLI_ASSOC);
        $first = mysqli_fetch_all($sqlFirstTrx, MYSQLI_ASSOC);
        $last = mysqli_fetch_all($sqlLastTrx, MYSQLI_ASSOC);
        $count = mysqli_fetch_all($sqlCountTrx, MYSQLI_ASSOC);
        $transactions = mysqli_fetch_all($sqlTransactionList, MYSQLI_ASSOC);
        $point = mysqli_fetch_all($qP, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "details"=>$details, "first"=>$first, "last"=>$last, "count"=>$count, "transactions"=>$transactions, "point"=>$point]);  
?>