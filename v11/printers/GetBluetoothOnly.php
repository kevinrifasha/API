<?php    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
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
    
    $q = mysqli_query($db_conn, "SELECT * FROM `printer` WHERE partnerId='$token->id_partner' AND deleted_at IS NULL AND is_bluetooth=1");
    $res = array();
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $i =0;
        foreach ($res as $value) {
            $find = $value['id'];
            $qs = mysqli_query($db_conn, "SELECT id, category_id FROM `printer_categories` WHERE printer_id='$find' AND deleted_at IS NULL");
            $res[$i]['categories']= mysqli_fetch_all($qs, MYSQLI_ASSOC);
            $qs = mysqli_query($db_conn, "SELECT `id`, `table_group_id` FROM `printer_table` WHERE `printer_id`='$find' AND `deleted_at` IS NULL");
            $res[$i]['table_groups']= mysqli_fetch_all($qs, MYSQLI_ASSOC);
            $i+=1;
        }
        $success = 1;
        $msg = "Success";
        $status = 200;
    }else{    
        $success = 0;
        $msg = "Data Not Found";
        $status = 204;
    }

}
    
        
$signupJson = json_encode(["msg"=>$msg, "status"=>$status, "success"=>$success, "printers"=>$res]);  
http_response_code($status);
echo $signupJson;

 ?>
 