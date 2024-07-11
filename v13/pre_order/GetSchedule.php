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
    $phone = $token->phone;
    $id = $_GET['id'];
    $date = date("Y-m-d");

    $allMenus = mysqli_query($db_conn, "SELECT id, item_sales, order_from, order_to, delivery_from, delivery_to FROM `pre_order_schedules` WHERE partner_id='$id' AND deleted_at IS NULL ");
    if (mysqli_num_rows($allMenus) > 0) {
        $res1 = mysqli_fetch_all($allMenus, MYSQLI_ASSOC);
        $i = 0;
        foreach ($res1 as $value) {
            if( strtotime($value['order_from']) <= strtotime('now') && strtotime($value['order_to']) >= strtotime('now') ) {
                $res[$i] = $value;
                $mn = json_decode($value['item_sales']);
                $j = 0;
                foreach ($mn as $value) {
                    $mID = $value->id;
                    $allMenusQ = mysqli_query($db_conn, "SELECT id, name, description, image, thumbnail, price FROM `pre_order_menus` WHERE id='$mID'");
                    $resQ = mysqli_fetch_all($allMenusQ, MYSQLI_ASSOC);
                    foreach ($resQ as $valueSQ) {
                        $res[$i]['detail'][$j]=$valueSQ;
                        $res[$i]['detail'][$j]['price']=$valueSQ['price'];
                        $res[$i]['detail'][$j]['quota']=$value->quota;
                        $j+=1;
                    }
                }
                $i+=1;
            }
        }
        $success = 1;
        $status = 200;
        $msg = "success";

    } else {
        $success = 0;
        $status = 204;
        $msg = "Data Tidak Ada";
    }

}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "menus"=>$res]);
