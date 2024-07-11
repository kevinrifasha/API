<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');
require '../../includes/functions.php';

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
    if(isset($_GET['id']) && !empty($_GET['id'])){
        $fs = new functions();
        $id = $_GET['id'];
        $query = "SELECT id, nama, is_recipe, stock FROM `menu` WHERE is_recipe=1 AND deleted_at IS NULL AND id_partner='$id'";
        $allRecom = mysqli_query($db_conn, $query);
        if (mysqli_num_rows($allRecom) > 0) {
            $rowR = mysqli_fetch_all($allRecom, MYSQLI_ASSOC);
            $res = $fs->stock_menu($rowR);
            foreach ($res as $value) {
                $mID = $value['id'];
                $stock = $value['stock'];
                $update = mysqli_query($db_conn, "UPDATE `menu` SET stock='$stock' WHERE id='$mID'");
            };
            $query = "SELECT v.id, v.name, v.stock, v.is_recipe FROM `variant` v JOIN `variant_group` vg ON vg.id=v.id JOIN `partner` p ON p.id_master=vg.id_master WHERE p.id='$id' AND v.is_recipe=1";
            $allRecom = mysqli_query($db_conn, $query);
            if (mysqli_num_rows($allRecom) > 0) {
                $rowR = mysqli_fetch_all($allRecom, MYSQLI_ASSOC);
                $res = $fs->stock_variant($rowR);
                foreach ($res as $value) {
                    $mID = $value['id'];
                    $stock = $value['stock'];
                    $update = mysqli_query($db_conn, "UPDATE `menu` SET stock='$stock' WHERE id='$mID'");
                };
            }
            $success = 1;
            $status = 200;
            $msg = "success";
        }else{
            $success = 0;
            $status = 204;
            $msg = "menu's not found";
        }
    }else{
        $success = 0;
        $status = 400;
        $msg = "missing required params";
    }
}
http_response_code($status);    
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]); 
?>