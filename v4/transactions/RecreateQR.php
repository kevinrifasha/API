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
        isset($data->id)
    ) {

        $is_takeaway = 0;
        $trxDate = date("ymd");
        $pid = (int) $partnerID;
        $pax = $data->pax;
        // $tableCode = $data->tableCode;
        $id = $data->id;
        $sql = "SELECT t.id, t.no_meja, t.source, t.customer_name, t.phone FROM transaksi t WHERE t.id='$data->id' AND t.deleted_at IS NULL AND t.status = 5";
        
        $test =[];
        
        $query = mysqli_query($db_conn, $sql);
        $data = mysqli_fetch_all($query, MYSQLI_ASSOC);
        $tableCode = $data[0]['no_meja'];
        $source = $data[0]['source'];
        $customer_phone = $data[0]['phone'];
        $name = $data[0]['customer_name'];
        $trxId = $data[0]['id'];
        $test[] = $customer_phone;
        $test[] = $name;
        $forceAsQR = "";

        if((($source == "POS" && $customer_phone == "POS/PARTNER") || ($source == "waiterApp" && $customer_phone == "WAITERAPP"))){
            $forceAsQR = "&forceQR=true";
            $updateQuery = mysqli_query($db_conn, "UPDATE transaksi SET customer_name = 'QR' WHERE id='$trxId'");
            $test[] = $updateQuery;
        }

        if(mysqli_num_rows($query) > 0){
            if($_ENV["BASEURL"] == 'https://ur-dev.codeontop.com/qr/'){
                $link = "https://so-staging.codeontop.com/?partnerID=" . $partnerID . "&tableID=" . $tableCode . "&id=" . $id . "&tempQR=1" . $forceAsQR  ;
            } else {
                $link = "https://selforder.ur-hub.com/?partnerID=" . $partnerID . "&tableID=" . $tableCode . "&id=" . $id . "&tempQR=1" . $forceAsQR;
            }
                
            $success = 1;
            $msg = "Success";
            $status = 200;
        } else {
            $success = 0;
            $msg = "Transaction ID Not Found Or Has Been Paid, Please Create New QR";
            $status = 203;
            $link = 'transaction_is_not_pending';
        }
      
    } else {
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;
    }
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "transaction_id" => $id, "link" => $link]);
