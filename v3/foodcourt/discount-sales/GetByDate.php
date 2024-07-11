<?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: access");
// header("Access-Control-Allow-Methods: GET");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// require_once("../../tokenModels/tokenManager.php"); 
// require_once("../../connection.php");
// require '../../../db_connection.php';
// require  __DIR__ . '/../../../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
// $dotenv->load();

// $headers = array();
//     $rx_http = '/\AHTTP_/';
//     foreach($_SERVER as $key => $val) {
//       if( preg_match($rx_http, $key) ) {
//         $arh_key = preg_replace($rx_http, '', $key);
//         $rx_matches = array();
//         // do some nasty string manipulations to restore the original letter case
//         // this should work in most cases
//         $rx_matches = explode('_', $arh_key);
//         if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
//           foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
//           $arh_key = implode('-', $rx_matches);
//         }
//         $headers[$arh_key] = $val;
//       }
//     }
// $token = '';
    
// foreach ($headers as $header => $value) {
//     if($header=="Authorization" || $header=="AUTHORIZATION"){
//         $token=substr($value,7);
//     }
// }

// $db = connectBase();
// $tokenizer = new TokenManager($db);
// $tokens = $tokenizer->validate($token);
// $tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
// $value = array();
// $success=0;
// $msg = 'Failed';
// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
//     $status = $tokens['status'];
//     $msg = $tokens['msg']; 
//     $success = 0;
    
// }else{
//     $arr = [];
//     $i=3;
//     $totalVoucher = 0;
//     $id = $_GET['id'];
//     $dateTo = $_GET['dateTo'];
//     $dateFrom = $_GET['dateFrom'];
    
//     $query = "SELECT SUM(diskon_spesial) AS special_discount, SUM(employee_discount) AS employee_discount, SUM(program_discount) AS program_discount 
//     FROM ( SELECT special_discount, employee_discount, program_discount 
//     FROM transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.transaksi_id JOIN menu ON menu.id=detail_transaksi.id_menu
//     WHERE menu='$id' AND (status=1 OR status=2 ) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
//     $dateFromStr = str_replace("-","", $dateFrom);
//     $dateToStr = str_replace("-","", $dateTo);
//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= "UNION ALL " ;
//             $query .= "SELECT special_discount, employee_discount, program_discount 
//             FROM `$transactions` JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.transaksi_id JOIN menu ON menu.id=`$detail_transactions`.id_menu
//             WHERE menu.id_partner='$id' AND (status=1 OR status=2 ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id  ";
//         }
//     }
//     $query .= ") AS tmp " ;
//     $sqlGetSpecialDiscount = mysqli_query($db_conn, $query);
//     while($row = mysqli_fetch_assoc($sqlGetSpecialDiscount)){
//         $arr[0]['name']="Diskon Pelanggan Spesial";
//         $arr[0]['sales']=$row['special_discount']==null?0:(int)$row['special_discount'];
//         $arr[1]['name']="Diskon Karyawan";
//         $arr[1]['sales']=$row['employee_discount']==null?0:(int)$row['employee_discount'];
//         $arr[2]['name']="Diskon Program";
//         $arr[2]['sales']=$row['program_discount']==null?0:(int)$row['program_discount'];
//         $totalVoucher += $arr[0]['sales'];
//         $totalVoucher += $arr[1]['sales'];
//         $totalVoucher += $arr[2]['sales'];
//     }
    
//     $sqlGetVoucherName = mysqli_query($db_conn, "SELECT id, code, title FROM voucher WHERE master_id='$tokenDecoded->masterID'");
//     while($row1 = mysqli_fetch_assoc($sqlGetVoucherName)){
//         $name = $row1['code'];
//         $query =  "SELECT SUM(sales) sales FROM( SELECT SUM(promo) AS sales from transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.transaksi_id JOIN menu ON menu.id=detail_transaksi.id_menu
//         WHERE transaksi.deleted_at IS NULL AND id_voucher='$name' AND menu.id_partner='$id' AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
        
//         $queryTrans = "SELECT table_name FROM information_schema.tables
//         WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//         $transaksi = mysqli_query($db_conn, $queryTrans);
//         while($row=mysqli_fetch_assoc($transaksi)){
//             $table_name = explode("_",$row['table_name']);
//             $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//             $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//             if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//                 $query .= "UNION ALL " ;
//                 $query .= "SELECT SUM(promo) AS sales from `$transactions` JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.transaksi_id JOIN menu ON menu.id=`$detail_transactions`.id_menu
//                 WHERE `$transactions`.deleted_at IS NULL AND id_voucher='$name' AND menu.id_partner='$id' AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
//             }
//         }
//         $query .= " ) AS tmp ";
//         $sqlGetTransaction = mysqli_query($db_conn, $query);
//         while($row2 = mysqli_fetch_assoc($sqlGetTransaction)){
//             if($row2['sales']==null || $row2['sales']==0){
//                 unset($arr[$i]);
//                 $i--;
//             }else{
//                 $arr[$i]['name']=$row1['title'];
//                 $arr[$i]['sales']=(int)$row2['sales'];
//                 $arr[$i]['qty']=(int)$row2['qty'];
//                 $totalVoucher += $arr[$i]['sales'];
//                 $totalQty += $arr[$i]['qty'];
//             }
//         }
//         $i++;
//     }
//     $sqlGetVoucherName = mysqli_query($db_conn, "SELECT id, code, title FROM redeemable_voucher WHERE master_id='$tokenDecoded->masterID'");
//     while($row1 = mysqli_fetch_assoc($sqlGetVoucherName)){
//         $name = $row1['code'];
//         $query =  "SELECT SUM(sales) sales FROM( SELECT SUM(promo) AS sales from transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.transaksi_id JOIN menu ON menu.id=detail_transaksi.id_menu
//         WHERE transaksi.deleted_at IS NULL AND id_voucher_redeemable='$name' AND menu.id_partner='$id' AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
        
//         $queryTrans = "SELECT table_name FROM information_schema.tables
//         WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//         $transaksi = mysqli_query($db_conn, $queryTrans);
//         while($row=mysqli_fetch_assoc($transaksi)){
//             $table_name = explode("_",$row['table_name']);
//             $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//             $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//             if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//                 $query .= "UNION ALL " ;
//                 $query .= "SELECT SUM(promo) AS sales from `$transactions` JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.transaksi_id JOIN menu ON menu.id=`$detail_transactions`.id_menu
//                 WHERE `$transactions`.deleted_at IS NULL AND id_voucher_redeemable='$name' AND menu.id_partner='$id' AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id  ";
//             }
//         }
//         $query .= " ) AS tmp ";
//         $sqlGetTransaction = mysqli_query($db_conn, $query);
//         while($row2 = mysqli_fetch_assoc($sqlGetTransaction)){
//             if($row2['sales']==null || $row2['sales']==0){
//                 unset($arr[$i]);
//                 $i--;
//             }else{
//                 $arr[$i]['name']=$row1['title'];
//                 $arr[$i]['sales']=(int)$row2['sales'];
//                 $arr[$i]['qty']=(int)$row2['qty'];
//                 $totalVoucher += $arr[$i]['sales'];
//                 $totalQty += $arr[$i]['qty'];
//             }
//         }
//         $i++;
//     }
//     $sqlGetVoucherName = mysqli_query($db_conn, "SELECT id, code, title FROM membership_voucher WHERE master_id='$tokenDecoded->masterID'");
//     while($row1 = mysqli_fetch_assoc($sqlGetVoucherName)){
//         $name = $row1['code'];
//         $query =  "SELECT SUM(sales) sales FROM( SELECT SUM(promo) AS sales from transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.transaksi_id JOIN menu ON menu.id=detail_transaksi.id_menu
//         WHERE transaksi.deleted_at IS NULL AND id_voucher_redeemable='$name' AND menu.id_partner='$id' AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
        
//         $queryTrans = "SELECT table_name FROM information_schema.tables
//         WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//         $transaksi = mysqli_query($db_conn, $queryTrans);
//         while($row=mysqli_fetch_assoc($transaksi)){
//             $table_name = explode("_",$row['table_name']);
//             $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//             $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//             if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//                 $query .= "UNION ALL " ;
//                 $query .= "SELECT SUM(promo) AS sales from `$transactions`  JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.transaksi_id JOIN menu ON menu.id=`$detail_transactions`.id_menu
//                 WHERE `$transactions`.deleted_at IS NULL AND id_voucher_redeemable='$name' AND menu.id_partner='$id' AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
//             }
//         }
//         $query .= " ) AS tmp ";
//         $sqlGetTransaction = mysqli_query($db_conn, $query);
//         while($row2 = mysqli_fetch_assoc($sqlGetTransaction)){
//             if($row2['sales']==null || $row2['sales']==0){
//                 unset($arr[$i]);
//                 $i--;
//             }else{
//                 $arr[$i]['name']=$row1['title'];
//                 $arr[$i]['sales']=(int)$row2['sales'];
//                 $arr[$i]['qty']=(int)$row2['qty'];
//                 $totalVoucher += $arr[$i]['sales'];
//                 $totalQty += $arr[$i]['qty'];
//             }
//         }
//         $i++;
//     }
    
//         $success = 1;
//         $status = 200;
//         $msg = "Success";
// }
// if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
//         http_response_code(200);
//     }else{
//         http_response_code($status);
//     }
// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "discount"=>$arr, "total"=>$totalVoucher]);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../tokenModels/tokenManager.php"); 
require_once("../../connection.php");
require '../../../db_connection.php';
require  __DIR__ . '/../../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
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
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;

}else{
    $arr = [];
    $i=3;
    $totalVoucher = 0;
    $id = $_GET['id'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    
    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    if($newDateFormat == 1)
    {
        $query = "SELECT SUM(diskon_spesial) AS special_discount, SUM(employee_discount) AS employee_discount, SUM(program_discount) AS program_discount 
        FROM ( SELECT special_discount, employee_discount, program_discount 
        FROM transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.transaksi_id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE menu='$id' AND (status=1 OR status=2 ) AND transaksi.deleted_at IS NULL AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
        $dateFromStr = str_replace("-","", $dateFrom);
        $dateToStr = str_replace("-","", $dateTo);
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT special_discount, employee_discount, program_discount 
                FROM `$transactions` JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.transaksi_id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE menu.id_partner='$id' AND (status=1 OR status=2 ) AND `$transactions`.deleted_at IS NULL AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id  ";
            }
        }
        $query .= ") AS tmp " ;
        $sqlGetSpecialDiscount = mysqli_query($db_conn, $query);
        while($row = mysqli_fetch_assoc($sqlGetSpecialDiscount)){
            $arr[0]['name']="Diskon Pelanggan Spesial";
            $arr[0]['sales']=$row['special_discount']==null?0:(int)$row['special_discount'];
            $arr[1]['name']="Diskon Karyawan";
            $arr[1]['sales']=$row['employee_discount']==null?0:(int)$row['employee_discount'];
            $arr[2]['name']="Diskon Program";
            $arr[2]['sales']=$row['program_discount']==null?0:(int)$row['program_discount'];
            $totalVoucher += $arr[0]['sales'];
            $totalVoucher += $arr[1]['sales'];
            $totalVoucher += $arr[2]['sales'];
        }
        
        $sqlGetVoucherName = mysqli_query($db_conn, "SELECT id, code, title FROM voucher WHERE master_id='$tokenDecoded->masterID'");
        while($row1 = mysqli_fetch_assoc($sqlGetVoucherName)){
            $name = $row1['code'];
            $query =  "SELECT SUM(sales) sales FROM( SELECT SUM(promo) AS sales from transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.transaksi_id JOIN menu ON menu.id=detail_transaksi.id_menu
            WHERE transaksi.deleted_at IS NULL AND id_voucher='$name' AND menu.id_partner='$id' AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
            
            $queryTrans = "SELECT table_name FROM information_schema.tables
            WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
            $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= "UNION ALL " ;
                    $query .= "SELECT SUM(promo) AS sales from `$transactions` JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.transaksi_id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                    WHERE `$transactions`.deleted_at IS NULL AND id_voucher='$name' AND menu.id_partner='$id' AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
                }
            }
            $query .= " ) AS tmp ";
            $sqlGetTransaction = mysqli_query($db_conn, $query);
            while($row2 = mysqli_fetch_assoc($sqlGetTransaction)){
                if($row2['sales']==null || $row2['sales']==0){
                    unset($arr[$i]);
                    $i--;
                }else{
                    $arr[$i]['name']=$row1['title'];
                    $arr[$i]['sales']=(int)$row2['sales'];
                    $arr[$i]['qty']=(int)$row2['qty'];
                    $totalVoucher += $arr[$i]['sales'];
                    $totalQty += $arr[$i]['qty'];
                }
            }
            $i++;
        }
        $sqlGetVoucherName = mysqli_query($db_conn, "SELECT id, code, title FROM redeemable_voucher WHERE master_id='$tokenDecoded->masterID'");
        while($row1 = mysqli_fetch_assoc($sqlGetVoucherName)){
            $name = $row1['code'];
            $query =  "SELECT SUM(sales) sales FROM( SELECT SUM(promo) AS sales from transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.transaksi_id JOIN menu ON menu.id=detail_transaksi.id_menu
            WHERE transaksi.deleted_at IS NULL AND id_voucher_redeemable='$name' AND menu.id_partner='$id' AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
            
            $queryTrans = "SELECT table_name FROM information_schema.tables
            WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
            $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= "UNION ALL " ;
                    $query .= "SELECT SUM(promo) AS sales from `$transactions` JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.transaksi_id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                    WHERE `$transactions`.deleted_at IS NULL AND id_voucher_redeemable='$name' AND menu.id_partner='$id' AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id  ";
                }
            }
            $query .= " ) AS tmp ";
            $sqlGetTransaction = mysqli_query($db_conn, $query);
            while($row2 = mysqli_fetch_assoc($sqlGetTransaction)){
                if($row2['sales']==null || $row2['sales']==0){
                    unset($arr[$i]);
                    $i--;
                }else{
                    $arr[$i]['name']=$row1['title'];
                    $arr[$i]['sales']=(int)$row2['sales'];
                    $arr[$i]['qty']=(int)$row2['qty'];
                    $totalVoucher += $arr[$i]['sales'];
                    $totalQty += $arr[$i]['qty'];
                }
            }
            $i++;
        }
        $sqlGetVoucherName = mysqli_query($db_conn, "SELECT id, code, title FROM membership_voucher WHERE master_id='$tokenDecoded->masterID'");
        while($row1 = mysqli_fetch_assoc($sqlGetVoucherName)){
            $name = $row1['code'];
            $query =  "SELECT SUM(sales) sales FROM( SELECT SUM(promo) AS sales from transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.transaksi_id JOIN menu ON menu.id=detail_transaksi.id_menu
            WHERE transaksi.deleted_at IS NULL AND id_voucher_redeemable='$name' AND menu.id_partner='$id' AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
            
            $queryTrans = "SELECT table_name FROM information_schema.tables
            WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
            $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= "UNION ALL " ;
                    $query .= "SELECT SUM(promo) AS sales from `$transactions`  JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.transaksi_id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                    WHERE `$transactions`.deleted_at IS NULL AND id_voucher_redeemable='$name' AND menu.id_partner='$id' AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
                }
            }
            $query .= " ) AS tmp ";
            $sqlGetTransaction = mysqli_query($db_conn, $query);
            while($row2 = mysqli_fetch_assoc($sqlGetTransaction)){
                if($row2['sales']==null || $row2['sales']==0){
                    unset($arr[$i]);
                    $i--;
                }else{
                    $arr[$i]['name']=$row1['title'];
                    $arr[$i]['sales']=(int)$row2['sales'];
                    $arr[$i]['qty']=(int)$row2['qty'];
                    $totalVoucher += $arr[$i]['sales'];
                    $totalQty += $arr[$i]['qty'];
                }
            }
            $i++;
        }
        
            $success = 1;
            $status = 200;
            $msg = "Success";
    }
    else
    {
        $query = "SELECT SUM(diskon_spesial) AS special_discount, SUM(employee_discount) AS employee_discount, SUM(program_discount) AS program_discount 
        FROM ( SELECT special_discount, employee_discount, program_discount 
        FROM transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.transaksi_id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE menu='$id' AND (status=1 OR status=2 ) AND transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
        $dateFromStr = str_replace("-","", $dateFrom);
        $dateToStr = str_replace("-","", $dateTo);
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT special_discount, employee_discount, program_discount 
                FROM `$transactions` JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.transaksi_id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE menu.id_partner='$id' AND (status=1 OR status=2 ) AND `$transactions`.deleted_at IS NULL AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id  ";
            }
        }
        $query .= ") AS tmp " ;
        $sqlGetSpecialDiscount = mysqli_query($db_conn, $query);
        while($row = mysqli_fetch_assoc($sqlGetSpecialDiscount)){
            $arr[0]['name']="Diskon Pelanggan Spesial";
            $arr[0]['sales']=$row['special_discount']==null?0:(int)$row['special_discount'];
            $arr[1]['name']="Diskon Karyawan";
            $arr[1]['sales']=$row['employee_discount']==null?0:(int)$row['employee_discount'];
            $arr[2]['name']="Diskon Program";
            $arr[2]['sales']=$row['program_discount']==null?0:(int)$row['program_discount'];
            $totalVoucher += $arr[0]['sales'];
            $totalVoucher += $arr[1]['sales'];
            $totalVoucher += $arr[2]['sales'];
        }
        
        $sqlGetVoucherName = mysqli_query($db_conn, "SELECT id, code, title FROM voucher WHERE master_id='$tokenDecoded->masterID'");
        while($row1 = mysqli_fetch_assoc($sqlGetVoucherName)){
            $name = $row1['code'];
            $query =  "SELECT SUM(sales) sales FROM( SELECT SUM(promo) AS sales from transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.transaksi_id JOIN menu ON menu.id=detail_transaksi.id_menu
            WHERE transaksi.deleted_at IS NULL AND id_voucher='$name' AND menu.id_partner='$id' AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
            
            $queryTrans = "SELECT table_name FROM information_schema.tables
            WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
            $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= "UNION ALL " ;
                    $query .= "SELECT SUM(promo) AS sales from `$transactions` JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.transaksi_id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                    WHERE `$transactions`.deleted_at IS NULL AND id_voucher='$name' AND menu.id_partner='$id' AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
                }
            }
            $query .= " ) AS tmp ";
            $sqlGetTransaction = mysqli_query($db_conn, $query);
            while($row2 = mysqli_fetch_assoc($sqlGetTransaction)){
                if($row2['sales']==null || $row2['sales']==0){
                    unset($arr[$i]);
                    $i--;
                }else{
                    $arr[$i]['name']=$row1['title'];
                    $arr[$i]['sales']=(int)$row2['sales'];
                    $arr[$i]['qty']=(int)$row2['qty'];
                    $totalVoucher += $arr[$i]['sales'];
                    $totalQty += $arr[$i]['qty'];
                }
            }
            $i++;
        }
        $sqlGetVoucherName = mysqli_query($db_conn, "SELECT id, code, title FROM redeemable_voucher WHERE master_id='$tokenDecoded->masterID'");
        while($row1 = mysqli_fetch_assoc($sqlGetVoucherName)){
            $name = $row1['code'];
            $query =  "SELECT SUM(sales) sales FROM( SELECT SUM(promo) AS sales from transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.transaksi_id JOIN menu ON menu.id=detail_transaksi.id_menu
            WHERE transaksi.deleted_at IS NULL AND id_voucher_redeemable='$name' AND menu.id_partner='$id' AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
            
            $queryTrans = "SELECT table_name FROM information_schema.tables
            WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
            $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= "UNION ALL " ;
                    $query .= "SELECT SUM(promo) AS sales from `$transactions` JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.transaksi_id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                    WHERE `$transactions`.deleted_at IS NULL AND id_voucher_redeemable='$name' AND menu.id_partner='$id' AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id  ";
                }
            }
            $query .= " ) AS tmp ";
            $sqlGetTransaction = mysqli_query($db_conn, $query);
            while($row2 = mysqli_fetch_assoc($sqlGetTransaction)){
                if($row2['sales']==null || $row2['sales']==0){
                    unset($arr[$i]);
                    $i--;
                }else{
                    $arr[$i]['name']=$row1['title'];
                    $arr[$i]['sales']=(int)$row2['sales'];
                    $arr[$i]['qty']=(int)$row2['qty'];
                    $totalVoucher += $arr[$i]['sales'];
                    $totalQty += $arr[$i]['qty'];
                }
            }
            $i++;
        }
        $sqlGetVoucherName = mysqli_query($db_conn, "SELECT id, code, title FROM membership_voucher WHERE master_id='$tokenDecoded->masterID'");
        while($row1 = mysqli_fetch_assoc($sqlGetVoucherName)){
            $name = $row1['code'];
            $query =  "SELECT SUM(sales) sales FROM( SELECT SUM(promo) AS sales from transaksi JOIN detail_transaksi ON transaksi.id=detail_transaksi.transaksi_id JOIN menu ON menu.id=detail_transaksi.id_menu
            WHERE transaksi.deleted_at IS NULL AND id_voucher_redeemable='$name' AND menu.id_partner='$id' AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
            
            $queryTrans = "SELECT table_name FROM information_schema.tables
            WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
            $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= "UNION ALL " ;
                    $query .= "SELECT SUM(promo) AS sales from `$transactions`  JOIN `$detail_transactions` ON `$transactions`.id=`$detail_transactions`.transaksi_id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                    WHERE `$transactions`.deleted_at IS NULL AND id_voucher_redeemable='$name' AND menu.id_partner='$id' AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
                }
            }
            $query .= " ) AS tmp ";
            $sqlGetTransaction = mysqli_query($db_conn, $query);
            while($row2 = mysqli_fetch_assoc($sqlGetTransaction)){
                if($row2['sales']==null || $row2['sales']==0){
                    unset($arr[$i]);
                    $i--;
                }else{
                    $arr[$i]['name']=$row1['title'];
                    $arr[$i]['sales']=(int)$row2['sales'];
                    $arr[$i]['qty']=(int)$row2['qty'];
                    $totalVoucher += $arr[$i]['sales'];
                    $totalQty += $arr[$i]['qty'];
                }
            }
            $i++;
        }
        
            $success = 1;
            $status = 200;
            $msg = "Success";
    }

}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "discount"=>$arr, "total"=>$totalVoucher]);  




