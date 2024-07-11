    <?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require '../../db_connection.php';
$headers = array();
$rx_http = '/\AHTTP_/';
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
            $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
    }
} 
$token = '';

$idInsert = 0;
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt', $token));
$success = 0;
$signupMsg = 'Failed';
$msg = "";
$remaining = 0;
$finalQty = 0;

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {
    $status = $tokens['status'];
    $signupMsg = $tokens['msg'];
} else {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $now = date("Y-m-d H:i:s");
    if (isset($data['sender']) && isset($data['goods_receipt_detail']) && isset($data['purchase_order_id'])) {
        $ap = 0;
        $delivery_order_number = $data['delivery_order_number'];
        $id_master = $data['id_master'];
        $sender = $data['sender'];
        $receiveDate = $data['date'];
        $purchase_order_id = $data['purchase_order_id'];
        $id_partner = 0;
        $id_partner = $data['id_partner'];
        $notes = $data['notes'];
        $getPOData = mysqli_query($db_conn, "SELECT supplier_id FROM purchase_orders WHERE id='$purchase_order_id'");
        while ($row = mysqli_fetch_assoc($getPOData)) {
            $supplierID = $row['supplier_id'];
        }
        
        $invalidQty = false;
        
        foreach ($data['goods_receipt_detail'] as $val) {
            $qty = (float)$val['qty'];
            $ceiling_qty = (float)$val['ceilingQty'];
            if($qty > $ceiling_qty || $qty < 0){
                $invalidQty = true;
                break;
            }
        
        }
        
        if($invalidQty == false){
            $qGR = "INSERT INTO `goods_receipt`(`purchase_order_id`, `delivery_order_number`, `id_master`, `id_partner`, `sender`, receiver_id, notes,recieve_date )
            VALUES ('$purchase_order_id', '$delivery_order_number', '$id_master', '$id_partner', '$sender', '$tokenDecoded->id', '$notes', '$receiveDate')";
            $insertPaket = mysqli_query($db_conn, $qGR);
            if ($insertPaket) {
                $id_gr = mysqli_insert_id($db_conn);
                $boolMenu = false;
                $boolRaw = false;
                $price = 0;
    
                foreach ($data['goods_receipt_detail'] as $val) {
    
                    $id_menu = $val['menu_id'];
                    $id_raw_material = $val['raw_id'];
                    $qty = (float)$val['qty'];
                    $id_metric = $val['metric_id'];
                    if (isset($val['expired_date'])) {
                        $date = $val['expired_date'];
                        $exp_date = "expired_date='$date', ";
                        $expDate = "exp_date='$date', ";
                    } else {
                        $exp_date = "";
                        $expDate = "";
                    }
                    
                    $todayDT = date("Y-m-d H:i:s");
                    $finalQty = 0;
                    if ($id_menu == 0 && $id_raw_material != 0) {
                        $checkYield = mysqli_query($db_conn, "SELECT id, yield FROM raw_material WHERE id='$id_raw_material' AND deleted_at IS NULL AND id_partner='$id_partner' AND id_master='$id_master'");
                        while ($row = mysqli_fetch_assoc($checkYield)) {
                            $finalQty = (float)$row['yield'] * $qty / 100;
                        }
                    } else {
                        $finalQty = $qty;
                    }
                    $price = 0;
                    $newPrice = 0;
                    $priceBefore = 0;
                    if ($id_menu == "0") {
                        $q = "SELECT id, price FROM purchase_orders_details WHERE raw_id='$id_raw_material' AND purchase_order_id='$purchase_order_id'";
                        $qBefore = "SELECT unit_price AS price FROM raw_material WHERE id='$id_raw_material' AND id_partner='$id_partner' AND id_master='$id_master' AND deleted_at IS NULL;";
                    } else {
                        $q = "SELECT id, price FROM purchase_orders_details WHERE menu_id='$id_menu' AND purchase_order_id='$purchase_order_id'";
                        $qBefore = "SELECT hpp AS price FROM menu WHERE id='$id_menu' AND id_partner='$id_partner' AND deleted_at IS NULL;";
                    }
    
                    $getPrice = mysqli_query($db_conn, $q);
                    while ($row = mysqli_fetch_assoc($getPrice)) {
                        $price = (float)$row['price'];
                    }
    
                    $getBefore = mysqli_query($db_conn, $qBefore);
                    $dataBefore = mysqli_fetch_assoc($getBefore);
                    $priceBefore = (float)$dataBefore['price'];
    
                    $newPrice = ($price + $priceBefore) / 2;
    
                    if ($id_menu == "0") {
                        $qUpdateRaw = mysqli_query($db_conn, "UPDATE raw_material SET unit_price='$newPrice' WHERE id='$id_raw_material' AND deleted_at IS NULL AND id_partner='$id_partner' AND id_master='$id_master'");
    
                        // ubah cogs variant disini
    
                        $sqlVariant = mysqli_query($db_conn, "SELECT id_variant, qty FROM `recipe` WHERE id_raw = '$id_raw_material' AND deleted_at IS NULL AND id_variant!=0 ORDER BY `id` DESC");
    
                        if (mysqli_num_rows($sqlVariant) > 0) {
                            $data = mysqli_fetch_all($sqlVariant, MYSQLI_ASSOC);
    
                            foreach ($data as $val) {
                                $variant_id = $val['id_variant'];
                                $qty_var = (int)$val['qty'];
                                $newCogs = 0;
    
                                $sqlVariantRecipe = mysqli_query($db_conn, "SELECT id_raw FROM `recipe` WHERE id_variant = '$variant_id' AND deleted_at IS NULL ORDER BY `id` DESC");
                                if (mysqli_num_rows($sqlVariantRecipe) > 0) {
                                    $dataVariant = mysqli_fetch_all($sqlVariantRecipe, MYSQLI_ASSOC);
                                    foreach ($dataVariant as $item) {
                                        $raw_id = $item['id_raw'];
    
                                        $sqlPrice = mysqli_query($db_conn, "SELECT unit_price FROM raw_material WHERE id = '$raw_id' AND id_partner='$id_partner' AND id_master='$id_master' AND deleted_at IS NULL");
                                        $dataPrice = mysqli_fetch_all($sqlPrice, MYSQLI_ASSOC);
                                        $price_raw = (float)$dataPrice[0]['unit_price'];
    
                                        $newCogs += $price_raw * $qty_var;
                                    }
    
                                    // update cogs variant
                                    $sqlUpdateCOGS = mysqli_query($db_conn, "UPDATE variant SET cogs = '$newCogs' WHERE id = '$variant_id' AND deleted_at IS NULL");
                                }
                            }
                        }
    
                        // ubah cogs variant disini end
    
                        // cari ke recipe
                        $qMenuRecipe = mysqli_query($db_conn, "SELECT id_menu FROM recipe WHERE id_raw='$id_raw_material' AND id_menu != '0' AND deleted_at IS NULL;");
                        if (mysqli_num_rows($qMenuRecipe) > 0) {
                            $recipes = mysqli_fetch_all($qMenuRecipe, MYSQLI_ASSOC);
    
                            // dari recipe yang ada id_menu nya cari ke menu menggunakan id_menu itu
                            foreach ($recipes as $recipe) {
                                $idMenu = $recipe['id_menu'];
                                $newHpp = 0;
                                $qGet = mysqli_query($db_conn, "SELECT r.qty, rm.unit_price AS price FROM `recipe` r LEFT JOIN `raw_material` rm ON rm.id = r.id_raw WHERE r.id_menu = '$idMenu' AND rm.id_master='$id_master' AND rm.id_partner='$id_partner' AND r.deleted_at IS NULL AND rm.deleted_at IS NULL;");
    
                                $menuRecipes = mysqli_fetch_all($qGet, MYSQLI_ASSOC);
                                foreach ($menuRecipes as $val) {
                                    $priceVal = (float)$val['price'];
                                    $qtyVal = (float)$val['qty'];
                                    $subTotal = $priceVal * $qtyVal;
                                    $newHpp += $subTotal;
                                }
    
                                // update hpp menu disini
                                $qUpdateHpp = mysqli_query($db_conn, "UPDATE menu SET hpp='$newHpp' WHERE id='$idMenu'");
                            }
                        }
                    } else {
                        $qUpdateMenu = mysqli_query($db_conn, "UPDATE menu SET hpp='$newPrice' WHERE id='$id_menu'");
                    }
    
                    $ap += $price * $qty;
                    $qInsertPaket = "INSERT INTO goods_receipt_detail SET id_gr='$id_gr', id_menu='$id_menu', id_raw_material='$id_raw_material', qty='$qty', id_metric='$id_metric', " . $exp_date . "unit_price='$price'";
                    $insertPaket = mysqli_query($db_conn, $qInsertPaket);
                    if ($insertPaket) {
                        $remaining = 0;
                        $last_id = mysqli_insert_id($db_conn);
                        // echo ($last_id);
                        if ($id_menu != 0 && $id_raw_material == 0) {
                            $updateMenuStock = mysqli_query($db_conn, "UPDATE `menu` SET `stock` = `stock` + $finalQty WHERE `menu`.`id` = $id_menu AND menu.deleted_at IS NULL AND menu.id_partner='$id_partner'");
                            $remainingStock = mysqli_query($db_conn, "SELECT remaining FROM stock_movements WHERE menu_id='$id_menu' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
                            if (mysqli_num_rows($remainingStock) > 0) {
                                $resRS =  mysqli_fetch_all($remainingStock, MYSQLI_ASSOC);
                                $remaining = (float)$resRS[0]['remaining'];
                            } else {
                                $remaining = 0;
                            }
                            $remaining = $remaining + $finalQty;
                            $movementMenu = mysqli_query($db_conn, "INSERT INTO stock_movements SET master_id='$id_master', partner_id='$id_partner', menu_id='$id_menu', metric_id='$id_metric', type=0, gr='$finalQty', remaining='$remaining'");
                            if ($updateMenuStock) {
                                // echo("Masuk IF");
                                $boolMenu = true;
                            }
                        } else if ($id_menu == 0 && $id_raw_material != 0) {
                            $q = "INSERT INTO raw_material_stock SET id_raw_material='$id_raw_material', stock='$finalQty', id_metric='$id_metric', " . $expDate . " id_goods_receipt_detail='$last_id'";
                            $insertStockRawPaket = mysqli_query($db_conn, $q);
                            $remainingStock = mysqli_query($db_conn, "SELECT remaining FROM stock_movements WHERE raw_id='$id_raw_material' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
                            if (mysqli_num_rows($remainingStock) > 0) {
                                $resRS =  mysqli_fetch_all($remainingStock, MYSQLI_ASSOC);
                                $remaining = (float)$resRS[0]['remaining'];
                            } else {
                                $remaining = 0;
                            }
                            $remaining = $remaining + $finalQty;
                            $movementMenu = mysqli_query($db_conn, "INSERT INTO stock_movements SET master_id='$id_master', partner_id='$id_partner', raw_id='$id_raw_material', metric_id='$id_metric', type=0, gr='$finalQty', remaining='$remaining'");
                            if ($insertStockRawPaket) {
                                // echo("Masuk IF");
                                $boolRaw = true;
                            }
                        }
                    }
                }
    
    
                $updateReceived = mysqli_query($db_conn, "UPDATE purchase_orders SET received=1, updated_at=NOW() WHERE id='$purchase_order_id'");
                $insertAP = mysqli_query($db_conn, "INSERT INTO account_payables SET partner_id='$id_partner', gr_id='$id_gr', supplier_id='$supplierID', amount='$ap'");
                $success = 1;
                $status = 200;
                $msg = "Berhasil terima barang. Stok dan hutang sudah diperbarui";
                }  else {
                    $success = 0;
                    $status = 204;
                    $msg = "Failed";
                }
        } 
        else {
            $success = 0;
            $status = 204;
            $msg = "Jumlah diterima tidak boleh melebihi jumlah dalam PO dan tidak boleh kurang dari 0";
        }

    } else {
        $success = 0;
        $status = 400;
        $msg = "Missing Required Field";
    }
}
echo json_encode(["msg" => $msg, "status" => $status, "success" => $success, "idInsert" => $idInsert, "q" => $remaining, "f" => $finalQty]);
