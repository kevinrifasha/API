<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require '../../db_connection.php';
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

$idInsert = 0;
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$success=0;
$signupMsg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
}else{
    $obj = json_decode(file_get_contents('php://input'));
    $now = date("Y-m-d H:i:s");
    if(isset($obj->id)){
        $qGR = "UPDATE purchase_orders SET notes='$obj->notes', created_at='$obj->poDate', no='$obj->poNo', supplier_id='$obj->supplierId', total='$obj->total' WHERE id='$obj->id'";
        $insertPaket = mysqli_query($db_conn,$qGR);
        if($insertPaket){
            $details = $obj->details;
            $poID = $obj->id;
            $isError = "0";
            // nanti foreach disini
            foreach($details as $dt){
                $metricID = $dt->metric_id;
                $qty = $dt->qty;
                $price = $dt->unitPrice;
                $rawID = $dt->raw_id;
                $menuID = $dt->menu_id;
                $detailID = $dt->id;
                if($dt->id) {
                    $idList .= $dt->id.",";
                }
                
                // edit detail: jika id dari masing-masing detail ada
                if($detailID) {
                    $sqlUpd = "UPDATE purchase_orders_details SET raw_id='$rawID', menu_id='$menuID', qty='$qty', metric_id='$metricID', price='$price', updated_at=NOW() WHERE id='$detailID' AND purchase_order_id='$poID' AND deleted_at IS NULL;";
                    $updateDetail = mysqli_query($db_conn,$sqlUpd);
                    if($updateDetail == false) {
                        $isError = "Error update detail";
                    }
                } else {
                // tambahkan detail: jika id dari masing-masing detail tidak ada
                    $sqlInsert = "INSERT INTO `purchase_orders_details`(`purchase_order_id`, `raw_id`, `menu_id`, `qty`, `metric_id`, `price`, `created_at`) VALUES ('$poID', '$rawID', '$menuID', '$qty', '$metricID', '$price', NOW());";
                    $insertDetail = mysqli_query($db_conn,$sqlInsert);
                    $newDetailID = mysqli_insert_id($db_conn);
                    $idList .= $newDetailID.",";
                    if($insertDetail == false){
                        $isError = "Error insert detail";
                    }
                }
            
            }
            
            // jika ada yang dihapus maka hapus
            $listID = rtrim($idList, ",");
            $extraQuery=" AND purchase_order_id='$poID' AND deleted_at IS NULL;";
            $query = "UPDATE purchase_orders_details SET deleted_at=NOW() WHERE id NOT IN($listID)".$extraQuery;
            $delete = mysqli_query($db_conn, $query);
            if($delete == false) {
                $isError = "Error delete detail";
            }
            
            if($isError == "0") {
                $success = 1;
                $status = 200;
                $msg = "Berhasil ubah data";
            } else {
                $success = 0;
                $status = 204;
                $msg = $isError;
            }
        }else{
            $success =0;
            $status =204;
            $msg = "Gagal ubah data. Mohon coba lagi";
        }

    }else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }
}
    echo json_encode(["msg"=>$msg, "status"=>$status,"success"=>$success]);
 ?>
