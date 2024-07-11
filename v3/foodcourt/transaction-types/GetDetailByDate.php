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
//         $dineIn = array();
//         $takeaway = array();
//         $preorder = array();
//         $delivery = array();
// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

//     $status = $tokens['status'];
//     $msg = $tokens['msg'];
//     $success = 0;

// }else{
//     $id = $_GET['id'];
//     $dateTo = $_GET['dateTo'];
//     $dateFrom = $_GET['dateFrom'];
//     $dateFromStr = str_replace("-","", $dateFrom);
//     $dateToStr = str_replace("-","", $dateTo);

//     $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty
//     FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
//     WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND
//     menu.id_partner='$id' AND takeaway=0 AND
//     pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND
//     (transaksi.status='1' OR transaksi.status='2' ) AND
//     transaksi.deleted_at IS NULL GROUP BY transaksi.id ";
//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= " UNION ALL " ;
//             $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty
//             FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
//             WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND
//             menu.id_partner='$id' AND takeaway=0 AND
//             pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND
//             (`$transactions`.status='1' OR `$transactions`.status='2' ) AND
//             `$transactions`.deleted_at IS NULL GROUP BY `$transactions`.id  ";
//         }
//     }
//     $query.=") AS tmp";
//     $sqlCountDineIn = mysqli_query($db_conn, $query);

//     $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
//     WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND
//     menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2') GROUP BY transaksi.id ";
//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= "UNION ALL " ;
//             $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
//             WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND
//             menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') GROUP BY `$transactions`.id ";
//         }
//     }
//     $query.=") AS tmp";
//     $sqlCountTakeaway = mysqli_query($db_conn, $query);

//     $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
//     WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND
//     menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND DATE(jam) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.pre_order_id !=0 AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL GROUP BY transaksi.id ";
//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= "UNION ALL " ;
//             $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi= `$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
//             WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND
//             menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND DATE(jam) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.pre_order_id !=0 AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL GROUP BY `$transactions`.id ";
//         }
//     }
//     $query.=") AS tmp";
//     $sqlCountPreorder = mysqli_query($db_conn, $query);

//     $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT d.id) AS qty FROM delivery d JOIN transaksi t ON t.id= d.transaksi_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=t.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND d.deleted_at IS NULL AND DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL GROUP BY t.id ";
//     $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= "UNION ALL " ;
//             $query .= "SELECT COUNT(DISTINCT d.id) AS qty FROM delivery d JOIN `$transactions` t ON t.id= d.transaksi_id JOIN   $detail_transactions ON   $detail_transactions.id_transaksi=t.id JOIN menu ON menu.id=  $detail_transactions.id_menu WHERE menu.id_partner='$id' AND d.deleted_at IS NULL AND DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL GROUP BY t.id ";
//         }
//     }
//     $query.=") AS tmp";
//     $sqlCountDelivery = mysqli_query($db_conn, $query);

//     if(
//         mysqli_num_rows($sqlCountDineIn) > 0 ||
//         mysqli_num_rows($sqlCountTakeaway) > 0 ||
//         mysqli_num_rows($sqlCountPreorder) > 0 ||
//         mysqli_num_rows($sqlCountDelivery) > 0
//         ) {
//         $data1 = mysqli_fetch_all($sqlCountDineIn, MYSQLI_ASSOC);
//         foreach ($data1 as $value) {
//           $dineIn['qty']+=(int) $value['qty'];
//         }
//         $data2 = mysqli_fetch_all($sqlCountTakeaway, MYSQLI_ASSOC);
//         foreach ($data2 as $value) {
//           $takeaway['qty']+=(int) $value['qty'];
//         }
//         $data3 = mysqli_fetch_all($sqlCountPreorder, MYSQLI_ASSOC);
//         foreach ($data3 as $value) {
//           $preorder['qty'] +=(int) $value['qty'];
//         }
//         $data4 = mysqli_fetch_all($sqlCountDelivery, MYSQLI_ASSOC);
//         foreach ($data4 as $value) {
//           $delivery['qty'] = (int) $value['qty'];
//         }
//         $dineIn['qty']-=$delivery['qty'] ;

//         $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
//         SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
//         SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
//         SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
//         SUM(charge_ur) AS charge_ur FROM (
//             SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=0 AND transaksi.pre_order_id=0 AND (transaksi.no_meja IS NOT NULL OR transaksi.no_meja!='') AND (transaksi.status='1' OR transaksi.status='2') AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
//         $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//         while($row=mysqli_fetch_assoc($transaksi)){
//             $table_name = explode("_",$row['table_name']);
//             $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//             $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//             if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//                 $query .= " UNION ALL " ;
//                 $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=0 AND `$transactions`.pre_order_id=0 AND (`$transactions`.no_meja IS NOT NULL OR `$transactions`.no_meja!='') AND (`$transactions`.status='1' OR `$transactions`.status='2') AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
//             }
//         }
//         $query .= ") AS tmp";
//         $trxDineIn = mysqli_query($db_conn, $query);

//         $i=1;
//         $subtotal = 0;
//         $promo = 0;
//         $program_discount = 0;
//         $diskon_spesial = 0;
//         $point = 0;
//         $service = 0;
//         $tax = 0;
//         $charge_ur = 0;

//         while ($row = mysqli_fetch_assoc($trxDineIn)) {
//           $subtotal += (int) $row['total'];
//           $promo += (int) $row['promo'];
//           $program_discount += (int) $row['program_discount'];
//           $diskon_spesial += (int) $row['diskon_spesial'];
//           $point += (int) $row['point'];
//           $tempS = (int) $row['service'];
//           $service += $tempS;
//           $charge_ur += (int) $row['charge_ur'];
//           $tempT = (double) $row['tax'] ;
//           $tax += $tempT;
//           $i++;
//         }

//         $dineIn['subtotal'] = $subtotal;
//         $dineIn['sales'] = $subtotal+$service+$tax+$charge_ur;
//         $dineIn['promo'] = $promo;
//         $dineIn['program_discount'] = $program_discount;
//         $dineIn['diskon_spesial'] = $diskon_spesial;
//         $dineIn['point'] = $point;
//         $dineIn['clean_sales'] = $dineIn['sales']-$promo-$program_discount-$diskon_spesial-$point;
//         $dineIn['service'] = $service;
//         $dineIn['charge_ur'] = $charge_ur;
//         $dineIn['tax'] = $tax;
//         $dineIn['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;

//         $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
//         SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
//         SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
//         SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
//         SUM(charge_ur) AS charge_ur FROM (
//             SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2') AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
//         $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//         while($row=mysqli_fetch_assoc($transaksi)){
//             $table_name = explode("_",$row['table_name']);
//             $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//             $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//             if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//                 $query .= " UNION ALL " ;
//                 $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
//             }
//         }
//         $query .= ") AS tmp";
//         $trxTakeAway = mysqli_query($db_conn, $query);

//         $i=1;
//         $subtotal = 0;
//         $promo = 0;
//         $program_discount = 0;
//         $diskon_spesial = 0;
//         $point = 0;
//         $service = 0;
//         $tax = 0;
//         $charge_ur = 0;

//         while ($row = mysqli_fetch_assoc($trxTakeAway)) {
//             $subtotal += (int) $row['total'];
//             $promo += (int) $row['promo'];
//             $program_discount += (int) $row['program_discount'];
//             $diskon_spesial += (int) $row['diskon_spesial'];
//             $point += (int) $row['point'];
//             $tempS = (int) $row['service'];
//             $service += $tempS;
//             $charge_ur += (int) $row['charge_ur'];
//             $tempT = ( double ) $row['tax'];
//             $tax += $tempT;
//             $i++;
//         }

//         $takeaway['subtotal'] = $subtotal;
//         $takeaway['sales'] = $subtotal+$service+$tax+$charge_ur;
//         $takeaway['promo'] = $promo;
//         $takeaway['program_discount'] = $program_discount;
//         $takeaway['diskon_spesial'] = $diskon_spesial;
//         $takeaway['point'] = $point;
//         $takeaway['clean_sales'] = $takeaway['sales']-$promo-$program_discount-$diskon_spesial-$point;
//         $takeaway['service'] = $service;
//         $takeaway['charge_ur'] = $charge_ur;
//         $takeaway['tax'] = $tax;
//         $takeaway['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;

//         $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
//         SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
//         SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
//         SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
//         SUM(charge_ur) AS charge_ur FROM (
//             SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.pre_order_id=!0 AND (transaksi.status='1' OR transaksi.status='2') AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
//         $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//     while($row=mysqli_fetch_assoc($transaksi)){
//         $table_name = explode("_",$row['table_name']);
//         $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//         $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//         if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//             $query .= "UNION ALL " ;
//             $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.pre_order_id=!0 AND (`$transactions`.status='1' OR `$transactions`.status='2') AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
//         }
//     }
//     $query .= ") AS tmp";
//     $trxPreorder = mysqli_query($db_conn, $query);

//     $i=1;
//     $subtotal = 0;
//     $promo = 0;
//     $program_discount = 0;
//     $diskon_spesial = 0;
//     $point = 0;
//     $service = 0;
//         $tax = 0;
//         $charge_ur = 0;

//         while ($row = mysqli_fetch_assoc($trxPreorder)) {
//           $subtotal += (int) $row['total'];
//           $promo += (int) $row['promo'];
//           $program_discount += (int) $row['program_discount'];
//           $diskon_spesial += (int) $row['diskon_spesial'];
//           $point += (int) $row['point'];
//           $tempS = (int) $row['service'] ;
//           $service += $tempS;
//           $charge_ur += (int) $row['charge_ur'];
//           $tempT =(double) $row['tax'];
//           $tax += $tempT;
//           $i++;
//         }

//         $preorder['subtotal'] = $subtotal;
//         $preorder['sales'] = $subtotal+$service+$tax+$charge_ur;
//         $preorder['promo'] = $promo;
//         $preorder['program_discount'] = $program_discount;
//         $preorder['diskon_spesial'] = $diskon_spesial;
//         $preorder['point'] = $point;
//         $preorder['clean_sales'] = $preorder['sales']-$promo-$program_discount-$diskon_spesial-$point;
//         $preorder['service'] = $service;
//         $preorder['charge_ur'] = $charge_ur;
//         $preorder['tax'] = $tax;
//         $preorder['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;

//         $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
//         SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
//         SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
//         SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
//         SUM(charge_ur) AS charge_ur FROM (
//             SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN delivery ON transaksi.id= delivery.transaksi_id WHERE menu.id_partner='$id' AND delivery.deleted_at IS NULL AND DATE(transaksi.jam) BETWEEN '$dateFrom' AND '$dateTo' AND (transaksi.status='1' OR transaksi.status='2' ) ";
//         $queryTrans = "SELECT table_name FROM information_schema.tables
//     WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
//     $transaksi = mysqli_query($db_conn, $queryTrans);
//         while($row=mysqli_fetch_assoc($transaksi)){
//             $table_name = explode("_",$row['table_name']);
//             $transactions = "transactions_".$table_name[1]."_".$table_name[2];
//             $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
//             if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
//                 $query .= "UNION ALL " ;
//                 $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN delivery ON `$transactions`.id= delivery.transaksi_id WHERE `$transactions`.id_partner='$id' AND delivery.deleted_at IS NULL AND DATE(`$transactions`.jam) BETWEEN '$dateFrom' AND '$dateTo' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) ";
//             }
//         }
//         $query .= ") AS tmp";
//         $trxDelivery = mysqli_query($db_conn, $query);

//         $i=1;
//         $subtotal = 0;
//         $promo = 0;
//         $program_discount = 0;
//         $diskon_spesial = 0;
//         $point = 0;
//         $service = 0;
//         $tax = 0;
//         $charge_ur = 0;

//         while ($row = mysqli_fetch_assoc($trxDelivery)) {
//           $subtotal += (int) $row['total'];
//           $promo += (int) $row['promo'];
//           $program_discount += (int) $row['program_discount'];
//           $diskon_spesial += (int) $row['diskon_spesial'];
//           $point += (int) $row['point'];
//           $tempS = (int) $row['service'];
//           $service += $tempS;
//           $charge_ur += (int) $row['charge_ur'];
//           $tempT = ( double ) $row['tax'];
//           $tax += $tempT;
//           $i++;
//         }

//         $delivery['subtotal'] = $subtotal;
//         $delivery['sales'] = $subtotal+$service+$tax+$charge_ur;
//         $delivery['promo'] = $promo;
//         $delivery['program_discount'] = $program_discount;
//         $delivery['diskon_spesial'] = $diskon_spesial;
//         $delivery['point'] = $point;
//         $delivery['clean_sales'] = $delivery['sales']-$promo-$program_discount-$diskon_spesial-$point;
//         $delivery['service'] = $service;
//         $delivery['charge_ur'] = $charge_ur;
//         $delivery['tax'] = $tax;
//         $delivery['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;

//         $dineIn['subtotal'] -= $subtotal;
//         $dineIn['sales'] -= $subtotal+$service+$tax+$charge_ur;
//         $dineIn['promo'] -= $promo;
//         $dineIn['program_discount'] -= $program_discount;
//         $dineIn['diskon_spesial'] -= $diskon_spesial;
//         $dineIn['point'] -= $point;
//         $dineIn['clean_sales'] -= $dineIn['sales']-$promo-$program_discount-$diskon_spesial-$point;
//         $dineIn['service'] -= $service;
//         $dineIn['charge_ur'] -= $charge_ur;
//         $dineIn['tax'] -= $tax;
//         $dineIn['total'] -= $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;

//         $success = 1;
//         $status = 200;
//         $msg = "Success";
//     }else{
//         $success = 0;
//         $status = 204;
//         $msg = "Data Not Found";
//     }

// }
// if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
//         http_response_code(200);
//     }else{
//         http_response_code($status);
//     }
// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "dineIn"=>$dineIn, "takeaway"=>$takeaway, "preorder"=>$preorder, "delivery"=>$delivery]);

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
        $dineIn = array();
        $takeaway = array();
        $preorder = array();
        $delivery = array();
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $id = $_GET['id'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    $dateFromStr = str_replace("-","", $dateFrom);
    $dateToStr = str_replace("-","", $dateTo);

    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    if($newDateFormat == 1)
    {
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty
        FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND
        menu.id_partner='$id' AND takeaway=0 AND
        pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND
        (transaksi.status='1' OR transaksi.status='2' ) AND
        transaksi.deleted_at IS NULL GROUP BY transaksi.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= " UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty
                FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND
                menu.id_partner='$id' AND takeaway=0 AND
                pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND
                (`$transactions`.status='1' OR `$transactions`.status='2' ) AND
                `$transactions`.deleted_at IS NULL GROUP BY `$transactions`.id  ";
            }
        }
        $query.=") AS tmp";
        $sqlCountDineIn = mysqli_query($db_conn, $query);
    
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND
        menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2') GROUP BY transaksi.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND
                menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') GROUP BY `$transactions`.id ";
            }
        }
        $query.=") AS tmp";
        $sqlCountTakeaway = mysqli_query($db_conn, $query);
    
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND
        menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND jam BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.pre_order_id !=0 AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL GROUP BY transaksi.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi= `$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND
                menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND jam BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.pre_order_id !=0 AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL GROUP BY `$transactions`.id ";
            }
        }
        $query.=") AS tmp";
        $sqlCountPreorder = mysqli_query($db_conn, $query);
    
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT d.id) AS qty FROM delivery d JOIN transaksi t ON t.id= d.transaksi_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=t.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL GROUP BY t.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT d.id) AS qty FROM delivery d JOIN `$transactions` t ON t.id= d.transaksi_id JOIN   $detail_transactions ON   $detail_transactions.id_transaksi=t.id JOIN menu ON menu.id=  $detail_transactions.id_menu WHERE menu.id_partner='$id' AND d.deleted_at IS NULL AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL GROUP BY t.id ";
            }
        }
        $query.=") AS tmp";
        $sqlCountDelivery = mysqli_query($db_conn, $query);
    
        if(
            mysqli_num_rows($sqlCountDineIn) > 0 ||
            mysqli_num_rows($sqlCountTakeaway) > 0 ||
            mysqli_num_rows($sqlCountPreorder) > 0 ||
            mysqli_num_rows($sqlCountDelivery) > 0
            ) {
            $data1 = mysqli_fetch_all($sqlCountDineIn, MYSQLI_ASSOC);
            foreach ($data1 as $value) {
              $dineIn['qty']+=(int) $value['qty'];
            }
            $data2 = mysqli_fetch_all($sqlCountTakeaway, MYSQLI_ASSOC);
            foreach ($data2 as $value) {
              $takeaway['qty']+=(int) $value['qty'];
            }
            $data3 = mysqli_fetch_all($sqlCountPreorder, MYSQLI_ASSOC);
            foreach ($data3 as $value) {
              $preorder['qty'] +=(int) $value['qty'];
            }
            $data4 = mysqli_fetch_all($sqlCountDelivery, MYSQLI_ASSOC);
            foreach ($data4 as $value) {
              $delivery['qty'] = (int) $value['qty'];
            }
            $dineIn['qty']-=$delivery['qty'] ;
    
            $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
            SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
            SUM(charge_ur) AS charge_ur FROM (
                SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=0 AND transaksi.pre_order_id=0 AND (transaksi.no_meja IS NOT NULL OR transaksi.no_meja!='') AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
            $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= " UNION ALL " ;
                    $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=0 AND `$transactions`.pre_order_id=0 AND (`$transactions`.no_meja IS NOT NULL OR `$transactions`.no_meja!='') AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
                }
            }
            $query .= ") AS tmp";
            $trxDineIn = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxDineIn)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = (int) $row['service'];
              $service += $tempS;
              $charge_ur += (int) $row['charge_ur'];
              $tempT = (double) $row['tax'] ;
              $tax += $tempT;
              $i++;
            }
    
            $dineIn['subtotal'] = $subtotal;
            $dineIn['sales'] = $subtotal+$service+$tax+$charge_ur;
            $dineIn['promo'] = $promo;
            $dineIn['program_discount'] = $program_discount;
            $dineIn['diskon_spesial'] = $diskon_spesial;
            $dineIn['point'] = $point;
            $dineIn['clean_sales'] = $dineIn['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $dineIn['service'] = $service;
            $dineIn['charge_ur'] = $charge_ur;
            $dineIn['tax'] = $tax;
            $dineIn['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
            SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
            SUM(charge_ur) AS charge_ur FROM (
                SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
            $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= " UNION ALL " ;
                    $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
                }
            }
            $query .= ") AS tmp";
            $trxTakeAway = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxTakeAway)) {
                $subtotal += (int) $row['total'];
                $promo += (int) $row['promo'];
                $program_discount += (int) $row['program_discount'];
                $diskon_spesial += (int) $row['diskon_spesial'];
                $point += (int) $row['point'];
                $tempS = (int) $row['service'];
                $service += $tempS;
                $charge_ur += (int) $row['charge_ur'];
                $tempT = ( double ) $row['tax'];
                $tax += $tempT;
                $i++;
            }
    
            $takeaway['subtotal'] = $subtotal;
            $takeaway['sales'] = $subtotal+$service+$tax+$charge_ur;
            $takeaway['promo'] = $promo;
            $takeaway['program_discount'] = $program_discount;
            $takeaway['diskon_spesial'] = $diskon_spesial;
            $takeaway['point'] = $point;
            $takeaway['clean_sales'] = $takeaway['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $takeaway['service'] = $service;
            $takeaway['charge_ur'] = $charge_ur;
            $takeaway['tax'] = $tax;
            $takeaway['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
            SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
            SUM(charge_ur) AS charge_ur FROM (
                SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.pre_order_id=!0 AND (transaksi.status='1' OR transaksi.status='2') AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
            $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.pre_order_id=!0 AND (`$transactions`.status='1' OR `$transactions`.status='2') AND `$transactions`.paid_date BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $query .= ") AS tmp";
        $trxPreorder = mysqli_query($db_conn, $query);
    
        $i=1;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $point = 0;
        $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxPreorder)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = (int) $row['service'] ;
              $service += $tempS;
              $charge_ur += (int) $row['charge_ur'];
              $tempT =(double) $row['tax'];
              $tax += $tempT;
              $i++;
            }
    
            $preorder['subtotal'] = $subtotal;
            $preorder['sales'] = $subtotal+$service+$tax+$charge_ur;
            $preorder['promo'] = $promo;
            $preorder['program_discount'] = $program_discount;
            $preorder['diskon_spesial'] = $diskon_spesial;
            $preorder['point'] = $point;
            $preorder['clean_sales'] = $preorder['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $preorder['service'] = $service;
            $preorder['charge_ur'] = $charge_ur;
            $preorder['tax'] = $tax;
            $preorder['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
            SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
            SUM(charge_ur) AS charge_ur FROM (
                SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN delivery ON transaksi.id= delivery.transaksi_id WHERE menu.id_partner='$id' AND delivery.deleted_at IS NULL AND transaksi.jam BETWEEN '$dateFrom' AND '$dateTo' AND (transaksi.status='1' OR transaksi.status='2' ) ";
            $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= "UNION ALL " ;
                    $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN delivery ON `$transactions`.id= delivery.transaksi_id WHERE `$transactions`.id_partner='$id' AND delivery.deleted_at IS NULL AND `$transactions`.jam BETWEEN '$dateFrom' AND '$dateTo' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) ";
                }
            }
            $query .= ") AS tmp";
            $trxDelivery = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxDelivery)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = (int) $row['service'];
              $service += $tempS;
              $charge_ur += (int) $row['charge_ur'];
              $tempT = ( double ) $row['tax'];
              $tax += $tempT;
              $i++;
            }
    
            $delivery['subtotal'] = $subtotal;
            $delivery['sales'] = $subtotal+$service+$tax+$charge_ur;
            $delivery['promo'] = $promo;
            $delivery['program_discount'] = $program_discount;
            $delivery['diskon_spesial'] = $diskon_spesial;
            $delivery['point'] = $point;
            $delivery['clean_sales'] = $delivery['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $delivery['service'] = $service;
            $delivery['charge_ur'] = $charge_ur;
            $delivery['tax'] = $tax;
            $delivery['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            $dineIn['subtotal'] -= $subtotal;
            $dineIn['sales'] -= $subtotal+$service+$tax+$charge_ur;
            $dineIn['promo'] -= $promo;
            $dineIn['program_discount'] -= $program_discount;
            $dineIn['diskon_spesial'] -= $diskon_spesial;
            $dineIn['point'] -= $point;
            $dineIn['clean_sales'] -= $dineIn['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $dineIn['service'] -= $service;
            $dineIn['charge_ur'] -= $charge_ur;
            $dineIn['tax'] -= $tax;
            $dineIn['total'] -= $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            $success = 1;
            $status = 200;
            $msg = "Success";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
        
    }
    else
    {
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty
        FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND
        menu.id_partner='$id' AND takeaway=0 AND
        pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND
        (transaksi.status='1' OR transaksi.status='2' ) AND
        transaksi.deleted_at IS NULL GROUP BY transaksi.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= " UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty
                FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND
                menu.id_partner='$id' AND takeaway=0 AND
                pre_order_id=0 AND (no_meja IS NOT NULL OR no_meja!='') AND
                (`$transactions`.status='1' OR `$transactions`.status='2' ) AND
                `$transactions`.deleted_at IS NULL GROUP BY `$transactions`.id  ";
            }
        }
        $query.=") AS tmp";
        $sqlCountDineIn = mysqli_query($db_conn, $query);
    
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND
        menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2') GROUP BY transaksi.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND
                menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') GROUP BY `$transactions`.id ";
            }
        }
        $query.=") AS tmp";
        $sqlCountTakeaway = mysqli_query($db_conn, $query);
    
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT transaksi.id) AS qty FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu
        WHERE DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND
        menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND DATE(jam) BETWEEN '$dateFrom' AND '$dateTo' AND transaksi.pre_order_id !=0 AND (transaksi.status='1' OR transaksi.status='2' ) AND transaksi.deleted_at IS NULL GROUP BY transaksi.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT `$transactions`.id) AS qty FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi= `$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu
                WHERE DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND
                menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND DATE(jam) BETWEEN '$dateFrom' AND '$dateTo' AND `$transactions`.pre_order_id !=0 AND (`$transactions`.status='1' OR `$transactions`.status='2' ) AND `$transactions`.deleted_at IS NULL GROUP BY `$transactions`.id ";
            }
        }
        $query.=") AS tmp";
        $sqlCountPreorder = mysqli_query($db_conn, $query);
    
        $query =  "SELECT SUM(qty) AS qty FROM ( SELECT COUNT(DISTINCT d.id) AS qty FROM delivery d JOIN transaksi t ON t.id= d.transaksi_id JOIN detail_transaksi ON detail_transaksi.id_transaksi=t.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND d.deleted_at IS NULL AND DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL GROUP BY t.id ";
        $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT COUNT(DISTINCT d.id) AS qty FROM delivery d JOIN `$transactions` t ON t.id= d.transaksi_id JOIN   $detail_transactions ON   $detail_transactions.id_transaksi=t.id JOIN menu ON menu.id=  $detail_transactions.id_menu WHERE menu.id_partner='$id' AND d.deleted_at IS NULL AND DATE(t.jam) BETWEEN '$dateFrom' AND '$dateTo' AND (t.status='1' OR t.status='2') AND t.deleted_at IS NULL GROUP BY t.id ";
            }
        }
        $query.=") AS tmp";
        $sqlCountDelivery = mysqli_query($db_conn, $query);
    
        if(
            mysqli_num_rows($sqlCountDineIn) > 0 ||
            mysqli_num_rows($sqlCountTakeaway) > 0 ||
            mysqli_num_rows($sqlCountPreorder) > 0 ||
            mysqli_num_rows($sqlCountDelivery) > 0
            ) {
            $data1 = mysqli_fetch_all($sqlCountDineIn, MYSQLI_ASSOC);
            foreach ($data1 as $value) {
              $dineIn['qty']+=(int) $value['qty'];
            }
            $data2 = mysqli_fetch_all($sqlCountTakeaway, MYSQLI_ASSOC);
            foreach ($data2 as $value) {
              $takeaway['qty']+=(int) $value['qty'];
            }
            $data3 = mysqli_fetch_all($sqlCountPreorder, MYSQLI_ASSOC);
            foreach ($data3 as $value) {
              $preorder['qty'] +=(int) $value['qty'];
            }
            $data4 = mysqli_fetch_all($sqlCountDelivery, MYSQLI_ASSOC);
            foreach ($data4 as $value) {
              $delivery['qty'] = (int) $value['qty'];
            }
            $dineIn['qty']-=$delivery['qty'] ;
    
            $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
            SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
            SUM(charge_ur) AS charge_ur FROM (
                SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=0 AND transaksi.pre_order_id=0 AND (transaksi.no_meja IS NOT NULL OR transaksi.no_meja!='') AND (transaksi.status='1' OR transaksi.status='2') AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY transaksi.id ";
            $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= " UNION ALL " ;
                    $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=0 AND `$transactions`.pre_order_id=0 AND (`$transactions`.no_meja IS NOT NULL OR `$transactions`.no_meja!='') AND (`$transactions`.status='1' OR `$transactions`.status='2') AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY `$transactions`.id ";
                }
            }
            $query .= ") AS tmp";
            $trxDineIn = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxDineIn)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = (int) $row['service'];
              $service += $tempS;
              $charge_ur += (int) $row['charge_ur'];
              $tempT = (double) $row['tax'] ;
              $tax += $tempT;
              $i++;
            }
    
            $dineIn['subtotal'] = $subtotal;
            $dineIn['sales'] = $subtotal+$service+$tax+$charge_ur;
            $dineIn['promo'] = $promo;
            $dineIn['program_discount'] = $program_discount;
            $dineIn['diskon_spesial'] = $diskon_spesial;
            $dineIn['point'] = $point;
            $dineIn['clean_sales'] = $dineIn['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $dineIn['service'] = $service;
            $dineIn['charge_ur'] = $charge_ur;
            $dineIn['tax'] = $tax;
            $dineIn['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
            SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
            SUM(charge_ur) AS charge_ur FROM (
                SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.takeaway=1 AND transaksi.pre_order_id=0 AND (transaksi.status='1' OR transaksi.status='2') AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
            $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= " UNION ALL " ;
                    $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.takeaway=1 AND `$transactions`.pre_order_id=0 AND (`$transactions`.status='1' OR `$transactions`.status='2') AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
                }
            }
            $query .= ") AS tmp";
            $trxTakeAway = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxTakeAway)) {
                $subtotal += (int) $row['total'];
                $promo += (int) $row['promo'];
                $program_discount += (int) $row['program_discount'];
                $diskon_spesial += (int) $row['diskon_spesial'];
                $point += (int) $row['point'];
                $tempS = (int) $row['service'];
                $service += $tempS;
                $charge_ur += (int) $row['charge_ur'];
                $tempT = ( double ) $row['tax'];
                $tax += $tempT;
                $i++;
            }
    
            $takeaway['subtotal'] = $subtotal;
            $takeaway['sales'] = $subtotal+$service+$tax+$charge_ur;
            $takeaway['promo'] = $promo;
            $takeaway['program_discount'] = $program_discount;
            $takeaway['diskon_spesial'] = $diskon_spesial;
            $takeaway['point'] = $point;
            $takeaway['clean_sales'] = $takeaway['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $takeaway['service'] = $service;
            $takeaway['charge_ur'] = $charge_ur;
            $takeaway['tax'] = $tax;
            $takeaway['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
            SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
            SUM(charge_ur) AS charge_ur FROM (
                SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.pre_order_id=!0 AND (transaksi.status='1' OR transaksi.status='2') AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
            $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
        while($row=mysqli_fetch_assoc($transaksi)){
            $table_name = explode("_",$row['table_name']);
            $transactions = "transactions_".$table_name[1]."_".$table_name[2];
            $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu WHERE menu.id_partner='$id' AND `$transactions`.deleted_at IS NULL AND `$transactions`.pre_order_id=!0 AND (`$transactions`.status='1' OR `$transactions`.status='2') AND DATE(`$transactions`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
            }
        }
        $query .= ") AS tmp";
        $trxPreorder = mysqli_query($db_conn, $query);
    
        $i=1;
        $subtotal = 0;
        $promo = 0;
        $program_discount = 0;
        $diskon_spesial = 0;
        $point = 0;
        $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxPreorder)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = (int) $row['service'] ;
              $service += $tempS;
              $charge_ur += (int) $row['charge_ur'];
              $tempT =(double) $row['tax'];
              $tax += $tempT;
              $i++;
            }
    
            $preorder['subtotal'] = $subtotal;
            $preorder['sales'] = $subtotal+$service+$tax+$charge_ur;
            $preorder['promo'] = $promo;
            $preorder['program_discount'] = $program_discount;
            $preorder['diskon_spesial'] = $diskon_spesial;
            $preorder['point'] = $point;
            $preorder['clean_sales'] = $preorder['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $preorder['service'] = $service;
            $preorder['charge_ur'] = $charge_ur;
            $preorder['tax'] = $tax;
            $preorder['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            $query = "SELECT SUM(program_discount) AS program_discount, SUM(promo) AS promo, SUM(diskon_spesial) AS diskon_spesial, SUM(employee_discount) AS employee_discount, SUM(total) AS total, SUM(point) AS point,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100) AS service,
            SUM((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point+charge_ur)*tax/100) AS tax,
            SUM((total-promo-program_discount-diskon_spesial-employee_discount-point+((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+((((total-promo-program_discount-diskon_spesial-employee_discount-point)*service/100)+total-promo-program_discount-diskon_spesial-employee_discount-point)*tax/100))*charge_ewallet/100) AS charge_ewallet,
            SUM(charge_ur) AS charge_ur FROM (
                SELECT transaksi.program_discount AS program_discount, transaksi.promo AS promo, transaksi.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(detail_transaksi.harga) AS total, transaksi.point AS point, transaksi.service AS service, transaksi.tax AS tax, transaksi.charge_ewallet AS charge_ewallet,  transaksi.charge_ur AS charge_ur FROM transaksi JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN delivery ON transaksi.id= delivery.transaksi_id WHERE menu.id_partner='$id' AND delivery.deleted_at IS NULL AND DATE(transaksi.jam) BETWEEN '$dateFrom' AND '$dateTo' AND (transaksi.status='1' OR transaksi.status='2' ) ";
            $queryTrans = "SELECT table_name FROM information_schema.tables
        WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
        $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= "UNION ALL " ;
                    $query .= "SELECT `$transactions`.program_discount AS program_discount, `$transactions`.promo AS promo, `$transactions`.diskon_spesial AS diskon_spesial, employee_discount AS employee_discount, SUM(`$detail_transactions`.harga) AS total, `$transactions`.point AS point, `$transactions`.service AS service, `$transactions`.tax AS tax, `$transactions`.charge_ewallet AS charge_ewallet, `$transactions`.charge_ur AS charge_ur FROM `$transactions` JOIN `$detail_transactions` ON `$detail_transactions`.id_transaksi=`$transactions`.id JOIN menu ON menu.id=`$detail_transactions`.id_menu JOIN delivery ON `$transactions`.id= delivery.transaksi_id WHERE `$transactions`.id_partner='$id' AND delivery.deleted_at IS NULL AND DATE(`$transactions`.jam) BETWEEN '$dateFrom' AND '$dateTo' AND (`$transactions`.status='1' OR `$transactions`.status='2' ) ";
                }
            }
            $query .= ") AS tmp";
            $trxDelivery = mysqli_query($db_conn, $query);
    
            $i=1;
            $subtotal = 0;
            $promo = 0;
            $program_discount = 0;
            $diskon_spesial = 0;
            $point = 0;
            $service = 0;
            $tax = 0;
            $charge_ur = 0;
    
            while ($row = mysqli_fetch_assoc($trxDelivery)) {
              $subtotal += (int) $row['total'];
              $promo += (int) $row['promo'];
              $program_discount += (int) $row['program_discount'];
              $diskon_spesial += (int) $row['diskon_spesial'];
              $point += (int) $row['point'];
              $tempS = (int) $row['service'];
              $service += $tempS;
              $charge_ur += (int) $row['charge_ur'];
              $tempT = ( double ) $row['tax'];
              $tax += $tempT;
              $i++;
            }
    
            $delivery['subtotal'] = $subtotal;
            $delivery['sales'] = $subtotal+$service+$tax+$charge_ur;
            $delivery['promo'] = $promo;
            $delivery['program_discount'] = $program_discount;
            $delivery['diskon_spesial'] = $diskon_spesial;
            $delivery['point'] = $point;
            $delivery['clean_sales'] = $delivery['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $delivery['service'] = $service;
            $delivery['charge_ur'] = $charge_ur;
            $delivery['tax'] = $tax;
            $delivery['total'] = $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            $dineIn['subtotal'] -= $subtotal;
            $dineIn['sales'] -= $subtotal+$service+$tax+$charge_ur;
            $dineIn['promo'] -= $promo;
            $dineIn['program_discount'] -= $program_discount;
            $dineIn['diskon_spesial'] -= $diskon_spesial;
            $dineIn['point'] -= $point;
            $dineIn['clean_sales'] -= $dineIn['sales']-$promo-$program_discount-$diskon_spesial-$point;
            $dineIn['service'] -= $service;
            $dineIn['charge_ur'] -= $charge_ur;
            $dineIn['tax'] -= $tax;
            $dineIn['total'] -= $subtotal-$promo-$program_discount-$diskon_spesial-$point+$service+$charge_ur+$tax;
    
            $success = 1;
            $status = 200;
            $msg = "Success";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
        
    }


}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "dineIn"=>$dineIn, "takeaway"=>$takeaway, "preorder"=>$preorder, "delivery"=>$delivery]);

?>