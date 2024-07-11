<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once '../../includes/DbOperation.php';
require '../../includes/functions.php';
require '../../includes/ValidatorV4.php';

$fs = new functions();
// date_default_timezone_set('Asia/Jakarta');
// POST DATA
$db = new DbOperation();
$validator = new ValidatorV4();

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
$res1 = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

function generateTransactionID($db_conn, $type, $trxDate, $pid){
    $code = $type."/".$trxDate."/".$pid;
    $q = mysqli_query($db_conn,"SELECT count(id) as id FROM `transaksi` WHERE id LIKE '%$code%' ORDER BY jam DESC LIMIT 1");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id1 =(int) $res[0]['id'];
        $index = (int) $id1 +1;
        if($index<10){
            $index = "00000".$index;
        }else if($index<100){
            $index = "0000".$index;
        }else if($index<1000){
            $index = "000".$index;
        }else if($index<10000){
            $index = "00".$index;
        }else if($index<100000){
            $index = "0".$index;
        }else{
            $index = $index;
        }
        $code = $code."/".$index;
        return $code;
    }else{
        $code = $code."/000001";
        return $code;
    }
}

function getService($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT service FROM `partner` WHERE id='$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['service'];
    }else{
        return 0;
    }
}

function getTaxEnabled($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT tax FROM `partner` WHERE id='$id'");
    $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
    $tax = $res[0]['tax'];
    return (double) $tax;
}

function getChargeEwallet($db_conn){
    $q = mysqli_query($db_conn,"SELECT value FROM settings WHERE name = 'charge_ewallet'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return $res[0]['value'];
    }else{
        return 0;
    }
}

function getChargeXendit($db_conn){
    $q = mysqli_query($db_conn,"SELECT value FROM settings WHERE name = 'charge_xendit'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['value'];
    }else{
        return 0;
    }
}

function getHideCharge($idPartner, $db_conn){
    $q = mysqli_query($db_conn,"SELECT partner.hide_charge FROM `master`
    JOIN partner ON master.id = partner.id_master
    WHERE partner.id ='$idPartner' ");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['hide_charge'];
    }else{
        return 0;
    }
}

function getStatus($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT master.status FROM `master`
    JOIN partner ON master.id = partner.id_master
    WHERE partner.id ='$id' ");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['status'];
    }else{
        return 0;
    }
  }

  function getShiftID($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT MAX(id) as id FROM `shift` WHERE partner_id='$id' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['id'];
    }else{
        return 0;
    }
  }

function getChargeUr($status, $hide, $db_conn, $id){
    if($status == "FULL" && $hide==0){
        $q = mysqli_query($db_conn,"SELECT charge_ur as value FROM `partner` WHERE id='$id'");
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            // return (int) $res[0]['value'];
            return 0;
        }else{
            return 0;
        }
    }else{
        return 0;
    }
}

function getIDVR($phone, $vr, $trx ,$db_conn){
        $q = mysqli_query($db_conn,"SELECT id  FROM `user_voucher_ownership` WHERE `userid` LIKE '$phone' AND `voucherid`='$vr' AND `transaksi_id` IS NULL ORDER BY id ASC limit 1");
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $id = $res[0]['id'];
            $update = mysqli_query($db_conn,"UPDATE `user_voucher_ownership` SET `transaksi_id`='$trx' WHERE id='$id'");
            return $update;
        }else{
            return 0;
        }
    }
$id = "";
$test = [];


$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $validator->checkShiftIDActive($db_conn, $token);
    $partnerID = $token->id_partner;
    $data = json_decode(file_get_contents('php://input'));
    if( isset($data->customer_phone)
        && isset($data->customer_name)
        && isset($data->total)
        && isset($data->paymentMethod)
        && !empty($data->customer_phone)
        // && !empty($data->customer_name)
        && !empty($data->total)
        && !empty($data->paymentMethod) ){
            $sourceId = $data->id;
            $is_takeaway = $data->is_takeaway;
            $trxDate = date("ymd");
            $pid = (int) $partnerID;
            if(isset($data->rounding)){
                $rounding = $data->rounding;
            }else{
                $rounding = 0;
            }
            if($is_takeaway==1 || $is_takeaway=='1'){
                $id = generateTransactionID($db_conn, "TA", $trxDate, $pid);
            }elseif (isset($data->surcharge_type) && !empty($data->surcharge_type)) {
                $id = generateTransactionID($db_conn, "ET", $trxDate, $pid);
            }else{
                $id = generateTransactionID($db_conn, "DI", $trxDate, $pid);
            }
            $phone = $data->customer_phone;
            $name = $data->customer_name;
            $reference_id = $data->reference_id;
            $paymentMethod = $data->paymentMethod;
            $total = $data->total;
            $tableCode = $data->tableCode;
            if(isset($data->surcharge_type) && !empty($data->surcharge_type)){
                $tableCode = $data->surcharge_type;
            }
            $is_queue = $data->is_queue;
            $id_voucher = $data->id_voucher;
            $id_voucher_redeemable = "";
            if(isset($data->id_voucher_redeemable) && !empty($data->id_voucher_redeemable)){
                $id_voucher_redeemable = $data->id_voucher_redeemable;
            }
            $notes = $data->notes;
            $foodcourtID=0;
            if(isset($data->foodcourtID) && !empty($data->foodcourtID)){
                $foodcourtID = $data->foodcourtID;
            }
            $diskon_spesial = 0;
            if(isset($data->diskon_spesial) && !empty($data->diskon_spesial)){
                $diskon_spesial = $total*ceil($data->diskon_spesial)/100;;
            }
            $promo = 0;
            if(isset($data->promo) && !empty($data->promo)){
                $promo = $data->promo;
            }
            $point = 0;
            if(isset($data->point) && !empty($data->point)){
                $point = $data->point;
            }
            $customer_email = "";
            if(isset($data->customer_email) && !empty($data->customer_email)){
                $customer_email = $data->customer_email;
            }
            $program_discount = 0;
            if(isset($data->program_discount) && !empty($data->program_discount)){
                $program_discount = $data->program_discount;
            }
            
            $status = 0;
            $service = getService($partnerID, $db_conn);
            $tax = getTaxEnabled($partnerID, $db_conn);
            $charge_ewallet = getChargeEwallet($db_conn);
            $charge_xendit = getChargeXendit($db_conn);
            $hide = getHideCharge($partnerID, $db_conn);
            $status =5;
            $pstatus = getStatus($partnerID, $db_conn);
            $today = date("Y-m-d H:i:s");
            $charge_ur =(int)getChargeUr($pstatus, $hide, $db_conn, $partnerID);
            $shiftID =(int)getShiftID($partnerID, $db_conn);
            if($paymentMethod=='11' || $paymentMethod==11 ){
                $status = 5;
            }
            $idx = 0;
            $il = 0;
            $dataDetail = $data->detail;
            $boolQty = true;
            if(isset($data->isConsignment)&& (int)$data->isConsignment==1){
                $tax=0;
                $service = 0;
            }
            if($boolQty==true){
                $edp = 0;
                $ed = 0;
                if(!empty($data->edp)){
                    $edp = (int)$data->edp;
                    $ed = (int)$total*$edp/100;
                }
            if(isset($data->group_id) && !empty($data->group_id)){
                $group_id = $data->group_id;
                $insert = mysqli_query($db_conn,"INSERT INTO transaksi(id, jam, phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, group_id, shift_id, customer_email, is_pos, employee_discount, employee_discount_percent, rounding, program_discount) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', $group_id, '$shiftID', '$customer_email','1', '$ed', '$edp', $rounding, '$program_discount')");
            }else{
                $insert = mysqli_query($db_conn,"INSERT INTO transaksi(id, jam, phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service, charge_ewallet, charge_xendit, charge_ur, id_voucher, id_voucher_redeemable, diskon_spesial, customer_name, reference_id, shift_id, customer_email, is_pos,employee_discount, employee_discount_percent, rounding, program_discount, pax) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$name', '$reference_id', '$shiftID', '$customer_email','1', '$ed', '$edp', $rounding, '$program_discount','0')");
            }
                if($insert){
                    mysqli_query($db_conn, "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`, `payment_method_before`, `payment_method_after`, `created_by`) VALUES ('$id', '0', '$status',  '0', '$paymentMethod', '$token->id')");
                    $printHistoryQuery = "
                        SELECT
                            transaction_id,
                            type
                        FROM
                            transaction_prints
                        WHERE
                            transaction_id = '$sourceId'";
                            
                    $printHistory = mysqli_query($db_conn, $printHistoryQuery);
                    while ($row = mysqli_fetch_assoc($printHistory)) {
                        $tmpType = $row['type'];
                        $test = mysqli_query($db_conn, "
                            INSERT INTO transaction_prints(
                                transaction_id,
                                type
                            )
                            VALUES(
                                '$id',
                                '$tmpType'
                            )
                        ");
                    }
                    getIDVR($phone, $id_voucher_redeemable, $id ,$db_conn);
                    $dataDetail = $data->detail;
                    $idx = 0;
                    $il=0;
                    $items = array();

                    $boolCheck = true;
                    $insertDT = $db->insertDetailTransaksiAndroid($id, $dataDetail);
                    if ($insertDT==5) {
                    }else{
                        $boolCheck=false;
                    }

                    if($boolCheck==true){
                        if(isset($data->customer_email) && !empty($data->customer_email) && $status=='2' && $paymentMethod!='11' && $paymentMethod!=11){
                            $query= "SELECT transaksi.no_meja, transaksi.total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.program_discount, partner.name AS partner_name, users.email, users.name, partner.id AS partner_id, payment_method.nama AS payment_method_name, transaksi.customer_email FROM `transaksi` JOIN `partner` ON `partner`.`id`=`transaksi`.`id_partner` LEFT JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id='$id'";
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
                                    $user_email = $row['customer_email'];
                                    if(isset($row['email']) && !empty($row['email'])){
                                        $user_email = $row['email'];
                                    }
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

                                $query = "SELECT template FROM `email_template` WHERE id=1";
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
                                    $template = str_replace('$id ',$id, $template);
                                    if(substr($id,0,2)=="DI"){
                                        $template = str_replace('$trx_type',"Dine In", $template);
                                    }elseif(substr($id,0,2)=="TA"){
                                        $template = str_replace('$trx_type',"Take Away", $template);
                                    }if(substr($id,0,2)=="DL"){
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
                                $query= "SELECT menu.nama,qty, detail_transaksi.harga FROM `detail_transaksi` JOIN menu ON menu.id=detail_transaksi.id_menu WHERE detail_transaksi.id_transaksi='$id' AND detail_transaksi.deleted_at IS NULL";
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
                                    $insertTe = mysqli_query($db_conn, "INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`, `created_at`) VALUES ('$user_email', '$partner_id', 'UR E-Receipt', $template, NOW())");
                        }
                        
                        if($paymentMethod==5 || $paymentMethod=="5" || $paymentMethod==7 || $paymentMethod=="7" || $paymentMethod==8 || $paymentMethod=="8" || $paymentMethod==9 || $paymentMethod=="9" || $paymentMethod==11 || $paymentMethod=="11" ){
                            $allTrans = mysqli_query($db_conn, "SELECT t.id, t.id_partner, t.queue, t.status, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, p.nama AS payment_method, u.name AS uname FROM transaksi t JOIN payment_method p ON p.id=t.tipe_bayar JOIN users u ON t.phone=u.phone WHERE t.id='$id'");
                            $order = mysqli_fetch_assoc($allTrans);
                            $devTokens = $db->getPartnerDeviceTokens($partnerID);
                            foreach ($devTokens as $val) {
                                $mID = $db->getMembership($partnerID, $phone);
                                if($mID==0){
                                    $isMembership = false;
                                }else{
                                    $isMembership = true;
                                }
                                $birth_date = $db->getBirthdate($phone);
                                $gender = $db->getGender($phone);
                                // if($is_takeaway==1 || $is_takeaway=='1'){
                                //         $notif = $db->savePaymentNotification($val['token'], 'Pesanan Baru', 'Pesanan Takeaway Baru', 'Takeaway', 'rn-push-notification-channel', $paymentMethod, 0, 0, $id, $partnerID, null, $order, $gender, $birth_date, $isMembership, "0", "employee", '');
                                // }else if($is_queue==1 || $is_queue=='1'){
                                //     $notif = $db->savePaymentNotification($val['token'], 'Pesanan Baru', 'Pesanan Antrian Baru', 'Antrian', 'rn-push-notification-channel', $paymentMethod, 0, 0, $id, $partnerID, null, $order, $gender, $birth_date, $isMembership, "0", "employee", '');
                                // }else{
                                //     $notif = $db->savePaymentNotification($val['token'], 'Pesanan Baru', 'Pesanan Baru '.$tableCode, $tableCode, 'rn-push-notification-channel', $paymentMethod, 0, 0, $id, $partnerID, null, $order, $gender, $birth_date, $isMembership, "0", "employee", '');
                                // }
                            }
                            
                        $msg = "Success";
                        $success = 1;
                        $status=200;
                        }
                    }else{
                        $msg = "Failed Create Detail";
                        $success = 0;
                        $status=204;
                    }
                }else{
                    $msg = "Failed Create Transaction";
                    $success = 0;
                    $status=204;
                }
            }else{
                $msg = "Stock Menu Tidak Mencukupi";
                $success = 0;
                $status=204;
            }
    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;
    }
}
if($status===204){
    http_response_code(200);
}else{
    http_response_code($status);
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "unavailable"=>$res1, "transaction_id"=>$id, "test"=>$test ]);
?>