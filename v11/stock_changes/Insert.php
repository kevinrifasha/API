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

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
$remainingValue = 0;
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {

    // POST DATA
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (
        (
            (isset($data['menu_id']) && !empty($data['menu_id']))
            ||
            (isset($data['raw_material_id']) && !empty($data['raw_material_id']))
        )
        && isset($data['metric_id'])
        && isset($data['qty'])
        && isset($data['notes'])
        && !empty($data['metric_id'])
        && !empty($data['notes'])
    ) {
        $realValue = $data['qty'];

        $raw_material_id = 0;
        if (isset($data['raw_material_id']) && !empty($data['raw_material_id'])) {
            $raw_material_id = $data['raw_material_id'];
            $qHPP = "SELECT unit_price AS unitPrice FROM raw_material WHERE id='$raw_material_id'";
            $qUpdate = "DELETE FROM `raw_material_stock` WHERE `id_raw_material`='$raw_material_id';";
            $qRemaining1 = "SELECT remaining FROM stock_movements sm WHERE sm.raw_id='$raw_material_id' AND sm.deleted_at IS NULL ORDER BY id ASC LIMIT 1";
            $qRemaining2 = "SELECT remaining FROM stock_movements sm WHERE sm.raw_id='$raw_material_id' AND sm.deleted_at IS NULL ORDER BY id DESC LIMIT 1";
        } else {
            $raw_material_id = 0;
        }

        $raw_material_stock_id = 0;
        if (isset($data['raw_material_stock_id']) && !empty($data['raw_material_stock_id'])) {
            $raw_material_stock_id = $data['raw_material_stock_id'];
        } else {
            $raw_material_stock_id = 0;
        }

        $menu_id = 0;
        if (!empty($data['menu_id'])) {
            $hihi = "masuk if";
            $menu_id = $data['menu_id'];
            $qHPP = "SELECT hpp AS unitPrice FROM menu WHERE id='$menu_id'";
            $qUpdate = "UPDATE `menu` SET `stock`='$realValue', `updated_at`=NOW() WHERE `id`='$menu_id'";
            $qRemaining1 = "SELECT remaining FROM stock_movements sm WHERE sm.menu_id='$menu_id' AND sm.deleted_at IS NULL ORDER BY id ASC LIMIT 1";
            $qRemaining2 = "SELECT remaining FROM stock_movements sm WHERE sm.menu_id='$menu_id' AND sm.deleted_at IS NULL ORDER BY id DESC LIMIT 1";
        } else {
            $hihi = "ga if";
            $menu_id = 0;
        }

        $metric_id = $data['metric_id'];
        $qty = $data['qty'];
        $notes = $data['notes'];
        $bookValue = $data['qty_before'] ?? 0;

        $update = mysqli_query($db_conn, $qUpdate);
        $hpp = mysqli_query($db_conn, $qHPP);
        $getRemaining = mysqli_query($db_conn, $qRemaining1);

        if (mysqli_num_rows($getRemaining) > 0) {
            $resRemaining = mysqli_fetch_all($getRemaining, MYSQLI_ASSOC);
            $remainingValue = $resRemaining[0]['remaining'];
        } else {
            $remainingValue = 0;
        }

        if ($update) {
            $sqlRemaining = mysqli_query($db_conn, $qRemaining2);
            $getRemainingData = mysqli_fetch_all($sqlRemaining, MYSQLI_ASSOC);
            $remainingQty = $getRemainingData[0]['remaining'];
            $adjusted = $qty - $bookValue;

            if ($bookValue == $remainingValue) {
                // skema 1
                if ($qty < 0) {
                    $adjusted = (0 - $bookValue) + $qty;
                    $remaining = $adjusted + $remainingQty;
                } else {
                    $remaining = $adjusted + $remainingQty;
                }
            } else {
                // skema 2
                if ($qty < 0) {
                    $adjusted = $qty - $remainingQty;
                } else {
                    if ($remainingQty < 0) {
                        $adjusted = $qty - $bookValue;
                    } else {
                        // $adjusted = $qty - $bookValue - $remainingValue;
                        $adjusted = $adjusted;
                    }
                }
                $remaining = $qty;
            }
        
            if ($menu_id != 0 || $menu_id != "0" || $menu_id != "") {
            } else {
                $xx = mysqli_query($db_conn, "INSERT INTO `raw_material_stock` SET `stock`='$remaining', `id_metric`='$metric_id', `id_raw_material`='$raw_material_id'");
            }

            $resHPP = mysqli_fetch_all($hpp, MYSQLI_ASSOC);
            $unitPrice = $resHPP[0]['unitPrice'];
            $diff = (float)$qty - (float)$bookValue;
            $moneyValue = $diff * $unitPrice;
            if ($menu_id == "" || $menu_id == false) {
                $menu_id = 0;
            }
            // $qInsert  = "INSERT INTO `stock_changes`(`master_id`, `partner_id`, `raw_material_id`, `menu_id`, `metric_id`, `qty`, `notes`, `created_by`, `created_at`, `raw_material_stock_id`, qty_before, money_value) VALUES ('$token->id_master', '$token->id_partner', '$raw_material_id', '$menu_id', '$metric_id', '$qty', '$notes', '$token->id', NOW(), '$raw_material_stock_id', '$bookValue', '$moneyValue')";
            $qInsert  = "INSERT INTO stock_changes SET master_id='$token->id_master', partner_id='$token->id_partner', raw_material_id='$raw_material_id', menu_id='$menu_id', metric_id='$metric_id', qty='$qty', notes='$notes', created_by='$token->id', created_at=NOW(), raw_material_stock_id='$raw_material_stock_id', qty_before='$bookValue', money_value='$moneyValue'";

            // $insert = mysqli_query($db_conn, "INSERT INTO `stock_changes` (`master_id`, `partner_id`, `raw_material_id`, `menu_id`, `metric_id`, `qty`, `notes`, `created_by`, `created_at`, `raw_material_stock_id`, qty_before, money_value) VALUES ('$token->id_master', '$token->id_partner', '$raw_material_id', '$menu_id', '$metric_id', '$qty', '$notes', '$token->id', NOW(), '$raw_material_stock_id', '$bookValue', '$moneyValue')");
            $insert = mysqli_query($db_conn, $qInsert);

            $qAdjustment = "INSERT INTO stock_movements SET master_id='$token->id_master', partner_id='$token->id_partner', menu_id='$menu_id', raw_id='$raw_material_id', metric_id='$metric_id', type=0, adjustment='$adjusted', remaining='$remaining'";
            $adjustment = mysqli_query($db_conn, $qAdjustment);

            if ($insert) {
                $success = 1;
                $status = 200;
                $msg = "Success";
            } else {
                $success = 0;
                $status = 204;
                $msg = "Gagal insert. Mohon periksa data dan coba lagi";
            }
        } else {
            $success = 0;
            $status = 204;
            $msg = "Gagal update. Mohon periksa data dan coba lagi";
        }
    } else {
        $success = 0;
        $status = 400;
        $msg = "Missing Required Field";
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg]);
