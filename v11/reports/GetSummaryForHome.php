<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once '../../includes/CalculateFunctions.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$cf = new CalculateFunction();

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
$resSubTotal = array();
$arr = array();
$resQ = array();
$payment_methods = array();
$shiftTrx = array();
$arr['hpp'] = 0;
$arr['sales'] = 0;
$arr['total_income'] = 0;
$arr['diskon_spesial'] = 0;
$arr['promo'] = 0;
$arr['employee_discount'] = 0;
$arr['program_discount'] = 0;
$arr['charge_ur'] = 0;
$arr['service'] = 0;
$arr['tax'] = 0;
$arr['gross_income'] = 0;
$arr['total_net_profit'] = 0;
$arr['delivery_fee'] = 0;
$arr['delivery_fee_resto'] = 0;
$arr['delivery_fee_shipper'] = 0;
$charge_ewallet = 0;
$opex = 0;
$all = "0";
//get token
foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
$idMaster = $token->id_master;
if (isset($tokenValidate['success']) && $tokenValidate['success'] == 0) {
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
} else {
    $id = $token->id_partner;
    if (isset($_GET['partnerID'])) {
        $id = $_GET['partnerID'];
    }
    // $dateFrom = $_GET['dateFrom'];
    // $dateTo = $_GET['dateTo'];
    $dateFrom = date('Y-m-01');
    $dateTo = date('Y-m-d');

    if (isset($_GET['all'])) {
        $all = $_GET['all'];
    }

    if ($all == "1") {
        $res = $cf->getSubTotalMaster($idMaster, $dateFrom, $dateTo);
    } else {
        $idMaster = null;
        $res = $cf->getSubTotal($id, $dateFrom, $dateTo);
    }

    $shiftTrx = $cf->getShiftTransaction($id, $dateFrom, $dateTo, $idMaster);

    $resSubTotal = $res;
    $res['hpp'] = 0;
    $res['gross_profit'] = $res['clean_sales'];
    $arr['gross_income'] = $res['clean_sales'];
    $res['gross_profit_afterservice'] = $res['gross_profit'] - $res['service'];
    $res['gross_profit_aftertax'] = $res['gross_profit_afterservice'] - $res['tax'];

    if ($all == "1") {

        $query = "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu JOIN partner p ON p.id = transaksi.id_partner WHERE p.id_master = '$idMaster' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 ";
    } else {
        $query =  "SELECT SUM(detail_transaksi.qty * menu.hpp) AS hpp FROM detail_transaksi JOIN transaksi ON transaksi.id=detail_transaksi.id_transaksi JOIN menu ON menu.id=detail_transaksi.id_menu WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 ";
    }

    $dateFromStr = str_replace("-", "", $dateFrom);
    $dateToStr = str_replace("-", "", $dateTo);
    $payments = $cf->getGroupPaymentMethod($id, $dateFrom, $dateTo, $idMaster);
    $i = 0;
    foreach ($payments as $x) {
        $charge_ewallet += (int)$x['charge_ewallet'];
        $i++;
    }

    $hppQ = mysqli_query($db_conn, $query);

    if ($hppQ && mysqli_num_rows($hppQ) > 0) {
        $resQ1 = mysqli_fetch_all($hppQ, MYSQLI_ASSOC);

        $resQ[0]['hpp'] = 0;
        foreach ($resQ1 as $value) {
            $resQ[0]['hpp'] += (float) $value['hpp'];
        }
        $res['hpp'] = (float)$resQ[0]['hpp'];
        $arr['hpp'] = $res['hpp'];
        $res['gross_profit'] = $res['gross_profit'] - $res['hpp'];
        $res['gross_profit_afterservice'] = $res['gross_profit'] - $res['service'];
        $res['gross_profit_aftertax'] = $res['gross_profit_afterservice'] - $res['tax'];
        $arr['gross_income'] = $res['gross_profit'];
    }

    if ($all == "1") {
        $query = "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE opc.master_id = '$idMaster' AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC";
    } else {
        $query = "SELECT SUM(op.amount) as amount FROM operational_expenses op JOIN operational_expense_categories opc ON op.category_id=opc.id JOIN partner p ON p.id_master=opc.master_id JOIN employees e ON e.id=op.created_by WHERE p.id='$id'AND op.deleted_at IS NULL AND DATE(op.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY op.id DESC";
    }

    $sql = mysqli_query($db_conn, $query);
    if (mysqli_num_rows($sql) > 0) {
        $opexes = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        if (!is_null($opexes[0]['amount'])) {
            $opex = (int)$opexes[0]['amount'];
        }
    }
    $arr['total_net_profit'] = $res['gross_profit_aftertax'] - $arr['charge_ur'] - $opex + ($shiftTrx['saldo']);
    $arr['total_net_profit_after_ewallet_charge'] = $arr['total_net_profit'] - $charge_ewallet;

    $success = 1;
    $status = 200;
    $msg = "Success";
}
$signupJson = json_encode([
    "success" => $success,
    "status" => $status,
    "msg" => $msg,
    "gross_profit" => $arr['gross_income'],
    "hpp" => $arr['hpp'],
    "net_profit" => $arr['total_net_profit_after_ewallet_charge'] ?? 0
]);
echo $signupJson;
