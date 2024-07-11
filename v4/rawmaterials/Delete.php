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
$menus = array();
$variants = array();
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
    $json = file_get_contents('php://input');
    $data = json_decode($json,true);
    if( 
        isset($data['id'])
        && !empty($data['id'])
        ){
            $id = $data['id'];
            $insert = mysqli_query($db_conn,"UPDATE `raw_material` SET deleted_at = NOW() WHERE id='$id'");
            if($insert){
                $insert = mysqli_query($db_conn,"UPDATE `raw_material_stock` SET deleted_at = NOW() WHERE id_raw_material='$id'");
                $q = mysqli_query($db_conn, "SELECT `id_menu`, `id_variant`, `sfg_id` FROM `recipe` WHERE id_raw='$id'");
                $res1 = array();
                if (mysqli_num_rows($q) > 0) {
                    $res1 = mysqli_fetch_all($q, MYSQLI_ASSOC);
                    $insert = mysqli_query($db_conn,"UPDATE `recipe` SET deleted_at = NOW() WHERE id_raw='$id'");
                    foreach ($res1 as $value) {
                        $id_menu=$value['id_menu'];
                        $id_variant=$value['id_variant'];
                        $qA = mysqli_query($db_conn, "SELECT `id` FROM `recipe` WHERE id_menu='$id_menu' AND id_variant = '$id_variant' AND deleted_at IS NULL");
                        
                        if (mysqli_num_rows($qA) == 0) {
                            if ($id_menu != '0'){
                                array_push($menus, $id_menu);
                            } elseif ($id_variant != '0') {
                                array_push($variants, $id_variant);
                            }
                        }
                    }
                    mysqli_query($db_conn, "UPDATE menu SET is_recipe = '0', updated_at=NOW() WHERE id IN ('" . implode("','", $menus) . "')");
                    mysqli_query($db_conn, "UPDATE variant SET is_recipe = '0', updated_at=NOW() WHERE id IN ('" . implode("','", $variants) . "')");
                }
                $success =1;
                $status =200;
                $msg = "Success";
            }else{
                $success =0;
                $status =204;
                $msg = "Failed";
            }
    }else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>