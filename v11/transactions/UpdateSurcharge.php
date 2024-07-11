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
$res1 = array();
$id = "";
$msg = "";
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
    $obj = json_decode(file_get_contents('php://input'));
    if( isset($obj->transactionID)
        && !empty($obj->transactionID)){
            
        // $qSID = mysqli_query($db_conn,"SELECT t.id, t.surcharge_id FROM `transaksi` t WHERE t.id='$obj->transactionID' AND deleted_at IS NULL AND status=5");
        $qSID = mysqli_query($db_conn,"SELECT t.id, t.surcharge_id, s.name as updated_name FROM `transaksi` t LEFT JOIN surcharges s ON s.id = '$obj->surchargeID' WHERE t.id='$obj->transactionID' AND t.deleted_at IS NULL AND t.status=5");
        $getData = mysqli_fetch_all($qSID, MYSQLI_ASSOC);
        if(count($getData) > 0){
            $surcharge_name = strtoupper($getData[0]["updated_name"]);
            $update = mysqli_query($db_conn,"UPDATE transaksi SET surcharge_id='$obj->surchargeID', no_meja='$surcharge_name' WHERE id='$obj->transactionID' AND deleted_at IS NULL AND status=5");
            
            if($update){
                
            $updateDetail = mysqli_query($db_conn,"UPDATE detail_transaksi SET surcharge_id='$obj->surchargeID' WHERE id_transaksi='$obj->transactionID'");
                
                $success = 1;
                $msg = "Berhasil Mengubah Data";
                $status = 200;
            } else {
                $success = 0;
                $msg = "Gagal Mengubah Data";
                $status = 203;
            }
            
        } else {
            $success = 0;
            $msg = "Data Tidak Ditemukan";
            $status = 203;
        }
        
    }else{
        $success = 0;
        $msg = "Mohon lengkapi field";
        $status = 400;
    }

}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
// echo json_encode(["test"=>"hit"]);
?>