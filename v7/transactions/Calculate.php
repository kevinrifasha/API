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
require_once '../../includes/ProgramDiscount.php';

// date_default_timezone_set('Asia/Jakarta');

// POST DATA
$db = new DbOperation();
$programsB = new Programs();

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

function array_some($array, $callback) {
    foreach ($array as $item) {
        if ($callback($item)) {
            return true;
        }
    }
    return false;
}

function getChargeUR($id, $db_conn, $transaction_type, $delivery_detail){
    $q = mysqli_query($db_conn,"SELECT partner.hide_charge FROM partner WHERE id='$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        if($res[0]['hide_charge']==='0'){
            if((int)$transaction_type==3 ){
                if(strpos($delivery_detail, 'Kurir Pribadi') !== false){
                    $qV = mysqli_query($db_conn,"SELECT charge_ur as value FROM `partner` WHERE id='$id'");
                }else{
                    $qV = mysqli_query($db_conn,"SELECT charge_ur_shipper as value FROM `partner` WHERE id='$id'");
                }
            }else{
                $qV = mysqli_query($db_conn,"SELECT charge_ur as value FROM `partner` WHERE id='$id'");
            }
            $resV = mysqli_fetch_all($qV, MYSQLI_ASSOC);
            return (int) $resV[0]['value'];
        }else{
            return 0;
        }
    }else{
        return 0;
    }
}

function getVariantPrice($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT price FROM `variant` WHERE id='$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['price'];
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
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
}else{
    $data = json_decode(file_get_contents('php://input'));
    if( isset($data->partnerID)
        && isset($data->transaction_type)
        && isset($data->detail)
        && !empty($data->partnerID)
        && !empty($data->transaction_type)
        && !empty($data->detail)){

            $delivery_detail="";
            if(isset($data->delivery_detail) && !empty($data->delivery_detail)){
                $delivery_detail = $data->delivery_detail;
            }
            $transaction_type = $data->transaction_type;
            // $charge_ur = getChargeUR($data->partnerID, $db_conn, $transaction_type, $delivery_detail);
            $charge_ur=0;
            $dataDetail = $data->detail;
            $totalProgram = 0;
            $total = 0;
            $isConsign = false;
            foreach ($dataDetail as $cart) {
                if (($cart->is_consignment == "1") && !$isConsign){
                    $isConsign = true;
                }
                if(isset($cart->status) && !empty($cart->status)){
                    if($cart->status==4){

                    }else{
                        if(isset($cart->is_program) && !empty(isset($cart->is_program)) && $cart->is_program!=0){
                            $totalProgram += (int) $cart->harga;
                        }else{
                            $total += (int) $cart->harga_satuan *(int) $cart->qty;
                        }
                    }
                }else{
                    if(isset($cart->is_program) && !empty(isset($cart->is_program)) && $cart->is_program!=0){
                        $totalProgram += (int) $cart->harga;
                    }else{
                        $total += (int) $cart->harga_satuan *(int) $cart->qty;
                    }
                }
                // if (!empty($cart->variant)) {
                //   $variant = $cart->variant;
                //   foreach ($variant as $vars) {
                //     $dvariant = $vars->data_variant;
                //     foreach ($dvariant as $detail) {
                //         $vID = $detail->id;
                //         $vPrice = getVariantPrice($vID,$db_conn);
                //         $total += $vPrice*(int) $cart->qty;
                //     }
                //   }
                // }
              }

            $promo = 0;
            if(isset($data->id_voucher) && !empty($data->id_voucher)){
                $q = mysqli_query($db_conn,"SELECT type_id, is_percent, discount, enabled, total_usage, prerequisite FROM voucher WHERE code='$data->id_voucher' AND partner_id='$data->partnerID' AND DATE(NOW()) BETWEEN DATE(valid_from) AND DATE(valid_until) AND enabled='1' ORDER BY id DESC LIMIT 1");
                if (mysqli_num_rows($q) > 0) {
                    $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                    $prerequisite = json_decode($res[0]['prerequisite']);
                    $bool = true;
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
                    $q = mysqli_query($db_conn,"SELECT voucher.type_id, voucher.is_percent, voucher.discount, voucher.enabled, voucher.total_usage, voucher.prerequisite FROM voucher JOIN partner ON voucher.master_id=partner.id_master WHERE voucher.code='$data->id_voucher' AND partner.id='$data->partnerID' AND DATE(NOW()) BETWEEN DATE(voucher.valid_from) AND DATE(voucher.valid_until) AND enabled='1' ORDER BY voucher.id DESC LIMIT 1");
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
            if(isset($data->id_voucher_redeemable) && !empty($data->id_voucher_redeemable)){
                if($data->voucher_type=="Redeem Code"){
                    $q = mysqli_query($db_conn,"SELECT type_id, is_percent, discount, enabled, total_usage, prerequisite FROM redeemable_voucher WHERE code='$data->id_voucher_redeemable' AND partner_id='$data->partnerID' AND DATE(NOW()) BETWEEN DATE(valid_from) AND DATE(valid_until) AND enabled='1' ORDER BY id DESC LIMIT 1");
                    if (mysqli_num_rows($q) > 0) {
                        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                        $prerequisite = json_decode($res[0]['prerequisite']);
                        $bool = true;
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
                        $q = mysqli_query($db_conn,"SELECT redeemable_voucher.type_id, redeemable_voucher.is_percent, redeemable_voucher.discount, redeemable_voucher.enabled, redeemable_voucher.total_usage, redeemable_voucher.prerequisite FROM redeemable_voucher JOIN partner ON redeemable_voucher.master_id=partner.id_master WHERE redeemable_voucher.code='$data->id_voucher_redeemable' AND partner.id='$data->partnerID' AND DATE(NOW()) BETWEEN DATE(redeemable_voucher.valid_from) AND DATE(redeemable_voucher.valid_until) AND enabled='1' ORDER BY redeemable_voucher.id DESC LIMIT 1");
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
                }else{
                    $q = mysqli_query($db_conn,"SELECT type_id, is_percent, discount, enabled, total_usage, prerequisite FROM membership_voucher WHERE code='$data->id_voucher_redeemable' AND partner_id='$data->partnerID' AND DATE(NOW()) BETWEEN DATE(valid_from) AND DATE(valid_until) AND enabled='1' ORDER BY id DESC LIMIT 1");
                    if (mysqli_num_rows($q) > 0) {
                        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                        $prerequisite = json_decode($res[0]['prerequisite']);
                        $bool = true;
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
                        $q = mysqli_query($db_conn,"SELECT membership_voucher.type_id, membership_voucher.is_percent, membership_voucher.discount, membership_voucher.enabled, membership_voucher.total_usage, membership_voucher.prerequisite FROM membership_voucher JOIN partner ON membership_voucher.master_id=partner.id_master WHERE membership_voucher.code='$data->id_voucher_redeemable' AND partner.id='$data->partnerID' AND DATE(NOW()) BETWEEN DATE(membership_voucher.valid_from) AND DATE(membership_voucher.valid_until) AND enabled='1' ORDER BY membership_voucher.id DESC LIMIT 1");
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
            }
            $diskon_spesial = 0;
            if(isset($data->diskon_spesial) && !empty($data->diskon_spesial)){
                $diskon_spesial = ceil(($total*(int)$data->diskon_spesial)/100);
            }

            $distance = 0;
            $delivery_fee = 0;
            if(isset($data->distance) && !empty($data->distance)){
                $qD = mysqli_query($db_conn,"SELECT radius, price FROM custom_deliveries JOIN partner ON partner.id_master=custom_deliveries.id WHERE radius*1000<='$distance' AND partner.id='$data->partnerID' AND custom_deliveries.deleted_at IS NULL ORDER BY radius ASC LIMIT 1");
                if (mysqli_num_rows($qD) > 0) {
                    $resD = mysqli_fetch_all($qD, MYSQLI_ASSOC);
                    $delivery_fee=$resD[0]['price'];
                }
            }

            if($promo+$diskon_spesial>$total){
                $promo = $total;
                $diskon_spesial = 0;
            }
            $program_discount = 0;
            $program_id = 0;
            $program_payment_method = 0;

            if(
                (isset($data->is_program_discount) && !empty($data->is_program_discount)) && empty($data->id_voucher)
                ){

                $resDp = $programsB->ProgramDiscount($data->partnerID, $transaction_type, $total, $dataDetail);

                $program_id = $resDp['id'];
                $program_discount = $resDp['discount'];
                $program_payment_method = $resDp['payment_method'];
            }
            $q = mysqli_query($db_conn,"SELECT service, ewallet_charge, tax FROM `partner` WHERE id='$data->partnerID'");
            if (mysqli_num_rows($q) > 0) {
                $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                $pservice =$res[0]['service'];
                $ewalletCharge=$res[0]['ewallet_charge'];
                $ptax = $res[0]['tax'];
            }else{
                $pservice=0;
                $ewalletCharge=0;
                $ptax=0;
            }
            
            $idx = 0;
            foreach ($dataDetail as $cart) {
                $items[$il]= new \stdClass();
                $items[$il]->id = $cart->id_menu;
                $items[$il]->quantity = $cart->qty;
                $menuQ = mysqli_query($db_conn, "SELECT nama, harga, stock, is_recipe FROM `menu` WHERE id='$cart->id_menu' AND enabled='1'");
                if (mysqli_num_rows($menuQ) > 0) {
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
                }else{
                    $boolQty = false;
                    $res1[$idx]["nama"] = $items[$il]->name;
                    $idx+=1;

                }
                $il+=1;

            }

            

            foreach ($dataDetail as $cart) {

                if(isset($cart->status) && !empty($cart->status)){
                    if($cart->status==4){

                    }else{
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
                }else{
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
            }
            $iDD = 0;
            foreach ($dataDetail as $cart) {
                $dataDetail[$iDD]->harga = $cart->qty*$cart->harga_satuan;
                $iDD +=1;
            }

            // $validateConsignment = array_some($dataDetail, function($detail){
            //     if($detail->is_consignment == "1" || $detail['is_consignment'] == "1" ){
            //         return false;
            //     } else {
            //         return true;
            //     }
            // });

            $ewalletCharge=0;
            if(!$isConsign){
                $service = ceil(($total+$totalProgram-$promo-$program_discount-$diskon_spesial)*$pservice/100);
                $tax = ceil((($total+$totalProgram-$promo-$program_discount-$diskon_spesial+$service+$charge_ur)*$ptax)/100);
            } else {
                $service = 0;
                $tax = 0;
            }
            $msg = "success";
            $success = 1;
            $status = 200;
            $gtotal = $total+$totalProgram-$promo-$diskon_spesial-$program_discount+$charge_ur+$service+$tax+$ewalletCharge;
            // $total = $total+$totalProgram;
            $il=0;
            $boolQty = true;


            $resP = array();
            $today = date("Y-m-d");
            $time = date("H:i:s");
            $dayNameEng = date('D');
            $dayNameInd = "";
            if($dayNameEng=="Mon"){
                $dayNameInd = "SENIN";
            }else if($dayNameEng=="Tue"){
                $dayNameInd = "SELASA";
            }else if($dayNameEng=="Wed"){
                $dayNameInd = "RABU";
            }else if($dayNameEng=="Thu"){
                $dayNameInd = "KAMIS";
            }else if($dayNameEng=="Fri"){
                $dayNameInd = "JUMAT";
            }else if($dayNameEng=="Sat"){
                $dayNameInd = "SABTU";
            }else{
                $dayNameInd = "MINGGU";
            }

            // if($totalProgram==0){

                    $qP = mysqli_query($db_conn,"SELECT `id`, `master_program_id`, `master_id`, `partner_id`, `title`, `minimum_value`, `menus`, `enabled`, `valid_from`, `valid_until`, `qty_redeem`, `minimum_value`-'$gtotal' AS need_extra,
                CASE
                    WHEN `minimum_value`-'$gtotal'>0 THEN 0
                    ELSE 1
                END AS active
                FROM `programs` WHERE `partner_id`='$data->partnerID' AND master_program_id='1' AND `deleted_at` IS NULL AND `enabled`='1' AND '$today' BETWEEN `valid_from` AND  `valid_until` AND ((`start_hour` IS NULL AND `end_hour` IS NULL) OR (`start_hour`='00:00:00' AND `end_hour`='00:00:00') OR '$time' BETWEEN `start_hour` AND `end_hour`) AND (`day`='' OR `day` IS NULL OR `day` LIKE '%$dayNameInd%') ORDER BY active DESC, need_extra DESC");
                if (mysqli_num_rows($qP) > 0) {
                    $resP = mysqli_fetch_all($qP, MYSQLI_ASSOC);
                    $iP=0;
                    foreach($resP as $value){
                        $menus = json_decode($value['menus']);
                        $jP = 0;
                        foreach($menus as $value1){
                            $mid = $value1->id;
                            $sqlM = mysqli_query($db_conn, "SELECT nama as name, harga FROM menu WHERE id='$mid'");
                            if(mysqli_num_rows($sqlM) > 0) {
                                $dataM = mysqli_fetch_all($sqlM, MYSQLI_ASSOC);
                                $menus[$jP]->name=$dataM[0]['name'];
                                $menus[$jP]->real_price=$dataM[0]['harga'];
                            }
                            $jP+=1;
                        }
                        $resP[$iP]['menus']=json_encode($menus);
                        $iP+=1;
                    }
                }
            // }
            $arrMenu = array();
            $dateFirstDb = date('Y-m-d', strtotime('-1 week'));
            $dateLastDb = date('Y-m-d');
                $qM = mysqli_query($db_conn,"SELECT  menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at, menu.sequence, partner.name, categories.name as cname, CASE WHEN menu.stock=0 THEN 0 ELSE 1 END AS tempFlag,
                    categories.name as cname
                    FROM menu
                    JOIN partner ON menu.id_partner = partner.id
                    JOIN categories ON categories.id = menu.id_category
                    WHERE partner.id = '$data->partnerID' AND menu.deleted_at IS NULL AND menu.enabled=1 AND menu.is_suggestions!=0
                    GROUP BY menu.id
                    ORDER BY tempFlag DESC, menu.is_suggestions ASC");
                if (mysqli_num_rows($qM) > 0) {
                    $arrMenu = mysqli_fetch_all($qM, MYSQLI_ASSOC);
                }
                if(count($arrMenu)<5){
                    $f = 5 - count($arrMenu);
                    $qM1 = mysqli_query($db_conn,"SELECT  menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at, partner.name, categories.name as cname, sum(detail_transaksi.qty) as qty, CASE WHEN stock=0 THEN 0 ELSE 1 END AS tempFlag FROM menu  JOIN partner ON menu.id_partner = partner.id JOIN categories ON categories.id = menu.id_category JOIN detail_transaksi ON detail_transaksi.id_menu=menu.id JOIN transaksi ON transaksi.id = detail_transaksi.id_transaksi WHERE partner.id = '$data->partnerID' AND menu.deleted_at IS NULL AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb' AND transaksi.status<=2 and transaksi.status>=1 AND menu.enabled='1' AND transaksi.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL GROUP BY menu.id ORDER BY tempFlag DESC, qty DESC LIMIT $f");
                    if (mysqli_num_rows($qM1) > 0) {
                        $arrMenu1 = mysqli_fetch_all($qM1, MYSQLI_ASSOC);
                        $i = count($arrMenu);
                        foreach ($arrMenu1 as $value) {
                            $arrMenu[$i]=$value;
                            $i+=1;
                        }
                    }

                }

            echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, 'charge_ur'=>$charge_ur, "subtotal"=>$total, "promo"=>$promo, "diskon_spesial"=>$diskon_spesial, "service"=>$service, "tax"=>$tax, "total"=>$gtotal, "delivery_fee"=>$delivery_fee, "percent_service"=>$pservice, "percent_tax"=>$ptax, "available"=>$boolQty, "menus_unvailable"=>$res1, "programs"=>$resP, "total_program"=>$totalProgram, "recommended"=>$arrMenu, "detail"=>$dataDetail, "program_discount"=>$program_discount, "program_payment_method"=>$program_payment_method, "program_id"=>$program_id, "ewallet_charge"=>$ewalletCharge]);
    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
    }
}
http_response_code($status);