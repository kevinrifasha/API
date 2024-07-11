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

$tokens = $tokenizer->validate($token);
$tokenDcrpt = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    $partner_id = $_GET['partner_id'];
    $q = mysqli_query($db_conn, "SELECT id, name, item_sales, order_from, order_to, delivery_from, delivery_to FROM `pre_order_schedules` WHERE partner_id='$partner_id' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $i=0;
        foreach ($res as $value) {
            $item_sales = json_decode($value['item_sales']);
            $j=0;
            foreach ($item_sales as $i_sales) {
                $isid = $i_sales->id;
                $qM = mysqli_query($db_conn, "SELECT id, name FROM `pre_order_menus` WHERE id='$isid'");
                if (mysqli_num_rows($qM) > 0) {
                    $temp = mysqli_fetch_all($qM, MYSQLI_ASSOC);
                    $res[$i]['items'][$j] = $temp[0];
                    $res[$i]['items'][$j]['quota'] = $i_sales->quota;
                    $j+=1;
                }
            }
            $i+=1;
        }
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "schedules"=>$res]);
?>