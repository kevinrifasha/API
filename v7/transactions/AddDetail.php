<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require '../../includes/functions.php';
require_once('../auth/Token.php');
require_once '../../includes/DbOperation.php';

// date_default_timezone_set('Asia/Jakarta');

// POST DATA
$fs = new functions();
$db = new DbOperation();
date_default_timezone_set('Asia/Jakarta');
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
$today1 = date('Y-m-d');
$tokenizer = new Token();
$token = '';
$res = array();
$employeeID=0;

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

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$total = 0;
$gtotal = 0;
$service = 0;
$tax = 0;
$charge_ur = 0;
$voucherID = "";
$vType=0;
$vIsPercent = 0;
$discountPercent = 0;
$prerequisite=[];
$promo = 0;
$detailIDs = [];
$detailIdx = 0;
$test = [];
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $obj = json_decode(file_get_contents('php://input'));
    if(
        isset($obj->dataDetail) && !empty($obj->dataDetail)
        && isset($obj->transaction_id) && !empty($obj->transaction_id)
    ){

        $q = mysqli_query($db_conn,"SELECT id, id_partner, charge_ur, status, employee_discount_percent, promo, id_voucher FROM `transaksi` WHERE id='$obj->transaction_id'  AND diskon_spesial='0' AND program_discount='0'");
        $partner_id = '';
        $charge_ur = 0;
        $total = 0;
        $status = 0;
        $edp=0;
        if (mysqli_num_rows($q) > 0) {
            while($row = mysqli_fetch_assoc($q)){
                $partner_id = $row['id_partner'];
                $charge_ur = (int) $row['charge_ur'];
                $status = (int) $row['status'];
                $edp = (int) $row['employee_discount_percent'];
                $voucherID = $row['id_voucher'];
                $promo = $row['promo'];
        }
       if((int)$promo>0){
            $promo = $obj->promo;
            $voucherID = $obj->voucherID;
       }
        if($status==5){
            $dataDetail = $obj->dataDetail;
            foreach ($dataDetail as $cart) {
                $surcharge_change = 0;
                if(isset($cart->surcharge_change) && !empty(isset($cart->surcharge_change)) && $cart->surcharge_change!=0){
                    $surcharge_change = $cart->surcharge_change;
                }

                $is_program = 0;
                if(isset($cart->is_program) && !empty(isset($cart->is_program)) && $cart->is_program!=0){
                    $is_program = $cart->is_program;
                }

                $is_smart_waiter  = 0;
                if(isset($cart->is_smart_waiter ) && !empty(isset($cart->is_smart_waiter )) && $cart->is_smart_waiter !=0){
                    $is_smart_waiter  = $cart->is_smart_waiter ;
                }

                $json = "'[''variant'':[";
                $i = 0;
                if (empty($cart->variant)) {
                    $json = "''";
                } else {
                    $variant = $cart->variant;
                    foreach ($variant as $vars) {
                        if ($i == 0) {
                          $json .= "{";
                        } else {
                          $json .= ",{";
                        }

                        $json .= "''name'':''" . $vars->name . "'',";
                        $json .= "''id'':''" . $vars->id_variant . "'',";
                        $json .= "''tipe'':''" . $vars->type . "'',";
                        $json .= "''detail'':[";
                        $dvariant = $vars->data_variant;
                        $i += 1;
                        $j = 0;
                        foreach ($dvariant as $detail) {
                          if ($j == 0) {
                            $json .= "{";
                          } else {
                            $json .= ",{";
                          }
                            $json .= "''id'':''" . $detail->id . "'',";
                            $json .= "''qty'':''" . $detail->qty . "'',";
                            $json .= "''name'':''" . $detail->name . "''}";
                            $j += 1;
                        }
                        $json .= "]}";
                    }
                    $json .= "]]'";
                          // $json = json_encode($json);
                }

                if(!isset($cart->employeeID)){
                    $employeeID = $token->id;
                }else{
                    $employeeID=$cart->employeeID;
                }
                $isConsignment=0;
                if(isset($cart->isConsignment) && !empty($cart->isConsignment)){
                    if($cart->isConsignment =="1" || $cart->isConsignment ==1){
                        $isConsignment = 1;
                        $service= 0;
                        $tax = 0;
                    }
                }
                $insertDetail = mysqli_query($db_conn, "INSERT INTO detail_transaksi(id_transaksi, id_menu, harga_satuan, qty, notes, harga,variant, is_program, surcharge_change, is_smart_waiter, server_id,is_consignment) VALUES ('$obj->transaction_id', '$cart->id_menu', '$cart->harga_satuan', '$cart->qty', '$cart->notes', '$cart->harga', $json, $is_program, '$surcharge_change', '$is_smart_waiter', '$employeeID', '$isConsignment');");
                $iidDetail = mysqli_insert_id($db_conn);
                $detailIDs[$detailIdx]=$iidDetail;
                if($status==1 || $status==2 || $status==6 || $status==5){
                    // $qDT = mysqli_query($db_conn,"SELECT id_menu, qty, variant FROM `detail_transaksi` WHERE id='$iidDetail' AND deleted_at IS NULL");
                    $qDT = mysqli_query($db_conn,"SELECT dt.id_menu, dt.qty, dt.variant, m.is_recipe FROM detail_transaksi dt JOIN menu m ON m.id = dt.id_menu WHERE dt.id='$iidDetail' AND dt.deleted_at IS NULL");
                    if(mysqli_num_rows($qDT) > 0){
                        $detailsTransaction = mysqli_fetch_all($qDT, MYSQLI_ASSOC);

                        $menusOrder = array();
                        $variantOrder = array();
                        $imo = 0;
                        $iv = 0;
                        foreach($detailsTransaction as $value){
                            $menusOrder[$imo]['id_menu'] = $value['id_menu'];
                            $menusOrder[$imo]['qty'] = $value['qty'];
                            $menusOrder[$imo]["is_recipe"] = $value["is_recipe"];
                            $cut = $value['variant'];
                            $cut = substr($cut, 11);
                            $cut = substr($cut, 0, -1);
                            $cut = str_replace("'",'"',$cut);
                            $menusOrder[$imo]['variant'] = json_decode($cut);
                            if($menusOrder[$imo]['variant']!=NULL){
                                foreach ($menusOrder[$imo]['variant'] as $value1) {
                                    foreach ($value1->detail as $value2) {
                                        $variantOrder[$iv]['id']=$value2->id;
                                        $variantOrder[$iv]['qty']=$value2->qty;
                                        $ch = $fs->variant_stock_reduce($value2->id, $value2->qty);
                                        $iv+=1;
                                    }
                                }
                            }
                            $imo+=1;
                        }
                        $sqlMaster = mysqli_query($db_conn, "SELECT id_master FROM partner WHERE id = '$partner_id' AND deleted_at IS NULL");
                        $getMaster = mysqli_fetch_all($sqlMaster, MYSQLI_ASSOC);
                        $id_master = $getMaster[0]['id_master'];
                        //Menu
                        foreach ($menusOrder as $value) {
                            $qtyOrder = $value["qty"];
                            $menuID = $value['id_menu'];
                            $isRecipe = $value["is_recipe"];
                            // $ch = $fs->stock_reduce($menuID, $qtyOrder);
                            $ch = $fs->stock_reduce($menuID, $qtyOrder, 0, $id_master, $partner_id, $isRecipe);
                        }
                    }
                }
            
                $detailIdx++;
            }

            $qDT = mysqli_query($db_conn,"SELECT harga_satuan, qty FROM `detail_transaksi` WHERE id_transaksi='$obj->transaction_id' AND deleted_at IS NULL");
            if (mysqli_num_rows($qDT) > 0) {
                while($row = mysqli_fetch_assoc($qDT)){
                    $total += (int) $row['harga_satuan'] * (int) $row['qty'];
                }
            }
            if($edp==0){
                $discountValue=0;
            }else{
            $discountValue = ceil($total*$edp/100);
            }

            $pservice =  getService($partner_id, $db_conn);
            $service = ceil(($total-$discountValue)*$pservice/100);
            $ptax =  getTaxEnabled($partner_id, $db_conn);
            $tax = ceil(((($total-$discountValue)+$service+$charge_ur)*$ptax)/100);
            $gtotal = $total-$discountValue+$charge_ur+$service+$tax;
            $transactionID = $obj->transaction_id;

            if ($insertDetail) {
                $allTrans = mysqli_query($db_conn, "SELECT t.id, t.id_partner, t.status, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur,p.nama AS payment_method, u.name AS uname FROM transaksi t LEFT JOIN payment_method p ON p.id=t.tipe_bayar LEFT JOIN users u ON t.phone=u.phone WHERE t.id='$transactionID'");
                $order = mysqli_fetch_assoc($allTrans);

                $devTokens = $db->getPartnerDeviceTokens($partner_id);
                $title = "Pesanan Tambahan";
                $tableNum = $order["no_meja"];
                $message = "Pesanan tambahan di Meja " . $tableNum;
                $detailIDs = json_encode($detailIDs);
                $gender = $db->getGender($phone);
                $birth_date = $db->getBirthdate($phone);
                $isMembership = false;
                $mID = $db->getMembership($partner_id, $phone);
                if ($mID == 0) {
                    $isMembership = false;
                } else {
                    $isMembership = true;
                }
                
                $devTokens = $db->getPartnerDeviceTokens($partner_id);
                $order = json_encode($order);
                foreach ($devTokens as $val) {
                
                $dev_token = $val['token'];
                
                    $qSelect = "SELECT e.id FROM employees e LEFT JOIN device_tokens dt ON dt.employee_id = e.id LEFT JOIN roles r ON r.id = e.role_id  WHERE r.is_order_notif=1 AND e.order_notification=1 AND e.deleted_at IS NULL AND dt.deleted_at IS NULL AND dt.tokens = '$dev_token' AND dt.id_partner = '$partner_id'";
                    $selectForNotif = mysqli_query($db_conn, $qSelect);
                    
                    if(mysqli_num_rows($selectForNotif) > 0){
                        // $db->savePaymentNotification($dev_token, $title, $message, $order->no_meja, 'rn-push-notification-channel', '11', 0, 0, $transactionID, $partner_id, null, $order, '', '', '', 0, "employee", '');
                        $qInsert = "INSERT INTO `pending_notification`(`phone`, `partner_id`, `dev_token`, `title`, `message`, `no_meja`, `channel_id`, `method_pay`, `status`, `queue`, `id_trans`,  `orders`, `delivery_fee`, `type`, created_at, details) VALUES ('WAITERAPP', '$partner_id', '$dev_token', '$title', '$message', '$tableNum', 'rn-push-notification-channel', '11', '0', '0', '$transactionID', '$order', '0', 'employee', NOW(), '$detailIDs')";
                        array_push($test, $qInsert);
                    $notif = mysqli_query($db_conn, $qInsert);
                    }
                }
                    
                $updateT = mysqli_query($db_conn, "UPDATE `transaksi` SET `total`='$total', `employee_discount`='$discountValue', promo='$promo', id_voucher='$voucherID' WHERE `id`='$obj->transaction_id'");
                $success =1;
                $status =200;
                $msg = "Berhasil";
            } else {
                $success =0;
                $status =204;
                $msg = "Gagal. Mohon coba lagi";
            }
        }else{
            $success =0;
            $status =204;
            $msg = "Tidak bisa menambah pesanan karena transaksi sudah selesai. Mohon refresh dan buat transaksi baru";
        }
        }else{
            $success =0;
            $status =204;
            $msg = "Tidak bisa menambah pesanan karena terdapat promo / diskon VIP di pesanan ini";
        }
    }else{
        $success=0;
        $msg="Missing require field's";
        $status=400;
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "charge_ur"=>$charge_ur, "service"=>$service, "tax"=>$tax, "total"=>$total, "grand_total"=>$gtotal, "detailIDs"=>$detailIDs, "testQuery"=>$test]);