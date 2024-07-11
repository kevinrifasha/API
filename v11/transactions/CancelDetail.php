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
// require_once '../../includes/DbOperation.php';

$fs = new functions();
// $db = new DbOperation();
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
$bool = true;
$voucherID="";
$promo=0;
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

function updateNewStatus($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT t.id, d.qty, d.qty_delivered, t.status FROM transaksi AS t CROSS JOIN (SELECT d.id_transaksi, SUM(d.qty) AS qty, SUM(d.qty_delivered) AS qty_delivered FROM detail_transaksi as d WHERE d.deleted_at IS NULL GROUP BY d.id_transaksi) AS d on d.id_transaksi = t.id WHERE t.id = '$id'");
    if (mysqli_num_rows($q) > 0) {
        while($row = mysqli_fetch_assoc($q)){
            $status         = (int) $row['status'];
            $qty            = (int) $row['qty'];
            $qty_delivered  = (int) $row['qty_delivered'];
            if($status == 5){
                $new_status = 6;
            } else {
                $new_status = 2;
            }
            if(($qty_delivered - $qty) >= 0 && in_array($status,array(1,5))){
                $update = mysqli_query($db_conn, "UPDATE `transaksi` SET `status`='$new_status'WHERE `id`='$id'");
            }
        }
    }
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
$rounding = 0;
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $statusResponse = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $obj = json_decode(file_get_contents('php://input'));
    if(
        isset($obj->detail_id) && !empty($obj->detail_id)
        && isset($obj->transaction_id) && !empty($obj->transaction_id)
    ){
        if(empty($obj->accBy)){
            $obj->accBy = $token->id;
        }
        $rounding = $obj->rounding??0;
        $q = mysqli_query($db_conn,"SELECT id, id_partner, charge_ur, status, employee_discount_percent, id_voucher FROM `transaksi` WHERE id='$obj->transaction_id'  AND diskon_spesial='0' AND program_discount='0'");
        $partner_id = '';
        $charge_ur = 0;
        $total = 0;
        $status = 0;
        $edp = 0;
        $promo=0;
        $voucherID="";
        $bool = true;
        if (mysqli_num_rows($q) > 0) {
            while($row = mysqli_fetch_assoc($q)){
                $partner_id = $row['id_partner'];
                $charge_ur = (int) $row['charge_ur'];
                $status = (int) $row['status'];
                $edp = (int) $row['employee_discount_percent'];
                $voucherID = $row['id_voucher'];

            }
            $getLastShift = mysqli_query($db_conn, "SELECT MAX(id) as id FROM `shift` WHERE partner_id='$token->id_partner' AND deleted_at IS NULL");
            while($row=mysqli_fetch_assoc($getLastShift)){
                $currentShiftID = $row['id'];
            }
            if(isset($obj->half) && !empty($obj->half)
                && isset($obj->qty_cancel) && !empty($obj->qty_cancel)
            ){

                $qty = 0;
                $qty_delivered = 0;
                $qDT = mysqli_query($db_conn,"SELECT qty, qty_delivered FROM `detail_transaksi` WHERE id='$obj->detail_id' AND deleted_at IS NULL");
                if (mysqli_num_rows($qDT) > 0) {
                    while($row = mysqli_fetch_assoc($qDT)){
                        $qty = (int) $row['qty'];
                        $qty_delivered = (int) $row['qty_delivered'];
                    }
                    if($qty_delivered==$qty-$obj->qty_cancel){
                        $temp = $qty-$obj->qty_cancel;
                        $update = mysqli_query($db_conn, "UPDATE `detail_transaksi` SET status='2', qty='$temp', harga=harga_satuan*$temp WHERE id='$obj->detail_id'");
                    }else{
                        $temp = $qty-$obj->qty_cancel;
                        $update = mysqli_query($db_conn, "UPDATE `detail_transaksi` SET qty='$temp', harga=harga_satuan*$temp WHERE id='$obj->detail_id'");
                    }
                }else{
                    $success =0;
                    $statusResponse = 200;
                    $msg = "Menu tidak terdaftar di transaksi ini.";
                }
                $qDT = mysqli_query($db_conn,"SELECT harga_satuan, qty FROM `detail_transaksi` WHERE id_transaksi='$obj->transaction_id' AND deleted_at IS NULL");
                if (mysqli_num_rows($qDT) > 0) {
                    while($row = mysqli_fetch_assoc($qDT)){
                        $total += (int) $row['harga_satuan'] * (int) $row['qty'];
                    }
                }

                if($status==1 || $status==2 || $status==6 || $status==5){
                    $qDT = mysqli_query($db_conn, "SELECT id_menu, qty, variant FROM `detail_transaksi` WHERE id='$obj->detail_id'");
                    if(mysqli_num_rows($qDT) > 0){
                        $detailsTransaction = mysqli_fetch_all($qDT, MYSQLI_ASSOC);
                        $menusOrder = array();
                        $variantOrder = array();
                        $imo = 0;
                        $iv = 0;
                        foreach($detailsTransaction as $value){
                            $menusOrder[$imo]['id_menu'] = $value['id_menu'];
                            $menusOrder[$imo]['qty'] = (int) $obj->qty_cancel;
                            if(!empty($value['variant'])){
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
                                            $ch = $fs->variant_stock_return($value2->id, $obj->qty_cancel);
                                            $iv+=1;
                                        }
                                    }
                                }
                            }
                            $imo+=1;
                        }
                        //Menu
                        foreach ($menusOrder as $value) {
                            $qtyOrder = $value["qty"];
                            $menuID = $value['id_menu'];
                            $ch = $fs->stock_return($menuID, $qtyOrder);
                        }
                    }
                }
                if($edp==0){
                    $discountValue=0;
                }else{
                $discountValue = ceil($total*$edp/100);
                }

                if(isset($voucherID) && !empty($voucherID) && strlen($voucherID)>0){
                    $qDT = mysqli_query($db_conn,"SELECT id_menu, harga_satuan, qty, status, is_program FROM `detail_transaksi` WHERE id_transaksi='$obj->transaction_id' AND deleted_at IS NULL AND status!=4");
                    $dataDetail = mysqli_fetch_all($qDT, MYSQLI_ASSOC);
                    $q = mysqli_query($db_conn,"SELECT type_id, is_percent, discount, enabled, total_usage, prerequisite FROM voucher WHERE code='$voucherID' AND partner_id='$partner_id' AND DATE(NOW()) BETWEEN DATE(valid_from) AND DATE(valid_until) AND enabled='1' ORDER BY id DESC LIMIT 1");
                    if (mysqli_num_rows($q) > 0) {
                        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                        $prerequisite = json_decode($res[0]['prerequisite']);

                        $tempPromo = 0;
                        if(isset($prerequisite->min)){
                            if((int) $prerequisite->min > $total){
                                $bool = false;
                            }
                        }
                        if(isset($prerequisite->transaction) && !empty($prerequisite->transaction)){
                            if($prerequisite->transaction != $transaction_type){
                                $bool = false;
                            }
                        }
                        if($res[0]['type_id']=='1'){
                            if($res[0]['is_percent']=="1"){
                                $tempPromo = ceil(((int) $res[0]['discount']*$total)/100);
                            }else{
                                $tempPromo = (int) $res[0]['discount'];
                            }
                        }else if($res[0]['type_id']=='3'){
                            $tempTot=0;
                            foreach ($dataDetail as $cart) {

                                if(isset($cart['status']) && !empty($cart['status'])){
                                    if($cart['status']==4){

                                    }else{
                                        if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                            // $totalProgram += (int) $cart['harga'];
                                        }else{
                                            $menuID = $cart['id_menu'];
                                            // $total += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                            $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$menuID'");
                                            if (mysqli_num_rows($qC) > 0) {
                                                $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                                $a = explode(",",$prerequisite->category_id);
                                                foreach ($a as $value) {
                                                    if($resC[0]['id_category']==$value){
                                                        $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }else{
                                    if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                        // $totalProgram += (int) $cart['harga'];
                                    }else{
                                        // $total += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                        $menuID = $cart['id_menu'];
                                        $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$menuID'");
                                        if (mysqli_num_rows($qC) > 0) {
                                            $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                            $a = explode(",",$prerequisite->category_id);
                                            foreach ($a as $value) {
                                                if($resC[0]['id_category']==$value){
                                                    $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if($res[0]['is_percent']=="1"){
                                $tempPromo = ceil(((int) $res[0]['discount']*$tempTot)/100);
                            }else{
                                $tempPromo = (int) $res[0]['discount'];
                            }
                        }else{
                            $tempTot=0;
                            foreach ($dataDetail as $cart) {

                                if(isset($cart['status']) && !empty($cart['status'])){
                                    if($cart['status']==4){

                                    }else{
                                        if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                        }else{
                                            if($cart['id_menu']==$prerequisite->menu_id){
                                                $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                            }
                                        }
                                    }
                                }else{
                                    if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                    }else{
                                        if($cart['id_menu']==$prerequisite->menu_id){
                                            $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                        }
                                    }
                                }

                            }
                            if($res[0]['is_percent']=="1"){
                                $tempPromo = ceil(((int) $res[0]['discount']*$tempTot)/100);
                            }else{
                                $tempPromo = (int) $res[0]['discount'];
                            }

                        }

                        if($bool==true){
                            if(isset($prerequisite->max) ){
                                if((int) $prerequisite->max < $tempPromo){
                                    $tempPromo = (int) $prerequisite->max;
                                }
                            }
                            $promo = $tempPromo;
                        }else{
                            $promo = 0;
                        }

                    }else{
                        $q = mysqli_query($db_conn,"SELECT voucher.type_id, voucher.is_percent, voucher.discount, voucher.enabled, voucher.total_usage, voucher.prerequisite FROM voucher JOIN partner ON voucher.master_id=partner.id_master WHERE voucher.code='$voucherID' AND partner.id='$token->id_partner' AND DATE(NOW()) BETWEEN DATE(voucher.valid_from) AND DATE(voucher.valid_until) AND enabled='1' ORDER BY voucher.id DESC LIMIT 1");
                        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                        $prerequisite = json_decode($res[0]['prerequisite']);
                        $bool = true;
                        $tempPromo = 0;
                        if(isset($prerequisite->min)){
                            if((int) $prerequisite->min > $total){
                                $bool = false;
                            }
                        }
                        if($res[0]['type_id']=='1'){
                            if($res[0]['is_percent']=="1"){
                                $tempPromo = ceil(((int) $res[0]['discount']*$total)/100);
                            }else{
                                $tempPromo = (int) $res[0]['discount'];
                            }
                        }else if($res[0]['type_id']=='3'){
                            $tempTot=0;
                            foreach ($dataDetail as $cart) {

                                if(isset($cart['status']) && !empty($cart['status'])){
                                    if($cart['status']==4){

                                    }else{
                                        if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                            // $totalProgram += (int) $cart['harga'];
                                        }else{
                                            // $total += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                            $menuID = $cart['id_menu'];
                                            $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$menuID'");
                                            if (mysqli_num_rows($qC) > 0) {
                                                $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                                $a = explode(",",$prerequisite->category_id);
                                                foreach ($a as $value) {
                                                    if($resC[0]['id_category']==$value){
                                                        $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }else{
                                    if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                        // $totalProgram += (int) $cart['harga'];
                                    }else{
                                        // $total += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                        $menuID = $cart['id_menu'];
                                        $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$menuID'");
                                        if (mysqli_num_rows($qC) > 0) {
                                            $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                            $a = explode(",",$prerequisite->category_id);
                                            foreach ($a as $value) {
                                                if($resC[0]['id_category']==$value){
                                                    $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if($res[0]['is_percent']=="1"){
                                $tempPromo = ceil(((int) $res[0]['discount']*$tempTot)/100);
                            }else{
                                $tempPromo = (int) $res[0]['discount'];
                            }
                        }else{
                            $tempTot=0;
                            foreach ($dataDetail as $cart) {

                                if(isset($cart['status']) && !empty($cart['status'])){
                                    if($cart['status']==4){

                                    }else{
                                        if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                        }else{
                                            if($cart['id_menu']==$prerequisite->menu_id){
                                                $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                            }
                                        }
                                    }
                                }else{
                                    if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                    }else{
                                        if($cart['id_menu']==$prerequisite->menu_id){
                                            $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                        }
                                    }
                                }
                            }
                            if($res[0]['is_percent']=="1"){
                                $tempPromo = ceil(((int) $res[0]['discount']*$tempTot)/100);
                            }else{
                                $tempPromo = (int) $res[0]['discount'];
                            }

                        }

                        if($bool==true){
                            if(isset($prerequisite->max) ){
                                if((int) $prerequisite->max < $tempPromo){
                                    $tempPromo = (int) $prerequisite->max;
                                }
                            }
                            $promo = $tempPromo;
                        }else{
                            $promo = 0;
                        }

                    }
                }
                $pservice =  getService($partner_id, $db_conn);
                $service = ceil(($total-$discountValue-$promo)*$pservice/100);
                $ptax =  getTaxEnabled($partner_id, $db_conn);
                $tax = ceil(((($total-$discountValue-$promo)+$service+$charge_ur)*$ptax)/100);
                $gtotal = $total-$discountValue-$promo+$charge_ur+$service+$tax;
                if ($update) {
                    $updateT = mysqli_query($db_conn, "UPDATE `transaksi` SET `charge_ur`='$charge_ur',`total`='$total', employee_discount='$discountValue', promo='$promo', rounding='$rounding' WHERE `id`='$obj->transaction_id'");
                    updateNewStatus($obj->transaction_id, $db_conn);
                    $success =1;
                    $statusResponse = 200;
                    $msg = "Berhasil";
                } else {
                    $success =0;
                    $statusResponse = 200;
                    $msg = "Gagal. Mohon coba lagi";
                }
                $update = mysqli_query($db_conn, "INSERT INTO `transaction_cancellation`(`transaction_id`, `detail_transaction_id`,  `notes`, `created_by`, `created_at`, shift_id, qty, acc_by) VALUES ('$obj->transaction_id', '$obj->detail_id', '$obj->detail_notes', '$obj->created_by', NOW(), '$currentShiftID', '$obj->qty_cancel', '$obj->accBy')");
            }else{
                if($status==1 || $status==2 || $status==6 || $status==5){
                    $qDT = mysqli_query($db_conn,"SELECT id_menu, qty, variant FROM `detail_transaksi` WHERE id='$obj->detail_id' AND deleted_at IS NULL");
                    if(mysqli_num_rows($qDT) > 0){
                        $detailsTransaction = mysqli_fetch_all($qDT, MYSQLI_ASSOC);
                        $menusOrder = array();
                        $variantOrder = array();
                        $imo = 0;
                        $iv = 0;

                        foreach($detailsTransaction as $value){
                            $menusOrder[$imo]['id_menu'] = $value['id_menu'];
                            $menusOrder[$imo]['qty'] = (int) $value['qty'];
                            if(!empty($value['variant'])){
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
                                            $ch = $fs->variant_stock_return($value2->id, intval($value2->qty));
                                            $iv+=1;
                                        }
                                    }
                                }
                            }
                            $imo+=1;
                        }
                        //Menu
                        foreach ($menusOrder as $value) {
                            $qtyOrder = $value["qty"];
                            $menuID = $value['id_menu'];
                            $ch = $fs->stock_return($menuID, $qtyOrder);
                        }
                    }
                }

                $qDT = mysqli_query($db_conn,"SELECT qty FROM `detail_transaksi` WHERE id='$obj->detail_id' AND deleted_at IS NULL");
                if (mysqli_num_rows($qDT) > 0) {
                    while($row = mysqli_fetch_assoc($qDT)){
                        $qtyCancelled = $row['qty'];
                        $update = mysqli_query($db_conn, "INSERT INTO `transaction_cancellation`(`transaction_id`, `detail_transaction_id`,  `notes`, `created_by`, `created_at`, shift_id, qty, acc_by) VALUES ('$obj->transaction_id', '$obj->detail_id', '$obj->detail_notes', '$obj->created_by', NOW(), '$currentShiftID', '$qtyCancelled','$obj->accBy')");
                    }
                }
                $update = mysqli_query($db_conn, "UPDATE `detail_transaksi` SET status='4', deleted_at = NOW() WHERE id='$obj->detail_id'");

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
                if(isset($voucherID) && !empty($voucherID) && strlen($voucherID)>0){
                    $qDT = mysqli_query($db_conn,"SELECT id_menu, harga_satuan, qty, status, is_program FROM `detail_transaksi` WHERE id_transaksi='$obj->transaction_id' AND deleted_at IS NULL AND status!=4");
                    $dataDetail = mysqli_fetch_all($qDT, MYSQLI_ASSOC);
                    $q = mysqli_query($db_conn,"SELECT type_id, is_percent, discount, enabled, total_usage, prerequisite FROM voucher WHERE code='$voucherID' AND partner_id='$partner_id' AND DATE(NOW()) BETWEEN DATE(valid_from) AND DATE(valid_until) AND enabled='1' ORDER BY id DESC LIMIT 1");
                    if (mysqli_num_rows($q) > 0) {
                        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                        $prerequisite = json_decode($res[0]['prerequisite']);

                        $tempPromo = 0;
                        if(isset($prerequisite->min)){
                            if((int) $prerequisite->min > $total){
                                $bool = false;
                            }
                        }
                        if(isset($prerequisite->transaction) && !empty($prerequisite->transaction)){
                            if($prerequisite->transaction != $transaction_type){
                                $bool = false;
                            }
                        }
                        if($res[0]['type_id']=='1'){
                            if($res[0]['is_percent']=="1"){
                                $tempPromo = ceil(((int) $res[0]['discount']*$total)/100);
                            }else{
                                $tempPromo = (int) $res[0]['discount'];
                            }
                        }else if($res[0]['type_id']=='3'){
                            $tempTot=0;
                            foreach ($dataDetail as $cart) {

                                if(isset($cart['status']) && !empty($cart['status'])){
                                    if($cart['status']==4){

                                    }else{
                                        if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                            // $totalProgram += (int) $cart['harga'];
                                        }else{
                                            // $total += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                            $menuID = $cart['id_menu'];
                                            $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$menuID'");
                                            if (mysqli_num_rows($qC) > 0) {
                                                $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                                $a = explode(",",$prerequisite->category_id);
                                                foreach ($a as $value) {
                                                    if($resC[0]['id_category']==$value){
                                                        $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }else{
                                    if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                        // $totalProgram += (int) $cart['harga'];
                                    }else{
                                        // $total += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                        $menuID = $cart['id_menu'];
                                        $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$menuID'");
                                        if (mysqli_num_rows($qC) > 0) {
                                            $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                            $a = explode(",",$prerequisite->category_id);
                                            foreach ($a as $value) {
                                                if($resC[0]['id_category']==$value){
                                                    $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if($res[0]['is_percent']=="1"){
                                $tempPromo = ceil(((int) $res[0]['discount']*$tempTot)/100);
                            }else{
                                $tempPromo = (int) $res[0]['discount'];
                            }
                        }else{
                            $tempTot=0;
                            foreach ($dataDetail as $cart) {

                                if(isset($cart['status']) && !empty($cart['status'])){
                                    if($cart['status']==4){

                                    }else{
                                        if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                        }else{
                                            if($cart['id_menu']==$prerequisite->menu_id){
                                                $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                            }
                                        }
                                    }
                                }else{
                                    if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                    }else{
                                        if($cart['id_menu']==$prerequisite->menu_id){
                                            $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                        }
                                    }
                                }

                            }
                            if($res[0]['is_percent']=="1"){
                                $tempPromo = ceil(((int) $res[0]['discount']*$tempTot)/100);
                            }else{
                                $tempPromo = (int) $res[0]['discount'];
                            }

                        }

                        if($bool==true){
                            if(isset($prerequisite->max) ){
                                if((int) $prerequisite->max < $tempPromo){
                                    $tempPromo = (int) $prerequisite->max;
                                }
                            }
                            $promo = $tempPromo;
                        }else{
                            $promo = 0;
                        }

                    }else{
                        $q = mysqli_query($db_conn,"SELECT voucher.type_id, voucher.is_percent, voucher.discount, voucher.enabled, voucher.total_usage, voucher.prerequisite FROM voucher JOIN partner ON voucher.master_id=partner.id_master WHERE voucher.code='$voucherID' AND partner.id='$partner_id' AND DATE(NOW()) BETWEEN DATE(voucher.valid_from) AND DATE(voucher.valid_until) AND enabled='1' ORDER BY voucher.id DESC LIMIT 1");
                        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                        $prerequisite = json_decode($res[0]['prerequisite']);
                        $bool = true;
                        $tempPromo = 0;
                        if(isset($prerequisite->min)){
                            if((int) $prerequisite->min > $total){
                                $bool = false;
                            }
                        }
                        if($res[0]['type_id']=='1'){
                            if($res[0]['is_percent']=="1"){
                                $tempPromo = ceil(((int) $res[0]['discount']*$total)/100);
                            }else{
                                $tempPromo = (int) $res[0]['discount'];
                            }
                        }else if($res[0]['type_id']=='3'){
                            $tempTot=0;
                            foreach ($dataDetail as $cart) {

                                if(isset($cart['status']) && !empty($cart['status'])){
                                    if($cart['status']==4){

                                    }else{
                                        if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                            // $totalProgram += (int) $cart['harga'];
                                        }else{
                                            // $total += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                            $menuID = $cart['id_menu'];
                                            $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$menuID'");
                                            if (mysqli_num_rows($qC) > 0) {
                                                $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                                $a = explode(",",$prerequisite->category_id);
                                                foreach ($a as $value) {
                                                    if($resC[0]['id_category']==$value){
                                                        $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }else{
                                    if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                        // $totalProgram += (int) $cart['harga'];
                                    }else{
                                        // $total += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                        $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$menuID'");
                                        if (mysqli_num_rows($qC) > 0) {
                                            $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                            $a = explode(",",$prerequisite->category_id);
                                            foreach ($a as $value) {
                                                if($resC[0]['id_category']==$value){
                                                    $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if($res[0]['is_percent']=="1"){
                                $tempPromo = ceil(((int) $res[0]['discount']*$tempTot)/100);
                            }else{
                                $tempPromo = (int) $res[0]['discount'];
                            }
                        }else{
                            $tempTot=0;
                            foreach ($dataDetail as $cart) {

                                if(isset($cart['status']) && !empty($cart['status'])){
                                    if($cart['status']==4){

                                    }else{
                                        if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                        }else{
                                            if($cart['id_menu']==$prerequisite->menu_id){
                                                $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                            }
                                        }
                                    }
                                }else{
                                    if(isset($cart['is_program']) && !empty($cart['is_program'])){
                                    }else{
                                        if($cart['id_menu']==$prerequisite->menu_id){
                                            $tempTot += (int) $cart['harga_satuan'] *(int) $cart['qty'];
                                        }
                                    }
                                }
                            }
                            if($res[0]['is_percent']=="1"){
                                $tempPromo = ceil(((int) $res[0]['discount']*$tempTot)/100);
                            }else{
                                $tempPromo = (int) $res[0]['discount'];
                            }

                        }

                        if($bool==true){
                            if(isset($prerequisite->max) ){
                                if((int) $prerequisite->max < $tempPromo){
                                    $tempPromo = (int) $prerequisite->max;
                                }
                            }
                            $promo = $tempPromo;
                        }else{
                            $promo = 0;
                        }

                    }
                }
                $pservice =  getService($partner_id, $db_conn);
                $service = ceil(($total-$discountValue-$promo)*$pservice/100);
                $ptax =  getTaxEnabled($partner_id, $db_conn);
                $tax = ceil(((($total-$discountValue-$promo)+$service+$charge_ur)*$ptax)/100);
                $gtotal = $total-$discountValue-$promo+$charge_ur+$service+$tax;

                if ($update) {
                    $updateT = mysqli_query($db_conn, "UPDATE `transaksi` SET `charge_ur`='$charge_ur',`total`='$total', employee_discount='$discountValue', promo='$promo', rounding='$rounding' WHERE `id`='$obj->transaction_id'");
                    updateNewStatus($obj->transaction_id, $db_conn);
                    $success =1;
                    $statusResponse = 200;
                    $msg = "Berhasil";
                } else {
                    $success =0;
                    $statusResponse = 200;
                    $msg = "Gagal. Mohon coba lagi";
                }
            }
        }else{
            $success =0;
            $statusResponse = 200;
            $msg = "Transaksi Ini Tidak Diijinkan Menghapus Menu";
        }
    }else{
        $success=0;
        $msg="Missing require field's";
        $statusResponse=400;
    }
}
echo json_encode(["success"=>$success, "status"=>$statusResponse, "msg"=>$msg, "charge_ur"=>$charge_ur, "service"=>$service, "tax"=>$tax, "total"=>$total, "grand_total"=>$gtotal, "can"=>$bool, "v"=>$voucherID, "promo"=>$promo]);