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
        && isset($data->printerID) && !empty($data->printerID)
    ){
        $ip = $data->ip;
        $id = $data->printerID;
        $partnerId = $token->id_partner;
        $name = $data->name;
        $isReceipt = $data->isReceipt;
        $isFullChecker = $data->isFullChecker;
        $isCategoryChecker = $data->isCategoryChecker;
        $isTableChecker = $data->isTableChecker;
        $isDoubleReceipt = $data->isDoubleReceipt;
        $is_temporary_qr = $data->isTemporaryQR;
        $paperSize = $data->paperSize;
        $mac_address = $data->macAddress;
        $insert = mysqli_query($db_conn,"UPDATE printer SET ip='$ip', partnerId='$partnerId', name='$name', isReceipt='$isReceipt',  isFullChecker='$isFullChecker', isCategoryChecker='$isCategoryChecker', is_table_checker='$isTableChecker', is_double_receipt='$isDoubleReceipt', paper_size='$paperSize', is_temporary_qr='$is_temporary_qr', mac_address='$mac_address' WHERE id='$id'");
        // $printer_id = mysqli_insert_id($db_conn);
        if($isCategoryChecker==1){
            
            $dataC = json_decode($data->categories);
            $q1 = mysqli_query($db_conn, "SELECT * FROM `printer_categories` WHERE printer_id='$id' AND deleted_at IS NULL");
            $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
            $res = array();
            // delete
            foreach ($res1 as $value1) {
                $deleted = true;
                $dID = $value1['id'];
                foreach ($dataC as $value) {
                    if($value->category_id==$value1['category_id']){
                        $deleted=false;
                    }
                    // $insert = mysqli_query($db_conn,"INSERT INTO `printer_categories`(`printer_id`, `category_id`) VALUES ('$printer_id', '$category_id')");
                }
                if($deleted==true){
                    $delete = mysqli_query($db_conn,"UPDATE printer_categories SET deleted_at=NOW() WHERE id='$dID'");
                }
            }
            //update or insert
            $q1 = mysqli_query($db_conn, "SELECT * FROM `printer_categories` WHERE printer_id='$id' AND deleted_at IS NULL");
            $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
            foreach ($dataC as $value) {
                $update = false;
                $uID = 0;
                $category_id = $value->category_id;
                foreach ($res1 as $value1) {
                    if($value->category_id==$value1['category_id']){
                        $uID = $value1['id'];
                        $update=true;
                    }
                }
                if($update==true){
                    $update = mysqli_query($db_conn,"UPDATE `printer_categories` SET `category_id` = '$category_id' WHERE id='$uID'");
                }else{
                    $insert = mysqli_query($db_conn,"INSERT INTO `printer_categories`(`printer_id`, `category_id`) VALUES ('$id', '$category_id')");
                }
            }
        }
        if($isTableChecker==1){
            
            $dataC = json_decode($data->table_groups);
            $q1 = mysqli_query($db_conn, "SELECT * FROM `printer_table` WHERE printer_id='$id' AND deleted_at IS NULL");
            $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
            $res = array();
            // delete
            foreach ($res1 as $value1) {
                $deleted = true;
                $dID = $value1['id'];
                foreach ($dataC as $value) {
                    if($value->table_group_id==$value1['table_group_id']){
                        $deleted=false;
                    }
                    // $insert = mysqli_query($db_conn,"INSERT INTO `printer_categories`(`printer_id`, `table_group_id`) VALUES ('$printer_id', '$table_group_id')");
                }
                if($deleted==true){
                    $delete = mysqli_query($db_conn,"UPDATE printer_table SET deleted_at=NOW() WHERE id='$dID'");
                }
            }
            //update or insert
            $q1 = mysqli_query($db_conn, "SELECT * FROM `printer_table` WHERE printer_id='$id' AND deleted_at IS NULL");
            $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
            foreach ($dataC as $value) {
                $update = false;
                $uID = 0;
                $table_group_id = $value->table_group_id;
                foreach ($res1 as $value1) {
                    if($value->table_group_id==$value1['table_group_id']){
                        $uID = $value1['id'];
                        $update=true;
                    }
                }
                if($update==true){
                    $update = mysqli_query($db_conn,"UPDATE `printer_table` SET `table_group_id` = '$table_group_id' WHERE id='$uID'");
                }else{
                    $insert = mysqli_query($db_conn,"INSERT INTO `printer_table`(`printer_id`, `table_group_id`) VALUES ('$id', '$table_group_id')");
                }
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
        
$signupJson = json_encode(["msg"=>$msg, "success"=>$success,"status"=>$status]);  
http_response_code($status);
echo $signupJson;
    
?>
     