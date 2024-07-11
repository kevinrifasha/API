<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require '../../includes/functions.php';

$fs = new functions();
//init var
date_default_timezone_set('Asia/Jakarta');
$now = date('Y-m-d H:i:s', time());
$today = date('Y-m-d', time());
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
    $obj = json_decode(file_get_contents('php://input'));
    if(
        isset($obj->transactionID) && !empty($obj->transactionID)
        && isset($obj->customer_email) && !empty($obj->customer_email)
    ){
        $sql = mysqli_query($db_conn, "UPDATE transaksi SET customer_email='$obj->customer_email' WHERE id='$obj->transactionID'");
        if($sql){
            if(isset($obj->status) && !empty($obj->status)){
                $temp = $obj->status;
                if($temp==2){
                    $query= "SELECT transaksi.no_meja, transaksi.total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.program_discount, partner.name AS partner_name, users.email, users.name, partner.id AS partner_id, payment_method.nama AS payment_method_name, transaksi.customer_email FROM `transaksi` JOIN `partner` ON `partner`.`id`=`transaksi`.`id_partner` LEFT JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id='$obj->transactionID'";
                $trxQ = mysqli_query($db_conn, $query);
                $subtotal = 0;
                $promo = 0;
                $program_discount = 0;
                $diskon_spesial = 0;
                $employee_discount = 0;
                $point = 0;
                $service = 0;
                $tax = 0;
                $charge_ur = 0;
                $payment_method_name = "";
                $partner_name = "";
                $partner_id = "";
                $user_name = "";
                $user_email = "";
                $no_meja = "";

                while ($row = mysqli_fetch_assoc($trxQ)) {
                    $payment_method_name = $row['payment_method_name'];
                    $partner_name = $row['partner_name'];
                    $partner_id = $row['partner_id'];
                    $user_name = $row['name'];
                    // $user_email = $row['customer_email'];
                    // if(isset($row['email']) && !empty($row['email'])){
                    //     $user_email = $row['email'];
                    // }
                    $no_meja = $row['no_meja'];
                  $subtotal += (int) $row['total'];
                  $promo += (int) $row['promo'];
                  $program_discount += (int) $row['program_discount'];
                  $diskon_spesial += (int) $row['diskon_spesial'];
                  $employee_discount += (int) $row['employee_discount'];
                  $point += (int) $row['point'];
                  $tempS = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] )*(int) $row['service'] / 100);
                  $service += $tempS;
                  $charge_ur += (int) $row['charge_ur'];
                  $tempT = ceil(( (int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * ( double ) $row['tax'] / 100);
                  $tax += $tempT;
                }
                $subtotal = $subtotal;
                $sales = $subtotal+$service+$tax+$charge_ur;
                $promo = $promo;
                $program_discount = $program_discount;
                $diskon_spesial = $diskon_spesial;
                $employee_discount = $employee_discount;
                $point = $point;
                $clean_sales = $sales-$promo-$program_discount-$diskon_spesial-$employee_discount-$point;
                $service = $service;
                $charge_ur = $charge_ur;
                $tax = $tax;
                $total = $subtotal-$promo-$program_discount-$diskon_spesial-$employee_discount-$point+$service+$charge_ur+$tax;
                $dateNow = date('d M Y');
                $timeNow = date("h:i");

                $query = "SELECT template FROM `email_template` WHERE name='receipt-natta'";
                $templateQ = mysqli_query($db_conn, $query);
                if (mysqli_num_rows($templateQ) > 0) {
                    $templates = mysqli_fetch_all($templateQ, MYSQLI_ASSOC);
                    $template = $templates[0]['template'];
                    $template = str_replace('$strTottotal',$fs->rupiah($total),$template);
                    $template = str_replace('$dateNow ',$dateNow,$template);
                    $template = str_replace('$timeNow ',$timeNow,$template);
                    $template = str_replace('$name ',$user_name, $template);
                    $template = str_replace('$partnerName ',$partner_name, $template);
                    $template = str_replace('$no_meja ',$no_meja, $template);
                    $template = str_replace('$strTot ',$fs->rupiah($subtotal), $template);
                    $template = str_replace('$strServ ',$fs->rupiah($service), $template);
                    $template = str_replace('$strChargeUR ',$fs->rupiah($charge_ur), $template);
                    $template = str_replace('$strTax ',$fs->rupiah($tax), $template);
                    $template = str_replace('$strSUbtot ',$fs->rupiah($total), $template);
                    $template = str_replace('$type ',$payment_method_name, $template);
                    $template = str_replace('$id ',$obj->transactionID, $template);
                    if(substr($obj->transactionID,0,2)=="DI"){
                        $template = str_replace('$trx_type',"Dine In", $template);
                    }elseif(substr($obj->transactionID,0,2)=="TA"){
                        $template = str_replace('$trx_type',"Take Away", $template);
                    }if(substr($obj->transactionID,0,2)=="DL"){
                        $template = str_replace('$trx_type',"Delivery", $template);
                    }else{
                        $template = str_replace('$trx_type',"Pre Order", $template);
                    }
                    if($promo>0){
                        $template = str_replace('$promo','
                        <tr>
                        <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Promo Voucher</td>
                        <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> '.$fs->rupiah($promo).' </td>
                       </tr>', $template);
                    }else{
                        $template = str_replace('$promo','', $template);
                    }

                    if($diskon_spesial>0){
                        $template = str_replace('$Diskon Spesial','<tr>
                        <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Diskon Spesial</td>
                        <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> '.$fs->rupiah($diskon_spesial) .'</td>
                       </tr>', $template);
                    }else{
                        $template = str_replace('$Diskon Spesial','', $template);
                    }

                    if($employee_discount>0){
                        $template = str_replace('$Diskon Karyawan','<tr>
                        <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Diskon Karyawan</td>
                        <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> '.$fs->rupiah($employee_discount) .'</td>
                       </tr>', $template);
                    }else{
                        $template = str_replace('$Diskon Karyawan','', $template);
                    }
                    if($program_discount>0){
                        $template = str_replace('$Diskon Program','<tr>
                        <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Diskon Program</td>
                        <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> '.$fs->rupiah($program_discount) .'</td>
                       </tr>', $template);
                    }else{
                        $template = str_replace('$Diskon Program','', $template);
                    }
                }
                $query= "SELECT menu.nama,qty, detail_transaksi.harga FROM `detail_transaksi` JOIN menu ON menu.id=detail_transaksi.id_menu WHERE detail_transaksi.id_transaksi='$obj->transactionID' AND detail_transaksi.deleted_at IS NULL";
                $detail_trx = mysqli_query($db_conn, $query);
                $detail_transaction="";
                while ($row = mysqli_fetch_assoc($detail_trx)) {
                    $nama_menu = $row['nama'];
                    $qty_menu = $row['qty'];
                    $harga_menu = $row['harga'];
                    $detail_transaction .= '
                    <tr>
                    <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> '.$nama_menu.' X  '.$qty_menu.' </td>
                    <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> '.$fs->rupiah($harga_menu).' </td>
                    </tr>';
                }
                $template = str_replace('$loop detail',$detail_transaction, $template);
                $template=json_encode($template);
                    if (filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
                        $insertTe = mysqli_query($db_conn, "INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`, `created_at`) VALUES ('$user_email', '$partner_id', 'Natta E-Receipt', $template, NOW())");
                    }
                }
            }
            $msg = "Success";
                $success = 1;
                $status=200;
        }else{
            $msg = "Failed to update";
            $success = 0;
            $status=400;

        }
    }else{
        $success=0;
        $msg="Missing required fields";
        $status=400;
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>