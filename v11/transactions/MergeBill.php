<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

//init var
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
$tokenizer = new Token();
$token = '';
$res = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$iid = 0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{

    $obj = json_decode(file_get_contents('php://input'));
    if(isset($obj->id) && !empty($obj->id)
        && isset($obj->transactionID) && !empty($obj->transactionID) ){
            $i = 0;
        
        $qGet = "SELECT t.id, t.tax, t.service, t.employee_discount_percent, p.is_rounding, p.rounding_down_below, p.rounding_digits FROM transaksi t LEFT JOIN partner p ON p.id = t.id_partner WHERE t.id = '$obj->transactionID' AND t.deleted_at IS NULL AND t.status != 4 ORDER BY t.updated_at LIMIT 1";
        $checkID = mysqli_query($db_conn, $qGet);
        $fetchedID = mysqli_fetch_all($checkID, MYSQLI_ASSOC);
        $ptax = $fetchedID[0]["tax"];
        $pservice = $fetchedID[0]["service"];
        $selectedID = $obj->transactionID;

        $startTrx = mysqli_query($db_conn, "START TRANSACTION");
        $savePoint = mysqli_savepoint($db_conn, $selectedID);
        $commit = mysqli_query($db_conn, "COMMIT");
        $idString = "";
    
        $sql = "UPDATE transaksi SET group_id = NULL, updated_at = NOW() WHERE id IN (";
        $trasactionID = explode(',', $obj->id);
        foreach ($trasactionID as $value) {
            if($i>0){
                $sql.=",";
                $idString.=",";
            }
            $sql .= "'$value'";
            $idString .= "'$value'";
            $i+=1;
        }
        $sql .=")";
        $update = mysqli_query($db_conn,$sql);
        $sqlGetGroup = "SELECT id, total, program_discount, diskon_spesial, employee_discount, pax FROM transaksi WHERE id IN(" . "$idString" . "," . "'$selectedID'"  . ") AND deleted_at IS NULL ORDER BY updated_at, created_at ASC";
        $getAllID = mysqli_query($db_conn, $sqlGetGroup);
        $fetchedGroupTrx = mysqli_fetch_all($getAllID, MYSQLI_ASSOC);
        $totalTransaction = 0;
        $totalProgramDiscount = 0;
        $totalDiskonSpesial = 0;
        $totalEmployeeDiscount = 0;
        $totalPromo = 0;
        $totalPax = 0;

        foreach($fetchedGroupTrx as $trx){
            $totalTransaction += $trx['total'];
            $totalPax += $trx['pax'];
            $totalProgramDiscount += $trx["program_discount"];
            $totalDiskonSpesial += $trx["diskon_spesial"];
            if($fetchedID[0]["employee_discount_percent"] > 0){
                $totalEmployeeDiscount = round($totalTransaction * $fetchedID[0]["employee_discount_percent"]/100);
            }else{
                $totalEmployeeDiscount += $trx["employee_discount"];
            }
            $totalPromo += $trx["promo"];
        }
        
        $totalDiscount = $totalProgramDiscount + $totalDiskonSpesial + $totalEmployeeDiscount + $totalPromo;
        if($totalDiscount > 0 && ($totalDiscount != $totalProgramDiscount && $totalDiscount != $totalDiskonSpesial && $totalDiscount != $totalPromo && $totalDiscount != $totalEmployeeDiscount)){
                $success =0;
                $status =203;
                $msg = "Gagal menggabungkan bill, bill akan memiliki lebih dari 1 jenis diskon"; 
        }else{
            $ewalletCharge = 0;
            $service = round(($totalTransaction  - $totalPromo - $totalProgramDiscount - $totalEmployeeDiscount - $totalDiskonSpesial) * $pservice / 100);
            $tax = round((($totalTransaction - $totalPromo - $totalProgramDiscount - $totalDiskonSpesial - $totalEmployeeDiscount + $service ) * $ptax) / 100);
            $gtotal = $totalTransaction  - $totalPromo - $diskon_spesial - $totalProgramDiscount - $totalEmployeeDiscount + $service + $tax + $ewalletCharge;
            
            //rounding
            if (isset($fetchedID[0]["rounding_down_below"]) && (isset($fetchedID[0]["is_rounding"]) && ($fetchedID[0]["is_rounding"] == "1" || $fetchedID[0]["is_rounding"] == 1))) {
                if ($fetchedID[0]["rounding_down_below"] !== 0 || $fetchedID[0]["rounding_down_below"] !== "0") {
                    $roundingData = (int) $fetchedID[0]["rounding_down_below"];
                    $roundingString = (string) $fetchedID[0]["rounding_down_below"];
                    if (strlen($roundingString) == 3) {
                        if ($gtotal % 1000 <= $roundingData) {
                            $roundedTotal = $gtotal - ($gtotal % 1000);
                        } else {
                            $roundedTotal = $gtotal + (1000 -  ($gtotal % 1000));
                        }
                    } else if (strlen($roundingString) == 2) {
                        if ($gtotal % 100 <= $roundingData) {
                            $roundedTotal = $totalTransaction - ($gtotal % 100);
                        } else {
                            $roundedTotal = $gtotal + (100 -  ($gtotal % 100));
                        }
                    } else if (strlen($roundingString) == 1) {
                        if ($gtotal % 10 <= $roundingData) {
                            $roundedTotal = $gtotal - ($gtotal % 10);
                        } else {
                            $roundedTotal = $gtotal + (10 -  ($gtotal % 10));
                        }
                    }
                } else {
                    $roundedTotal = $gtotal;
                }

                $roundingDif = $roundedTotal - $gtotal;
            } else {
                $roundingDif = 0;
                $roundedTotal = $gtotal;
            }
            
            $sqlUpdateTrx = "UPDATE transaksi SET total='$totalTransaction', program_discount='$totalProgramDiscount', promo='$totalPromo', diskon_spesial='$totalDiskonSpesial', employee_discount=  $totalEmployeeDiscount, rounding='$roundingDif', pax='$totalPax' WHERE id='$selectedID'";
            $newStr = "";
            $trxID = explode(',', $idString);
            $j = 0;
            foreach($trxID as $id){
                if($id != "'" . $selectedID . "'"){
                    if($j>0){
                    $newStr.=",";
                    }
                    $newStr .= $id;
                    $j++;
                }
            }
            
            $sqlDeleteTrx = "UPDATE transaksi SET deleted_at= NOW(), status=8, group_id=NULL WHERE id IN(" . $newStr . ")"; 

            $sqlUpdateDetail = "UPDATE detail_transaksi SET id_transaksi = '$selectedID' WHERE id_transaksi IN(" . $idString . ")";

            $valid = false;
            $updateTrx = mysqli_query($db_conn, $sqlUpdateTrx);
            $deleteTrx = mysqli_query($db_conn,$sqlDeleteTrx);
            $updateDetail = mysqli_query($db_conn,$sqlUpdateDetail);
            if($updateTrx && $deleteTrx && $updateDetail){
                $valid = true;
            }
            
            if($valid == true){
                $iid = mysqli_insert_id($db_conn);
                $success =1;
                $status =200;
                $msg = "Berhasil menggabungkan bill";
            }else{
                $success =0;
                $status =203;
                $msg = "Gagal menggabungkan bill. Mohon coba lagi";
            }
        }
        
    }else{
        $success =0;
        $status =400;
        $msg = "Mohon lengkapi form";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "insertedID"=>$iid]);
?>