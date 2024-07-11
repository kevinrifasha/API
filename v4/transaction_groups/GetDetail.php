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
    if(isset($_GET['groupID']) && !empty($_GET['groupID'])){
        
        $groupID = $_GET['groupID'];
        $q = mysqli_query($db_conn, "SELECT id, name FROM transaction_groups WHERE status = 0 AND id='$groupID' AND deleted_at IS NULL ORDER BY id DESC");
        
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $i =0;
            foreach($res as $r){
                $find = $r['id'];
                $query = "SELECT `id`, `jam`, `phone`, `id_partner`, `shift_id`, `no_meja`, `status`, `total`, `id_voucher`, `id_voucher_redeemable`, `tipe_bayar`, `promo`, `diskon_spesial`, `point`,  `notes`, `tax`, `service`, `qr_string`, `charge_ur`, `confirm_at`, `partner_note`, `created_at`, `employee_discount`, `program_discount` FROM ( SELECT `id`, `jam`, `phone`, `id_partner`, `shift_id`, `no_meja`, `status`, `total`, `id_voucher`, `id_voucher_redeemable`, `tipe_bayar`, `promo`, `diskon_spesial`, `point`,  `notes`, `tax`, `service`, `qr_string`, `charge_ur`, `confirm_at`, `partner_note`, `created_at`, `employee_discount`, `program_discount` FROM `transaksi` WHERE group_id='$find' AND deleted_at IS NULL ";
                    
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
                
                $j=0;
                foreach($res[$i]['transactions'] as $re){
                    $trdID = $re['id'];
                    
                    $query = "SELECT id, id_menu, harga_satuan, qty, notes, harga, status, nama, variant, cName, category_id FROM ( SELECT dt.id, dt.id_menu, dt.harga_satuan, dt.qty, dt.notes, dt.harga, dt.status, m.nama, dt.variant, c.name AS cName, c.id AS category_id FROM detail_transaksi dt JOIN menu m ON dt.id_menu=m.id JOIN categories c ON m.id_category=c.id WHERE dt.id_transaksi='$trdID' ";
                    
    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
                    while($row=mysqli_fetch_assoc($transaksi)){
                        $table_name = explode("_",$row['table_name']);
                        $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                        $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                        // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                            $query .= "UNION ALL " ;
                            $query .=  "SELECT dt.id, dt.id_menu, dt.harga_satuan, dt.qty, dt.notes, dt.harga, dt.status, m.nama, dt.variant, c.name AS cName, c.id AS category_id FROM `$detail_transactions` dt JOIN menu m ON dt.id_menu=m.id JOIN categories c ON m.id_category=c.id WHERE dt.id_transaksi='$trdID' ";
                        // }
                    }
                    $query .= " ) AS tmp ";
                    $q = mysqli_query($db_conn, $query);
        
                    $data = array();
                    if (mysqli_num_rows($q) > 0) {
            
                        $resD = mysqli_fetch_all($q, MYSQLI_ASSOC);
                        foreach ($resD as $value) {
                            $temp = $value;
                            if($value['variant'] != null) {
                                $variant = $value['variant'];
                                $variant =  substr($variant,11);
                                $variant = substr_replace($variant ,"",-1);
                                $variant = str_replace("'",'"',$variant);
                                $temp['variant'] = json_decode($variant);
                            }else{
                                $temp['variant'] = [];
                            }
                            array_push($data, $temp);
                        }
                    }
                    $res[$i]['transactions'][$j]['details']=$data;
                    $j+=1;
                }
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
    }else{
        $success =0;
        $status =204;
        $msg = "Missing Required Fields";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "transaction_groups"=>$res]);
?>
