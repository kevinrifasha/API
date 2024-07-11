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
$arr = array();

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
    $q = mysqli_query($db_conn, "SELECT id, name, price, description, image, thumbnail, cogs, is_variant, is_recipe, enabled FROM `pre_order_menus` WHERE partner_id='$partner_id' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $i =0;
        foreach ($res as $value) {
            $arr[$i]=$value;
            $mID = $value['id'];
            if($value['is_variant']=="1"){
                $qM = mysqli_query($db_conn, "SELECT pomv.variant_group_id, vg.name FROM pre_order_menu_variants pomv JOIN variant_group vg ON pomv.variant_group_id=vg.id WHERE pomv.pre_order_menu_id='$mID' AND pomv.deleted_at IS NULL");
                if (mysqli_num_rows($qM) > 0) {
                    $resM = mysqli_fetch_all($qM, MYSQLI_ASSOC);
                    $j=0;
                    foreach ($resM as $valueM) {
                        $arr[$i]['variant_group'][$j]=$valueM;
                        $vgID = $valueM['variant_group_id'];
                        $QV = mysqli_query($db_conn, "SELECT id, name, price FROM `variant` WHERE id_variant_group='$vgID'");
                        if (mysqli_num_rows($QV) > 0) {
                            $arr[$i]['variant_group'][$j]['detail'] = mysqli_fetch_all($QV, MYSQLI_ASSOC);
                        }
                        $j+=1;
                    }
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
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "items"=>$arr]);
?>