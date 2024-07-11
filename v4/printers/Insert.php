<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
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
$printer_id = "";
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    // POST DATA
    $data = json_decode(json_encode($_POST));
    $now = date("Y-m-d H:i:s");
    if(
        isset($data->name) && !empty($data->name)
    ){
        $ip = $data->ip;
        $partnerId = $token->id_partner;
        $name = $data->name;
        $isReceipt = $data->isReceipt;
        $isFullChecker = $data->isFullChecker;
        $isCategoryChecker = $data->isCategoryChecker;
        $isTableChecker = $data->isTableChecker;
        $isDoubleReceipt = $data->isDoubleReceipt;
        $paperSize = $data->paperSize;
        $is_temporary_qr = $data->isTemporaryQR;
        if(!empty($data->macAddress)){
            $isBluetooth = 1;
        }else{
            $isBluetooth = 0;
        }
        $insert = mysqli_query($db_conn,"INSERT INTO printer SET ip='$ip', partnerId='$partnerId', name='$name', isReceipt='$isReceipt',  isFullChecker='$isFullChecker', isCategoryChecker='$isCategoryChecker', is_table_checker='$isTableChecker', is_double_receipt='$isDoubleReceipt', paper_size='$paperSize', mac_address='$data->macAddress', is_bluetooth='$isBluetooth', is_temporary_qr='$is_temporary_qr'");
        $printer_id = mysqli_insert_id($db_conn);
        if($isCategoryChecker=='1'){
            $dataC = json_decode($data->categories);
            foreach ($dataC as $value) {
                $category_id = $value->category_id;
                $insert = mysqli_query($db_conn,"INSERT INTO `printer_categories`(`printer_id`, `category_id`) VALUES ('$printer_id', '$category_id')");
            }
        }
        if($isTableChecker=='1'){
            $dataC = json_decode($data->table_groups);
            foreach ($dataC as $value) {
                $table_group_id = $value->table_group_id;
                $insert = mysqli_query($db_conn,"INSERT INTO `printer_table`(`printer_id`, `table_group_id`, `created_at`) VALUES ('$printer_id','$table_group_id',NOW())");
            }
        }

        if($insert){
            $msg = "Success";
            $success = 1;
            $status=200;
        }else{
            $msg = "Failed";
            $success = 1;
            $status=204;
        }
    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;  
    }

}
        
$signupJson = json_encode(["msg"=>$msg, "success"=>$success,"status"=>$status,"id"=>$printer_id]);  
http_response_code($status);
echo $signupJson;
    
?>
     