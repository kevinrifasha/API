<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require  __DIR__ . '/../../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
$dotenv->load();

//import require file
require '../../../db_connection.php';
require_once('../../auth/Token.php');
require_once '../../../includes/DbOperation.php';
require_once '../../../includes/ProgramDiscount.php';

// date_default_timezone_set('Asia/Jakarta');

// POST DATA
$db = new DbOperation();
$sql = "";
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
$res1 = array();
$ewallet_response = array();
$id = "";

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

//function
function generateTransactionID($db_conn, $type, $trxDate, $pid){
    $code = $type."/".$trxDate."/".$pid;
    $q = mysqli_query($db_conn,"SELECT count(id) as id FROM `transaksi` WHERE id LIKE '%$code%' AND transaksi.deleted_at IS NULL ORDER BY jam DESC LIMIT 1");
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
function generateInvoiceID($db_conn, $type, $trxDate, $pid){
    $code = $type."/".$trxDate."/".$pid;
    $q = mysqli_query($db_conn,"SELECT count(invoice_code) as id FROM `invoices` WHERE invoice_code LIKE '%$code%' AND invoices.deleted_at IS NULL ORDER BY created_at DESC LIMIT 1");
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

function minUserPoint($phone, $id_voucher_r, $partner_id ,$db_conn){
    $q = mysqli_query($db_conn,"SELECT vr.point FROM membership_voucher vr JOIN partner p ON p.id_master=vr.id_master WHERE vr.code='$id_voucher_r' AND vr.deleted_at IS NULL AND p.id='$partner_id'");
    $q1 = mysqli_query($db_conn,"SELECT m.id, point, m.master_id FROM memberships m JOIN partner p ON p.id_master AND m.master_id WHERE m.user_phone='$phone' AND p.id='$partner_id' ORDER BY m.id DESC LIMIT 1");
    if (mysqli_num_rows($q) > 0 && mysqli_num_rows($q1) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $pPay = (int)$res[0]['point'];
        $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        $uPoint = (int)$res1[0]['point'];
        $uID = $res1[0]['id'];
        $master_id = $res1[0]['master_id'];
        $point = $uPoint-$pPay;
        $update = mysqli_query($db_conn,"UPDATE `memberships` SET point='$point' WHERE id='$uID'");
        $insertPoints = mysqli_query($db_conn,"INSERT INTO `points`(`master_id`, `user_phone`, `point`, `description`, `created_at`) VALUES ('$master_id', '$phone', '-$pPay', 'Redeem Voucher $id_voucher_r', NOW())");
        return $uID;
    }else{
        return 0;
    }
}

function addPoint($phone, $total, $partner_id ,$db_conn){
    $q = mysqli_query($db_conn,"SELECT harga_point, transaction_point_max FROM master m JOIN partner p ON p.id_master=m.id WHERE p.id='$partner_id'");
    $q1 = mysqli_query($db_conn,"SELECT m.id, m.master_id FROM memberships m JOIN partner p ON p.id_master=m.master_id WHERE m.user_phone='$phone' AND p.id='$partner_id' ORDER BY m.id DESC LIMIT 1");
    if (mysqli_num_rows($q) > 0 && mysqli_num_rows($q1) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $harga_point = (int)$res[0]['harga_point'];
        $transaction_point_max = (int)$res[0]['transaction_point_max'];
        $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        $mID = $res1[0]['id'];
        $master_id = $res1[0]['master_id'];
        if($harga_point>0){
            $point = $total/$harga_point;
            if($point > $transaction_point_max){
                $point=$transaction_point_max;
            }
            $update = mysqli_query($db_conn,"UPDATE `memberships` SET point=point+'$point' WHERE id='$mID'");
            $insertPoints = mysqli_query($db_conn,"INSERT INTO `points`(`master_id`, `user_phone`, `point`, `description`, `created_at`) VALUES ('$master_id', '$phone', '$point', 'point tambahan dari transaksi', NOW())");
        }
        return 1;
    }else{
        return 0;
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
            return (int) $res[0]['value'];
        }else{
            return 0;
        }
    }else{
        return 0;
    }
}
function getChargeUrShipper($status, $hide, $db_conn, $id){
    if($status == "FULL" && $hide==0){
        $q = mysqli_query($db_conn,"SELECT charge_ur_shipper as value FROM `partner` WHERE id='$id'");
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            return (int) $res[0]['value'];
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

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $data = json_decode(file_get_contents('php://input'));
    if( isset($data->partnerID)
        && isset($data->total)
        && isset($data->paymentMethod)
        && !empty($data->partnerID)
        && !empty($data->total)
        && !empty($data->paymentMethod) ){



            $is_takeaway = $data->is_takeaway;
            $is_delivery=0;
            $trxDate = date("ymd");
            $pid = (int) $data->partnerID;
            $inv_code = generateInvoiceID($db_conn, "INV", $trxDate, $pid);
            if(isset($data->is_delivery) && !empty($data->is_delivery)){
                $is_delivery = $data->is_delivery;
            }
            if($is_delivery==1 || $is_delivery=='1'){
                $id = generateTransactionID($db_conn, "DL", $trxDate, $pid);
            }else if($is_takeaway==1 || $is_takeaway=='1'){
                $id = generateTransactionID($db_conn, "TA", $trxDate, $pid);
            }else{
                $id = generateTransactionID($db_conn, "DI", $trxDate, $pid);
            }
            $phone = $token->phone;
            $partnerID = $data->partnerID;
            $total = $data->total;
            $paymentMethod = $data->paymentMethod;
            $tableCode = $data->tableCode;
            $is_queue = $data->is_queue;
            $id_voucher = $data->id_voucher;
            $id_voucher_redeemable = "";
            if(isset($data->id_voucher_redeemable) && !empty($data->id_voucher_redeemable)){
                $id_voucher_redeemable = $data->id_voucher_redeemable;
            }
            $delivery_fee=0;
            if(isset($data->delivery_fee) && !empty($data->delivery_fee)){
                $delivery_fee = (int) $data->delivery_fee;
            }
            $rate_id=0;
            if(isset($data->rate_id) && !empty($data->rate_id)){
                $rate_id = $data->rate_id;
            }
            $user_address_id=0;
            if(isset($data->user_address_id) && !empty($data->user_address_id)){
                $user_address_id = $data->user_address_id;
            }
            $is_insurance=0;
            if(isset($data->is_insurance) && !empty($data->is_insurance)){
                $is_insurance = $data->is_insurance;
            }
            $delivery_detail="";
            if(isset($data->delivery_detail) && !empty($data->delivery_detail)){
                $delivery_detail = $data->delivery_detail;
            }

            $distance="0";
            if(isset($data->distance) && !empty($data->distance)){
                $distance = $data->distance;
            }
            $is_takeaway = $data->is_takeaway;
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
            $total_program = 0;
            if(isset($data->total_program) && !empty($data->total_program)){
                $total_program = $data->total_program;
                $total += $total_program;
            }
            $program_id = 0;
            if(isset($data->program_id) && !empty($data->program_id)){
                $program_id = $data->program_id;
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
            $status = getStatus($partnerID, $db_conn);
            $today = date("Y-m-d H:i:s");
            $charge_ur =(int)getChargeUr($status, $hide, $db_conn, $partnerID);
            if($is_delivery=='1' || $is_delivery==1){
                if (strpos($delivery_detail, 'Kurir Pribadi') !== false) {
                }else{
                    $charge_ur =(int)getChargeUrShipper($status, $hide, $db_conn, $partnerID);
                }
            }
            if($paymentMethod=='11' || $paymentMethod==11 ){
                $status = 5;
                $shiftID =(int)getShiftID($partnerID, $db_conn);
            }
            if( $paymentMethod=='1' || $paymentMethod==1 || $paymentMethod=='2' || $paymentMethod==2 || $paymentMethod=='3' || $paymentMethod==3 || $paymentMethod=='4' || $paymentMethod==4 || $paymentMethod=='6' || $paymentMethod==6 || $paymentMethod=='10' || $paymentMethod==10){
                $shiftID =(int)getShiftID($partnerID, $db_conn);
            }

            $idx = 0;

            $il = 0;
            $dataDetail = $data->detail;
            $boolQty = true;
            foreach ($dataDetail as $cart) {
                $items[$il]= new \stdClass();
                $items[$il]->id = $cart->id_menu;
                $items[$il]->quantity = $cart->qty;
                $menuQ = mysqli_query($db_conn, "SELECT nama, harga, stock, is_recipe FROM `menu` WHERE id='$cart->id_menu' AND enabled='1'");
                $mn = mysqli_fetch_all($menuQ, MYSQLI_ASSOC);
                $items[$il]->name = $mn[0]['nama'];
                $items[$il]->price = $mn[0]['harga'];
                $stockMenu = (int) $mn[0]['stock'];
                $is_recipeMenu = $mn[0]['is_recipe'];
                $id_menu = $cart->id_menu;

                if($stockMenu < $cart->qty){
                    $boolQty = false;
                    $res1[$idx]["nama"] = $items[$il]->name;
                    $idx+=1;
                }

               $il+=1;
            }
            foreach ($dataDetail as $cart) {
                $vQty = $cart->qty;
                if (!empty($cart->variant)) {
                    $variant = $cart->variant;
                    foreach ($variant as $vars) {
                        $dvariant = $vars->data_variant;
                        foreach ($dvariant as $detail) {
                            $vID = $detail->id;
                            $menuQ = mysqli_query($db_conn, "SELECT name, stock  FROM `variant` WHERE `id` = '$vID'");
                            $mn = mysqli_fetch_all($menuQ, MYSQLI_ASSOC);
                            $stockMenu = (int) $mn[0]['stock'];
                            if($stockMenu < $cart->qty){
                                $boolQty = false;
                                $res1[$idx]["nama"] = 'Varian '.$mn[0]['name'];
                                $idx+=1;
                            }
                        }
                    }
                }
            }

            if($boolQty==true){

                // mysqli_autocommit($db_conn,FALSE);
                $COMMIT = mysqli_query($db_conn,"
                START TRANSACTION;
                SAVEPOINT '$id';
                COMMIT;
                ");
                $sql = "START TRANSACTION;";
                if($paymentMethod=='11' || $paymentMethod==11 || $paymentMethod=='1' || $paymentMethod==1 || $paymentMethod=='2' || $paymentMethod==2 || $paymentMethod=='3' || $paymentMethod==3 || $paymentMethod=='4' || $paymentMethod==4 || $paymentMethod=='6' || $paymentMethod==6 || $paymentMethod=='10' || $paymentMethod==10){

                    $sql .= "INSERT INTO transaksi(`id`, `jam`, `phone`, `id_partner`, `no_meja`, `status`, `total`, `tipe_bayar`, `promo`, `point`, `queue`, `takeaway`, `notes`, `id_foodcourt`, `tax`, `service`, `charge_ewallet`, `charge_xendit`, `charge_ur`, `id_voucher`, `id_voucher_redeemable`, `diskon_spesial`, `shift_id`, `program_discount`, `program_id`) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$shiftID', '$program_discount', '$program_id'); ";
                }else{
                    $sql .= "INSERT INTO transaksi(`id`, `jam`, `phone`, `id_partner`, `no_meja`, `status`, `total`, `tipe_bayar`, `promo`, `point`, `queue`, `takeaway`, `notes`, `id_foodcourt`, `tax`, `service`, `charge_ewallet`, `charge_xendit`, `charge_ur`, `id_voucher`, `id_voucher_redeemable`, `diskon_spesial`, `program_discount`, `program_id`) VALUES ('$id', '$today', '$phone', '$partnerID', '$tableCode', '$status', '$total', '$paymentMethod', '$promo', '$point', '$is_queue', '$is_takeaway', '$notes', '$foodcourtID', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur', '$id_voucher', '$id_voucher_redeemable', '$diskon_spesial', '$program_discount', '$program_id'); ";
                }

                $delivery_id=0;
                if($is_delivery=='1' || $is_delivery==1){
                    if (strpos($delivery_detail, 'Kurir Pribadi') !== false) {
                        // echo 'true';
                    }else{

                    }
                    // $sql .= "INSERT INTO `delivery`(`transaksi_id`, `ongkir`, `rate_id`, `user_address_id`, `is_insurance`, `delivery_detail`, `distance`) VALUES ('$id', '$delivery_fee', '$rate_id', '$user_address_id', '$is_insurance', '$delivery_detail', '$distance'); ";

                    $insertDelivery = mysqli_query($db_conn,"INSERT INTO `delivery`(`transaksi_id`, `ongkir`, `rate_id`, `user_address_id`, `is_insurance`, `delivery_detail`, `distance`) VALUES ('$id', '$delivery_fee', '$rate_id', '$user_address_id', '$is_insurance', '$delivery_detail', '$distance'); ");
                    $delivery_id = mysqli_insert_id($db_conn);
                }

                // if($insert){
                    addPoint($phone, $total, $partnerID ,$db_conn);
                    getIDVR($phone, $id_voucher_redeemable, $id ,$db_conn);
                    $dataDetail = $data->detail;
                    $idx = 0;
                    $il=0;
                    $items = array();

                    $boolCheck = true;
                    // $insertDT = $db->insertDetailTransaksiAndroid($id, $dataDetail);

                        foreach ($dataDetail as $cart) {
                            $is_program = 0;
                            if(isset($cart->is_program) && !empty(isset($cart->is_program)) && $cart->is_program!=0){
                                $is_program = $cart->is_program;

                            }
                            $is_smart_waiter = 0;
                            if(isset($cart->is_smart_waiter) && !empty(isset($cart->is_smart_waiter)) && $cart->is_smart_waiter!=0){
                                $is_smart_waiter = $cart->is_smart_waiter;

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
                          $tempH = $cart->harga_satuan*$cart->qty;
                          $sql .= " INSERT INTO detail_transaksi(id_transaksi, id_menu, harga_satuan, qty, notes, harga,variant, is_program, is_smart_waiter) VALUES ('$id', '$cart->id_menu', '$cart->harga_satuan', '$cart->qty', '$cart->notes', '$tempH', $json, '$is_program', '$is_smart_waiter');";
                        }

                    // if ($insertDT==5) {
                    // }else{
                    //     $boolCheck=false;
                    // }
                    $sql .=" COMMIT;";
                    // $act= mysqli_multi_query($db_conn,$sql)or die(mysqli_error($db_conn));

                    if (mysqli_multi_query($db_conn,$sql)) {
                        do {
                            if ($r = mysqli_store_result($db_conn)) {
                                mysqli_free_result($r);
                            }
                            } while (mysqli_more_results($db_conn) && mysqli_next_result($db_conn));

                        // $customer = mysqli_query($db_conn, "SELECT id FROM `users` WHERE phone='$phone'");
                        // $customers = mysqli_fetch_assoc($customer);
                        // $customer_id = $customers['id'];
                        // $amount = (int) ceil($total-$promo-$program_discount-$diskon_spesial+$delivery_fee+$charge_ur);
                        $tservice = ceil(($total-$promo-$program_discount-$diskon_spesial)*$service/100);
                        $ttax = ceil(($total-$promo-$program_discount-$diskon_spesial+$tservice+$charge_ur)*$tax/100);
                        $amount = (int) ceil($total-$promo-$program_discount-$diskon_spesial+$tservice+$ttax+$delivery_fee+$charge_ur);
                        $inv_tax = 0;
                        if($ttax>0){
                            $inv_tax = 1;
                        }
                        $insertInvoice = mysqli_query($db_conn," INSERT INTO `invoices`(`invoice_code`, `customer_id`, `type`, `tax`, `total`, `payment_term`, `created_at`) VALUES ('$inv_code', '$phone', 'Receivable', '$inv_tax', '$amount', '1', NOW())");
                        if($insertInvoice){
                            $inv_id = mysqli_insert_id($db_conn);
                            $insertInvoiceDetails = mysqli_query($db_conn,"INSERT INTO `invoice_details`(`invoice_id`, `transaction_id`, `delivery_id`, `subtotal`, `program_discount`, `voucher_discount`, `employee_discount`, `special_discount`, `tax`, `service`, `charge_ur`, `total`, `created_at`) VALUES ('$inv_id', '$id', '$delivery_id', '$total', '$program_discount', '$promo', '0', '$diskon_spesial', '$ttax', '$tservice', '$charge_ur', '$amount', NOW())");

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
                                if($is_delivery==1 || $is_delivery=='1'){
                                    $notif = $db->savePaymentNotification($val['token'], 'Pesanan Baru', 'Pesanan Delivery Baru', "Delivery", 'rn-push-notification-channel', $paymentMethod, 0, 0, $id, $partnerID, null, $order, $gender, $birth_date, $isMembership, $delivery_fee, "employee", '');
                                }else if($is_takeaway==1 || $is_takeaway=='1'){
                                        $notif = $db->savePaymentNotification($val['token'], 'Pesanan Baru', 'Pesanan Takeaway Baru', 'Takeaway', 'rn-push-notification-channel', $paymentMethod, 0, 0, $id, $partnerID, null, $order, $gender, $birth_date, $isMembership, $delivery_fee, "employee", '');
                                }else if($is_queue==1 || $is_queue=='1'){
                                    $notif = $db->savePaymentNotification($val['token'], 'Pesanan Baru', 'Pesanan Antrian Baru', 'Antrian', 'rn-push-notification-channel', $paymentMethod, 0, 0, $id, $partnerID, null, $order, $gender, $birth_date, $isMembership, $delivery_fee, "employee", '');
                                }else{
                                    $notif = $db->savePaymentNotification($val['token'], 'Pesanan Baru', 'Pesanan Baru di Meja '.$tableCode, $tableCode, 'rn-push-notification-channel', $paymentMethod, 0, 0, $id, $partnerID, null, $order, $gender, $birth_date, $isMembership, $delivery_fee, "employee", '');
                                }
                            }
                        }else{
                            $pmq = mysqli_query($db_conn, "SELECT nama FROM `payment_method` WHERE id='$paymentMethod'");
                            $pm = mysqli_fetch_all($pmq, MYSQLI_ASSOC);
                            $ewallet_type= $pm[0]['nama'];
                            $tservice = ceil(($total-$promo-$program_discount-$diskon_spesial)*$service/100);
                            $ttax = ceil(($total-$promo-$program_discount-$diskon_spesial+$tservice+$charge_ur)*$tax/100);
                            $amount = (int) ceil($total-$promo-$program_discount-$diskon_spesial+$tservice+$ttax+$delivery_fee+$charge_ur);
                            $phone1 = substr($phone, 1);
                            $phone1 = '+62'.$phone1;

                            if($paymentMethod=="1" || $paymentMethod==1){
                                $params = [
                                    'external_id' => $inv_code,
                                    'reference_id' => $inv_code,
                                    'currency' => 'IDR',
                                    'amount' => $amount,
                                    'checkout_method'=>'ONE_TIME_PAYMENT',
                                    'channel_code'=> 'ID_OVO',
                                    'channel_properties'=> [
                                        'mobile_number'=> $phone1,
                                    ],
                                    'ewallet_type' => 'ID_OVO',
                                    'phone' => $phone,
                                    'items' => $items
                                ];
                            }else if($paymentMethod=="3" || $paymentMethod==3){
                                $params = [
                                    'external_id' => $inv_code,
                                    'reference_id' => $inv_code,
                                    'currency' => 'IDR',
                                    'amount' => $amount,
                                    'checkout_method'=>'ONE_TIME_PAYMENT',
                                    'channel_code'=> 'ID_DANA',
                                    'channel_properties'=> [
                                        'success_redirect_url'=> 'https://ur-hub.com/',
                                    ],
                                    'ewallet_type' => 'ID_DANA',
                                    'phone' => $phone,
                                    'items' => $items
                                ];
                            }else if($paymentMethod=="4" || $paymentMethod==4){
                                $params = [
                                    'external_id' => $inv_code,
                                    'reference_id' => $inv_code,
                                    'currency' => 'IDR',
                                    'amount' => $amount,
                                    'checkout_method'=>'ONE_TIME_PAYMENT',
                                    'channel_code'=> 'ID_LINKAJA',
                                    'channel_properties'=> [
                                        'success_redirect_url'=> 'https://ur-hub.com/',
                                    ],
                                    'ewallet_type' => 'ID_LINKAJA',
                                    'phone' => $phone,
                                    'items' => $items
                                ];
                            }else if($paymentMethod=="10" || $paymentMethod==10){
                                $params = [
                                    'reference_id' => $inv_code,
                                    'currency' => 'IDR',
                                    'amount' => $amount,
                                    'checkout_method'=>'ONE_TIME_PAYMENT',
                                    'channel_code'=> 'ID_SHOPEEPAY',
                                    'channel_properties'=> [
                                        'success_redirect_url'=> 'https://ur-hub.com/',
                                    ],
                                    'metadata' => [
                                        'branch_code' => 'tree_branch'
                                    ]
                                ];
                            }
                            $ch = curl_init();
                            $timestamp = new DateTime();
                            $body = json_encode($params);
                            curl_setopt($ch, CURLOPT_URL, 'https://'.$_ENV['XENDIT_URL'].'/ewallets/charges');
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                            curl_setopt($ch, CURLOPT_USERPWD, $_ENV['XENDIT_KEY']. ':' . '');

                            $headers = array();
                            $headers[] = 'Content-Type: application/json';
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            $result = curl_exec($ch);
                            if (curl_errno($ch)) {
                                echo 'Error:' . curl_error($ch);
                            }
                            $ewallet_response = $result;
                            curl_close($ch);
                        }
                        $msg = "Success";
                        $success = 1;
                        $status=200;
                        // mysqli_close($db_conn);
                    }else{
                        $sql = "START TRANSACTION; ROLLBACK TO '$id';";
                        $act= mysqli_multi_query($db_conn,$sql)or die(mysqli_error($db_conn));
                        // mysqli_close($db_conn);
                        $msg = "Failed Create Detail";
                        $success = 0;
                        $status=204;
                    }
                // }else{
                //     $msg = "Failed Create Transaction";
                //     $success = 0;
                //     $status=204;
                // }
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
if($status==204){
    http_response_code(200);
}else{

}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "unavailable"=>$res1, "transaction_id"=>$id, "ewallet_response"=>$ewallet_response]);
?>