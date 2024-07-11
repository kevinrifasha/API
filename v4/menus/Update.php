<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header(
    "Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"
);
require "../../db_connection.php";
require_once "../auth/Token.php";

//init var
$headers = [];
$rx_http = "/\AHTTP_/";
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, "", $key);
        $rx_matches = [];
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode("_", $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) {
                $rx_matches[$ak_key] = ucfirst($ak_val);
            }
            $arh_key = implode("-", $rx_matches);
        }
        $headers[$arh_key] = $val;
    }
}
$tokenizer = new Token();
$token = "";
$res = [];

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption("decrypt", $token));
if (isset($tokenValidate["success"]) && $tokenValidate["success"] == 0) {
    $status = $tokenValidate["status"];
    $msg = $tokenValidate["msg"];
    $success = 0;
} else {
    // POST DATA
    $obj = json_decode(file_get_contents("php://input"));
    $now = date("Y-m-d H:i:s");
    if (
        isset($obj->id) &&
        !empty($obj->id) &&
        isset($obj->name) &&
        !empty($obj->name) &&
        isset($obj->price)
    ) {
        if (!isset($obj->hpp) || empty($obj->hpp)) {
            $obj->hpp = 0;
        }
        if (!isset($obj->is_auto_cogs) || empty($obj->is_auto_cogs)) {
            $obj->is_auto_cogs = 0;
        }
        $imp = 0;
        if(!isset($obj->is_multiple_price)){
            $imp = 0;
        }else{
            $imp = $obj->is_multiple_price;
        }
        $obj->name = mysqli_real_escape_string($db_conn, $obj->name);
        $obj->description = mysqli_real_escape_string($db_conn, $obj->description);
        $insert = mysqli_query(
            $db_conn,
            "UPDATE
            `menu`
          SET
            `nama` = '$obj->name',
            `harga` = '$obj->price',
            `Deskripsi` = '$obj->description',
            `category` = '',
            `id_category` = '$obj->id_category',
            `img_data` = '$obj->img_data',
            `sku` = '$obj->sku',
            `enabled` = '$obj->enabled',
            `stock` = '$obj->stock',
            `hpp` = '$obj->hpp',
            `harga_diskon` = '$obj->discount_price',
            `is_variant` = '$obj->is_variant',
            `is_recommended` = '$obj->is_recommended',
            `is_recipe` = '$obj->is_recipe',
            `thumbnail` = '$obj->thumbnail',
            is_auto_cogs = '$obj->is_auto_cogs',
            `is_multiple_price` = '$imp',
            updated_at = NOW(),
            `show_in_sf`='$obj->show_in_sf', 
            `show_in_waiter`='$obj->show_in_waiter'
          WHERE
            `id` = '$obj->id'
          "
           );

        if ($insert) {
            if(isset($obj->surcharges)&& !empty($obj->surcharges)){
                $deleteExisting = mysqli_query($db_conn, "DELETE FROM menu_surcharge_types WHERE menu_id='$obj->id' AND partner_id='$token->id_partner'");
                foreach($obj->surcharges AS $s){
                    $id = $s->id_surcharge;
                    $price = $s->surcharge;
                    $insert = mysqli_query($db_conn,"INSERT INTO menu_surcharge_types SET menu_id='$obj->id', surcharge_id='$id', partner_id='$token->id_partner', price='$price'");
                }
            }
            if ($obj->is_variant == "1" || $obj->is_variant == 1) {
                $sql = mysqli_query(
                    $db_conn,
                    "SELECT * FROM `menus_variantgroups` WHERE menu_id='{$obj->id}' AND deleted_at IS NULL"
                );
                if (mysqli_num_rows($sql) > 0) {
                    $menuVariantGroups = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    foreach ($menuVariantGroups as $value) {
                        $deleted = 1;
                        foreach ($obj->variants as $data) {
                            if (
                                $data->id_variant_group ==
                                $value["variant_group_id"]
                            ) {
                                $deleted = 0;
                            }
                        }
                        if ($deleted == 1) {
                            $DID = $value["id"];
                            $sql = mysqli_query(
                                $db_conn,
                                "DELETE FROM `menus_variantgroups` WHERE id='$DID'"
                            );
                        }
                    }
                }

                //add menu variants groups
                $sql = mysqli_query(
                    $db_conn,
                    "SELECT * FROM `menus_variantgroups` WHERE menu_id='{$obj->id}' AND deleted_at IS NULL"
                );
                if (mysqli_num_rows($sql) > 0) {
                    $menuVariantGroups = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    foreach ($obj->variants as $data) {
                        $add = true;
                        $vgId = 0;
                        foreach ($menuVariantGroups as $value) {
                            if (
                                $data->id_variant_group ==
                                $value["variant_group_id"]
                            ) {
                                $add = false;
                            }
                        }
                        if ($add == true) {
                            $id_vg = $data->id_variant_group;
                            $insert = mysqli_query(
                                $db_conn,
                                "INSERT INTO `menus_variantgroups`(`menu_id`, `variant_group_id`, `created_at`) VALUES ('$obj->id', '$id_vg', NOW())"
                            );
                        }
                    }
                } else {
                    foreach ($obj->variants as $data) {
                        $id_vg = $data->id_variant_group;
                        $insert = mysqli_query(
                            $db_conn,
                            "INSERT INTO `menus_variantgroups`(`menu_id`, `variant_group_id`, `created_at`) VALUES ('$obj->id', '$id_vg', NOW())"
                        );
                    }
                }
            }
            if ($obj->is_variant == "0" || $obj->is_variant == 0) {
                $sql = mysqli_query(
                    $db_conn,
                    "SELECT * FROM `menus_variantgroups` WHERE menu_id='$obj->id' AND deleted_at IS NULL"
                );
                if (mysqli_num_rows($sql) > 0) {
                    $menuVariantGroups = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    foreach ($menuVariantGroups as $value) {
                        $DID = $value["id"];
                        $sql = mysqli_query(
                            $db_conn,
                            "SELECT deleted_at=NOW() FROM `menus_variantgroups` WHERE id='$DID'"
                        );
                    }
                }
            }

            if($imp == 0 || $imp == "0"){
                $updateSurcharge = "UPDATE menu_surcharge_types SET deleted_at = NOW() WHERE menu_id='$obj->id' AND partner_id='$token->id_partner'";
                $update = mysqli_query($db_conn,$updateSurcharge);
            }

            if ($obj->is_recipe == "1" || $obj->is_recipe == 1) {
                $rawIDs = $obj->recipes;
                $registered = [];
                $sql = mysqli_query(
                    $db_conn,
                    "SELECT * FROM `recipe` WHERE id_menu ='$obj->id'"
                );
                if (mysqli_num_rows($sql) > 0) {
                    $registered = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                }
                foreach ($registered as $rgstrd) {
                    $delete = true;
                    foreach ($rawIDs as $rawID) {
                        $ID = $rgstrd["id"];
                        $rID = $rawID->id_raw;
                        $mID = $rawID->id_metric;
                        $qty = $rawID->qty;
                        if (
                            $rID == $rgstrd["id_raw"] &&
                            $mID == $rgstrd["id_metric"]
                        ) {
                            $delete = false;
                        }
                    }
                    if ($delete == true) {
                        $DID = $rgstrd["id"];
                        $deleted = mysqli_query(
                            $db_conn,
                            "DELETE FROM `recipe` WHERE id='$DID'"
                        );
                    }
                }

                $rawIDs = $obj->recipes;
                $registered = [];
                $sql = mysqli_query(
                    $db_conn,
                    "SELECT * FROM `recipe` WHERE id_menu ='$obj->id'"
                );
                if (mysqli_num_rows($sql) > 0) {
                    $registered = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                }
                foreach ($rawIDs as $rawID) {
                    $rID = $rawID->id_raw;
                    $mID = $rawID->id_metric;
                    $qty = $rawID->qty;
                    $uID = 0;
                    foreach ($registered as $rgstrd) {
                        if ($rID == $rgstrd["id_raw"]) {
                            $uID = $rgstrd["id"];
                        }
                    }
                    if ($uID == 0) {
                        $sqlInsert = mysqli_query(
                            $db_conn,
                            "INSERT INTO `recipe`(`id_menu`, `id_raw`, `qty`, `id_metric`, `partner_id`) VALUES ('$obj->id', '$rID', '$qty', '$mID', '$token->id_partner')"
                        );
                    } else {
                        $sqlInsert = mysqli_query(
                            $db_conn,
                            "UPDATE `recipe` SET id_menu='$obj->id', id_raw='$rID', qty='$qty', id_metric='$mID' WHERE id='$uID'"
                        );
                    }
                }
            }
            if ((int) $obj->is_auto_cogs == 1) {
                $id_partner = $token->id_partner;
                $getIAC = mysqli_query(
                    $db_conn,
                    "SELECT is_average_cogs FROM `partner` WHERE id='$id_partner'"
                );
                $IAC = mysqli_fetch_all($getIAC, MYSQLI_ASSOC);
                $is_average_cogs = (int) $IAC[0]["is_average_cogs"];

                if ($is_average_cogs == 0) {
                    foreach ($recipe as $raw) {
                        $rawPrice = 0;
                        $recipeRawID = $raw["id_raw"];
                        $getRaw = mysqli_query(
                            $db_conn,
                            "SELECT unit_price, id_metric_price FROM `raw_material` WHERE id='$recipeRawID'"
                        );
                        $raws = mysqli_fetch_all($getRaw, MYSQLI_ASSOC);
                        $price = (int) $raws[0]["unit_price"];
                        $id_metric = $raws[0]["id_metric_price"];
                        $getPO = mysqli_query(
                            $db_conn,
                            "SELECT qty, metric_id, price, price/qty unit_price FROM `purchase_orders_details` WHERE raw_id='$recipeRawID' AND deleted_at IS NULL ORDER BY unit_price DESC LIMIT 1"
                        );
                        $po = mysqli_fetch_all($getPO, MYSQLI_ASSOC);
                        $i = 0;

                        $poMetricID = 0;
                        $poQty = 0;
                        $poPrice = 0;
                        foreach ($po as $item) {
                            $poMetricID = $item["metric_id"];
                            $poQty = (int) $item["qty"];
                            $poPrice = (int) $item["price"];
                            $rawPrice = (int) $item["unit_price"];
                        }

                        $rawMetric = $poMetricID;
                        if ($price > $rawPrice) {
                            $rawPrice = $price;
                            $rawMetric = $id_metric;
                        }

                        if ($rawMetric == $raw["id_metric"]) {
                            $rawPrices += $rawPrice * $raw["qty"];
                        } else {
                            $id_metric = $raw["id_metric"];
                            $getMC = mysqli_query(
                                $db_conn,
                                "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$rawMetric' AND `id_metric2`='$id_metric'"
                            );
                            if (mysqli_num_rows($getMC) > 0) {
                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                $conVal = (int) $mc[0]["value"];
                                $rawPrices +=
                                    ($rawPrice / $conVal) * $raw["qty"];
                            } else {
                                $getMC = mysqli_query(
                                    $db_conn,
                                    "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$id_metric' AND `id_metric2`='$rawMetric'"
                                );
                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                $conVal = (int) $mc[0]["value"];
                                $rawPrices += $rawPrice * $conVal * $raw["qty"];
                            }
                        }
                        $cogs = ceil($rawPrices);
                    }
                } else {
                    foreach ($recipe as $raw) {
                        $rawPrice = 0;
                        $recipeRawID = $raw["id_raw"];
                        $getRaw = mysqli_query(
                            $db_conn,
                            "SELECT unit_price, id_metric_price FROM `raw_material` WHERE id='$recipeRawID'"
                        );
                        $raws = mysqli_fetch_all($getRaw, MYSQLI_ASSOC);
                        $price = (int) $raws[0]["unit_price"];
                        $id_metric = $raws[0]["id_metric_price"];
                        $getPO = mysqli_query(
                            $db_conn,
                            "SELECT qty, metric_id, price FROM `purchase_orders_details` WHERE raw_id='$recipeRawID' AND deleted_at IS NULL"
                        );
                        $po = mysqli_fetch_all($getPO, MYSQLI_ASSOC);
                        $i = 0;

                        $poMetricID = 0;
                        $poQty = 0;
                        $poPrice = 0;
                        foreach ($po as $item) {
                            if ($i == 0) {
                                $poMetricID = $item["metric_id"];
                                $poQty = (int) $item["qty"];
                                $poPrice = (int) $item["price"];
                            } else {
                                $poPrice += (int) $item["price"];
                                if ($poMetricID == $item["metric_id"]) {
                                    $poQty += (int) $item["qty"];
                                } else {
                                    $findMetricID = $item["metric_id"];
                                    $getMC = mysqli_query(
                                        $db_conn,
                                        "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$poMetricID' AND `id_metric2`='$findMetricID'"
                                    );
                                    if (mysqli_num_rows($getMC) > 0) {
                                        $mc = mysqli_fetch_all(
                                            $getMC,
                                            MYSQLI_ASSOC
                                        );
                                        $poMetricID = $findMetricID;
                                        $conVal = (int) $mc[0]["value"];
                                        $poQty = $poQty * $conVal;
                                        $poQty += (int) $item["qty"];
                                    } else {
                                        $getMC = mysqli_query(
                                            $db_conn,
                                            "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$findMetricID' AND `id_metric2`='$poMetricID'"
                                        );
                                        $mc = mysqli_fetch_all(
                                            $getMC,
                                            MYSQLI_ASSOC
                                        );
                                        $conVal = (int) $mc[0]["value"];
                                        $item["qty"] = $item["qty"] * $conVal;
                                        $poQty += (int) $item["qty"];
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
                            $getMC = mysqli_query(
                                $db_conn,
                                "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$rawMetric' AND `id_metric2`='$id_metric'"
                            );
                            if (mysqli_num_rows($getMC) > 0) {
                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                $rawMetric = $id_metric;
                                $conVal = (int) $mc[0]["value"];
                                $rawPrice = $rawPrice / $conVal;
                                $rawPrice += $price;
                                $rawPrice = $rawPrice / 2;
                            } else {
                                $getMC = mysqli_query(
                                    $db_conn,
                                    "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$id_metric' AND `id_metric2`='$rawMetric'"
                                );
                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                $conVal = (int) $mc[0]["value"];
                                $price = $price / $conVal;
                                $rawPrice += $price;
                                $rawPrice = $rawPrice / 2;
                            }
                        }
                        if ($rawMetric == $raw["id_metric"]) {
                            $rawPrices += $rawPrice * $raw["qty"];
                        } else {
                            $id_metric = $raw["id_metric"];
                            $getMC = mysqli_query(
                                $db_conn,
                                "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$rawMetric' AND `id_metric2`='$id_metric'"
                            );
                            if (mysqli_num_rows($getMC) > 0) {
                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                $conVal = (int) $mc[0]["value"];
                                $rawPrices +=
                                    ($rawPrice / $conVal) * $raw["qty"];
                            } else {
                                $getMC = mysqli_query(
                                    $db_conn,
                                    "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$id_metric' AND `id_metric2`='$rawMetric'"
                                );
                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                $conVal = (int) $mc[0]["value"];
                                $rawPrices += $rawPrice * $conVal * $raw["qty"];
                            }
                        }
                        $cogs = ceil($rawPrices);
                    }
                }
                $updateMenu = mysqli_query(
                    $db_conn,
                    "UPDATE `menu` SET `hpp`='$cogs' WHERE id='$obj->id'"
                );
            }
            $msg = "Berhasil mengubah data";
            $success = 1;
            $status = 200;
        } else {
            $msg = "Gagal mengubah data. Mohon periksa data dan coba lagi";
            $success = 0;
            $status = 204;
        }
    } else {
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 400;
    }
}
echo json_encode(["status" => $status, "success" => $success, "msg" => $msg]);

?>
