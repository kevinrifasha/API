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
    $q = mysqli_query($db_conn, "SELECT id, name, payment_method FROM transaction_groups WHERE status = 0 AND partner_id='$token->id_partner' AND deleted_at IS NULL ORDER BY id DESC");
    
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $i =0;
        foreach($res as $r){
            $find = $r['id'];
            $query = "SELECT `id`, `jam`, `phone`, `id_partner`, `shift_id`, `no_meja`, `status`, `total`, `id_voucher`, `rounding`, `id_voucher_redeemable`, `tipe_bayar`, `promo`, `diskon_spesial`, `point`,  `notes`, `tax`, `service`, `qr_string`, `charge_ur`, `confirm_at`, `partner_note`, `created_at`, `employee_discount`, `program_discount` FROM ( SELECT `id`, `jam`, `phone`, `id_partner`, `shift_id`, `no_meja`, `status`, `total`, `id_voucher`, `id_voucher_redeemable`, `tipe_bayar`, `promo`, `diskon_spesial`, `point`,  `notes`, `tax`, `rounding`, `service`, `qr_string`, `charge_ur`, `confirm_at`, `partner_note`, `created_at`, `employee_discount`, `program_discount` FROM `transaksi` WHERE group_id='$find' AND deleted_at IS NULL ";
                
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
                while($row=mysqli_fetch_assoc($transaksi)){
                    $table_name = explode("_",$row['table_name']);
                    $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                    $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                    // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                        $query .= "UNION ALL " ;
                        $query .=  "SELECT `id`, `jam`, `phone`, `id_partner`, `shift_id`, `no_meja`, `status`, `total`, `id_voucher`, `id_voucher_redeemable`, `tipe_bayar`, `promo`, `diskon_spesial`, `point`,  `notes`, `tax`, `service`, `qr_string`, `charge_ur`, `confirm_at`, `partner_note`, `created_at`, `employee_discount`, `program_discount` FROM `$transactions` WHERE group_id='$find' AND deleted_at IS NULL ";
                    // }
                }
                $query .= " ) AS tmp ";
            $q1 = mysqli_query($db_conn, $query);
            $res[$i]['transactions'] = mysqli_fetch_all($q1, MYSQLI_ASSOC);
            
            $i+=1;
        }
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "transaction_groups"=>$res]);
?>