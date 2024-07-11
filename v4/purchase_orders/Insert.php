<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
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
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    $supplier_id = $obj['supplier_id'];
    $total = $obj['total'];
    $no = $obj['no'];
    $validateNo = mysqli_query($db_conn, "SELECT no FROM purchase_orders WHERE master_id='$token->id_master' AND partner_id='$token->id_partner' AND no='$no' AND deleted_at IS NULL");
    if(mysqli_num_rows($validateNo)==0){
        $sql = mysqli_query($db_conn, "INSERT INTO `purchase_orders`(`master_id`, `partner_id`, `no`, `supplier_id`, `total`, `created_at`, created_by) VALUES ('$token->id_master', '$token->id_partner', '$no', '$supplier_id', '$total', NOW(), '$token->id')");
        if($sql){
            $id = mysqli_insert_id($db_conn);
            $details = $obj['details'];
            foreach($details as $dt){
                $metricID = $dt['metric_id'];
                $price = $dt['price'];
                $qty = $dt['qty'];
                if($dt['raw_id']==null){
                    $menuID = $dt['menu_id'];
                    $sql = mysqli_query($db_conn, "INSERT INTO `purchase_orders_details`(`purchase_order_id`, `menu_id`, `qty`, `metric_id`, `price`, `created_at`) VALUES ('$id', '$menuID', '$qty', '$metricID', '$price', NOW())");
                }else{
                    $rawID = $dt['raw_id'];
                    $sql = mysqli_query($db_conn, "INSERT INTO `purchase_orders_details`(`purchase_order_id`, `raw_id`, `qty`, `metric_id`, `price`, `created_at`) VALUES ('$id', '$rawID', '$qty', '$metricID', '$price', NOW())");
                }

            }
            $success=1;
            $msg="Berhasil buat PO";
            $status=200;
        }else{
            $success=0;
            $msg="Gagal buat PO. Mohon coba lagi";
            $status=400;
        }
    }else{
        $success=0;
        $msg="Nomor PO sudah pernah digunakan. Mohon gunakan nomor lain";
        $status=400;
    }

}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);

?>