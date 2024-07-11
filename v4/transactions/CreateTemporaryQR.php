<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once '../../includes/DbOperation.php';
require '../../includes/functions.php';
require '../../includes/ValidatorV4.php';

$fs = new functions();
// date_default_timezone_set('Asia/Jakarta');
// POST DATA
$db = new DbOperation();
$validator = new ValidatorV4();
$err = "";

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
$res1 = array();

//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

function generateTransactionID($db_conn, $type, $trxDate, $pid)
{
    $code = $type . "/" . $trxDate . "/" . $pid;
    // $q = mysqli_query($db_conn, "SELECT count(id) as id FROM `transaksi` WHERE id LIKE '%$code%' AND transaksi.deleted_at IS NULL ORDER BY jam DESC LIMIT 1");
    $q = mysqli_query($db_conn, "SELECT count(id) as id FROM `transaksi` WHERE id LIKE '%$code%' ORDER BY jam DESC LIMIT 1");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id1 = (int) $res[0]['id'];
        $index = (int) $id1 + 1;
        if ($index < 10) {
            $index = "00000" . $index;
        } else if ($index < 100) {
            $index = "0000" . $index;
        } else if ($index < 1000) {
            $index = "000" . $index;
        } else if ($index < 10000) {
            $index = "00" . $index;
        } else if ($index < 100000) {
            $index = "0" . $index;
        } else {
            $index = $index;
        }
        $code = $code . "/" . $index;
        return $code;
    } else {
        $code = $code . "/000001";
        return $code;
    }
}

function getService($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT service FROM `partner` WHERE id='$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['service'];
    } else {
        return 0;
    }
}

function getTaxEnabled($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT tax FROM `partner` WHERE id='$id'");
    $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
    $tax = $res[0]['tax'];
    return (float) $tax;
}

function getChargeEwallet($db_conn)
{
    $q = mysqli_query($db_conn, "SELECT value FROM settings WHERE name = 'charge_ewallet'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return $res[0]['value'];
    } else {
        return 0;
    }
}

function getChargeXendit($db_conn)
{
    $q = mysqli_query($db_conn, "SELECT value FROM settings WHERE name = 'charge_xendit'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['value'];
    } else {
        return 0;
    }
}

function getHideCharge($idPartner, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT partner.hide_charge FROM `master`
    JOIN partner ON master.id = partner.id_master
    WHERE partner.id ='$idPartner' ");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['hide_charge'];
    } else {
        return 0;
    }
}

function getStatus($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT master.status FROM `master`
    JOIN partner ON master.id = partner.id_master
    WHERE partner.id ='$id' ");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['status'];
    } else {
        return 0;
    }
}

function getShiftID($id, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT MAX(id) as id FROM `shift` WHERE partner_id='$id' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['id'];
    } else {
        return 0;
    }
}

function getChargeUr($status, $hide, $db_conn, $id)
{
    if ($status == "FULL" && $hide == 0) {
        $q = mysqli_query($db_conn, "SELECT charge_ur as value FROM `partner` WHERE id='$id'");
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            return (int) $res[0]['value'];
        } else {
            return 0;
        }
    } else {
        return 0;
    }
}

function getIDVR($phone, $vr, $trx, $db_conn)
{
    $q = mysqli_query($db_conn, "SELECT id  FROM `user_voucher_ownership` WHERE `userid` LIKE '$phone' AND `id_voucher`='$vr' AND `transaksi_id` IS NULL ORDER BY id ASC limit 1");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id = $res[0]['id'];
        $update = mysqli_query($db_conn, "UPDATE `user_voucher_ownership` SET `transaksi_id`='$trx' WHERE id='$id'");
        return $update;
    } else {
        return 0;
    }
}
$id = "";



$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $validator->checkShiftIDActive($db_conn, $token);
    $partnerID = $token->id_partner;
    $data = json_decode(file_get_contents('php://input'));
    if (
        isset($data->tableCode) &&
        isset($data->pax)
    ) {

        $is_takeaway = 0;
        $trxDate = date("ymd");
        $pid = (int) $partnerID;
        $pax = $data->pax;
        $tableCode = $data->tableCode;
        
        if ($is_takeaway == 1 || $is_takeaway == '1') {
            $id = generateTransactionID($db_conn, "TA", $trxDate, $pid);
        } elseif (isset($data->surcharge_type) && !empty($data->surcharge_type)) {
            $id = generateTransactionID($db_conn, "ET", $trxDate, $pid);
        } else {
            $id = generateTransactionID($db_conn, "DI", $trxDate, $pid);
        }

        $status = 0;
        $service = getService($partnerID, $db_conn);
        $tax = getTaxEnabled($partnerID, $db_conn);
        $charge_ewallet = getChargeEwallet($db_conn);
        $charge_xendit = getChargeXendit($db_conn);
        // // $hide = getHideCharge($partnerID, $db_conn);
        // $status = 2;
        // $pstatus = getStatus($partnerID, $db_conn);
        $today = date("Y-m-d H:i:s");
        // // $charge_ur =(int)getChargeUr($pstatus, $hide, $db_conn, $partnerID);
        $charge_ur = 0;
        $shiftID = (int)getShiftID($partnerID, $db_conn);
        
        $getOverride = mysqli_query($db_conn, "SELECT allow_override_stock,is_queue_tracking FROM partner WHERE id='$partnerID'");
        $override = mysqli_fetch_all($getOverride, MYSQLI_ASSOC);
        $allowOverride = (int)$override[0]['allow_override_stock'];
        if ((int)$override[0]['is_queue_tracking'] == 1) {
            $is_queue = 1;
        }

        $lastQueue = 0;
        if ((int)$is_queue == 1) {

            $qLQ = mysqli_query($db_conn, "SELECT MAX(queue) as LastQueue FROM transaksi WHERE id_partner = '$partnerID' AND DATE(jam) = DATE('$today') LIMIT 1");
            if (mysqli_num_rows($qLQ) > 0) {
                $lq = mysqli_fetch_all($qLQ, MYSQLI_ASSOC);
                $lastQueue = (int) $lq[0]['LastQueue'];
                $lastQueue += 1;
            } else {
                $lastQueue = 1;
            }
        }
        $is_queue = $lastQueue;
        $paymentMethod = 11;
        $status = 5;

        $sql = "INSERT INTO transaksi(id, jam, id_partner, no_meja, tipe_bayar, tax, service, charge_ewallet, charge_xendit, charge_ur, pax, shift_id, queue, status, customer_name, total, phone) VALUES ('$id', '$today', '$partnerID', '$tableCode', '$paymentMethod', '$tax', '$service', '$charge_ewallet', '$charge_xendit', '$charge_ur','$pax', '$shiftID', '$is_queue', '$status', 'QR', '0', 'POS/PARTNER' ); ";
        
        $data = mysqli_query($db_conn, $sql);

        

        if($data){
            if($_ENV["BASEURL"] == 'https://ur-dev.codeontop.com/qr/'){
                $link = "http://so-staging.codeontop.com/?partnerID=" . $partnerID . "&tableID=" . $tableCode . "&id=" . $id . "&tempQR=1";
            } else {
                $link = "https://selforder.ur-hub.com/?partnerID=" . $partnerID . "&tableID=" . $tableCode . "&id=" . $id . "&tempQR=1";
            }
                
            $success = 1;
            $msg = "Success";
            $status = 200;
        } else {
            $success = 0;
            $msg = "Failed Creating Transaction";
            $status = 203;
        }
      
    } else {
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "transaction_id" => $id, "link" => $link]);
