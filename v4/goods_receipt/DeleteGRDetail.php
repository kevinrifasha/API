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
$iid = 0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{

    $data = json_decode(file_get_contents("php://input"));
    if (gettype($data) == "NULL") {
        $data = json_decode(json_encode($_POST));
    }

    if (isset($data->id)) {
        // echo("asd");
        $delID = $data->id;
        $bool = false;

        $checkMenu = mysqli_query($db_conn, "SELECT `id_menu`, `id_raw_material` FROM `goods_receipt_detail` WHERE `id`='$delID'");
        // echo ("SELECT `id_menu`, `id_raw_material` FROM `goods_receipt_detail` WHERE `id`='$delID'");
        while ($row = mysqli_fetch_assoc($checkMenu)) {

            $id_menu = $row['id_menu'];
            $id_raw_material = $row['id_raw_material'];
        }
        // echo ($id_menu);
        // echo "\n";
        // echo ($id_raw_material);

        if ($id_menu != 0 && $id_raw_material == 0) {
            $checkStock = mysqli_query($db_conn, "SELECT `qty` FROM `goods_receipt_detail` WHERE `id`='$delID'");

            while ($row = mysqli_fetch_assoc($checkStock)) {
                $tempStock = (int)$row['qty'];
            }

            $updateMenuStock = mysqli_query($db_conn, "UPDATE `menu` SET `stock` = `stock` - $tempStock WHERE `menu`.`id` = $id_menu");
            if ($updateMenuStock) {
                // echo("Masuk IF");
                $deletePaket = mysqli_query($db_conn, "DELETE FROM goods_receipt_detail WHERE id='$delID'");
                if ($deletePaket) {
                    $bool = true;
                }
            }
        } else if ($id_menu == 0 && $id_raw_material != 0) {
            $deleteRawPaket = mysqli_query($db_conn, "DELETE FROM raw_material_stock WHERE id_goods_receipt_detail='$delID'");
            if ($deleteRawPaket) {
                $deletePaket = mysqli_query($db_conn, "DELETE FROM goods_receipt_detail WHERE id='$delID'");
                if ($deletePaket) {
                    $bool = true;
                }
            }
        }


        if ($bool) {
            $success =1;
            $status =200;
            $msg = "Deleted";
        }
        else{
            $success =0;
            $status =204;
            $msg = "Failed";
        }
    }
    else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
