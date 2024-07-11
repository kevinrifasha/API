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
$idMaster = $token->id_master;

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    if(isset($_GET['from'])&&!empty($_GET['from']) && isset($_GET['to'])&&!empty($_GET['to'])){
        $id = $token->id_partner;
        
        $subtotal = 0;
        $service = 0;
        $tax = 0;
        $grandTotal=0;
        $from = $_GET['from'];
        $to = $_GET['to'];
        $type = $_GET['type'];
        
        $data = [];
    
        $type_in_query = "DATE(jam)";
        $type_in_query_order = "jam";
        if($type && $type == "paid_date"){
            $type_in_query = "DATE(paid_date)";
            $type_in_query_order = "paid_date";
        } else if($type && $type == "order_date"){
            $type_in_query = "DATE(jam)"; 
            $type_in_query_order = "jam";
        }

    
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $id = $partner['partner_id'];
                $result = array();
                
                $addQuery1 = "t.id_partner='$id'";
                $addQuery2 = "";
                
                $dateFromStr = str_replace("-","", $from);
                $dateToStr = str_replace("-","", $to);
        
                $query = "SELECT transaction_is_pos, id, jam, phone, no_meja, status, total, id_voucher, id_voucher_redeemable, tipe_bayar, promo, diskon_spesial, employee_discount,point, queue, takeaway, notes, tax, service, charge_ur, payment_method, uname, is_pos , program_discount, customer_email,is_helper, surcharge_id, paid_date, employee_discount_percent FROM ( SELECT t.is_pos as transaction_is_pos, t.id, t.jam, t.phone, t.paid_date, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.employee_discount,t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, pm.nama as payment_method, case when u.name is null or t.is_helper = 1 or t.is_pos = 1 then t.customer_name else u.name end AS uname, case when u.name is null then 1 else 0 end AS is_pos, t.program_discount, t.customer_email,t.is_helper, t.surcharge_id, t.employee_discount_percent FROM transaksi t JOIN payment_method pm ON t.tipe_bayar = pm.id LEFT JOIN users u ON u.phone=t.phone AND u.deleted_at IS NULL ". $addQuery2 ."
                WHERE ". $addQuery1 ." AND t.deleted_at IS NULL AND ((t.id LIKE '%TA/%' AND t.status>1) OR (t.id LIKE '%DL/%' AND t.status>1) OR (t.id NOT LIKE '%TA/%' AND t.id NOT LIKE '%DL/%')) AND " . $type_in_query .  " BETWEEN '$from' AND '$to' ) AS tmp ORDER BY " . $type_in_query_order . " DESC ";
                $q = mysqli_query($db_conn, $query);
        
                if (mysqli_num_rows($q) > 0) {
                    $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                    $index = 0;
                    foreach ($res as $f) {
                        $find =  $f['id'];
                        $result[$index]['delivery_fee'] =  '0';
                        $result[$index] =  $f;
                        $subtotal = (int) $f['total']-(int)$f['employee_discount']-(int)$f['program_discount']-(int)$f['promo']-(int)$f['diskon_spesial'];
                        $service = ceil($subtotal*(int)$f['service']/100);
                        $tax = ceil(($subtotal+ $service + (int)$f['charge_ur'])*(int)$f['tax']/100);
                        $grandTotal = $subtotal+$service+$tax+ (int)$f['charge_ur'];
                        $result[$index]['sales']=$grandTotal;
                        $result[$index]['hpp']=0;
        
                        $query = "SELECT SUM(hpp) AS hpp FROM ( SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id='$find' AND detail_transaksi.deleted_at IS NULL ";
                        $query .= " ) AS tmp ";
                        $hppQ = mysqli_query(
                            $db_conn,
                            $query
                        );
        
                        if (mysqli_num_rows($hppQ) > 0) {
                            $resQ = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
                            $result[$index]['hpp']=(double)$resQ[0]['hpp'];
                        }
                        $result[$index]['gross_profit'] = $result[$index]['sales'] -$result[$index]['hpp'];
        
                        $qD = mysqli_query($db_conn, "SELECT ongkir, rate_id, user_address_id, delivery_detail, is_insurance FROM `delivery` WHERE transaksi_id='$find'");
                        if (mysqli_num_rows($qD) > 0) {
                            $resDel = mysqli_fetch_all($qD, MYSQLI_ASSOC);
                            $result[$index]['delivery_fee'] =  $resDel[0]['ongkir'];
                        }
        
                        $is_program=0;
                        $query = "SELECT is_program FROM ( SELECT is_program FROM `detail_transaksi` WHERE id_transaksi='$find' AND deleted_at IS NULL ";
                        $query .= " ) AS tmp ORDER BY is_program DESC LIMIT 1 ";
                        $isP = mysqli_query($db_conn, $query);
                        if(mysqli_num_rows($isP)>0) {
                            $deliv = mysqli_fetch_all($isP, MYSQLI_ASSOC);
                            $is_program=$deliv[0]['is_program'];
                        }
                        $result[$index]['is_program']=$is_program;
                        $index+=1;
                    }
                } 
                $partner['orders'] = $result;
                
                if(count($result) > 0) {
                    array_push($data, $partner);
                }
            }
            
            $success =1;
            $status =200;
            $msg = "Success";
        }
    }else{
        $success =0;
        $status =204;
        $msg = "400 Missing Required Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "data"=>$data]);
?>