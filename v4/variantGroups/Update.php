<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once("../../v3/connection.php");
require_once("../../v3/variantGroupModels/variantGroupManager.php");
require_once("../../v3/variantModels/variantManager.php");
require_once("../../v3/recipeModels/recipeManager.php");

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
$db = connectBase();

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $VariantGroupManager = new VariantGroupManager($db);
    $json = file_get_contents('php://input');
    $obj = json_decode($json, true);
    $cogs = 0;
    $variantGroup = $VariantGroupManager->getById($obj['id']);
    $obj['name'] = mysqli_real_escape_string($db_conn, $obj['name']);
    if (isset($obj['name']) && !empty($obj['name'])) {
        $variantGroup->setName($obj['name']);
    }
    if (isset($obj['type'])) {
        $variantGroup->setType($obj['type']);
    }
    $update = $VariantGroupManager->update($variantGroup);
    if ($update != false) {

        $variantManager = new VariantManager($db);
        $variants = $variantManager->getByVariantGroupId($obj['id']);
        if ($variants != false) {

            // delete variant yang dihapus
            foreach ($variants as $variant) {
                $deleted = 1;
                foreach ($obj['variants'] as $value) {
                    if ($variant->getId() == $value['id']) {
                        $deleted = 0;
                    }
                }
                if ($deleted === 1) {
                    $delete = $variantManager->delete($variant->getId());
                }
            }

            // add variant
            foreach ($obj['variants'] as $value) {
                $add = 1;
                $valName = mysqli_real_escape_string($db_conn, $value['name']);
                foreach ($variants as $variant) {
                    if ($variant->getId() == $value['id']) {
                        $add = 0;
                    }
                }

                if ($add == 1) {
                    $newvariant = new Variant(array("id_variant_group" => $obj['id'], "name" => $valName, "price" => $value['price'], "stock" => $value['stock'], "is_recipe" => $value['is_recipe']));
                    $add = $variantManager->add($newvariant);

                    $query = "SELECT id FROM `variant` ORDER BY `id` DESC LIMIT 1";
                    $sql = mysqli_query($db_conn, $query);
                    $getLastData = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $lastID = $getLastData[0]['id'];

                    // tambahkan recipe nya jika dia pakai recipe
                    if ($value['is_recipe'] == '1' && $add != false || $value['is_recipe'] == 1 && $add != false) {

                        $cogs = 0;
                        foreach ($value['recipes'] as $value) {
                            $recipeManager = new RecipeManager($db);
                            $recipes = new Recipe(array("id_menu" => 0, "id_raw" => $value['id_raw'], "qty" => $value['qty'], "id_metric" => $value['id_metric'], "id_variant" => $add));
                            $addRecipe = $recipeManager->add($recipes);

                            $raw_id = $value['id_raw'];
                            $sql = mysqli_query($db_conn, "SELECT unit_price FROM raw_material WHERE id = '$raw_id' AND deleted_at IS NULL");
                            $dataRaw = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                            $unitPrice = $dataRaw[0]['unit_price'];

                            $cogs += ((int)$value['qty'] * $unitPrice);
                        }

                        // disini nanti masukan cogs nya
                        $queryUpdate = "UPDATE `variant` SET `cogs`='$cogs' WHERE id = '$lastID'";
                        $updateCOGS = mysqli_query($db_conn, $queryUpdate);
                    }
                } else {
                    $variant = $variantManager->getById($value['id']);
                    if (isset($value['name']) && !empty($value['name'])) {
                        $variant->setName($valName);
                    }

                    if (isset($value['price'])) {
                        $variant->setPrice($value['price']);
                    }

                    if (isset($value['stock'])) {
                        $variant->setStock($value['stock']);
                    }

                    // if (isset($value['is_recipe']) && !empty($value['is_recipe'])) {
                    if (isset($value['is_recipe'])) {
                        $cogs = 0;
                        $variant->setIs_recipe($value['is_recipe']);
                        if ($value['is_recipe'] == '1' || $value['is_recipe'] == 1) {
                            $recipeManager = new RecipeManager($db);
                            $rawIDs = $value['recipes'];
                            $registered = $recipeManager->getByVariantID($value['id']);
                            // var_dump($registered);
                            foreach ($registered as $rgstrd) {
                                // var_dump($rgstrd);
                                $details = $rgstrd->getDetails();
                                // var_dump($details);
                                $delete = true;
                                foreach ($rawIDs as $rawID) {
                                    // var_dump($rawID);
                                    $ID = $details['id'];
                                    $rID = $rawID['id_raw'];
                                    $mID = $rawID['id_metric'];
                                    $qty = $rawID['qty'];
                                    if ($rID == $details['id_raw'] && $mID == $details['id_metric']) {
                                        $delete = false;
                                    }
                                }
                                if ($delete == true) {
                                    $deleted = $recipeManager->delete($details['id']);
                                }
                            }
                            $rawIDs = $value['recipes'];
                            $registered = $recipeManager->getByVariantID($value['id']);

                            $existingID = "";
                            $variantID = $value['id'];

                            foreach ($rawIDs as $rawID) {
                                $rID = $rawID['id_raw'];
                                $mID = $rawID['id_metric'];
                                $qty = $rawID['qty'];
                                $uID = 0;
                                $recipeID = "";

                                if (isset($rawID['id'])) {
                                    $uID = $rawID['id'];
                                    $recipeID = $uID;
                                }

                                if (strlen($existingID) == 0) {
                                    $existingID .= $recipeID;
                                } else {
                                    $existingID .= "," . $recipeID;
                                }

                                $sql = mysqli_query($db_conn, "SELECT unit_price FROM raw_material WHERE id = '$rID' AND deleted_at IS NULL");
                                $dataRaw = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                                $unitPrice = $dataRaw[0]['unit_price'];

                                $cogs += $unitPrice * (int)$qty;

                                foreach ($registered as $rgstrd) {
                                    $details = $rgstrd->getDetails();
                                    if ($rID == $details['id_raw']) {
                                        $uID = $details['id'];
                                    }
                                }
                                if ($uID == 0) {
                                    $recipe = new Recipe(array("id" => 0, "id_menu" => 0, "id_raw" => $rID, "qty" => $qty, "id_metric" => $mID, "id_variant" => $value['id']));
                                    $insert = $recipeManager->add($recipe);

                                    $id_variant = $value['id'];
                                    $sqlRecipe = mysqli_query($db_conn, "SELECT id FROM recipe WHERE id_variant = '$id_variant' ORDER BY id DESC LIMIT 1");
                                    $fetchRecipe = mysqli_fetch_all($sqlRecipe, MYSQLI_ASSOC);
                                    $recipe_id = $fetchRecipe[0]['id'];
                                    $existingID .= "," . $recipe_id;
                                } else {
                                    $recipe = new Recipe(array("id" => $uID, "id_menu" => 0, "id_raw" => $rID, "qty" => $qty, "id_metric" => $mID, "id_variant" => $value['id']));
                                    $insert = $recipeManager->update($recipe);
                                }
                            }

                            // delete yang terdelete
                            $qDeleteRecipe = "UPDATE recipe SET deleted_at = NOW() WHERE id_variant = '$variantID' AND id NOT IN ($existingID)";
                            $sqlDeleteRecipe = mysqli_query($db_conn, $qDeleteRecipe);
                        } else {
                            $recipeManager = new RecipeManager($db);
                            $deleted = $recipeManager->deleteByVariant($value['id']);
                        }
                    }

                    $variant->setCogs($cogs);
                    $update = $variantManager->update($variant);
                }
            }
        } else {
            foreach ($obj->variants as $value) {
                $valueName = mysqli_real_escape_string($db_conn, $value->name);
                $variantManager = new VariantManager($db);
                $newvariant = new Variant(array("id_variant_group" => $obj->id, "name" => $valueName, "price" => $value->price, "stock" => $value->stock, "is_recipe" => $value->is_recipe));
                $add = $variantManager->add($newvariant);
            }
        }
    }

    if ($update == true) {
        $success = 1;
        $msg = "Success";
        $status = 200;
    } else {
        $success = 0;
        $msg = "Failed";
        $status = 204;
    }
}


$signupJson = json_encode(["msg" => $msg, "status" => $status, "success" => $success]);
if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
    http_response_code(200);
} else {
    http_response_code($status);
}
echo $signupJson;
