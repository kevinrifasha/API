<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';
require '../../includes/functions.php';


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

$fs = new functions();
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
$remainingValue = 0;
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{

    // POST DATA
    $data = json_decode(file_get_contents('php://input'));
    $partnerID = $data->partnerID;
    
    if(!empty($data->id)){
        if($data->type=="menu"){
            $menuID = $data->id;
            $partnerID = $data->partnerID;
            $rmID = 0;
            $metricID=6;
            $qUpdate = "UPDATE `menu` SET `stock`='$data->realValue', `updated_at`=NOW() WHERE `id`='$data->id'";
            $qHPP = "SELECT hpp AS unitPrice FROM menu WHERE id='$data->id'";
            $qRemaining = "SELECT remaining FROM stock_movements sm WHERE sm.menu_id='$menuID' AND sm.deleted_at IS NULL  ORDER BY id DESC LIMIT 1";
        }else{
            $rmID = $data->id;
            $menuID = 0;
            $metricID=$data->metricID;
            $qUpdate = "DELETE FROM `raw_material_stock` WHERE `id_raw_material`='$rmID';";
            $qHPP = "SELECT unit_price AS unitPrice FROM raw_material WHERE id='$data->id'";
            $qRemaining = "SELECT remaining FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.deleted_at IS NULL  ORDER BY id DESC LIMIT 1";
        }
        $update = mysqli_query($db_conn, $qUpdate);
        $hpp = mysqli_query($db_conn, $qHPP);
        $getRemaining = mysqli_query($db_conn, $qRemaining);
        if(mysqli_num_rows($getRemaining)>0){
            $resRemaining = mysqli_fetch_all($getRemaining, MYSQLI_ASSOC);
            $remainingValue = $resRemaining[0]['remaining'];
        }else{
            $remainingValue = 0;
        }
        if($update){
            if($data->type=="menu"){}else{
                $xx = mysqli_query($db_conn, "INSERT INTO `raw_material_stock` SET `stock`='$data->realValue', `id_metric`='$metricID', `id_raw_material`='$rmID'");
                $fs->update_raw_material_stock([$rmID]);
            }
            $resHPP = mysqli_fetch_all($hpp, MYSQLI_ASSOC);
            $unitPrice = $resHPP[0]['unitPrice'];
            $diff = (double)$data->realValue-(double)$data->bookValue;
            $moneyValue = $diff*$unitPrice;
            // $qInsert="INSERT INTO `stock_changes`(`master_id`, `partner_id`, `raw_material_id`, `menu_id`, `metric_id`, `qty`, `qty_before`, `notes`, `created_by`, `money_value`) VALUES ('$tokenDecoded->masterID', '$tokenDecoded->partnerID', '$rmID', '$menuID', '$metricID', '$data->realValue', '$data->bookValue', '$data->reason', '$tokenDecoded->id', '$moneyValue')";
            $qInsert="INSERT INTO `stock_changes`(`master_id`, `partner_id`, `raw_material_id`, `menu_id`, `metric_id`, `qty`, `qty_before`, `notes`, `created_by`, `money_value`) VALUES ('$tokenDecoded->masterID', '$partnerID', '$rmID', '$menuID', '$metricID', '$data->realValue', '$data->bookValue', '$data->reason', '$tokenDecoded->id', '$moneyValue')";
            $insert = mysqli_query($db_conn,$qInsert);
            // $adjusted = $remainingValue-$diff;
            $adjusted=$diff;
            // $adjustment=mysqli_query($db_conn, "INSERT INTO stock_movements SET master_id='$tokenDecoded->masterID', partner_id='$tokenDecoded->partnerID', menu_id='$menuID', raw_id='$rmID', metric_id='$metricID', type=0, adjustment='$adjusted', remaining='$data->realValue'");
            $qInsertStockMV = "INSERT INTO stock_movements SET master_id='$tokenDecoded->masterID', partner_id='$partnerID', menu_id='$menuID', raw_id='$rmID', metric_id='$metricID', type=0, adjustment='$adjusted', remaining='$data->realValue'";
            $adjustment=mysqli_query($db_conn, $qInsertStockMV);
            if($insert){
                $success =1;
                $status =200;
                $msg = "Success";
            }else{
                $success =0;
                $status =204;
                $msg = "Gagal isi adjustment stok. Mohon periksa data dan coba lagi";
            }
        }else{
            $success =0;
            $status =204;
            $msg = "Gagal ubah adjustment stok. Mohon periksa data dan coba lagi";
        }

    }else{
        $success =0;
        $status =400;
        $msg = "Data tidak lengkap";
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "qStockChanges"=>$qInsert, "qStockMovements"=>$qInsertStockMV]);
