<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("../../db_connection.php");
require_once("./../tokenModels/tokenManager.php");
require '../../includes/functions.php';

$fs = new functions();
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
$token = '';

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];

}else{
    // POST DATA
    $json = file_get_contents('php://input');
    $data = json_decode($json,true);
    $now = date("Y-m-d H:i:s");
    if(
        isset($data['goods_receipt_detail'])
        && isset($data['purchase_order_id'])
        && !empty($data['goods_receipt_detail'])
        && !empty($data['purchase_order_id'])
        ){
            $received=1;
            $getPO = mysqli_query($db_conn,"SELECT received FROM purchase_orders WHERE id='$purchase_order_id' AND deleted_at IS NULL");
            if(mysqli_num_rows($getPO)>0){
                $resPO = mysqli_fetch_all($getPO, MYSQLI_ASSOC);
                $received = (int)$resPO[0]['received'];
            }else{
                $received = 1;
            }
            if($received==0){
                $delivery_order_number = "";
            if(isset($data['delivery_order_number']) && !empty($data['delivery_order_number']) ){
                $delivery_order_number = $data['delivery_order_number'];
            }
            $sender = "";
            if(isset($data['sender']) && !empty($data['sender'])){
                $sender = $data['sender'];
            }
            $id_master = $data['id_master'];
        $receive_date = date("Y-m-d");
        $purchase_order_id = $data['purchase_order_id'];
        $id_partner = 0;
        if(isset($data['id_partner'])&&!empty($data['id_partner'])){
            $id_partner = $data['id_partner'];
        }
        $length = count($data['goods_receipt_detail']);
        $insertPaket = mysqli_query($db_conn,"INSERT INTO `goods_receipt`(`purchase_order_id`, `delivery_order_number`, `id_master`, `id_partner`, `sender`, `recieve_date`, `created_at`)
        VALUES ('$purchase_order_id', '$delivery_order_number', '$id_master', '$id_partner', '$sender', '$receive_date', '$now')");

        if($insertPaket){
            $id_gr = mysqli_insert_id($db_conn);
            $boolMenu = false;
            $boolRaw = false;
            // echo "\n";

            // echo ($length);
            // $id_variant = mysqli_real_escape_string($db_conn, trim($data['id_variant));
            for ($i = 0; $i < $length; $i++) {
                $id_menu = $data['goods_receipt_detail'][$i]['item']['id_menu'];
                $id_raw_material = $data['goods_receipt_detail'][$i]['item']['id_raw_material'];
                $qty = $data['goods_receipt_detail'][$i]['item']['qty'];
                $id_metric = $data['goods_receipt_detail'][$i]['item']['id_metric'];
                if(isset($data['goods_receipt_detail'][$i]['item']['expired_date']) && !empty($data['goods_receipt_detail'][$i]['item']['expired_date'])){

                    $exp_date = $data['goods_receipt_detail'][$i]['item']['expired_date'];
                    $todayDT = date("Y-m-d H:i:s");
                    $insertPaket = mysqli_query($db_conn, "INSERT INTO `goods_receipt_detail` (`id_gr`, `id_menu`, `id_raw_material`, `qty`, `id_metric`, `created_at`, `expired_date`) VALUES ('$id_gr', '$id_menu', '$id_raw_material', '$qty', '$id_metric',  '$todayDT', '$exp_date')");
                    if ($insertPaket) {
                        $last_id = mysqli_insert_id($db_conn);
                        // echo ($last_id);
                        if ($id_menu != 0 && $id_raw_material == 0) {
                            $updateMenuStock = mysqli_query($db_conn, "UPDATE `menu` SET `stock` = `stock` + $qty WHERE `menu`.`id` = $id_menu");
                            if ($updateMenuStock) {
                                // echo("Masuk IF");
                                $boolMenu = true;
                            }
                        } else if ($id_menu == 0 && $id_raw_material != 0) {
                            $insertStockRawPaket = mysqli_query($db_conn, "INSERT INTO `raw_material_stock` (`id_raw_material`, `stock`, `id_metric`, `exp_date`, `id_goods_receipt_detail`) VALUES ('$id_raw_material', '$qty', '$id_metric', '$exp_date', '$last_id')");
                            if ($insertStockRawPaket) {
                                // echo("Masuk IF");
                                $boolRaw = true;
                            }
                        }
                    }
                }

            }
            $success =1;
            $status =200;
            $msg = "Success";
            $updatePO = mysqli_query($db_conn,"UPDATE purchase_orders SET received=1 WHERE id='$purchase_order_id'");
            $query = "SELECT id, nama, is_recipe, stock FROM `menu` WHERE is_recipe=1 AND id_partner='$id_partner' AND deleted_at IS NULL";
            $allRecom = mysqli_query($db_conn, $query);
            if (mysqli_num_rows($allRecom) > 0) {
                $rowR = mysqli_fetch_all($allRecom, MYSQLI_ASSOC);
                $res = $fs->stock_menu($rowR);
                foreach ($res as $value) {
                    $mID = $value['id'];
                    $stock = $value['stock'];
                    $update = mysqli_query($db_conn, "UPDATE `menu` SET stock='$stock' WHERE id='$mID'");
                };
            }

            $query = "SELECT variant.id, variant.name, variant.is_recipe, variant.stock FROM `variant` JOIN `variant_group` ON `variant`.`id_variant_group`=variant_group.id WHERE is_recipe=1 AND variant_group.id_master='$id_master'";
            $allRecom = mysqli_query($db_conn, $query);
            if (mysqli_num_rows($allRecom) > 0) {
                $rowR = mysqli_fetch_all($allRecom, MYSQLI_ASSOC);
                $res = $fs->stock_variant($rowR);
                foreach ($res as $value) {
                    $mID = $value['id'];
                    $stock = $value['stock'];
                    $update = mysqli_query($db_conn, "UPDATE `variant` SET stock='$stock' WHERE id='$mID'");
                };
            }

        }else{
            $success =0;
            $status =204;
            $msg = "Failed";
        }
            }else{
                $success =0;
                $status =204;
                $msg = "Gagal terima barang karena sudah diterima sebelumnya / PO di hapus. Mohon refresh";
            }
    }else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }
}

$signupJson = json_encode(["msg"=>$msg, "success"=>$success,"status"=>$status]);
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;

?>