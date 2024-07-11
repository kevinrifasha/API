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
$employeeID=0;
$test = 0;

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
$rounding = 0;
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

        $program_discount = 0;
        if(isset($obj->program_discount) && !empty($obj->program_discount)){
            $program_discount = $obj->program_discount;
        }
        
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
            $rounding = $obj->rounding??0;
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
                
                $bundle_id  = 0;
                if(isset($cart->bundle_id ) && !empty(isset($cart->bundle_id )) && $cart->bundle_id !=0){
                    $bundle_id  = $cart->bundle_id ;
                }

                $bundle_qty  = 0;
                if(isset($cart->bundle_qty ) && !empty(isset($cart->bundle_qty )) && $cart->bundle_qty !=0){
                    $bundle_qty  = $cart->bundle_qty ;
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
                        $name = str_replace("'","\\\''",$vars->name);
                        $json .= "''name'':''" . $name . "'',";
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
                            $detailName = str_replace("'","\\\''",$detail->name);
                            $json .= "''name'':''" . $detailName . "''}";
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
                
                $surcharge_id = 0;
                if(isset($cart->surcharge_id) && !empty(isset($cart->surcharge_id))){
                    $surcharge_id  = $cart->surcharge_id;
                }
                
                $insertDetail = mysqli_query($db_conn, "INSERT INTO detail_transaksi(id_transaksi, id_menu, harga_satuan, qty, notes, harga,variant, is_program, surcharge_change, is_smart_waiter, server_id,is_consignment, surcharge_id, bundle_id, bundle_qty) VALUES ('$obj->transaction_id', '$cart->id_menu', '$cart->harga_satuan', '$cart->qty', '$cart->notes', '$cart->harga', $json, $is_program, '$surcharge_change', '$is_smart_waiter', '$employeeID', '$isConsignment', '$surcharge_id', '$bundle_id','$bundle_qty');");
                $iidDetail = mysqli_insert_id($db_conn);
                if($status==1 || $status==2 || $status==6 || $status==5){
                    
                    $qDT = mysqli_query($db_conn,"SELECT dt.id_menu, dt.qty, dt.variant, m.is_recipe FROM `detail_transaksi` dt JOIN menu m ON m.id = dt.id_menu WHERE dt.id='$iidDetail' AND dt.deleted_at IS NULL");
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
                        //Menu
                        $sqlMaster = mysqli_query($db_conn, "SELECT id_master FROM partner WHERE id = '$partner_id' AND deleted_at IS NULL");
                        $getMaster = mysqli_fetch_all($sqlMaster, MYSQLI_ASSOC);
                        $id_master = $getMaster[0]['id_master'];
                        foreach ($menusOrder as $value) {
                            $qtyOrder = $value["qty"];
                            $menuID = $value['id_menu'];
                            $isRecipe = $value["is_recipe"];
                            
                            $ch = $fs->stock_reduce($menuID, $qtyOrder, 0, $id_master, $partner_id, $isRecipe);
                        }
                    }
                }
            }

            $qDT = mysqli_query($db_conn,"SELECT harga, harga_satuan, qty, bundle_id, bundle_qty FROM `detail_transaksi` WHERE id_transaksi='$obj->transaction_id' AND deleted_at IS NULL");
            if (mysqli_num_rows($qDT) > 0) {
                while($row = mysqli_fetch_assoc($qDT)){
                    $total += $row['harga_satuan'] * $row['qty'];
                }
                
            $normalTotal = $total;
                
            $roundingData = 0;
            $roundingInt = 0;
            $roundingString = "";
            if($rounding != 0){
                $roundingData = (int) $rounding;
                $roundingInt = $roundingData;
                if($roundingData < 0){
                    $roundingInt = $roundingData * (-1);
                }
                
                $roundingString = (string) $roundingInt;
                    
                if (strlen($roundingString) == 3) {
                        if ($total % 1000 < $roundingInt) {
                            $total = $total - ($total % 1000);
                        } else {
                            $total = $total + (1000 -  ($total % 1000));
                        }
                    } else if (strlen($roundingString) == 2) {
                        if ($total % 100 < $roundingInt) {
                            $total = $total - ($total % 100);
                        } else {
                            $total = $total + (100 -  ($total % 100));
                        }
                    } else if (strlen($roundingString) == 1) {
                        if ($total % 10 < $roundingInt) {
                            $total = $total - ($total % 10);
                        } else {
                            $total = $total + (10 -  ($total % 10));
                        }
                    }
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

            if ($insertDetail) {
                $updateT = mysqli_query($db_conn, "UPDATE `transaksi` SET `total`='$total', `employee_discount`='$discountValue', promo='$promo', employee_discount_percent='$edp', id_voucher='$voucherID', rounding='$rounding', program_discount='$program_discount' WHERE `id`='$obj->transaction_id'");
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
if($status==204){
    http_response_code(200);
}else{
    http_response_code($status);
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "charge_ur"=>$charge_ur, "service"=>$service, "tax"=>$tax, "total"=>$total, "grand_total"=>$gtotal, "test"=>["rounding"=>$rounding, "roundingInt"=>$roundingInt, "roundingData"=>$roundingData, "roundingString"=>$roundingString, "normalTotal"=>$normalTotal]]);