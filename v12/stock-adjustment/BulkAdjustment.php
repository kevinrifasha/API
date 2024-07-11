<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
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

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
// function getMetricIDByName($db_conn, $x){

// }
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    // POST DATA
    $qAdjust="";
    $adjustStock="";
    $rmID=0;
    $menuID=0;
    $data = json_decode(file_get_contents('php://input'));
    if(!empty($data->source)&& !empty($data->partnerID)){
        $source = json_decode($data->source,true);
        foreach($source as $x){
            $type = $x['type'];
            $id = $x['id'];
            $bookStock = $x['bookStock'];
            $realStock = $x['realStock'];
            $unitPrice = $x['unitPrice'];
            $diff = (double)$x['realStock']-(double)$x['bookStock'];
            $moneyValue = $diff * $unitPrice;
            $reason = $x['reason'];
            $metricName = strtolower($x['metricName']);
            $q = mysqli_query($db_conn, "SELECT id FROM metric WHERE LOWER(name)='$metricName'");
            if(mysqli_num_rows($q)>0){
                $resQ = mysqli_fetch_all($q, MYSQLI_ASSOC);
                $metricID= $resQ[0]['id'];
            }else{
                $metricID= "6";
            }
            if($type=="Bahan Jadi"){
                $menuID = $id;
                $rmID = 0;
                $qAdjust .= "INSERT INTO stock_changes SET menu_id='$id', qty_before='$bookStock', qty='$realStock', notes='$reason', created_by='$tokenDecoded->id', metric_id=6, master_id='$tokenDecoded->masterID', partner_id='$data->partnerID', money_value='$moneyValue'; ";
                $adjustStock .= "UPDATE menu SET stock ='$realStock' WHERE id='$id' AND id_partner='$data->partnerID'; ";
            }else{
                $rmID = $id;
                $menuID = 0;
                $deleteStock = mysqli_query($db_conn, "DELETE FROM raw_material_stock WHERE id_raw_material=".$id.";");
                $qAdjust .= "INSERT INTO stock_changes SET raw_material_id='$id', qty_before='$bookStock', qty='$realStock', notes='$reason', created_by='$tokenDecoded->id',metric_id='$metricID', master_id='$tokenDecoded->masterID', partner_id='$data->partnerID', money_value='$moneyValue'; ";
                $adjustStock .= "INSERT INTO raw_material_stock SET stock ='$realStock', id_metric='$metricID', id_raw_material='$id'; ";
            }
            $adjusted = $realStock-$bookStock;
            $adjustment=mysqli_query($db_conn, "INSERT INTO stock_movements SET master_id='$tokenDecoded->masterID', partner_id='$data->partnerID', menu_id='$menuID', raw_id='$rmID', metric_id='$metricID', type=0, adjustment='$adjusted', remaining='$realStock'");
        }
        $createAdjustment = mysqli_multi_query($db_conn,$qAdjust);
        while(mysqli_next_result($db_conn)){;}
        if($createAdjustment){
            $updateStock = mysqli_multi_query($db_conn,$adjustStock)or die(mysqli_error($db_conn));
            while(mysqli_next_result($db_conn)){;}
            // if($updateStock){
                $success =1;
                $status =200;
                $msg = "Berhasil import data adjustment";
            // }else{
            //     $success =0;
            //     $status =204;
            //     $msg = "Gagal import data adjustment";
            // }
        }else{
            $success =0;
            $status =204;
            $msg = "Gagal buat adjustment";
        }
    }else{
        $success =0;
        $status =400;
        $msg = "Data tidak lengkap";
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "qAdjust"=>$qAdjust, "adjustStock"=>$adjustStock]);
?>
