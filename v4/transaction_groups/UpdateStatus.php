<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once '../../includes/DbOperation.php';
require '../../includes/functions.php';
require_once('../auth/Token.php');
require '../../includes/ValidatorV4.php';
$today11 = date("Y-m-d H:i:s");

$dateNow = date('d M Y');
$timeNow = date("h:i");
// //init var
$fs = new functions();
$db = new DbOperation();
$validator = new ValidatorV4();
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

function getShiftID($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT MAX(id) as id FROM `shift` WHERE partner_id='$id' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['id'];
    }else{
        return 0;
    }
  }



$tokenizer = new Token();
$token = '';
$res = array();
$status=200;

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
    $validator->checkShiftIDActive($db_conn, $token);
    $partnerID  = $token->id_partner;
    $data       = json_decode(file_get_contents('php://input'));
    $shiftID    = (int) getShiftID($partnerID, $db_conn);
    $birthDate="";
    $payment_method="";
    $gender="";
    if(isset($data->transactionsID) &&!empty($data->transactionsID) && isset($data->paymentMethod) &&!empty($data->paymentMethod) && isset($data->status) ){
        // if(($data->paymentMethod==13 || $data->paymentMethod=="13") && !empty($data->groupID)){
        //     $success = 0;
        //     $status = 204;
        //     $msg = "Sementara tidak bisa membayar transaksi grup dengan DP. Mohon satukan transaksi atau buat transaksi baru";
        // }else{

        if(isset($data->groupID) && !empty($data->groupID)){
            if($data->groupID=="group_table"){
                $q = mysqli_query($db_conn, "INSERT INTO `transaction_groups`(`partner_id`, `name`) VALUES ('$token->id_partner', '$data->name')");
                if ($q) {
                    $iid = mysqli_insert_id($db_conn);
                    $data->groupID= $iid;
                    $transactionID = explode(',', $data->transactionsID);
                    foreach ($transactionID as $value) {
                        $q = mysqli_query($db_conn, "UPDATE `transaksi` SET `group_id`='$data->groupID' WHERE id='$value'");
                    }
                }
            }
        }
        
        $transactionID = explode(',', $data->transactionsID);
        if($data->paymentMethod==12 || $data->paymentMethod=="12"){
            if($data->groupID==0||$data->groupID=="0"){
                foreach($transactionID as $value){
                    $insertAP = mysqli_query($db_conn, "INSERT INTO account_receivables SET master_id='$token->id_master', partner_id='$partnerID', user_name='$data->arName', user_phone='$data->arPhone', company='$data->arCompany', created_by='$token->id', shift_id='$shiftID', transaction_id='$value'");
                    $updateAR = mysqli_query($db_conn, "UPDATE transaksi SET is_ar=1 WHERE id='$value'");
                }
            }else{
                $insertAP = mysqli_query($db_conn, "INSERT INTO account_receivables SET master_id='$token->id_master', partner_id='$partnerID', user_name='$data->arName', user_phone='$data->arPhone', company='$data->arCompany', created_by='$token->id', shift_id='$shiftID', group_id='$data->groupID'");
                $updateAR = mysqli_query($db_conn, "UPDATE transaksi SET is_ar=1 WHERE group_id='$data->groupID'");
            }

        }

        if($data->paymentMethod!=0 || $data->paymentMethod!="0" ||
        $data->paymentMethod!=1 || $data->paymentMethod!="1" ||
        $data->paymentMethod!=2 || $data->paymentMethod!="2" ||
        $data->paymentMethod!=3 || $data->paymentMethod!="3" ||
        $data->paymentMethod!=4 || $data->paymentMethod!="4" ||
        $data->paymentMethod!=6 || $data->paymentMethod!="6" ||
        $data->paymentMethod!=10 || $data->paymentMethod!="10" ||
        $data->paymentMethod!=11 || $data->paymentMethod!="11" || $data->paymentMethod!=12 || $data->paymentMethod!="12"){
            $sql = "";
            $sql1 = "";
            if($data->paymentMethod==13 || $data->paymentMethod=="13"){
                $i=0;
                foreach ($transactionID as $value) {
                    if($data->status==2){
                        $qShiftID = "shift_id='$shiftID', paid_date=NOW(), ";
                    }else{
                        $qShiftID = "";
                    }
                    if(!isset($data->groupID)){
                        $sql = "UPDATE `transaksi` SET status='$data->status', tipe_bayar='$data->remainingPM', dp_id='$data->dpID', dp_total='$data->dpTotal', ".$qShiftID."group_id=null WHERE id IN (";
                    }else{
                        $sql = "UPDATE `transaksi` SET status='$data->status', tipe_bayar='$data->remainingPM', dp_id='$data->dpID', ".$qShiftID." dp_total='$data->dpTotal' WHERE id IN (";
                    }
                    $updateDP = mysqli_query($db_conn, "UPDATE down_payments SET transaction_id='$value', used_at=NOW() WHERE id='$data->dpID'");
                }
            }else{
                $collector_id = $token->id;
                if(empty($data->groupID)){
                    if($data->status==2){
                    $sql  =  "UPDATE `transaksi` SET status='$data->status', tipe_bayar='$data->paymentMethod', shift_id='$shiftID', collector_id='$collector_id', group_id=null WHERE id IN (";
                    $sql1 =  "UPDATE `transaksi` SET status='$data->status', tipe_bayar='$data->paymentMethod', shift_id='$shiftID', collector_id='$collector_id', group_id=null WHERE id IN (";
                    }else{
                        $sql  =  "UPDATE `transaksi` SET status='$data->status', tipe_bayar='$data->paymentMethod',group_id=null WHERE id IN (";
                    }
                }else{
                    if($data->status==2){
                    $sql  = "UPDATE `transaksi` SET status='$data->status', tipe_bayar='$data->paymentMethod', shift_id='$shiftID', collector_id='$collector_id' WHERE id IN (";
                    $sql1 =  "UPDATE `transaksi` SET status='$data->status', tipe_bayar='$data->paymentMethod', shift_id='$shiftID', collector_id='$collector_id' WHERE id IN (";
                    }else{
                        $sql  =  "UPDATE `transaksi` SET status='$data->status', tipe_bayar='$data->paymentMethod' WHERE id IN (";
                    }
                }

            }


            $i = 0;
            foreach ($transactionID as $value) {
                $getTrx = mysqli_query($db_conn, "SELECT transaksi.no_meja, transaksi.id_partner, users.dev_token AS udev_token, meja.is_queue, transaksi.takeaway, transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount,transaksi.point, transaksi.queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.charge_ur, users.name, users.name AS uname, payment_method.nama AS payment_method, users.Gender, users.TglLahir as birth_date, transaksi.status, transaksi.customer_email, users.email, partner.name AS partner_name, transaksi.is_helper FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar JOIN meja ON  meja.idmeja=transaksi.no_meja WHERE transaksi.id='$value'");
                if(mysqli_num_rows($getTrx)==0) {
                    $getTrx = mysqli_query($db_conn, "SELECT transaksi.no_meja, transaksi.id_partner, users.dev_token AS udev_token, transaksi.takeaway, transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.point, transaksi.queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.charge_ur, users.name, users.name AS uname, payment_method.nama AS payment_method, users.Gender, users.TglLahir as birth_date, transaksi.status, transaksi.customer_email, users.email, partner.name AS partner_name, transaksi.is_helper FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id='$value'");
                    if(mysqli_num_rows($getTrx)==0) {
                        $getTrx = mysqli_query($db_conn, "SELECT transaksi.no_meja, transaksi.id_partner, transaksi.takeaway, transaksi.phone, transaksi.jam, transaksi.total, transaksi.id_voucher, transaksi.id_voucher_redeemable, transaksi.tipe_bayar, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.point, transaksi.queue, transaksi.notes, transaksi.tax, transaksi.service, transaksi.charge_ur, transaksi.customer_name as name, transaksi.customer_name uname, payment_method.nama AS payment_method, 'unknown' Gender, '2000-01-01' as birth_date, transaksi.status, transaksi.customer_email, transaksi.customer_email as email, partner.name AS partner_name, transaksi.is_helper FROM `transaksi` JOIN partner ON transaksi.id_partner=partner.id JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id='$value'");
                    }
                }
                $no_meja=0;
                $id_partner=0;
                $dev_token=0;
                $udev_token=0;
                $is_queue = 0;
                $is_helper = 0;
                $takeaway = 0;
                $tStatus=1;
                $queue = 0;
                $order = [];
                $charge_ur = 0;
                $phone = "";
                $user_email = "";
                $user_name = "";
                $partner_name = "";
                $title="Pembayaran Diterima";
                $message="Pembayaran untuk pesanan anda telah diterima, silahkan tunggu pesanan anda.";
                $id = null;
                $action = null;

                while ($row = mysqli_fetch_assoc($getTrx)) {
                    $order = $row;
                    $no_meja=$row['no_meja'];
                    $id_partner=$row['id_partner'];
                    $is_helper = $row['is_helper'];
                    // $dev_token=$row['device_token'];
                    $udev_token="";
                    if(isset($row['udev_token'])){
                        $udev_token = $row['udev_token'];
                    }
                    if(isset($row['is_queue'])){
                        $is_queue = $row['is_queue'];
                    }
                    $partner_name = $row['partner_name'];
                    $user_name = $row['name'];
                    $user_email = $row['customer_email'];
                    if(isset($row['email']) && !empty($row['email'])){
                        $user_email = $row['email'];
                    }
                    $takeaway = $row['takeaway'];
                    $jam = $row['jam'];
                    $phone = $row['phone'];
                    $total = $row['total'];
                    $id_voucher = $row['id_voucher'];
                    $id_voucher_redeemable = $row['id_voucher_redeemable'];
                    $tipe_bayar = $row['tipe_bayar'];
                    $promo = $row['promo'];
                    $diskon_spesial = $row['diskon_spesial'];
                    $employee_discount = $row['employee_discount'];
                    $point = $row['point'];
                    $queue = $row['queue'];
                    $takeaway = $row['takeaway'];
                    $notes = $row['notes'];
                    $tax = $row['tax'];
                    $service = $row['service'];
                    $charge_ur = $row['charge_ur'];
                    $payment_method = $row['payment_method'];
                    $uname = $row['uname'];
                    $birthDate = $row['birth_date'];
                    $gender = $row['Gender'];
                    $tStatus = $row['status'];
                }
                if($is_helper == 1){
                    $tokensQ = mysqli_query($db_conn, "SELECT tokens FROM `device_tokens` WHERE no_meja='$no_meja' AND deleted_at IS NULL");
                } else {
                    $tokensQ = mysqli_query($db_conn, "SELECT tokens FROM `device_tokens` WHERE user_phone='$phone' AND deleted_at IS NULL");
                }
                $udevTokens = array();
                $index=0;
                while ($row = mysqli_fetch_assoc($tokensQ)) {
                    $udevTokens[$index]['token'] = $row['tokens'];
                    $index++;
                }
                $membershipQ = mysqli_query($db_conn,"SELECT m.id FROM memberships m JOIN partner p ON p.id_master=m.master_id WHERE m.user_phone='$phone' AND p.id='$id_partner' ORDER BY m.id DESC LIMIT 1");
                if (mysqli_num_rows($membershipQ) > 0) {
                    $isMembership = true;
                }else{
                    $isMembership = false;
                }
                foreach ($udevTokens as $val) {
                    $dev_token=$val['token'];
                    $fcm_token=$dev_token;
                    $notif = $db->savePaymentNotification($fcm_token, $title, $message, $no_meja, 'ur-user', $payment_method, $data->status, $queue, $value, $id_partner, $action, $order, $gender, $birthDate, $isMembership, 0, '', $phone);
                    $insertMessage = mysqli_query($db_conn, "INSERT INTO `messages` SET phone='$phone', title='$title', content='$message', type=0, transaction_id='$value'");
                }

                if($i>0){
                    $sql.=",";
                    $sql1.=",";
                }
                $sql .= "'$value'";
                $sql1 .= "'$value'";
                $i+=1;
                $tStatus=0;
                $getStatus = mysqli_query($db_conn, "SELECT status FROM `transaksi` WHERE id='$value'");
                while($row=mysqli_fetch_assoc($getStatus)){
                    $tStatus= $row['status'];
                }
                
                $payment_method_before = $tipe_bayar;
                $payment_method_after = $data->paymentMethod;
                if($data->status == '4' || $data->status == 4){
                    $payment_method_after = 0;
                }
                if(($data->status == '2' || $data->status == 2) && ($tStatus !== 5 && $tStatus !== '5') ){
                    $payment_method_after = $payment_method_before;
                }
                if($payment_method_before = 11 || $payment_method_before = '11'){
                    $payment_method_before = 0;
                }
                
                $insertOST = mysqli_query($db_conn, "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`, `payment_method_before`, `payment_method_after`, `created_by`) 
                    SELECT 
                    	transaction_id, 
                        $tStatus, 
                        $data->status,
                        payment_method_after,
                        $payment_method_after,
                        $token->id
                    FROM order_status_trackings
                    WHERE transaction_id = '$value'
                    ORDER BY id DESC
                    LIMIT 1");

                $masterID = mysqli_query($db_conn, "SELECT id_master FROM `partner` WHERE id='$id_partner'");
                $id_master=0;
                while($row=mysqli_fetch_assoc($masterID)){
                    $id_master= $row['id_master'];
                }
                // $updateDeposit = mysqli_query($db_conn,"UPDATE `master` SET `deposit_balance`=`deposit_balance`-'$charge_ur' WHERE id='$id_master'");
                if($data->status!=7 || $data->paymentMethod!=12 || $data->status!="7" || $data->paymentMethod!="12"){
                    $updateTrans = mysqli_query($db_conn,"UPDATE transaksi SET paid_date='$today11' WHERE id='$value'");
                }
            }
            
            $update = "";
            $paidValidator = 0;
            if($data->groupID == ""){
                if(isset($data->is_change_payment) && $data->is_change_payment == "1"){
                    if($data->voucher_type == "Membership" || $data->voucher_type == "Redeemable"){
                        $qVoucher = mysqli_query($db_conn, "UPDATE `transaksi` SET tipe_bayar='$data->paymentMethod', id_voucher_redeemable='', promo=0 WHERE id='$data->transactionsID'");
                    } else if ($data->voucher_type == "Voucher"){
                        $qVoucher = mysqli_query($db_conn, "UPDATE `transaksi` SET tipe_bayar='$data->paymentMethod', id_voucher='', promo=0 WHERE id='$data->transactionsID'");
                    }
                }
                $qP = mysqli_query($db_conn, "SELECT id, status FROM transaksi WHERE status = 2 AND id = '$data->transactionsID'");
                if(mysqli_num_rows($qP) > 0){
                    $paidValidator = 1;
                } else {
                    $sql .=")";
                    $update = mysqli_query($db_conn,$sql);
                }
            } else {
                $sql .=")";
                $update = mysqli_query($db_conn,$sql);
            }
            
            if($data->groupID){
                $qP = mysqli_query($db_conn, "SELECT id  FROM `transaction_groups` WHERE id='$data->groupID' AND status=2");
                if(mysqli_num_rows($qP) > 0){
                    $paidValidator = 1;
                }
            }
            
            foreach ($transactionID as $value) {
                $groupID=0;
                $getGroupID = mysqli_query($db_conn, "SELECT group_id FROM `transaksi` WHERE id='$value'");
                while($row=mysqli_fetch_assoc($getGroupID)){
                    $groupID= $row['group_id'];
                    $getStatusG = mysqli_query($db_conn, "SELECT status  FROM `transaksi` WHERE `group_id` = '$groupID'");
                    $tgStatus="2";
                    $indexCo = 0;
                    while($row=mysqli_fetch_assoc($getStatusG)){
                        if($row['status']!="2"){
                            $tgStatus=$row['status'];
                        }
                        $indexCo += 1;
                    }
                    if($tgStatus=="2" && $indexCo>0){
                        $updateTG = mysqli_query($db_conn, "UPDATE `transaction_groups` set status='$data->status', payment_method='$data->paymentMethod', paid_date=NOW() WHERE id='$groupID'");
                    }
                }
            }
            
            if($data->paymentMethod==13 || $data->paymentMethod=="13"){
                if($tgStatus=="2" && $indexCo>0){
                $updateTG = mysqli_query($db_conn, "UPDATE `transaction_groups` set status='$data->status', payment_method='$data->remainingPM', paid_date=NOW() WHERE id='$groupID'");
                }
            }else{
                if($tgStatus=="2" && $indexCo>0){
                $updateTG = mysqli_query($db_conn, "UPDATE `transaction_groups` set status='$data->status', payment_method='$data->paymentMethod', paid_date=NOW() WHERE id='$groupID'");
                }
            }
            
            
            if($paidValidator == 0){
                if($data->paymentMethod==13 || $data->paymentMethod=="13"){
                }else{
                    $sql1 .=") AND status!='1'";
                    $update1 = mysqli_query($db_conn,$sql1);
                }
                
                if($update || $update1){
                    if($data->paymentMethod ==13 || $data->paymentMethod=="13"){
    
                    }else{
                        if(isset($data->groupID) && !empty($data->groupID)){
                            if($data->status==2){
                                $updateTG = mysqli_query($db_conn, "UPDATE `transaction_groups` set status='$data->status', payment_method='$data->paymentMethod', paid_date=NOW() WHERE id='$data->groupID'");
                                $updateTGI = mysqli_query($db_conn, "UPDATE `transaksi` SET status='$data->status', tipe_bayar='$data->paymentMethod', shift_id='$shiftID' WHERE group_id='$data->groupID' AND status!='1'");
                                $updateTGI = mysqli_query($db_conn, "UPDATE `transaksi` SET status='$data->status', tipe_bayar='$data->paymentMethod' WHERE group_id='$data->groupID' AND status='1' ");
                            }else{
                                $updateTG = mysqli_query($db_conn, "UPDATE `transaction_groups` set status='$data->status', payment_method='$data->paymentMethod', paid_date=NOW() WHERE id='$data->groupID'");
                                $updateTGI = mysqli_query($db_conn, "UPDATE `transaksi` SET status='$data->status', tipe_bayar='$data->paymentMethod' WHERE group_id='$data->groupID'");
                            }
                        }
                    }
                    //stock reducer
                       if($data->status==1){ $getIdMenu = mysqli_query(
                    $db_conn,
                    "SELECT dt.id_menu, dt.qty, dt.variant, m.is_recipe FROM `detail_transaksi` dt JOIN menu m ON m.id = dt.id_menu WHERE id_transaksi='$data->transactionsID' AND dt.deleted_at IS NULL"
                );
               
                
             
                    while ($rowIdMenu = mysqli_fetch_assoc($getIdMenu)) {
                        $idmenu = $rowIdMenu["id_menu"];
                        $qty = $rowIdMenu["qty"];
                        $isRecipe = $rowIdMenu['is_recipe'];
                        $variant = $rowIdMenu["variant"];
                        $var = substr($variant, 11);
                        $var = substr($var, 0, -1);
                        $var = str_replace("'", '"', $var);
                        $arr_var = json_decode($var, true);
                        if ($arr_var != null) {
                            foreach ($arr_var as $vars) {
                                $d_vars = $vars["detail"];
                                foreach ($d_vars as $detail) {
                                    $var_id = $detail["id"];
                                    $ch = $fs->variant_stock_reduce(
                                        $var_id,
                                        $qty
                                    );
                                }
                            }
                        }
                        // $ch = $fs->stock_reduce($idmenu, $qty);
                        $ch = $fs->stock_reduce($idmenu, $qty, 0, $id_master, $id_partner, $isRecipe);
                        // $debug = $idmenu." ".$qty." ".$id_master." ".$id_partner." ".$isRecipe;
                    }}
                    
                
                    //kirim email
                     
                    if($data->status==2){
                        $transactionID = explode(',', $data->transactionsID);
                        $i = 0;
                        foreach ($transactionID as $value) {
                            $query= "SELECT DISTINCT transaksi.no_meja, transaksi.total, transaksi.promo,transaksi.rounding, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.program_discount, partner.name AS partner_name, users.email, users.name, partner.id AS partner_id, payment_method.nama AS payment_method_name, transaksi.customer_email FROM `transaksi` JOIN `partner` ON `partner`.`id`=`transaksi`.`id_partner` LEFT JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id='$value'";
                            $trxQ = mysqli_query($db_conn, $query);
                            $subtotal = 0;
                            $promo = 0;
                            $program_discount = 0;
                            $diskon_spesial = 0;
                            $employee_discount = 0;
                            $point = 0;
                            $service = 0;
                            $tax = 0;
                            $rounding = 0;
                            $charge_ur = 0;
                            $payment_method_name = "";
                            $partner_name = "";
                            $partner_id = "";
                            $user_name = "";
                            $user_email = "";
                            $no_meja = "";
    
                            while ($row = mysqli_fetch_assoc($trxQ)) {
                                $id_partner=$row['partner_id'];
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
                                $rounding += (int) $row['rounding'];
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
                            $rounding = $rounding;
                            $program_discount = $program_discount;
                            $diskon_spesial = $diskon_spesial;
                            $employee_discount = $employee_discount;
                            $point = $point;
                            $clean_sales = $sales-$promo-$program_discount-$diskon_spesial-$employee_discount-$point;
                            $service = $service;
                            $charge_ur = $charge_ur;
                            $tax = $tax;
                            $total = $subtotal-$promo-$program_discount-$diskon_spesial-$employee_discount-$point+$service+$charge_ur+$tax + $rounding;
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
                                    $template = str_replace('$strRounding ',$fs->rupiah($rounding), $template);
                                    $template = str_replace('$strTax ',$fs->rupiah($tax), $template);
                                    $template = str_replace('$strSUbtot ',$fs->rupiah($total), $template);
                                    $template = str_replace('$type ',$payment_method_name, $template);
                                    $template = str_replace('$id ',$value, $template);
                                    if(substr($value,0,2)=="DI"){
                                        $template = str_replace('$trx_type',"Dine In", $template);
                                    }elseif(substr($value,0,2)=="TA"){
                                        $template = str_replace('$trx_type',"Take Away", $template);
                                    }if(substr($value,0,2)=="DL"){
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
                                $query= "SELECT menu.nama,qty, detail_transaksi.harga FROM `detail_transaksi` JOIN menu ON menu.id=detail_transaksi.id_menu WHERE detail_transaksi.id_transaksi='$value' AND detail_transaksi.deleted_at IS NULL";
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
                                if(isset($user_email) && !empty($user_email)){
                                    if (filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
                                        $insertTe = mysqli_query($db_conn, "INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`, `created_at`) VALUES ('$user_email', '$partner_id', 'UR E-Receipt', $template, NOW())");
                                    }}
                            }
                    }
                    $status = 200;
                    $msg = "Berhasil";
                    $success = 1;
                }else{
                    $status = 204;
                    $msg = "Gagal! silahkan coba lagi";
                    $success = 0;
                }
            } else {
                    $status = 204;
                    $msg = "Transaksi Telah Dibayar";
                    $success = 0;
            }
        }else{
            $status = 204;
            $msg = "Pembayaran yang diijinkan hanya tunai, kartu debit, kartu kredit, dan QRIS";
            $success = 0;
        }
    // }

    }else{
        $status = 400;
        $msg = "Missing Required Fields!";
        $success = 0;
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "transactionDetails"=>$data]);