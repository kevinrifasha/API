<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../../db_connection.php';
require_once('../../auth/Token.php');

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
$totalPending = "0";

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
    $status=200;
    if(isset($_GET['code'])){
        $code = $_GET['code'];
        $is_tenant = $_GET['is_tenant'];
        if($is_tenant=='0'){
            $sql = mysqli_query($db_conn, "SELECT `temporary_qr_cart`.`id`, `temporary_qr_cart`.`qr_id`, `temporary_qr_cart`.`menu_id`, `temporary_qr_cart`.`unit_price`, `temporary_qr_cart`.`qty`, `temporary_qr_cart`.`price`, `temporary_qr_cart`.`notes`, `temporary_qr_cart`.`variant`, `temporary_qr_cart`.`status`, `temporary_qr_cart`.`created_at`, menu.nama AS menu_name FROM `temporary_qr_cart` JOIN `temporary_qr` ON `temporary_qr`.`id`=`temporary_qr_cart`.`qr_id` JOIN menu ON menu.id=temporary_qr_cart.menu_id WHERE `temporary_qr`.`code`='$code' AND `temporary_qr_cart`.deleted_at IS NULL AND `temporary_qr_cart`.status='1'");
        }else{
            $sql = mysqli_query($db_conn, "SELECT `temporary_qr_cart`.`id`, `temporary_qr_cart`.`qr_id`, `temporary_qr_cart`.`menu_id`, `temporary_qr_cart`.`unit_price`, `temporary_qr_cart`.`qty`, `temporary_qr_cart`.`price`, `temporary_qr_cart`.`notes`, `temporary_qr_cart`.`variant`, `temporary_qr_cart`.`status`, `temporary_qr_cart`.`created_at`, menu.nama AS menu_name FROM `temporary_qr_cart` JOIN `temporary_qr` ON `temporary_qr`.`id`=`temporary_qr_cart`.`qr_id` JOIN menu ON menu.id=temporary_qr_cart.menu_id WHERE `temporary_qr`.`code`='$code' AND `temporary_qr_cart`.`tenant_id`='$token->id_partner' AND `temporary_qr_cart`.deleted_at IS NULL AND `temporary_qr_cart`.status='1'");

        }
        if(mysqli_num_rows($sql)>0){
            $res = mysqli_fetch_all($sql, MYSQLI_ASSOC);
            $i = 0;
            foreach ($res as $value) {
                $temp = $value;
                if($value['variant'] != null) {
                    $variant = $value['variant'];
                    $variant =  substr($variant,11);
                    $variant = substr_replace($variant ,"",-1);
                    $variant = str_replace("'",'"',$variant);
                    $temp['variant'] = json_decode($variant);
                }else{
                    $temp['variant'] = [];
                }
                $res[$i]['vartiant']=$temp['variant'];
            }
            $success = 1;
            $msg = "Data ditemukan";
        }else{
            $success = 0;
            $msg = "Data tidak ditemukan";
        }
    }else{
        $success =0;
        $msg = "400 Missing Required Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "carts"=>$res]);
?>