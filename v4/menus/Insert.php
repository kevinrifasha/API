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
$tokenizer = new Token();
$token = '';
$res = array();

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}
$id_menu = "";
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
$id_partner = $token->id_partner;
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    // POST DATA
    $obj = json_decode(file_get_contents('php://input'));
    $now = date("Y-m-d H:i:s");
    $is_average_cogs = 0;
    if (
        isset($obj->name) && !empty($obj->name)
        && isset($obj->price)
    ) {
        $imp = 0;
        if (!isset($obj->is_multiple_price)) {
            $imp = 0;
        } else {
            $imp = $obj->is_multiple_price;
        }
        if (empty($obj->hpp)) {
            $obj->hpp = 0;
        }
        $obj->name = mysqli_real_escape_string($db_conn, $obj->name);
        $obj->description = mysqli_real_escape_string($db_conn, $obj->description);
        $insertMenu = "INSERT INTO `menu` SET `id_partner`='{$token->id_partner}',`nama`='{$obj->name}',`harga`='{$obj->price}', `sku`='{$obj->sku}',`Deskripsi`='{$obj->description}',`id_category`='{$obj->id_category}',`img_data`='{$obj->img_data}',`enabled`='{$obj->enabled}',`stock`='{$obj->stock}',`hpp`='{$obj->hpp}',`harga_diskon`='{$obj->discount_price}',`is_variant`='{$obj->is_variant}',`is_recommended`='{$obj->is_recommended}',`is_recipe`='$obj->is_recipe', `thumbnail`='$obj->thumbnail', `is_multiple_price`='$imp', `show_in_sf`='$obj->show_in_sf', `show_in_waiter`='$obj->show_in_waiter' ";
        $insert = mysqli_query($db_conn, $insertMenu);
        $id_menu = mysqli_insert_id($db_conn);

        if ($obj->is_recipe == 0 || $obj->is_recipe == "0") {
            $movement = mysqli_query($db_conn, "INSERT INTO stock_movements SET master_id='$token->id_master', partner_id='$token->id_partner', menu_id='$id_menu', metric_id='6', type=0, initial='$obj->stock', remaining='$obj->stock'");
        }

        if ($insert) {
            if (isset($obj->surcharges) && !empty($obj->surcharges)) {
                foreach ($obj->surcharges as $s) {
                    $id = $s->id_surcharge;
                    $price = $s->surcharge;
                    $insert = mysqli_query($db_conn, "INSERT INTO menu_surcharge_types SET menu_id='$id_menu', surcharge_id='$id', partner_id='$token->id_partner', price='$price'");
                }
            }
            if ($obj->is_variant == '1' || $obj->is_variant == 1) {
                foreach ($obj->variants as $value) {
                    $id_vg = $value->id_variant_group;
                    $insert = mysqli_query($db_conn, "INSERT INTO `menus_variantgroups`(`menu_id`, `variant_group_id`, `created_at`) VALUES ('$id_menu', '$id_vg', NOW())");
                }
            }

            if ($obj->is_recipe == '1' || $obj->is_recipe == 1) {
                foreach ($obj->recipes as $value) {
                    $id_raw = $value->id_raw;
                    $qty = $value->qty;
                    $id_metric = $value->id_metric;
                    $insert = mysqli_query($db_conn, "INSERT INTO `recipe`(`id_menu`, `id_raw`, `qty`, `id_metric`, `id_variant`, `partner_id`) VALUES ('$id_menu', '$id_raw', '$qty', '$id_metric', '0', '$id_partner')");
                }
            }
            if ((int)$obj->is_auto_cogs == 1) {


                $getIAC = mysqli_query($db_conn, "SELECT is_average_cogs FROM `partner` WHERE id='$id_partner'");
                $IAC = mysqli_fetch_all($getIAC, MYSQLI_ASSOC);
                $is_average_cogs = (int) $IAC[0]['is_average_cogs'];

                if ($is_average_cogs == 0) {
                    foreach ($recipe as $raw) {
                        $rawPrice = 0;
                        $recipeRawID = $raw['id_raw'];
                        $getRaw = mysqli_query($db_conn, "SELECT unit_price, id_metric_price FROM `raw_material` WHERE id='$recipeRawID'");
                        $raws = mysqli_fetch_all($getRaw, MYSQLI_ASSOC);
                        $price = (int) $raws[0]['unit_price'];
                        $id_metric = $raws[0]['id_metric_price'];
                        $getPO = mysqli_query($db_conn, "SELECT qty, metric_id, price, price/qty unit_price FROM `purchase_orders_details` WHERE raw_id='$recipeRawID' AND deleted_at IS NULL ORDER BY unit_price DESC LIMIT 1");
                        $po = mysqli_fetch_all($getPO, MYSQLI_ASSOC);
                        $i = 0;

                        $poMetricID = 0;
                        $poQty = 0;
                        $poPrice = 0;
                        foreach ($po as $item) {
                            $poMetricID = $item['metric_id'];
                            $poQty = (int) $item['qty'];
                            $poPrice = (int) $item['price'];
                            $rawPrice = (int) $item['unit_price'];
                        }

                        $rawMetric = $poMetricID;
                        if ($price > $rawPrice) {
                            $rawPrice = $price;
                            $rawMetric = $id_metric;
                        }

                        if ($rawMetric == $raw['id_metric']) {
                            $rawPrices += $rawPrice * $raw['qty'];
                        } else {
                            $id_metric = $raw['id_metric'];
                            $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$rawMetric' AND `id_metric2`='$id_metric'");
                            if (mysqli_num_rows($getMC) > 0) {
                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                $conVal = (int) $mc[0]['value'];
                                $rawPrices += $rawPrice / $conVal * $raw['qty'];
                            } else {
                                $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$id_metric' AND `id_metric2`='$rawMetric'");
                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                $conVal = (int) $mc[0]['value'];
                                $rawPrices += $rawPrice * $conVal * $raw['qty'];
                            }
                        }
                        $cogs = ceil($rawPrices);
                    }
                } else {
                    foreach ($recipe as $raw) {
                        $rawPrice = 0;
                        $recipeRawID = $raw['id_raw'];
                        $getRaw = mysqli_query($db_conn, "SELECT unit_price, id_metric_price FROM `raw_material` WHERE id='$recipeRawID'");
                        $raws = mysqli_fetch_all($getRaw, MYSQLI_ASSOC);
                        $price = (int) $raws[0]['unit_price'];
                        $id_metric = $raws[0]['id_metric_price'];
                        $getPO = mysqli_query($db_conn, "SELECT qty, metric_id, price FROM `purchase_orders_details` WHERE raw_id='$recipeRawID' AND deleted_at IS NULL");
                        $po = mysqli_fetch_all($getPO, MYSQLI_ASSOC);
                        $i = 0;

                        $poMetricID = 0;
                        $poQty = 0;
                        $poPrice = 0;
                        foreach ($po as $item) {
                            if ($i == 0) {
                                $poMetricID = $item['metric_id'];
                                $poQty = (int) $item['qty'];
                                $poPrice = (int) $item['price'];
                            } else {
                                $poPrice += (int) $item['price'];
                                if ($poMetricID == $item['metric_id']) {
                                    $poQty += (int) $item['qty'];
                                } else {
                                    $findMetricID = $item['metric_id'];
                                    $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$poMetricID' AND `id_metric2`='$findMetricID'");
                                    if (mysqli_num_rows($getMC) > 0) {
                                        $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                        $poMetricID = $findMetricID;
                                        $conVal = (int) $mc[0]['value'];
                                        $poQty = $poQty * $conVal;
                                        $poQty += (int) $item['qty'];
                                    } else {
                                        $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$findMetricID' AND `id_metric2`='$poMetricID'");
                                        $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                        $conVal = (int) $mc[0]['value'];
                                        $item['qty'] = $item['qty'] * $conVal;
                                        $poQty += (int) $item['qty'];
                                    }
                                }
                            }
                            $i += 1;
                        }
                        if ($poQty > 0 && $poPrice > 0) {
                            $rawPrice = $poPrice / $poQty;
                        }
                        $rawMetric = $poMetricID;
                        if ($rawMetric == $id_metric || $poMetricID == 0) {
                            $rawPrice += $price;
                            if ($poMetricID != 0) {
                                $rawPrice = $rawPrice / 2;
                            } else {
                                $rawMetric = $id_metric;
                            }
                        } else {
                            $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$rawMetric' AND `id_metric2`='$id_metric'");
                            if (mysqli_num_rows($getMC) > 0) {
                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                $rawMetric = $id_metric;
                                $conVal = (int) $mc[0]['value'];
                                $rawPrice = $rawPrice / $conVal;
                                $rawPrice += $price;
                                $rawPrice = $rawPrice / 2;
                            } else {
                                $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$id_metric' AND `id_metric2`='$rawMetric'");
                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                $conVal = (int) $mc[0]['value'];
                                $price = $price / $conVal;
                                $rawPrice += $price;
                                $rawPrice = $rawPrice / 2;
                            }
                        }
                        if ($rawMetric == $raw['id_metric']) {
                            $rawPrices += $rawPrice * $raw['qty'];
                        } else {
                            $id_metric = $raw['id_metric'];
                            $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$rawMetric' AND `id_metric2`='$id_metric'");
                            if (mysqli_num_rows($getMC) > 0) {
                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                $conVal = (int) $mc[0]['value'];
                                $rawPrices += $rawPrice / $conVal * $raw['qty'];
                            } else {
                                $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$id_metric' AND `id_metric2`='$rawMetric'");
                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                $conVal = (int) $mc[0]['value'];
                                $rawPrices += $rawPrice * $conVal * $raw['qty'];
                            }
                        }
                        $cogs = ceil($rawPrices);
                    }
                }
                $updateMenu = mysqli_query($db_conn, "UPDATE `menu` SET `hpp`='$cogs' WHERE id='$obj->id'");
            }

            $msg = "Berhasil menambahkan data";
            $success = 1;
            $status = 200;
        } else {
            $msg = "Gagal menambahkan data";
            $success = 0;
            $status = 204;
        }
    } else {
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 400;
    }
}
echo json_encode(["status" => $status, "success" => $success, "msg" => $msg, "id_menu" => $id_menu]);
