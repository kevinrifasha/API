<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once '../../includes/DbOperation.php';

// date_default_timezone_set('Asia/Jakarta');

// POST DATA
$db = new DbOperation();

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
$id = "";
$msg = "";
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
    if( isset($obj->transactionID)
        && isset($obj->voucherID)
        && !empty($obj->transactionID)
        ){
            $bool = true;
            $voucherID = $obj->voucherID;
            $transactionID = $obj->transactionID;
            $i = 0;
            $returned = 0;
            $promo = 0;
            $q = mysqli_query($db_conn,"SELECT total, promo  FROM `transaksi` WHERE `id` LIKE '$transactionID'");
            if (mysqli_num_rows($q) > 0) {
                $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                $total= (int)$res[0]['total'];
                $returned = $total + (int)$res[0]['promo'];
                $total = $returned;
            }else{
                $total= 0;
            }
            $qDT = mysqli_query($db_conn,"SELECT id_menu, harga_satuan, qty, status, is_program FROM `detail_transaksi` WHERE id_transaksi='$obj->transactionID' AND deleted_at IS NULL AND status!=4");
            $dataDetail = mysqli_fetch_all($qDT, MYSQLI_ASSOC);
            if(strlen($voucherID)>0){
                $q = mysqli_query($db_conn,"SELECT type_id, is_percent, discount, enabled, total_usage, prerequisite FROM voucher WHERE code='$voucherID' AND partner_id='$token->id_partner' AND DATE(NOW()) BETWEEN DATE(valid_from) AND DATE(valid_until) AND enabled='1' ORDER BY id DESC LIMIT 1");
            if (mysqli_num_rows($q) > 0) {
                $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                $prerequisite = json_decode($res[0]['prerequisite']);

                $tempPromo = 0;
                if(isset($prerequisite->min)){
                    if((int) $prerequisite->min > $total){
                        $bool = false;
                        $msg .= "di bawah minimum order sebesar ".$prerequisite->min;
                    }
                }
                if(isset($prerequisite->transaction) && !empty($prerequisite->transaction)){
                    if($prerequisite->transaction != $transaction_type || (int)$prerequisite->transaction!=0 ){
                        $bool = false;
                        $msg .= "tidak sesuai tipe transaksi";
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
                                    // $totalProgram += (int) $cart->harga;
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
                                // $totalProgram += (int) $cart->harga;
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
                        $msg .= "di bawah minimum order sebesar ".$prerequisite->min;
                    }
                }
                if(isset($prerequisite->transaction) && !empty($prerequisite->transaction)){
                    if($prerequisite->transaction != $transaction_type || (int)$prerequisite->transaction!=0 ){
                        $bool = false;
                        $msg .= "tidak sesuai tipe transaksi";
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
                                    // $totalProgram += (int) $cart->harga;
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
                                // $totalProgram += (int) $cart->harga;
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
            $finalTotal = $returned-$promo;
            if($bool==true){
                $sql = mysqli_query($db_conn, "UPDATE transaksi SET promo='$promo', id_voucher='$voucherID' WHERE id='$transactionID'");
            if($sql){
                $msg = "Berhasil ubah voucher";
                $success = 1;
                $status=200;
            }else{
                $msg = "Kesalahan Sistem. Mohon coba lagi";
                $success = 0;
                $status=204;

            }
            }else{
                $msg = "Transaksi tidak memenuhi syarat voucher ".$msg;
                $success = 0;
                $status=204;
            }

    }else{
        $success = 0;
        $msg = "Mohon lengkapi field";
        $status = 400;
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "promo"=>$promo]);
?>