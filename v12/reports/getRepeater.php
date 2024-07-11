<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require_once '../../includes/CalculateFunctions.php';
require '../../db_connection.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();
$cf = new CalculateFunction();
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
$token = '';

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $tokenDecoded->masterID;
$value = array();
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $id = $_GET['partnerID'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];
    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }

    if($newDateFormat == 1){ 
        if($all == "1") {
            $queryRepeater = "SELECT COUNT(trx.phone) as monthly_repeater FROM (SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master='$tokenDecoded->masterID' AND t.is_repeat='1' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryAll = "SELECT COUNT(trx.phone) as monthly FROM (SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master='$tokenDecoded->masterID' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryAllTrx = "SELECT COUNT(trx.id) as allTrx FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id_partner, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master='$tokenDecoded->masterID' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryTrxByNew = "SELECT COUNT(t.id) as trxNew FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master='$tokenDecoded->masterID' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' AND t.is_repeat = '0') trx";
            $queryTrxByRepeater = "SELECT COUNT(t.id) as trxRepeater FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id_partner, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master='$tokenDecoded->masterID' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' AND t.is_repeat = '1') trx";
            $queryNew = "SELECT COUNT(trx.phone) as new FROM (SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master='$tokenDecoded->masterID' AND t.is_repeat='0' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryAgeNew = "SELECT COUNT(trx.phone) as count, TIMESTAMPDIFF(YEAR, u.TglLahir, CURDATE()) AS age FROM (SELECT DISTINCT t.phone  FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master='$tokenDecoded->masterID' AND t.is_repeat='0' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY age";
            $queryGenderNew = "SELECT COUNT(trx.phone) as count, u.Gender as  gender FROM (SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master='$tokenDecoded->masterID' AND t.is_repeat='0' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY gender";
            $queryAgeRepeater = "SELECT COUNT(trx.phone) as count, TIMESTAMPDIFF(YEAR, u.TglLahir, CURDATE()) AS age FROM (SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master='$tokenDecoded->masterID' AND t.is_repeat='1' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY gender";
            $queryGenderRepeater = "SELECT COUNT(trx.phone) as count, u.Gender as gender FROM (SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master='$tokenDecoded->masterID' AND t.is_repeat='1' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY gender";
            $queryNonRepeater = "SELECT COUNT(nr.phone) as monthly_non_repeater FROM ( SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.is_repeat = '0'       AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone, t.id_partner FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' ) trx  ) )nr WHERE nr.phone != 'POS/PARTNER'";
            $queryAgeNonRepeater = "SELECT COUNT(nr.phone) as count, TIMESTAMPDIFF(YEAR, u.TglLahir, CURDATE()) AS age FROM ( SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone, t.id_partner FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' ) trx ) )nr LEFT JOIN users u ON nr.phone = u.phone WHERE nr.phone != 'POS/PARTNER' AND u.deleted_at IS NULL GROUP BY age";
            $queryGenderNonRepeater = "SELECT COUNT(nr.phone) as count, u.Gender as gender FROM ( SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone, t.id_partner FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' ) trx ) )nr LEFT JOIN users u ON nr.phone = u.phone AND u.deleted_at IS NULL WHERE nr.phone != 'POS/PARTNER' GROUP BY gender";
            
            $queryTrxByNew = "SELECT COUNT(trx.id) as trxNew FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' AND t.is_repeat =0) trx";
            $queryTrxByRepeater = "SELECT COUNT(trx.id) as trxRepeater FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' AND t.is_repeat =1) trx";
            $queryTrxNonRepeater = "SELECT COUNT(nr.id) as count FROM ( SELECT DISTINCT t.phone, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx ) )nr WHERE nr.phone != 'POS/PARTNER'";
        } else {
            $queryRepeater = "SELECT COUNT(trx.phone) as monthly_repeater FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.is_repeat='1' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryAll = "SELECT COUNT(trx.phone) as monthly FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryAllTrx = "SELECT COUNT(trx.id) as allTrx FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryTrxByNew = "SELECT COUNT(trx.id) as trxNew FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' AND t.is_repeat =0) trx";
            $queryTrxByRepeater = "SELECT COUNT(trx.id) as trxRepeater FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' AND t.is_repeat =1) trx";
            $queryNew = "SELECT COUNT(trx.phone) as new FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.is_repeat='0' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryAgeNew = "SELECT COUNT(trx.phone) as count, TIMESTAMPDIFF(YEAR, u.TglLahir, CURDATE()) AS age  FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.is_repeat='0' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY age";
            $queryAgeRepeater = "SELECT COUNT(trx.phone) as count, TIMESTAMPDIFF(YEAR, u.TglLahir, CURDATE()) AS age  FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.is_repeat='1' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY age";
            $queryNonRepeater = "SELECT COUNT(nr.phone) as monthly_non_repeater FROM ( SELECT DISTINCT t.phone FROM transaksi t WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner = '$id'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner = '$id' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx ) )nr WHERE nr.phone != 'POS/PARTNER'";
            $queryAgeNonRepeater = "SELECT COUNT(nr.phone) as count, TIMESTAMPDIFF(YEAR, u.TglLahir, CURDATE()) as age FROM ( SELECT DISTINCT t.phone FROM transaksi t WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner = '$id'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner = '$id' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx ) )nr LEFT JOIN users u ON nr.phone = u.phone AND u.deleted_at IS NULL WHERE nr.phone != 'POS/PARTNER' GROUP BY age";
            $queryGenderNonRepeater = "SELECT COUNT(nr.phone) as count, u.Gender as gender FROM ( SELECT DISTINCT t.phone FROM transaksi t WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner = '$id'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner = '$id' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx ) )nr LEFT JOIN users u ON nr.phone = u.phone AND u.deleted_at IS NULL WHERE nr.phone != 'POS/PARTNER' GROUP BY gender";
            $queryTrxNonRepeater = "SELECT COUNT(nr.id) as count FROM ( SELECT DISTINCT t.phone, t.id FROM transaksi t WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner = '$id'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone, t.id FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner = '$id' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx ) )nr WHERE nr.phone != 'POS/PARTNER'";
            $queryGenderNew = "SELECT COUNT(trx.phone) as count, u.Gender as gender  FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.is_repeat='0' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY gender";
            $queryGenderRepeater = "SELECT COUNT(trx.phone) as count, u.Gender as gender  FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner='$id' AND t.is_repeat='1' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY gender";
        }
    }else{
        if($all == "1") {
            $queryRepeater = "SELECT COUNT(trx.phone) as monthly_repeater FROM (SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master='$tokenDecoded->masterID' AND t.is_repeat='1' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryAll = "SELECT COUNT(trx.phone) as monthly FROM (SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master='$tokenDecoded->masterID' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryAllTrx = "SELECT COUNT(trx.id) as allTrx FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id_partner, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master='$tokenDecoded->masterID' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryTrxByNew = "SELECT COUNT(t.id) as trxNew FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master='$tokenDecoded->masterID' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' AND t.is_repeat = '0') trx";
            $queryTrxByRepeater = "SELECT COUNT(t.id) as trxRepeater FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id_partner, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master='$tokenDecoded->masterID' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' AND t.is_repeat = '1') trx";
            $queryNew = "SELECT COUNT(trx.phone) as new FROM (SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master='$tokenDecoded->masterID' AND t.is_repeat='0' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryAgeNew = "SELECT COUNT(trx.phone) as count, TIMESTAMPDIFF(YEAR, u.TglLahir, CURDATE()) AS age FROM (SELECT DISTINCT t.phone  FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master='$tokenDecoded->masterID' AND t.is_repeat='0' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY age";
            $queryGenderNew = "SELECT COUNT(trx.phone) as count, u.Gender as  gender FROM (SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master='$tokenDecoded->masterID' AND t.is_repeat='0' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY gender";
            $queryAgeRepeater = "SELECT COUNT(trx.phone) as count, TIMESTAMPDIFF(YEAR, u.TglLahir, CURDATE()) AS age FROM (SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master='$tokenDecoded->masterID' AND t.is_repeat='1' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY gender";
            $queryGenderRepeater = "SELECT COUNT(trx.phone) as count, u.Gender as gender FROM (SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master='$tokenDecoded->masterID' AND t.is_repeat='1' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY gender";
            $queryNonRepeater = "SELECT COUNT(nr.phone) as monthly_non_repeater FROM ( SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.is_repeat = '0'       AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone, t.id_partner FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master = '$tokenDecoded->masterID' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' ) trx  ) )nr WHERE nr.phone != 'POS/PARTNER'";
            $queryAgeNonRepeater = "SELECT COUNT(nr.phone) as count, TIMESTAMPDIFF(YEAR, u.TglLahir, CURDATE()) AS age FROM ( SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone, t.id_partner FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master = '$tokenDecoded->masterID' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' ) trx ) )nr LEFT JOIN users u ON nr.phone = u.phone WHERE nr.phone != 'POS/PARTNER' AND u.deleted_at IS NULL GROUP BY age";
            $queryGenderNonRepeater = "SELECT COUNT(nr.phone) as count, u.Gender as gender FROM ( SELECT DISTINCT t.phone FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone, t.id_partner FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master = '$tokenDecoded->masterID' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' ) trx ) )nr LEFT JOIN users u ON nr.phone = u.phone AND u.deleted_at IS NULL WHERE nr.phone != 'POS/PARTNER' GROUP BY gender";
            
            $queryTrxByNew = "SELECT COUNT(trx.id) as trxNew FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master = '$tokenDecoded->masterID' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' AND t.is_repeat =0) trx";
            $queryTrxByRepeater = "SELECT COUNT(trx.id) as trxRepeater FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master = '$tokenDecoded->masterID' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' AND t.is_repeat =1) trx";
            $queryTrxNonRepeater = "SELECT COUNT(nr.id) as count FROM ( SELECT DISTINCT t.phone, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND p.id_master = '$tokenDecoded->masterID'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone, t.id FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND p.id_master = '$tokenDecoded->masterID' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx ) )nr WHERE nr.phone != 'POS/PARTNER'";
        } else {
            $queryRepeater = "SELECT COUNT(trx.phone) as monthly_repeater FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner='$id' AND t.is_repeat='1' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryAll = "SELECT COUNT(trx.phone) as monthly FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner='$id' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryAllTrx = "SELECT COUNT(trx.id) as allTrx FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner='$id' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryTrxByNew = "SELECT COUNT(trx.id) as trxNew FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner='$id' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' AND t.is_repeat =0) trx";
            $queryTrxByRepeater = "SELECT COUNT(trx.id) as trxRepeater FROM (SELECT DISTINCT t.phone, t.is_repeat, t.id FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner='$id' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER' AND t.is_repeat =1) trx";
            $queryNew = "SELECT COUNT(trx.phone) as new FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner='$id' AND t.is_repeat='0' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx";
            $queryAgeNew = "SELECT COUNT(trx.phone) as count, TIMESTAMPDIFF(YEAR, u.TglLahir, CURDATE()) AS age  FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner='$id' AND t.is_repeat='0' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY age";
            $queryAgeRepeater = "SELECT COUNT(trx.phone) as count, TIMESTAMPDIFF(YEAR, u.TglLahir, CURDATE()) AS age  FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner='$id' AND t.is_repeat='1' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY age";
            $queryNonRepeater = "SELECT COUNT(nr.phone) as monthly_non_repeater FROM ( SELECT DISTINCT t.phone FROM transaksi t WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner = '$id'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner = '$id' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx ) )nr WHERE nr.phone != 'POS/PARTNER'";
            $queryAgeNonRepeater = "SELECT COUNT(nr.phone) as count, TIMESTAMPDIFF(YEAR, u.TglLahir, CURDATE()) as age FROM ( SELECT DISTINCT t.phone FROM transaksi t WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner = '$id'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner = '$id' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx ) )nr LEFT JOIN users u ON nr.phone = u.phone AND u.deleted_at IS NULL WHERE nr.phone != 'POS/PARTNER' GROUP BY age";
            $queryGenderNonRepeater = "SELECT COUNT(nr.phone) as count, u.Gender as gender FROM ( SELECT DISTINCT t.phone FROM transaksi t WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner = '$id'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner = '$id' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx ) )nr LEFT JOIN users u ON nr.phone = u.phone AND u.deleted_at IS NULL WHERE nr.phone != 'POS/PARTNER' GROUP BY gender";
            $queryTrxNonRepeater = "SELECT COUNT(nr.id) as count FROM ( SELECT DISTINCT t.phone, t.id FROM transaksi t WHERE t.is_repeat = '0' AND t.jam BETWEEN '$dateFrom' AND '$dateTo' AND t.id_partner = '$id'  AND t.status NOT IN(3, 4) AND t.phone NOT IN(SELECT trx.phone as monthly_repeater FROM  ( SELECT DISTINCT t.phone, t.id FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner = '$id' AND t.is_repeat = '1' AND t.status NOT IN(3, 4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx ) )nr WHERE nr.phone != 'POS/PARTNER'";
            $queryGenderNew = "SELECT COUNT(trx.phone) as count, u.Gender as gender  FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner='$id' AND t.is_repeat='0' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY gender";
            $queryGenderRepeater = "SELECT COUNT(trx.phone) as count, u.Gender as gender  FROM (SELECT DISTINCT t.phone FROM transaksi t WHERE t.jam BETWEEN DATE('$dateFrom') AND DATE('$dateTo') AND t.id_partner='$id' AND t.is_repeat='1' AND t.status NOT IN(3,4) AND t.deleted_at IS NULL AND t.phone != 'POS/PARTNER') trx LEFT JOIN users u ON trx.phone = u.phone AND u.deleted_at IS NULL GROUP BY gender";
        }
    }
    
    $sqlRepeater = mysqli_query($db_conn, $queryRepeater);
    $fetchRepeater = mysqli_fetch_all($sqlRepeater, MYSQLI_ASSOC);
    $repeater = $fetchRepeater[0]['monthly_repeater'];
    
    $sqlNonRepeater = mysqli_query($db_conn, $queryNonRepeater);
    $fetchNonRepeater = mysqli_fetch_all($sqlNonRepeater, MYSQLI_ASSOC);
    $nonRepeater = $fetchNonRepeater[0]['monthly_non_repeater'];
    
    $sqlNew = mysqli_query($db_conn, $queryNew);
    $fetchNew = mysqli_fetch_all($sqlNew, MYSQLI_ASSOC);
    $new = $fetchNew[0]['new'];
    
    $sqlAll = mysqli_query($db_conn, $queryAll);
    $fetchAll = mysqli_fetch_all($sqlAll, MYSQLI_ASSOC);
    $all = $fetchAll[0]['monthly'];

    $sqlTrxNew = mysqli_query($db_conn, $queryTrxByNew);
    $fetchTrxNew = mysqli_fetch_all($sqlTrxNew, MYSQLI_ASSOC);
    $trxNew = $fetchTrxNew[0]['trxNew'];

    $sqlTrxRepeater = mysqli_query($db_conn, $queryTrxByRepeater);
    $fetchTrxRepeater = mysqli_fetch_all($sqlTrxRepeater, MYSQLI_ASSOC);
    $trxRepeater = $fetchTrxRepeater[0]['trxRepeater'];

    $sqlTrxNonRepeater = mysqli_query($db_conn, $queryTrxNonRepeater);
    $fetchTrxNonRepeater = mysqli_fetch_all($sqlTrxNonRepeater, MYSQLI_ASSOC);
    $trxNonRepeater = $fetchTrxNonRepeater[0]['count'];
    
    $sqlAgeNew = mysqli_query($db_conn, $queryAgeNew);
    $fetchAgeNew = mysqli_fetch_all($sqlAgeNew, MYSQLI_ASSOC);
    $ageNew = array_values($fetchAgeNew);

    $sqlAgeRepeater = mysqli_query($db_conn, $queryAgeRepeater);
    $fetchAgeRepeater = mysqli_fetch_all($sqlAgeRepeater, MYSQLI_ASSOC);
    $ageRepeater = array_values($fetchAgeRepeater);
    
    $sqlAgeNonRepeater = mysqli_query($db_conn, $queryAgeNonRepeater);
    $fetchAgeNonRepeater = mysqli_fetch_all($sqlAgeNonRepeater, MYSQLI_ASSOC);
    $ageNonRepeater = array_values($fetchAgeNonRepeater);

    $sqlGenderNonRepeater = mysqli_query($db_conn, $queryGenderNonRepeater);
    $fetchGenderNonRepeater = mysqli_fetch_all($sqlGenderNonRepeater, MYSQLI_ASSOC);
    $genderNonRepeater = array_values($fetchGenderNonRepeater);
    
    $sqlGenderNew = mysqli_query($db_conn, $queryGenderNew);
    $fetchGenderNew = mysqli_fetch_all($sqlGenderNew, MYSQLI_ASSOC);
    $genderNew = array_values($fetchGenderNew);

    $sqlGenderRepeater = mysqli_query($db_conn, $queryGenderRepeater);
    $fetchGenderRepeater = mysqli_fetch_all($sqlGenderRepeater, MYSQLI_ASSOC);
    $genderRepeater = array_values($fetchGenderRepeater);
    
    $sqlAllTrx = mysqli_query($db_conn, $queryAllTrx);
    $fetchAllTrx = mysqli_fetch_all($sqlAllTrx, MYSQLI_ASSOC);
    $allTrx = $fetchAllTrx[0]['allTrx'];
    $success=1;
    $status=200;
    $msg = 'Succeed';
}
echo json_encode([
  "success"=>$success,
  "status"=>$status,
  "msg"=>$msg,
  "monthly_repeater"=>$repeater,
  "monthly_new"=>$new,
  "monthly_all"=>$all,
  "monthly_allTrx"=>$allTrx,
  "ageNew"=>$ageNew,
  "ageRepeater"=>$ageRepeater,
  "monthly_non_repeater"=>$nonRepeater,
  "ageNonRepeater"=>$ageNonRepeater,
  "genderNew"=>$genderNew,
  "genderRepeater"=>$genderRepeater,
  "genderNonRepeater"=>$genderNonRepeater,
  "trxNew"=>$trxNew,
  "trxRepeater"=>$trxRepeater,
  "allTrx"=>$allTrx,
  "trxNonRepeater"=>$trxNonRepeater,
  "q"=>$queryTrxByNew
]);
