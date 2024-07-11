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
$today1 = date('Y-m-d');
$tokenizer = new Token();
$token = '';
$res = array();
$res1 = array();
$total_program = 0;
$ewallet_response = array();
$id = "";

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

function getChargeUR($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT partner.hide_charge FROM partner WHERE id='$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        if($res[0]['hide_charge']==='0'){
            $qV = mysqli_query($db_conn,"SELECT charge_ur as value FROM `partner` WHERE id='$id'");
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
        && isset($data->pre_order_schedules_id)
        && isset($data->transaction_type)
        && isset($data->detail)
        && !empty($data->partnerID)
        && !empty($data->pre_order_schedules_id)
        && !empty($data->transaction_type)
        && !empty($data->detail)){

            // $charge_ur = getChargeUR($data->partnerID, $db_conn);
            $charge_ur=0;
            $transaction_type = $data->transaction_type;
            $pre_order_schedules_id = $data->pre_order_schedules_id;
            $dataDetail = $data->detail;
            $total = 0;
            foreach ($dataDetail as $cart) {
                $total += (int) $cart->harga_satuan *(int) $cart->qty;
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
                            $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$cart->id_menu'");
                            if (mysqli_num_rows($qC) > 0) {
                                $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                if($resC[0]['id_category']==$prerequisite->category_id){
                                    $tempTot += (int) $cart->harga_satuan *(int) $cart->qty;
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
                            if($cart->id_menu==$prerequisite->menu_id){
                                $tempTot += (int) $cart->harga_satuan *(int) $cart->qty;
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
                            $qC = mysqli_query($db_conn,"SELECT id_category FROM `menu` WHERE id='$cart->id_menu'");
                            if (mysqli_num_rows($qC) > 0) {
                                $resC = mysqli_fetch_all($qC, MYSQLI_ASSOC);
                                if($resp[0]['id_category']==$prerequisite->category_id){
                                    $tempTot += (int) $cart->harga_satuan *(int) $cart->qty;
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
                            if($cart->id_menu==$prerequisite->menu_id){
                                $tempTot += (int) $cart->harga_satuan *(int) $cart->qty;
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
            $service = ceil(($total-$promo-$diskon_spesial)*$pservice/100);
            $tax = ceil((($total-$promo-$diskon_spesial+$service+$charge_ur)*$ptax)/100);
            $msg = "success";
            $success = 1;
            $status = 200;
            $gtotal = $total-$promo-$diskon_spesial+$charge_ur+$service+$tax+$ewalletCharge;
            $il=0;
            $boolQty = true;

                    $idx = 0;
            foreach ($dataDetail as $cart) {
                $preOrderSSQ = mysqli_query($db_conn, "SELECT item_sales FROM `pre_order_schedules` WHERE id='$pre_order_schedules_id'");

                if(mysqli_num_rows($preOrderSSQ)){
                    $mn = mysqli_fetch_all($preOrderSSQ, MYSQLI_ASSOC);
                    $mn = json_decode($mn[0]['item_sales']);
                    foreach ($mn as $value) {
                        if($value->id==$cart->id_menu){
                            $items[$il]= new \stdClass();
                            $items[$il]->quantity = $cart->qty;

                            $mnQtyQ = mysqli_query($db_conn, "SELECT dt.qty FROM transaksi t JOIN detail_transaksi dt ON t.id=dt.id_transaksi WHERE t.pre_order_id='$pre_order_schedules_id' AND dt.id_menu='$cart->id_menu' AND t.deleted_at IS NULL");
                            $mnQQ = mysqli_fetch_all($mnQtyQ, MYSQLI_ASSOC);
                            $tempQQ = 0;
                            foreach ($mnQQ as $valueQQ) {
                                $tempQQ += (int) $valueQQ['qty'];
                            }

                            $menusQ = mysqli_query($db_conn, "SELECT name, price FROM `pre_order_menus` WHERE id='$cart->id_menu'");
                            $mnQ = mysqli_fetch_all($menusQ, MYSQLI_ASSOC);

                            $items[$il]->id = $cart->id_menu;
                            $items[$il]->name = $mnQ[0]['name'];
                            $items[$il]->price = $mnQ[0]['price'];
                            $stockMenu = (int) $value->quota + (int) $tempQQ;
                            $id_menu = $cart->id_menu;
                            if($stockMenu < $cart->qty){
                                $boolQty = false;
                                $res1[$idx]["nama"] = $items[$il]->name;
                                $idx+=1;
                            }
                            $il+=1;
                        }
                    }
                }
            }

            echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, 'charge_ur'=>$charge_ur, "subtotal"=>$total, "promo"=>$promo, "diskon_spesial"=>$diskon_spesial, "service"=>$service, "tax"=>$tax, "total"=>$gtotal, "delivery_fee"=>$delivery_fee, "percent_service"=>$pservice, "percent_tax"=>$ptax, "available"=>$boolQty, "menus_unvailable"=>$res1, "total_program"=>$total_program, "ewallet_charge"=>$ewalletCharge]);
    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
    }
}