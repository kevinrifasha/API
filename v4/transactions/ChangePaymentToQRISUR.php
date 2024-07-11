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
$err = "";

//init var
$headers = array();
$rx_http = '/\AHTTP_/';
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
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
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

function generateTransactionID($db_conn, $type, $trxDate, $pid)
{
    $code = $type . "/" . $trxDate . "/" . $pid;
    // $q = mysqli_query($db_conn, "SELECT count(id) as id FROM `transaksi` WHERE id LIKE '%$code%' AND transaksi.deleted_at IS NULL ORDER BY jam DESC LIMIT 1");
    $q = mysqli_query($db_conn, "SELECT count(id) as id FROM `transaksi` WHERE id LIKE '%$code%' ORDER BY jam DESC LIMIT 1");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id1 = (int) $res[0]['id'];
        $index = (int) $id1 + 1;
        if ($index < 10) {
            $index = "00000" . $index;
        } else if ($index < 100) {
            $index = "0000" . $index;
        } else if ($index < 1000) {
            $index = "000" . $index;
        } else if ($index < 10000) {
            $index = "00" . $index;
        } else if ($index < 100000) {
            $index = "0" . $index;
        } else {
            $index = $index;
        }
        $code = $code . "/" . $index;
        return $code;
    } else {
        $code = $code . "/000001";
        return $code;
    }
}

function generateChangedTransactionID($db_conn, $id){
    $b = (explode("/",$id));
    $code = $b[0]."/".$b[1]."/".$b[2];
    $code1 = $b[0]."/".$b[1]."/".$b[2]."/".$b[3];
    $q = mysqli_query($db_conn,"SELECT id FROM `transaksi` WHERE id LIKE '%$code1%' AND transaksi.deleted_at IS NULL ORDER BY jam DESC LIMIT 1");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id1 = $res[0]['id'];
        $b = (explode("/",$id1));
        $c = (explode("-",$b[3]));
        if(isset($c[1]) && !empty($c[1])){
            $c[1] = (int) $c[1]+1;
        }else{
            $c[1]=1;
        }
        $index = (int) $c[0];
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
        $code = $code."/".$index."-".$c[1];
        return $code;
    }
}

function getService($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT service FROM `partner` WHERE id='$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['service'];
    } else {
        return 0;
    }
}

function getTaxEnabled($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT tax FROM `partner` WHERE id='$id'");
    $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
    $tax = $res[0]['tax'];
    return (float) $tax;
}

function getChargeEwallet($db_conn)
{
    $q = mysqli_query($db_conn, "SELECT value FROM settings WHERE name = 'charge_ewallet'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return $res[0]['value'];
    } else {
        return 0;
    }
}

function getChargeXendit($db_conn)
{
    $q = mysqli_query($db_conn, "SELECT value FROM settings WHERE name = 'charge_xendit'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['value'];
    } else {
        return 0;
    }
}

function getHideCharge($idPartner, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT partner.hide_charge FROM `master`
    JOIN partner ON master.id = partner.id_master
    WHERE partner.id ='$idPartner' ");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['hide_charge'];
    } else {
        return 0;
    }
}

function getStatus($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT master.status FROM `master`
    JOIN partner ON master.id = partner.id_master
    WHERE partner.id ='$id' ");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['status'];
    } else {
        return 0;
    }
}

function getShiftID($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT MAX(id) as id FROM `shift` WHERE partner_id='$id' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['id'];
    } else {
        return 0;
    }
}

function getChargeUr($status, $hide, $db_conn, $id)
{
    if ($status == "FULL" && $hide == 0) {
        $q = mysqli_query($db_conn, "SELECT charge_ur as value FROM `partner` WHERE id='$id'");
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            return (int) $res[0]['value'];
        } else {
            return 0;
        }
    } else {
        return 0;
    }
}

function getIDVR($phone, $vr, $trx, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT id  FROM `user_voucher_ownership` WHERE `userid` LIKE '$phone' AND `id_voucher`='$vr' AND `transaksi_id` IS NULL ORDER BY id ASC limit 1");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id = $res[0]['id'];
        $update = mysqli_query($db_conn, "UPDATE `user_voucher_ownership` SET `transaksi_id`='$trx' WHERE id='$id'");
        return $update;
    } else {
        return 0;
    }
}
$id = "";

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $data = json_decode(file_get_contents('php://input'));
    $id = $data->transactionID;
    if($data->paymentMethod == 14  || $data->paymentMethod == "14"){
          $queryPrevStateTrx = "SELECT t.id, t.id_partner, t.queue, t.status, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, p.nama AS payment_method, t.customer_name as name FROM transaksi t JOIN payment_method p ON p.id=t.tipe_bayar  WHERE t.id IN ('" . implode("','", $id). "')";
          
          $mqPrevStateTrx = mysqli_query($db_conn, $queryPrevStateTrx);
          
          $fetchPrevStateTrx = mysqli_fetch_all($mqPrevStateTrx, MYSQLI_ASSOC);
          
          foreach($fetchPrevStateTrx as $trx){
                $validator->checkShiftIDActive($db_conn, $token);
                $partnerID = $token->id_partner;
              
              $editedTrxId = $trx["id"]; 
              $editedStatusTrx = $trx["status"]; 
              $editedPaymentMethodTrx = $trx["tipe_bayar"];
              if (
                    // isset($data->customer_phone)
                    // && isset($data->customer_name)
                    isset($data->total)
                    && isset($data->paymentMethod)
                    // && !empty($data->customer_phone)
                    // && !empty($data->customer_name)
                    && !empty($data->total)
                    && !empty($data->paymentMethod)
                    && (
                        $editedPaymentMethodTrx == "11" 
                        || $editedPaymentMethodTrx == 11 
                        || $editedStatusTrx == "5" 
                        || $editedStatusTrx == 5
                    )
                ) {
                    $employeeID = $token->id;
                    $paymentMethod = $data->paymentMethod;
                    $total = $data->total;
        
                    $boolQty = true;
                    $qr = "";
        
                    if ($boolQty == true) {
                        $statusForOST = $status;
                        if($paymentMethod == 11 || $paymentMethod == '11'){
                            $statusForOST = 0;
                        }
                        
                        $sql .= "INSERT INTO `order_status_trackings`(`transaction_id`, `status_before`, `status_after`, `payment_method_before`, `payment_method_after`, `created_by`) VALUES ('$editedTrxId', '0', '$status',  '0', '$paymentMethod',  '$token->id');";
            
                        // if ($insertDT==5) {
                        // }else{
                        //     $boolCheck=false;
                        // }
                        $sql .= " COMMIT;";
                        $execution = mysqli_multi_query($db_conn, $sql);
                       do {
                        // fetch results
                            if($result = mysqli_use_result($db_conn)){
                                while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                    $test1 = $row;
                                    // if(count($row) > 17)
                                    //     $orders[] = $row;
                                    // elseif(count($row) == 6)
                                    //     $inv[] = $row;
                                }
                            }
                            $c++;
                            if(!mysqli_more_results($db_conn)) break;
                            if(!mysqli_next_result($db_conn) || mysqli_errno($db_conn)) {
                                // report error
                                $err = mysqli_error($db_conn);
                                break;
                            }
                        } while(true);
                        mysqli_free_result($result);
        
                        if($err == ""){
                            $qDT = mysqli_query($db_conn, "SELECT dt.id_menu, dt.qty, dt.variant, m.is_recipe FROM `detail_transaksi` dt JOIN menu m ON m.id = dt.id_menu WHERE dt.id_transaksi='$editedTrxId' AND dt.deleted_at IS NULL");
                            if (mysqli_num_rows($qDT) > 0) {
                                $detailsTransaction = mysqli_fetch_all($qDT, MYSQLI_ASSOC);
                                $menusOrder = array();
                                $variantOrder = array();
                                $imo = 0;
                                $iv = 0;
                                foreach ($detailsTransaction as $value) {
                                    $menusOrder[$imo]['id_menu'] = $value['id_menu'];
                                    $menusOrder[$imo]['qty'] = $value['qty'];
                                    $menusOrder[$imo]["is_recipe"] = $value["is_recipe"];
                                    $cut = $value['variant'];
                                    $cut = substr($cut, 11);
                                    $cut = substr($cut, 0, -1);
                                    $cut = str_replace("'", '"', $cut);
                                    $menusOrder[$imo]['variant'] = json_decode($cut);
                                    if ($menusOrder[$imo]['variant'] != NULL) {
                                        foreach ($menusOrder[$imo]['variant'] as $value1) {
                                            foreach ($value1->detail as $value2) {
                                                $variantOrder[$iv]['id'] = $value2->id;
                                                $variantOrder[$iv]['qty'] = $value2->qty;
                                                $ch = $fs->variant_stock_reduce($value2->id, $value2->qty);
                                                $iv += 1;
                                            }
                                        }
                                    }
                                    $imo += 1;
                                }
                                $sqlMaster = mysqli_query($db_conn, "SELECT id_master FROM partner WHERE id = '$partnerID' AND deleted_at IS NULL");
                                $getMaster = mysqli_fetch_all($sqlMaster, MYSQLI_ASSOC);
                                $id_master = $getMaster[0]['id_master'];
                                //Menu
                                foreach ($menusOrder as $value) {
                                    $qtyOrder = $value["qty"];
                                    $menuID = $value['id_menu'];
                                    $isRecipe = $value["is_recipe"];
                                    $ch = $fs->stock_reduce($menuID, $qtyOrder, 0, $id_master, $partnerID, $isRecipe);
                                }
                            }
            
                            if (isset($data->customer_email) && !empty($data->customer_email) && $status == '2' && $paymentMethod != '11' && $paymentMethod != 11 && $paymentMethod != 12 && $paymentMethod != "12") {
                                $query = "SELECT transaksi.no_meja, transaksi.total, transaksi.promo, transaksi.diskon_spesial, transaksi.employee_discount, transaksi.service, transaksi.charge_ur, transaksi.point, transaksi.tax, transaksi.program_discount, partner.name AS partner_name, users.email, users.name, partner.id AS partner_id, payment_method.nama AS payment_method_name, transaksi.customer_email FROM `transaksi` JOIN `partner` ON `partner`.`id`=`transaksi`.`id_partner` LEFT JOIN users ON users.phone=transaksi.phone JOIN payment_method ON payment_method.id=transaksi.tipe_bayar WHERE transaksi.id='$editedTrxId'";
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
                                $partnerID = "";
                                $user_name = "";
                                $user_email = "";
                                $no_meja = "";
            
                                while ($row = mysqli_fetch_assoc($trxQ)) {
                                    $payment_method_name = $row['payment_method_name'];
                                    $partner_name = $row['partner_name'];
                                    $partnerID = $row['partner_id'];
                                    $user_name = $row['name'];
                                    $user_email = $row['customer_email'];
                                    if (isset($row['email']) && !empty($row['email'])) {
                                        $user_email = $row['email'];
                                    }
                                    $no_meja = $row['no_meja'];
                                    $subtotal += (int) $row['total'];
                                    $promo += (int) $row['promo'];
                                    $program_discount += (int) $row['program_discount'];
                                    $diskon_spesial += (int) $row['diskon_spesial'];
                                    $employee_discount += (int) $row['employee_discount'];
                                    $point += (int) $row['point'];
                                    $tempS = (((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point']) * (int) $row['service'] / 100);
                                    $service += $tempS;
                                    $charge_ur += (int) $row['charge_ur'];
                                    $tempT = (((int) $row['total'] - (int) $row['promo'] - (int) $row['program_discount'] - (int) $row['diskon_spesial'] - (int) $row['employee_discount'] - (int) $row['point'] + $tempS + (int) $row['charge_ur']) * (float) $row['tax'] / 100);
                                    $tax += $tempT;
                                }
                                $subtotal = $subtotal;
                                $sales = $subtotal + $service + $tax + $charge_ur;
                                $promo = $promo;
                                $program_discount = $program_discount;
                                $diskon_spesial = $diskon_spesial;
                                $employee_discount = $employee_discount;
                                $point = $point;
                                $clean_sales = $sales - $promo - $program_discount - $diskon_spesial - $employee_discount - $point;
                                $service = $service;
                                $charge_ur = $charge_ur;
                                $tax = $tax;
                                $total = ceil($subtotal - $promo - $program_discount - $diskon_spesial - $employee_discount - $point + $service + $charge_ur + $tax);
                                $dateNow = date('d M Y');
                                $timeNow = date("h:i");
            
                                $query = "SELECT template FROM `email_template` WHERE id=1";
                                $templateQ = mysqli_query($db_conn, $query);
                                if (mysqli_num_rows($templateQ) > 0) {
                                    $templates = mysqli_fetch_all($templateQ, MYSQLI_ASSOC);
                                    $template = $templates[0]['template'];
                                    $template = str_replace('$strTottotal', $fs->rupiah($total), $template);
                                    $template = str_replace('$dateNow ', $dateNow, $template);
                                    $template = str_replace('$timeNow ', $timeNow, $template);
                                    $template = str_replace('$name ', $user_name, $template);
                                    $template = str_replace('$partnerName ', $partner_name, $template);
                                    $template = str_replace('$no_meja ', $no_meja, $template);
                                    $template = str_replace('$strTot ', $fs->rupiah($subtotal), $template);
                                    $template = str_replace('$strServ ', $fs->rupiah($service), $template);
                                    $template = str_replace('$strChargeUR ', $fs->rupiah($charge_ur), $template);
                                    $template = str_replace('$strTax ', $fs->rupiah($tax), $template);
                                    $template = str_replace('$strSUbtot ', $fs->rupiah($total), $template);
                                    $template = str_replace('$type ', $payment_method_name, $template);
                                    $template = str_replace('$id ', $editedTrxId, $template);
                                    if (substr($editedTrxId, 0, 2) == "DI") {
                                        $template = str_replace('$trx_type', "Dine In", $template);
                                    } elseif (substr($editedTrxId, 0, 2) == "TA") {
                                        $template = str_replace('$trx_type', "Take Away", $template);
                                    }
                                    if (substr($editedTrxId, 0, 2) == "DL") {
                                        $template = str_replace('$trx_type', "Delivery", $template);
                                    } else {
                                        $template = str_replace('$trx_type', "Pre Order", $template);
                                    }
                                    if ($promo > 0) {
                                        $template = str_replace('$promo', '
                                                    <tr>
                                                    <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Promo Voucher</td>
                                                    <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($promo) . ' </td>
                                                </tr>', $template);
                                    } else {
                                        $template = str_replace('$promo', '', $template);
                                    }
            
                                    if ($diskon_spesial > 0) {
                                        $template = str_replace('$Diskon Spesial', '<tr>
                                                    <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Diskon Spesial</td>
                                                    <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($diskon_spesial) . '</td>
                                                </tr>', $template);
                                    } else {
                                        $template = str_replace('$Diskon Spesial', '', $template);
                                    }
            
                                    if ($employee_discount > 0) {
                                        $template = str_replace('$Diskon Karyawan', '<tr>
                                                    <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Diskon Karyawan</td>
                                                    <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($employee_discount) . '</td>
                                                </tr>', $template);
                                    } else {
                                        $template = str_replace('$Diskon Karyawan', '', $template);
                                    }
                                    if ($program_discount > 0) {
                                        $template = str_replace('$Diskon Program', '<tr>
                                                    <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Diskon Program</td>
                                                    <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($program_discount) . '</td>
                                                </tr>', $template);
                                    } else {
                                        $template = str_replace('$Diskon Program', '', $template);
                                    }
                                }
                                $query = "SELECT menu.nama,qty, detail_transaksi.harga FROM `detail_transaksi` JOIN menu ON menu.id=detail_transaksi.id_menu WHERE detail_transaksi.id_transaksi='$editedTrxId' AND detail_transaksi.deleted_at IS NULL";
                                $detail_trx = mysqli_query($db_conn, $query);
            
                                $detail_transaction = "";
                                while ($row = mysqli_fetch_assoc($detail_trx)) {
                                    $nama_menu = $row['nama'];
                                    $qty_menu = $row['qty'];
                                    $harga_menu = $row['harga'];
                                    $detail_transaction .= '
                                                <tr>
                                                <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $nama_menu . ' X  ' . $qty_menu . ' </td>
                                                <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"> ' . $fs->rupiah($harga_menu) . ' </td>
                                                </tr>';
                                }
                                $template = str_replace('$loop detail', $detail_transaction, $template);
                                $template = json_encode($template);
                                if (filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
                                    $insertTe = mysqli_query($db_conn, "INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`, `created_at`) VALUES ('$user_email', '$partnerID', 'UR E-Receipt', $template, NOW())");
                                }
                            }
                            
                            if (
                                $paymentMethod != 0 || $paymentMethod != "0" ||
                                $paymentMethod != 1 || $paymentMethod != "1" ||
                                $paymentMethod != 2   || $paymentMethod != "2" ||
                                $paymentMethod != 3 || $paymentMethod != "3" ||
                                $paymentMethod != 4 || $paymentMethod != "4" ||
                                $paymentMethod != 6 || $paymentMethod != "6" ||
                                $paymentMethod != 10 || $paymentMethod != "10"
                            ) {
                                $allTrans = mysqli_query($db_conn, "SELECT t.id, t.id_partner, t.queue, t.status, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, p.nama AS payment_method, u.name AS uname FROM transaksi t JOIN payment_method p ON p.id=t.tipe_bayar JOIN users u ON t.phone=u.phone WHERE t.id='$editedTrxId'");
                                $order = mysqli_fetch_assoc($allTrans);
                                $devTokens = $db->getPartnerDeviceTokens($partnerID);
                                foreach ($devTokens as $val) {
                                    $mID = $db->getMembership($partnerID, $phone);
                                    if ($mID == 0) {
                                        $isMembership = false;
                                    } else {
                                        $isMembership = true;
                                    }
                                    $birth_date = $db->getBirthdate($phone);
                                    $gender = $db->getGender($phone);
                                }
                                $msg = "Success";
                                $success = 1;
                                $status = 200;
                            }
                            
                                $params = [
                                    // "external_id" => $newID,
                                    "reference_id" => $editedTrxId,
                                    // "callback_url" => $_ENV["BASEURL"] . "xendit/qris/Callback.php",
                                    "currency" => "IDR",
                                    "amount" => $total,
                                    "type" => "DYNAMIC",
                                    "channel_code" => "ID_DANA",
                                    "metadata" => [
                                        "branch_code" => "tree_branch",
                                    ],
                                ];
                                
                                $ch = curl_init();
                                $timestamp = new DateTime();
                                // curl_setopt($ch, CURLOPT_URL, $url);
                                $body = json_encode($params);
                                curl_setopt(
                                $ch,
                                    CURLOPT_URL,
                                    "https://" .
                                        $_ENV["XENDIT_URL"] .
                                        "/qr_codes"
                                );
                                
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($ch, CURLOPT_POST, 1);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                                curl_setopt($ch, CURLOPT_USERPWD, $_ENV['XENDIT_KEY']. ':' . '');
                                $headers = array();
                                $headers[] = 'Content-Type: application/json';
                                $headers[] = "api-version: 2022-07-31";
                                $headers[] = "webhook-url: " . $_ENV["BASEURL"] . "xendit/qris/CallbackQRISPOS.php";
                                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                $result = curl_exec($ch);
                                if (curl_errno($ch)) {
                                    echo 'Error:' . curl_error($ch);
                                }
                                $ewallet_response = $result;
                                $ewallet_response_returned = json_decode($ewallet_response);
                                $qrString =  $ewallet_response_returned->qr_string;
                                $updateQR = mysqli_query($db_conn, "UPDATE transaksi SET id='$editedTrxId', qr_string='$qrString' WHERE id='$editedTrxId'"); 
                                curl_close($ch);
                                $UpdateCallback = mysqli_query(
                                    $db_conn,
                                    "INSERT INTO `xendit_callbacks`(`transaction_id`, `value`, `created_at`) VALUES ('$editedTrxId', '$result', NOW())"
                                );
                            
                        } else {
                            $msg = "Terjadi Kesalahan, Mohon Coba Lagi";
                            $success = 0;
                            $status = 203;  
                        }
                        // if ($execution) {
                        // } else {
                        //     $sql = "START TRANSACTION; ROLLBACK TO '$editedTrxId';";
                        //     $act = mysqli_multi_query($db_conn, $sql) or die(mysqli_error($db_conn));
                        //     $msg = "Failed Create Detail";
                        //     $success = 0;
                        //     $status = 204;
                        // }
                    } else {
                        $msg = "Stock ";
                        foreach ($res1 as $x) {
                            $msg .= $x['nama'] . ", ";
                            if (empty($x['rm'])) {
                            } else {
                                $msg .= "bahan baku ";
                                foreach ($x['rm'] as $y) {
                                    $msg .= $y['name'] . " (dibutuhkan " . (int)$y['needed'] . ", tersedia " . $y['inStock'] . ", selisih " . $y['difference'] . "), ";
                                }
                            }
                        }
                        $msg .= "tidak mencukupi";
                        // $msg = "Stock Menu Tidak Mencukupi";
                        // $msg = "Stock".$res1." tidak mencukupi";
                        $success = 0;
                        $status = 204;
                    }
                } else {
                    $success = 0;
                    $msg = "Missing Mandatory Field";
                    $status = 400;
                }
          }
          if (count($fetchPrevStateTrx) == 0){
            $success = 0;
            $msg = "Transaction Id Cannot be Empty";
            $status = 400;
          }
    } else {
        $success = 0;
        $msg = "Payment Method Is Not QRIS UR";
        $status = 400;
    }
    
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "unavailable" => $res1, "transaction_id" => $id, "qris_string"=>$qrString, "ewallet_response"=> $ewallet_response_returned]);

