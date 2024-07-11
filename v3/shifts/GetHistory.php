<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

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

foreach ($headers as $header => $value) {
    if ($header == "Authorization" || $header == "AUTHORIZATION") {
        $token = substr($value, 7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt', $token));
$idMaster = $token->masterID;
$value = array();
$success = 0;
$msg = 'Failed';
$all = "0";

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
} else {
    $partner_id = $_GET['partner_id'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    $query = "";
    if (isset($_GET['all'])) {
        $all = $_GET['all'];
    }

    $newDateFormat = 0;

    if (strlen($dateTo) !== 10 && strlen($dateFrom) !== 10) {
        $dateTo = str_replace("%20", " ", $dateTo);
        $dateFrom = str_replace("%20", " ", $dateFrom);
        $newDateFormat = 1;
    }

    if ($newDateFormat == 1) {
        if ($all !== "1") {
            $idMaster = null;
            $query = "SELECT s.id, s.start, s.end, s.petty_cash, s.employee_id, s.actual_cash, s.partner_id, p.name AS partner_name FROM shift s JOIN partner p ON p.id = s.partner_id WHERE s.partner_id='$partner_id' AND s.deleted_at IS NULL AND s.created_at BETWEEN '$dateFrom' AND '$dateTo' ORDER BY id DESC";
        } else {
            $query = "SELECT s.id, s.start, s.end, s.petty_cash, s.employee_id, s.actual_cash, s.partner_id, p.name AS partner_name FROM shift s JOIN partner p ON p.id = s.partner_id WHERE p.id_master = '$idMaster' AND s.deleted_at IS NULL AND s.created_at BETWEEN '$dateFrom' AND '$dateTo' ORDER BY id DESC";
        }

        $q = mysqli_query($db_conn, $query);

        if (mysqli_num_rows($q) > 0) {
            $vals = array();
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $i = 0;
            foreach ($res as $value) {
                $empID = explode(",", $value['employee_id']);
                $j = 0;
                foreach ($empID as $eID) {
                    $qK = mysqli_query($db_conn, "SELECT nama as name FROM employees WHERE employees.id='$eID'");
                    $resK = mysqli_fetch_all($qK, MYSQLI_ASSOC);
                    $res[$i]['name'][$j] = $resK[0];
                    $j += 1;
                }
                $i += 1;
            }
            $type = 1;
            foreach ($res as $value) {
                $sID = $value['id'];
                $value['cash_income'] = 0;
                $value['petty_cash'] = ceil($value['petty_cash']);

                $query = "SELECT SUM(transaksi.promo) AS promo, SUM(program_discount) AS program_discount,SUM(transaksi.diskon_spesial) AS diskon_spesial,SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+charge_ur)*transaksi.tax/100) AS tax,
                    0 AS charge_ewallet, payment_method.nama AS pmName, SUM(transaksi.dp_total) AS dp_total FROM shift s JOIN transaksi ON transaksi.shift_id=s.id JOIN payment_method ON transaksi.tipe_bayar=payment_method.id WHERE s.id='$sID' AND  transaksi.status IN(1,2,5) AND transaksi.tipe_bayar=5 ";

                // $queryTrans = "SELECT table_name FROM information_schema.tables
                // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
                // $transaksi = mysqli_query($db_conn, $queryTrans);
                //             while($row=mysqli_fetch_assoc($transaksi)){
                //                 $table_name = explode("_",$row['table_name']);
                //                 $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                //                 $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                //                 // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                //                     $query .= "UNION ALL " ;
                //                     $query .= "SELECT SUM(`$transactions`.promo) AS promo, SUM(program_discount) AS program_discount,SUM(`$transactions`.diskon_spesial) AS diskon_spesial,SUM(`$transactions`.employee_discount) AS employee_discount, SUM(`$transactions`.total) AS total, SUM(`$transactions`.charge_ur) AS charge_ur,SUM(`$transactions`.point) AS point, SUM((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.service/100) AS service, SUM((((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.service/100)+`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point+charge_ur)*`$transactions`.tax/100) AS tax,
                //                     SUM((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point+((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.service/100)+((((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.service/100)+`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.tax/100))*`$transactions`.charge_ewallet/100) AS charge_ewallet, payment_method.nama AS pmName FROM shift s JOIN `$transactions` ON `$transactions`.shift_id=s.id JOIN payment_method ON `$transactions`.tipe_bayar=payment_method.id WHERE s.id='$sID' AND  (`$transactions`.status=1 OR `$transactions`.status=2 OR `$transactions`.status=5) GROUP BY `$transactions`.tipe_bayar  ";
                //                 // }
                //             }
                $qPM = mysqli_query($db_conn, $query);
                // $qST = mysqli_query($db_conn, "SELECT `id`, `type`, `amount`, `description` FROM `shift_transactions` WHERE `shift_id`='$sID' AND transaksi.deleted_at IS NULL IS NULL");
                $paymentMethodIncome = mysqli_fetch_all($qPM, MYSQLI_ASSOC);
                // $shiftTransactions = mysqli_fetch_all($qST, MYSQLI_ASSOC);
                // $i=0;

                foreach ($paymentMethodIncome as $valuePMI) {
                    $value['payment_method_income'][$i]['payment_method'] = $valuePMI['pmName'];
                    $value['payment_method_income'][$i]['income'] += ceil($valuePMI['total']) - ceil($valuePMI['promo'] - ceil($valuePMI['diskon_spesial'])) - ceil($valuePMI['employee_discount']) - ceil($valuePMI['point']) + ceil($valuePMI['service']) + ceil($valuePMI['tax']) + ceil($valuePMI['charge_ur']);
                    if ($valuePMI['pmName'] == "TUNAI") {
                        $value['cash_income'] += ceil($valuePMI['total']) - ceil($valuePMI['promo']) - ceil($valuePMI['program_discount']) - ceil($valuePMI['diskon_spesial']) - ceil($valuePMI['employee_discount']) - ceil($valuePMI['point']) + ceil($valuePMI['service']) + ceil($valuePMI['tax']) + ceil($valuePMI['charge_ur']) - ceil($valuePMI['dp_total']);
                    }

                    $i += 1;
                }
                if ($i == 0) {
                    $value["payment_method_income"] = array();
                }
                // $j=0;
                // foreach ($shiftTransactions as $valueST) {
                //     $value['shift_transactions'][$j] = $valueST;
                //     $value['shift_transactions'][$j]['amount'] = $valueST['amount'];
                //     $j+=1;
                // }
                // if($j == 0){
                //     $value["shift_transaction"] = array();
                // }
                // }
                array_push($vals, $value);
            }
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    } else {
        if ($all !== "1") {
            $idMaster = null;
            $query = "SELECT s.id, s.start, s.end, s.petty_cash, s.employee_id, s.actual_cash, s.partner_id, p.name AS partner_name FROM shift s JOIN partner p ON p.id = s.partner_id WHERE s.partner_id='$partner_id' AND s.deleted_at IS NULL AND DATE(s.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY id DESC";
        } else {
            $query = "SELECT s.id, s.start, s.end, s.petty_cash, s.employee_id, s.actual_cash, s.partner_id, p.name AS partner_name FROM shift s JOIN partner p ON p.id = s.partner_id WHERE p.id_master = '$idMaster' AND s.deleted_at IS NULL AND DATE(s.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY id DESC";
        }

        $q = mysqli_query($db_conn, $query);

        if (mysqli_num_rows($q) > 0) {
            $vals = array();
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $i = 0;
            foreach ($res as $value) {
                $empID = explode(",", $value['employee_id']);
                $j = 0;
                foreach ($empID as $eID) {
                    $qK = mysqli_query($db_conn, "SELECT nama as name FROM employees WHERE employees.id='$eID'");
                    $resK = mysqli_fetch_all($qK, MYSQLI_ASSOC);
                    $res[$i]['name'][$j] = $resK[0];
                    $j += 1;
                }
                $i += 1;
            }
            $type = 1;
            foreach ($res as $value) {
                $sID = $value['id'];
                $value['cash_income'] = 0;
                $value['petty_cash'] = ceil($value['petty_cash']);

                $query = "SELECT SUM(transaksi.promo) AS promo, SUM(program_discount) AS program_discount,SUM(transaksi.diskon_spesial) AS diskon_spesial,SUM(transaksi.employee_discount) AS employee_discount, SUM(transaksi.total) AS total, SUM(transaksi.charge_ur) AS charge_ur,SUM(transaksi.point) AS point, SUM((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100) AS service, SUM((((transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point)*transaksi.service/100)+transaksi.total-transaksi.promo-program_discount-transaksi.diskon_spesial-transaksi.employee_discount-transaksi.point+charge_ur)*transaksi.tax/100) AS tax,
                    0 AS charge_ewallet, payment_method.nama AS pmName,SUM(transaksi.dp_total) AS dp_total FROM shift s JOIN transaksi ON transaksi.shift_id=s.id JOIN payment_method ON transaksi.tipe_bayar=payment_method.id WHERE s.id='$sID' AND  transaksi.status IN(1,2,5) AND transaksi.tipe_bayar=5";

                // $queryTrans = "SELECT table_name FROM information_schema.tables
                // WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
                // $transaksi = mysqli_query($db_conn, $queryTrans);
                //             while($row=mysqli_fetch_assoc($transaksi)){
                //                 $table_name = explode("_",$row['table_name']);
                //                 $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                //                 $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                //                 // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                //                     $query .= "UNION ALL " ;
                //                     $query .= "SELECT SUM(`$transactions`.promo) AS promo, SUM(program_discount) AS program_discount,SUM(`$transactions`.diskon_spesial) AS diskon_spesial,SUM(`$transactions`.employee_discount) AS employee_discount, SUM(`$transactions`.total) AS total, SUM(`$transactions`.charge_ur) AS charge_ur,SUM(`$transactions`.point) AS point, SUM((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.service/100) AS service, SUM((((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.service/100)+`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point+charge_ur)*`$transactions`.tax/100) AS tax,
                //                     SUM((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point+((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.service/100)+((((`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.service/100)+`$transactions`.total-`$transactions`.promo-program_discount-`$transactions`.diskon_spesial-`$transactions`.employee_discount-`$transactions`.point)*`$transactions`.tax/100))*`$transactions`.charge_ewallet/100) AS charge_ewallet, payment_method.nama AS pmName FROM shift s JOIN `$transactions` ON `$transactions`.shift_id=s.id JOIN payment_method ON `$transactions`.tipe_bayar=payment_method.id WHERE s.id='$sID' AND  (`$transactions`.status=1 OR `$transactions`.status=2 OR `$transactions`.status=5) GROUP BY `$transactions`.tipe_bayar  ";
                //                 // }
                //             }
                $qPM = mysqli_query($db_conn, $query);
                // $qST = mysqli_query($db_conn, "SELECT `id`, `type`, `amount`, `description` FROM `shift_transactions` WHERE `shift_id`='$sID' AND transaksi.deleted_at IS NULL IS NULL");
                $paymentMethodIncome = mysqli_fetch_all($qPM, MYSQLI_ASSOC);
                // $shiftTransactions = mysqli_fetch_all($qST, MYSQLI_ASSOC);
                // $i=0;

                foreach ($paymentMethodIncome as $valuePMI) {
                    $value['payment_method_income'][$i]['payment_method'] = $valuePMI['pmName'];
                    $value['payment_method_income'][$i]['income'] += ceil($valuePMI['total']) - ceil($valuePMI['promo'] - ceil($valuePMI['diskon_spesial'])) - ceil($valuePMI['employee_discount']) - ceil($valuePMI['point']) + ceil($valuePMI['service']) + ceil($valuePMI['tax']) + ceil($valuePMI['charge_ur']);
                    if ($valuePMI['pmName'] == "TUNAI") {
                        $value['cash_income'] += ceil($valuePMI['total']) - ceil($valuePMI['promo']) - ceil($valuePMI['program_discount']) - ceil($valuePMI['diskon_spesial']) - ceil($valuePMI['employee_discount']) - ceil($valuePMI['point']) + ceil($valuePMI['service']) + ceil($valuePMI['tax']) + ceil($valuePMI['charge_ur']) - ceil($valuePMI['dp_total']);
                    }

                    $i += 1;
                }
                if ($i == 0) {
                    $value["payment_method_income"] = array();
                }
                // $j=0;
                // foreach ($shiftTransactions as $valueST) {
                //     $value['shift_transactions'][$j] = $valueST;
                //     $value['shift_transactions'][$j]['amount'] = $valueST['amount'];
                //     $j+=1;
                // }
                // if($j == 0){
                //     $value["shift_transaction"] = array();
                // }
                // }
                array_push($vals, $value);
            }
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
    http_response_code(200);
} else {
    http_response_code($status);
}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "type" => $type, "shifts" => $vals]);
