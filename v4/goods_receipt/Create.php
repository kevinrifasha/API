<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../db_connection.php';
require_once('../auth/Token.php');

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
$test = [];
$res = array();

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

    // POST DATA
    $json = file_get_contents('php://input');
    $data = json_decode($json,true);
    $now = date("Y-m-d H:i:s");
    if( isset($data['delivery_order_number'])
        && isset($data['sender'])
        && isset($data['receive_date'])
        && isset($data['goods_receipt_detail'])
        && isset($data['purchase_order_id'])
        && !empty($data['sender'])
        && !empty($data['receive_date'])
        && !empty($data['goods_receipt_detail'])
        && !empty($data['purchase_order_id'])
        ){
        $ap = 0;
        $delivery_order_number = $data['delivery_order_number'];
        $id_master = $token->id_master;
        $sender = mysqli_real_escape_string($db_conn, $data['sender']);
        $receive_date = $data['receive_date'];
        $purchase_order_id = $data['purchase_order_id'];
        $id_partner = 0;
        if(isset($data['id_partner'])&&!empty($data['id_partner'])){
            $id_partner = $data['id_partner'];
        }
        $length = count($data['goods_receipt_detail']);
        $updateReceived = mysqli_query($db_conn, "UPDATE purchase_orders SET received=1, updated_at=NOW() WHERE id='$purchase_order_id'");

        $getPOData= mysqli_query($db_conn, "SELECT supplier_id FROM purchase_orders WHERE id='$purchase_order_id'");
        while($row=mysqli_fetch_assoc($getPOData)){
            $supplierID=$row['supplier_id'];
        }

        $insertPaket = mysqli_query($db_conn,"INSERT INTO `goods_receipt`(`purchase_order_id`, `delivery_order_number`, `id_master`, `id_partner`, `sender`, `recieve_date`, receiver_id)
        VALUES ('$purchase_order_id', '$delivery_order_number', '$id_master', '$id_partner', '$sender', '$receive_date', '$token->id')");
        if($insertPaket){
            $id_gr = mysqli_insert_id($db_conn);
            $boolMenu = false;
            $boolRaw = false;
            $price=0;
            // echo "\n";

            // echo ($length);
            // $id_variant = mysqli_real_escape_string($db_conn, trim($data['id_variant));
            for ($i = 0; $i < $length; $i++) {
                $id_menu = (int)$data['goods_receipt_detail'][$i]['id_menu'];
                $id_raw_material = (int)$data['goods_receipt_detail'][$i]['id_raw_material'];
                $qty = (double)$data['goods_receipt_detail'][$i]['qty'];
                $id_metric = $data['goods_receipt_detail'][$i]['id_metric'];
                $exp_date = $data['goods_receipt_detail'][$i]['exp_date'];
                if(strlen($exp_date)<4){
                    $exp_date="2030-12-12 23:59:59";
                }
                $todayDT = date("Y-m-d H:i:s");
                $finalQty=0;

                // array_push($test, [$id_raw_material, $qty, $exp_date]);
                if((int)$id_metric==0){
                    $id_metric="6";
                }

                if((int)$id_menu==0 && (int)$id_raw_material != 0){
                    $checkYield = mysqli_query($db_conn, "SELECT id, yield FROM raw_material WHERE id='$id_raw_material'");
                    while($row=mysqli_fetch_assoc($checkYield)){
                        $finalQty=(double)$row['yield']*$qty/100;
                    }
                }else{
                    $finalQty=(int)$qty;
                }

                $price=0;
                $newPrice=0;
                $priceBefore=0;
                if((int)$id_menu==0){
                    $q="SELECT id, price FROM purchase_orders_details WHERE raw_id='$id_raw_material' AND purchase_order_id='$purchase_order_id'";
                    $qBefore="SELECT unit_price AS price FROM raw_material WHERE id='$id_raw_material' AND id_partner='$id_partner';";
                }else{
                    $q="SELECT id, price FROM purchase_orders_details WHERE menu_id='$id_menu' AND purchase_order_id='$purchase_order_id'";
                    $qBefore="SELECT hpp AS price FROM menu WHERE id='$id_menu' AND id_partner='$id_partner';";
                }
                $getPrice = mysqli_query($db_conn, $q);
                while($row=mysqli_fetch_assoc($getPrice)){
                    $price = $row['price'];
                }

                $getBefore = mysqli_query($db_conn, $qBefore);
                $dataBefore=mysqli_fetch_assoc($getBefore);
                $priceBefore= (double)$dataBefore['price'];

                $newPrice = ($price + $priceBefore) / 2;

                if((int)$id_menu==0){
                    $qUpdateRaw = mysqli_query($db_conn, "UPDATE raw_material SET unit_price='$newPrice' WHERE id='$id_raw_material'");

                    // ubah cogs variant disini

                    $sqlVariant = mysqli_query($db_conn, "SELECT id_variant, qty FROM `recipe` WHERE id_raw = '$id_raw_material' AND deleted_at IS NULL ORDER BY `id` DESC");

                    if(mysqli_num_rows($sqlVariant) > 0) {
                        $data_recipe = mysqli_fetch_all($sqlVariant, MYSQLI_ASSOC);

                        foreach($data_recipe as $val) {
                            $variant_id = $val['id_variant'];
                            $qty_var = (int)$val['qty'];
                            $newCogs = 0;

                            $sqlVariantRecipe = mysqli_query($db_conn, "SELECT id_raw FROM `recipe` WHERE id_variant = '$variant_id' AND deleted_at IS NULL ORDER BY `id` DESC");
                            $dataVariant = mysqli_fetch_all($sqlVariantRecipe, MYSQLI_ASSOC);
                            foreach($dataVariant as $item) {
                                $raw_id = $item['id_raw'];

                                $sqlPrice = mysqli_query($db_conn, "SELECT unit_price FROM raw_material WHERE id = '$raw_id' AND deleted_at IS NULL");
                                $dataPrice = mysqli_fetch_all($sqlPrice, MYSQLI_ASSOC);
                                $price_raw = (double)$dataPrice[0]['unit_price'];

                                $newCogs += $price_raw *$qty_var ;
                            }

                            // update cogs variant
                            $sqlUpdateCOGS = mysqli_query($db_conn, "UPDATE variant SET cogs = '$newCogs' WHERE id = '$variant_id' AND deleted_at IS NULL");
                        }
                    }

                    // ubah cogs variant disini end

                    // cari ke recipe
                    $qSelectRecipe = mysqli_query($db_conn, "SELECT id_menu FROM recipe WHERE id_raw='$id_raw_material' AND id_menu != '0';");
                    if(mysqli_num_rows($qSelectRecipe) > 0) {
                        $recipes = mysqli_fetch_all($qSelectRecipe, MYSQLI_ASSOC);

                        // dari recipe yang ada id_menu nya cari ke menu mengugnakan id_menu itu
                        foreach($recipes as $recipe) {
                            $idMenu = $recipe['id_menu'];
                            $newHpp = 0;
                            $qGet = mysqli_query($db_conn, "SELECT r.id, r.id_menu, r.sfg_id, r.id_raw, r.qty, r.id_metric, r.id_variant, rm.unit_price AS price FROM `recipe` r LEFT JOIN `raw_material` rm ON rm.id = r.id_raw WHERE r.id_menu = '$idMenu';");

                            $menuRecipes=mysqli_fetch_all($qGet, MYSQLI_ASSOC);
                            foreach($menuRecipes as $val) {
                                $priceVal = (double)$val['price'];
                                $qtyVal = (double)$val['qty'];
                                $subTotal= $priceVal * $qtyVal;
                                $newHpp += $subTotal;
                            }

                            // update hpp menu disini
                            $qUpdateHpp = mysqli_query($db_conn, "UPDATE menu SET hpp='$newHpp' WHERE id='$idMenu'");
                        }
                    }
                }else{
                    $qUpdateMenu = mysqli_query($db_conn, "UPDATE menu SET hpp='$newPrice' WHERE id='$id_menu'");
                }

                $ap+=(double)$price*(double)$qty;
                $qPaket="INSERT INTO `goods_receipt_detail` (`id_gr`, `id_menu`, `id_raw_material`, `qty`, `id_metric`, `created_at`, `expired_date`, unit_price) VALUES ('$id_gr', '$id_menu', '$id_raw_material', '$qty', '$id_metric',  '$todayDT', '$exp_date', '$price')";
                $insertPaket = mysqli_query($db_conn, $qPaket);
                // var_dump(mysqli_error($db_conn));
                if ($insertPaket) {
                    $last_id = mysqli_insert_id($db_conn);
                    $remaining = 0;
                    // echo ($last_id);
                    if ($id_menu != 0 && $id_raw_material == 0) {
                        $remainingStock = mysqli_query($db_conn, "SELECT remaining FROM stock_movements WHERE menu_id='$id_menu' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
                        if(mysqli_num_rows($remainingStock)>0){
                            $resRS =  mysqli_fetch_all($remainingStock, MYSQLI_ASSOC);
                            $remaining = (double)$resRS[0]['remaining'];
                        }else{
                            $remaining = 0;
                        }
                        $remaining = $remaining+$finalQty;
                        $movementMenu = mysqli_query($db_conn, "INSERT INTO stock_movements SET master_id='$id_master', partner_id='$id_partner', menu_id='$id_menu', metric_id='$id_metric', type=0, gr='$finalQty', remaining='$remaining'");
                        $updateMenuStock = mysqli_query($db_conn, "UPDATE `menu` SET `stock` = `stock` + $finalQty WHERE `menu`.`id` = $id_menu");
                        if ($updateMenuStock) {
                            // echo("Masuk IF");
                            $boolMenu = true;
                        }
                    } else if ($id_menu == 0 && $id_raw_material != 0) {
                        $remainingStock = mysqli_query($db_conn, "SELECT remaining FROM stock_movements WHERE raw_id='$id_raw_material' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
                        if(mysqli_num_rows($remainingStock)>0){
                            $resRS =  mysqli_fetch_all($remainingStock, MYSQLI_ASSOC);
                            $remaining = (double)$resRS[0]['remaining'];
                        }else{
                            $remaining = 0;
                        }
                        $remaining = $remaining+$finalQty;
                        $movementMenu = mysqli_query($db_conn, "INSERT INTO stock_movements SET master_id='$id_master', partner_id='$id_partner', raw_id='$id_raw_material', metric_id='$id_metric', gr='$finalQty', remaining='$remaining'");

                        $insertStockRawPaket = mysqli_query($db_conn, "INSERT INTO `raw_material_stock` (`id_raw_material`, `stock`, `id_metric`, `exp_date`, `id_goods_receipt_detail`) VALUES ('$id_raw_material', '$finalQty', '$id_metric', '$exp_date', '$last_id')");
                        if ($insertStockRawPaket) {
                            // echo("Masuk IF");
                            $boolRaw = true;
                        }
                    }
                }
            }
            $insertAP = mysqli_query($db_conn, "INSERT INTO account_payables SET partner_id='$id_partner', gr_id='$id_gr', supplier_id='$supplierID', amount='$ap'");
            $success =1;
            $status =200;
            $msg = "Berhasil terima barang. Stok dan hutang sudah diperbarui";
        }else{
            $success =0;
            $status =204;
            $msg = "Failed";
        }

    }else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
