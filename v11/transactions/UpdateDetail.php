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

$fs = new functions();
$db = new DbOperation();
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

function getIDVR($phone, $vr, $trx ,$db_conn){
        $q = mysqli_query($db_conn,"SELECT id  FROM `user_voucher_ownership` WHERE `userid` LIKE '$phone' AND `voucherid`='$vr' AND `transaksi_id`='$trx' ORDER BY id ASC limit 1");
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $id = $res[0]['id'];
            $update = mysqli_query($db_conn,"UPDATE `user_voucher_ownership` SET `transaksi_id`=NULL WHERE id='$id'");
            return $update;
        }else{
            return 0;
        }

}

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
        && isset($obj->detail) && !empty($obj->detail)
    ){
        if(empty($obj->rounding)){
            $rounding=0;
        }else{
            $rounding = $obj->rounding;
        }

        $dataDetail = $obj->detail;
        $qT = mysqli_query($db_conn, "SELECT id FROM `transaksi` WHERE id='$obj->transactionID'");

        if (mysqli_num_rows($qT) > 0) {
            $dT = mysqli_query($db_conn, "DELETE FROM `detail_transaksi` WHERE id_transaksi='$obj->transactionID'");

            $total = 0;
            foreach ($dataDetail as $cart) {
                $total += (int) $cart->harga_satuan *(int) $cart->qty;
            }
            $edp = 0;
            $ed = 0;
            if(!empty($obj->edp)){
                $edp = (int)$obj->edp;
                $ed = (int)$total*$edp/100;
            }
            $insertDT = $db->insertDetailTransaksiAndroid($obj->transactionID, $dataDetail);
            if(!empty($obj->promo)){
                $promo = $obj->promo;
                $voucherID = $obj->id_voucher;
            }else{
                $promo = 0;
                $voucherID = "";
            }
            if(isset($voucherID) && !empty($voucherID) && strlen($voucherID)>0){
                $bool = true;
                $q = mysqli_query($db_conn,"SELECT type_id, is_percent, discount, enabled, total_usage, prerequisite FROM voucher WHERE code='$voucherID' AND partner_id='$token->id_partner' AND DATE(NOW()) BETWEEN DATE(valid_from) AND DATE(valid_until) AND enabled='1' ORDER BY id DESC LIMIT 1");
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

                            if(isset($cart->status) && !empty($cart->status)){
                                if($cart->status==4){

                                }else{
                                    if(isset($cart->is_program) && !empty($cart->is_program)){
                                        // $totalProgram += (int) $cart->harga;
                                    }else{
                                        // $total += (int) $cart->harga_satuan *(int) $cart->qty;
                                        $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$cart->id_menu'");
                                        if (mysqli_num_rows($qC) > 0) {
                                            $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                            $a = explode(",",$prerequisite->category_id);
                                            foreach ($a as $value) {
                                                if($resC[0]['id_category']==$value){
                                                    $tempTot += (int) $cart->harga_satuan *(int) $cart->qty;
                                                }
                                            }
                                        }
                                    }
                                }
                            }else{
                                if(isset($cart->is_program) && !empty($cart->is_program)){
                                    // $totalProgram += (int) $cart->harga;
                                }else{
                                    // $total += (int) $cart->harga_satuan *(int) $cart->qty;
                                    $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$cart->id_menu'");
                                    if (mysqli_num_rows($qC) > 0) {
                                        $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                        $a = explode(",",$prerequisite->category_id);
                                        foreach ($a as $value) {
                                            if($resC[0]['id_category']==$value){
                                                $tempTot += (int) $cart->harga_satuan *(int) $cart->qty;
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

                            if(isset($cart->status) && !empty($cart->status)){
                                if($cart->status==4){

                                }else{
                                    if(isset($cart->is_program) && !empty($cart->is_program)){
                                    }else{
                                        if($cart->id_menu==$prerequisite->menu_id){
                                            $tempTot += (int) $cart->harga_satuan *(int) $cart->qty;
                                        }
                                    }
                                }
                            }else{
                                if(isset($cart->is_program) && !empty($cart->is_program)){
                                }else{
                                    if($cart->id_menu==$prerequisite->menu_id){
                                        $tempTot += (int) $cart->harga_satuan *(int) $cart->qty;
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

                            if(isset($cart->status) && !empty($cart->status)){
                                if($cart->status==4){

                                }else{
                                    if(isset($cart->is_program) && !empty($cart->is_program)){
                                        // $totalProgram += (int) $cart->harga;
                                    }else{
                                        // $total += (int) $cart->harga_satuan *(int) $cart->qty;
                                        $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$cart->id_menu'");
                                        if (mysqli_num_rows($qC) > 0) {
                                            $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                            $a = explode(",",$prerequisite->category_id);
                                            foreach ($a as $value) {
                                                if($resC[0]['id_category']==$value){
                                                    $tempTot += (int) $cart->harga_satuan *(int) $cart->qty;
                                                }
                                            }
                                        }
                                    }
                                }
                            }else{
                                if(isset($cart->is_program) && !empty($cart->is_program)){
                                    // $totalProgram += (int) $cart->harga;
                                }else{
                                    // $total += (int) $cart->harga_satuan *(int) $cart->qty;
                                    $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$cart->id_menu'");
                                    if (mysqli_num_rows($qC) > 0) {
                                        $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                        $a = explode(",",$prerequisite->category_id);
                                        foreach ($a as $value) {
                                            if($resC[0]['id_category']==$value){
                                                $tempTot += (int) $cart->harga_satuan *(int) $cart->qty;
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

                            if(isset($cart->status) && !empty($cart->status)){
                                if($cart->status==4){

                                }else{
                                    if(isset($cart->is_program) && !empty($cart->is_program)){
                                    }else{
                                        if($cart->id_menu==$prerequisite->menu_id){
                                            $tempTot += (int) $cart->harga_satuan *(int) $cart->qty;
                                        }
                                    }
                                }
                            }else{
                                if(isset($cart->is_program) && !empty($cart->is_program)){
                                }else{
                                    if($cart->id_menu==$prerequisite->menu_id){
                                        $tempTot += (int) $cart->harga_satuan *(int) $cart->qty;
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
            
            $program_discount = 0;
            if(isset($obj->program_discount) && !empty($obj->program_discount)){
                $program_discount = $obj->program_discount;
            }
            
            $update = mysqli_query($db_conn, "UPDATE `transaksi` SET total='$total', employee_discount='$ed', employee_discount_percent='$edp', promo='$promo', rounding='$rounding', program_discount = '$program_discount' WHERE id='$obj->transactionID'");
            if ($update) {
                $success =1;
                $status =200;
                $msg = "Berhasil pindahkan ke grup";
            } else {
                $success =0;
                $status =204;
                $msg = "Gagal pindahkan ke grup. Mohon coba lagi";
            }

        }else{
            $msg = "Data Not Registered";
            $success = 0;
            $status=400;
        }
    }else{
        $success=0;
        $msg="Missing require field's";
        $status=400;
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);