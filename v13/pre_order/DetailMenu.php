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

    $id = $_GET['id'];

    $allMenus = mysqli_query($db_conn, "SELECT `id`, `partner_id`, `name`, `price`, `description`, `image`, `thumbnail`, `enabled`, `cogs`, `is_variant` FROM `pre_order_menus` WHERE id='$id' AND deleted_at IS NULL");

    if (mysqli_num_rows($allMenus) > 0) {

        $res = mysqli_fetch_all($allMenus, MYSQLI_ASSOC);
        $i =0;
        foreach ($res as $value) {
            $j = 0;
            $id = $value['id'];
            $allQ = mysqli_query($db_conn, "SELECT vg.name as variant_group_name, vg.type as variant_group_type, vg.id as variant_group_id FROM pre_order_menu_variants pomv JOIN variant_group vg ON pomv.variant_group_id=vg.id WHERE pomv.pre_order_menu_id='$id'");
            $resVG = mysqli_fetch_all($allQ, MYSQLI_ASSOC);
            foreach ($resVG as $valueVG) {
                $res[$i]['variants'][$j]=$valueVG;
                $find = $valueVG['variant_group_id'];
                $type = $valueVG['variant_group_type'];

                $allQV = mysqli_query($db_conn, "SELECT id,name, price, stock, id_variant_group as variant_group_id  FROM `variant` WHERE id_variant_group='$find'");
                $resV = mysqli_fetch_all($allQV, MYSQLI_ASSOC);
                $k = 0;
                foreach ($resV as $valueV) {
                    $res[$i]['variants'][$j]['detail'][$k]=$valueV;
                    $res[$i]['variants'][$j]['detail'][$k]['variant_group_type']=$type;
                    $k+=1;
                }
                $j +=1;
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
