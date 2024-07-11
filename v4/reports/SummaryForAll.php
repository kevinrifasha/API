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
$resSubTotal = array();
$arr = array();
$resQ = array();
$transaction_type = array();
$payment_methods = array();
$shiftTrx = array();

// $countTrx = 0;
// $countUser = 0;
// $countQty = 0;
// $resSubTotal = array();
// $arr = array();
// $arr['hpp'] = 0;
// $arr['sales']=0;
// $arr['total_income']=0;
// $arr['diskon_spesial']=0;
// $arr['promo']=0;
// $arr['employee_discount']=0;
// $arr['program_discount']=0;
// $arr['charge_ur']=0;
// $arr['service']=0;
// $arr['tax']=0;
// $arr['gross_income']=0;
// $arr['total_net_profit']=0;
$resQ = array();
// $payment_methods = array();
$dineIn = array();
$dineIn['qty'] = 0;
$takeaway = array();
$takeaway['qty'] =0;
$preorder = array();
$preorder['qty'] =0;
$delivery = array();
$delivery['qty'] =0;
// $charge_ewallet=0;
$countQty=0;
$countTrx=0;
$countUser=0;
// $opex=0;
// $dataDP=array();
// $transaction_type = [];
// $shiftTrx = [];
// $all = "0";
$array = [];
    
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
    
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];

    $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
    if(mysqli_num_rows($sqlPartner) > 0) {
        $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
        foreach($getPartners as $partner) {
            $id = $partner['partner_id'];
            
            // Get summary 
            $resSubTotal = array();
            $charge_ewallet=0;
            $payment_methods = array();
            $opex=0;
            $countQty = 0;
            $countUser = 0;
            $countTrx = 0;
            $transaction_type = [];
            $shiftTrx = [];
            $dataDP=array();
            $arr = array();
            $arr['hpp'] = 0;
            $arr['sales']=0;
            $arr['total_income']=0;
            $arr['diskon_spesial']=0;
            $arr['promo']=0;
            $arr['employee_discount']=0;
            $arr['program_discount']=0;
            $arr['charge_ur']=0;
            $arr['service']=0;
            $arr['tax']=0;
            $arr['netSales']=0;
            $arr['gross_income']=0;
            $arr['total_net_profit']=0;
            $arr['total_net_profit_after_ewallet_charge'] = 0;
            
            $res = $cf->getSubTotal($id, $dateFrom, $dateTo);
            $shiftTrx = $cf->getShiftTransaction($id, $dateFrom, $dateTo, null);
            $transaction_type = $cf->getByTransactionType($id, $dateFrom, $dateTo, null);
            
            $resSubTotal = $res;
            $res['hpp']=0;
            $res['gross_profit']=$res['clean_sales'];
            $arr['gross_income']=$res['clean_sales'];
            $res['gross_profit_afterservice']=$res['gross_profit']-$res['service'];
            $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
        
            $query =  "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.deleted_at IS NULL ";
            
            $dateFromStr = str_replace("-","", $dateFrom);
            $dateToStr = str_replace("-","", $dateTo);
            $payments = $cf->getGroupPaymentMethod($id, $dateFrom, $dateTo, null);
            $i=0;
            
            foreach($payments as $x){
                $payment_methods[$i]=$x;
                $intType = (int)$x['tipe'];
                $charge_ewallet += (int)$x['charge_ewallet'];
                if($intType==1||$intType==3||$intType==4||$intType==10){
                    $payment_methods[$i]['mdr']=1.5;
                    $payment_methods[$i]['tax']=11;
                }else if($intType==2){
                    $payment_methods[$i]['mdr']=2;
                    $payment_methods[$i]['tax']=11;
                }else{
                    $payment_methods[$i]['mdr']=0;
                    $payment_methods[$i]['tax']=0;
                }
                $payment_methods[$i]['mdr_rupiah']=ceil((int)$payment_methods[$i]['value']*$payment_methods[$i]['mdr']/100);
                $payment_methods[$i]['tax_rupiah']=ceil((int)$payment_methods[$i]['mdr_rupiah']*$payment_methods[$i]['tax']/100);
                $payment_methods[$i]['income']= $payment_methods[$i]['value']-$payment_methods[$i]['mdr_rupiah']-$payment_methods[$i]['tax_rupiah'];
                $i++;
            }
        
            $hppQ = mysqli_query($db_conn,$query);
            
            if (mysqli_num_rows($hppQ) > 0) {
              $resQ1 = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
              $resQ[0]['hpp']=0;
              foreach ($resQ1 as $value) {
                  $resQ[0]['hpp']+=(double) $value['hpp'];
              }
              $res['hpp']=(double)$resQ[0]['hpp'];
              $arr['hpp']=$res['hpp'];
              $res['gross_profit'] = $res['gross_profit'] - $res['hpp'];
              $res['gross_profit_afterservice']=$res['gross_profit']-$res['service'];
              $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
        
        
              $arr['sales']=$res['sales'];
              $arr['clean_sales']=$res['clean_sales'];
              $arr['total_income']=$res['clean_sales'];
              $arr['diskon_spesial']=$res['diskon_spesial'];
              $arr['promo']=$res['promo'];
              $arr['employee_discount']=$res['employee_discount'];
              $arr['program_discount']=$res['program_discount'];
              $arr['charge_ur']=$res['charge_ur'];
              $arr['service']=$res['service'];
              $arr['tax']=$res['tax'];
              $arr['gross_income'] = $res['gross_profit'];
              $arr['netSales']=ceil($res['clean_sales']-$res['tax']-$res['service']-$res['charge_ur']-$res['delivery_fee_resto']);
            }
            
            $query = "SELECT SUM(am.amount) as amount FROM (SELECT DISTINCT op.amount as amount, op.id FROM operational_expenses op LEFT JOIN operational_expense_categories opc ON op.category_id=opc.id LEFT JOIN partner p ON p.id_master=opc.master_id LEFT JOIN employees e ON e.id=op.created_by WHERE e.id_partner='$id' AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo') am";
            
            $sql = mysqli_query($db_conn, $query);
            if(mysqli_num_rows($sql) > 0) {
                $opexes = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                if(!is_null($opexes[0]['amount'])){
                    $opex = (int)$opexes[0]['amount'];
                }
            }
            $arr['total_net_profit']=$res['gross_profit_aftertax']-$arr['charge_ur']-$opex+($shiftTrx['saldo']);
            $arr['total_net_profit_after_ewallet_charge']=$res['gross_profit_aftertax']-$arr['charge_ur']-$opex+($shiftTrx['saldo'])-$charge_ewallet;
        
            $dateFromStr = str_replace("-","", $dateFrom);
            $dateToStr = str_replace("-","", $dateTo);
            
            $query = "SELECT COUNT(transaksi.id) AS trx FROM `transaksi` WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2,5) AND transaksi.deleted_at IS NULL AND  DATE(transaksi.jam) BETWEEN '$dateFrom' AND '$dateTo' ";
            $sql = mysqli_query($db_conn, $query);
        
            $query = "SELECT SUM(pax) AS user FROM `transaksi` WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2,5) AND transaksi.deleted_at IS NULL AND  DATE(transaksi.jam) BETWEEN '$dateFrom' AND '$dateTo' ";
            $sql1 = mysqli_query($db_conn, $query);
        
            $query = "SELECT SUM(detail_transaksi.qty) AS qty FROM `transaksi` JOIN detail_transaksi ON detail_transaksi.id_transaksi=transaksi.id WHERE transaksi.id_partner='$id' AND transaksi.status IN(1,2,5) AND transaksi.deleted_at IS NULL AND DATE(transaksi.jam) BETWEEN '$dateFrom' AND '$dateTo'";
            $sql2 = mysqli_query($db_conn, $query);
            
            if(mysqli_num_rows($sql2) > 0) {
                $data2 = mysqli_fetch_all($sql2, MYSQLI_ASSOC);
                foreach ($data2 as $value) {
                    $countQty += (int) $value['qty'];
                }
            }
            if(mysqli_num_rows($sql1) > 0) {
                $data1 = mysqli_fetch_all($sql1, MYSQLI_ASSOC);
                foreach ($data1 as $value) {
                    $countUser += (int) $value['user'];
                }
            }
            if(mysqli_num_rows($sql) > 0) {
                $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                foreach ($data as $value) {
                    $countTrx +=(int) $value['trx'];
                }
            }
            
            $query = "SELECT SUM(amount) AS total, pm.nama AS pmName FROM `down_payments` JOIN payment_method pm ON pm.id = down_payments.payment_method_id WHERE down_payments.partner_id='$id' AND down_payments.deleted_at IS NULL AND  DATE(down_payments.created_at) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY down_payments.payment_method_id ORDER BY total DESC";
            
            $sqlDP = mysqli_query($db_conn, $query);
            if(mysqli_num_rows($sqlDP)>0){
                $dataDP = mysqli_fetch_all($sqlDP, MYSQLI_ASSOC);
            }
            
            $summary = [];
            $summary['data_subtotal'] = $resSubTotal;
            $summary['sales'] = $arr['sales'];
            $summary['service'] = $arr['service'];
            $summary['tax'] = $arr['tax'];
            $summary['netSales'] = $arr['netSales'];
            $summary['total_income'] = $arr['total_income'];
            $summary['promo'] = $arr['promo'];
            $summary['diskon_spesial'] = $arr['diskon_spesial'];
            $summary['employee_discount'] = $arr['employee_discount'];
            $summary['program_discount'] = $arr['program_discount'];
            $summary['charge_ur'] = $arr['charge_ur'];
            $summary['gross_income'] = $arr['gross_income'];
            $summary['hpp'] = $arr['hpp'];
            $summary['charge_ewallet'] = $charge_ewallet;
            $summary['payment_methods'] = $payment_methods;
            $summary['opex'] = $opex;
            $summary['total_net_profit'] = $arr['total_net_profit'];
            $summary['count_qty'] = $countQty;
            $summary['count_user'] = $countUser;
            $summary['count_trx'] = $countTrx;
            $summary['transaction_type'] = $transaction_type;
            $summary['total_net_profit_after_ewallet_charge'] = $arr['total_net_profit_after_ewallet_charge'];
            $summary['transactions_shift'] = $shiftTrx;
            $summary['dp'] = $dataDP;
            // Get summary end
            
            // Get gross profit
            $res = $cf->getSubTotal($id, $dateFrom, $dateTo);
            
            $shiftTrx = $cf->getShiftTransaction($id, $dateFrom, $dateTo, null);
            $res['income'] = $shiftTrx['debit'];
            $res['expense'] = $shiftTrx['credit'];
            $payments = $cf->getGroupPaymentMethod($id, $dateFrom, $dateTo, null);
            foreach($payments as $x){
                // $charge_ewallet1 += (int)$x['charge_ewallet'];
                ($charge_ewallet1 ?? $charge_ewallet1 = 0) ? $charge_ewallet1 += (int)$x['charge_ewallet'] : $charge_ewallet1 = (int)$x['charge_ewallet'];
            }
            $res['charge_ewallet'] = $charge_ewallet1;
            $res['hpp']=0;
            $res['gross_profit']=$res['clean_sales'];
            $res['gross_profit_afterincome'] = $res['gross_profit'] + $res['income'];
            $res['gross_profit_afterexpense'] = $res['gross_profit_afterincome'] - $res['expense'];
            $res['gross_profit_afterservice']=$res['gross_profit_afterexpense']-$res['service'];
            $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
            $res['gross_profit_aftercharge']=$res['gross_profit_aftertax']-$res['charge_ewallet'];
            
            $query = "";
            $query =  "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND (transaksi.status=2 OR transaksi.status=1 ) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' ";
            
            $hppQ = mysqli_query($db_conn,$query);
            
            if (mysqli_num_rows($hppQ) > 0) {
                $resQ1 = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);
                $resQ[0]['hpp']=0;
                foreach ($resQ1 as $value) {
                    $resQ[0]['hpp']+=(double) $value['hpp'];
                }
                $res['hpp']=(double)$resQ[0]['hpp'];
                $res['gross_profit'] = $res['gross_profit'] - $res['hpp'];
                $res['gross_profit_afterincome'] = $res['gross_profit'] + $res['income'];
                $res['gross_profit_afterexpense'] = $res['gross_profit_afterincome'] - $res['expense'];
                $res['gross_profit_afterservice']=$res['gross_profit_afterexpense']-$res['service'];
                $res['gross_profit_aftertax']=$res['gross_profit_afterservice']-$res['tax'];
                $res['gross_profit_aftercharge']=$res['gross_profit_aftertax']-$res['charge_ewallet'];
            }
           
            $grossProfit = []; 
            $grossProfit['data'] = $res;
            $grossProfit['hpp'] = $resQ;
            // "data"=>$res, "hpp"=>$resQ
            // Get gross profit end
            
            // Get Department Sales
            $arr = [];
            $total = 0;
            
            $query = "SELECT SUM(detail_transaksi.harga) AS qty, departments.name AS nama, categories.name, departments.id FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories ON categories.id=menu.id_category JOIN departments ON categories.department_id=departments.id WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND detail_transaksi.deleted_at IS NULL AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo'  GROUP BY departments.id";
            
            $sqlGetSales = mysqli_query($db_conn, $query);
            if(mysqli_num_rows($sqlGetSales) > 0) {
                while($row2=mysqli_fetch_assoc($sqlGetSales)){
                    $namaMenu2 = $row2['nama'];
                    $qty2 = (int) $row2['qty'];
                    $i=0;
                    $add = true;
                    foreach ($arr as $value) {
                        if($value['name']==$namaMenu2){
                            $arr[$i]['sales']+= $qty2;
                            $add = false;
                            // $total+= $qty2;
                        }
                        $i+=1;
                    }
                    if($add==true){
                        array_push($arr, array("name" => "$namaMenu2", "sales" => $qty2));
                    }
                    $total+= $qty2;
                }
               
                $sorted = array();
                $sorted = array_column($arr, 'sales');
                array_multisort($sorted, SORT_DESC, $arr);
            }
            
            $departmentSales = [];
            $departmentSales['sales'] = $arr;
            $departmentSales['total'] = $total;
            // "sales"=>$arr, "total"=>$total
            // Get Department Sales end
            
            $partner['summary'] = $summary;
            $partner['grossProfit'] = $grossProfit;
            $partner['departmentSales'] = $departmentSales;
            
            if($summary['data_subtotal']['subtotal'] > 0) {
                array_push($array, $partner);
            }
            
        }
        
        $success=1;
        $status=200;
        $msg="Success";
    } else {
        $success=0;
        $status=203;
        $msg="Data not found";
    }
    
    
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "data"=>$array]);

?>