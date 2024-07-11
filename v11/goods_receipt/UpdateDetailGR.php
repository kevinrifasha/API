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
    $data = json_decode(file_get_contents("php://input"));
    if (gettype($data) == "NULL") {
        $data = json_decode(json_encode($_POST));
    }

    $res = array();
    $iR = 0;
    if (
        isset($data->id_gr)
        // && !empty(trim($data->id_gr))
    ) {

        $id_gr = mysqli_real_escape_string($db_conn, trim($data->id_gr));
        $todayDT = date("Y-m-d H:i:s");

        // echo ($id_gr);

        // // echo ($id_variant);

        $allGoods = mysqli_query($db_conn, "SELECT goods_receipt_detail.id_gr, goods_receipt_detail.id,  goods_receipt_detail.id_menu, goods_receipt_detail.id_raw_material
            FROM goods_receipt_detail JOIN goods_receipt ON goods_receipt.id=goods_receipt_detail.id_gr
            WHERE goods_receipt_detail.id_gr ='$id_gr'");
        // }
        // echo(mysqli_num_rows($allGoods));
        foreach ($data->goods_receipt_detail as $input) {
            $is_insert_menu = 1;
            $is_insert_raw = 1;
            $updateIdMenu = $input->id_menu;
            $updateIdRawMaterial = $input->id_raw_material;
            $updateQty = $input->qty;
            $updateMetric = $input->id_metric;
            $updateExpDate = $input->exp_date;
            // echo(mysqli_num_rows($allGoods));
            foreach ($allGoods as $goods) {
                $id = $goods['id'];
                $id_gr = $goods['id_gr'];
                $id_menu = $goods['id_menu'];
                $id_raw = $goods['id_raw_material'];


                if ($updateIdRawMaterial == 0 && $id_menu == $updateIdMenu) {
                    // echo "\n";
                    // echo ($id_menu);
                    // echo "\n";
                    // echo ($updateIdMenu);
                    $checkStock = mysqli_query($db_conn, "SELECT `qty` FROM `goods_receipt_detail` WHERE `id`='$id'");
                    // echo ("SELECT `qty` FROM `goods_receipt_detail` WHERE `id`='$id'");
                    while ($row = mysqli_fetch_assoc($checkStock)) {
                        // echo ($row['in_hour']);
                        $tempStock = (int)$row['qty'];
                    }

                    $updateMenuStock = mysqli_query($db_conn, "UPDATE `menu` SET `stock` = `stock` - $tempStock + $updateQty WHERE `menu`.`id` = $updateIdMenu");

                    if ($updateMenuStock) {
                        $updatePaket = mysqli_query($db_conn, "UPDATE `goods_receipt_detail` SET `id_menu` = '$updateIdMenu', `id_raw_material` = '$updateIdRawMaterial', `qty` = '$updateQty', `id_metric` = '$updateMetric', `expired_date` = '$updateExpDate', `updated_at` = '$todayDT' WHERE `goods_receipt_detail`.`id` = '$id'");
                        if ($updatePaket) {

                            $arr[$iR]['success'] = $updatePaket;
                            $arr[$iR]['msg'] = "Update Berhasil Untuk id_menu :" . $updateIdMenu;
                            $iR += 1;
                            $is_insert_menu = 0 ;
                        }
                    }
                }

                if ($updateIdMenu == 0 && $id_raw == $updateIdRawMaterial) {
                    $updateRawStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `id_raw_material` = '$updateIdRawMaterial', `stock` = '$updateQty', `id_metric` = '$updateMetric', `exp_date` = '$updateExpDate' WHERE `raw_material_stock`.`id_goods_receipt_detail` = $id");

                    // echo "\n";
                    // echo "masuk if";
                    if ($updateRawStock) {
                        $temp_id_menu = 0;
                        $updatePaket = mysqli_query($db_conn, "UPDATE `goods_receipt_detail` SET `id_menu` = '$temp_id_menu', `id_raw_material` = '$updateIdRawMaterial', `qty` = '$updateQty', `id_metric` = '$updateMetric', `expired_date` = '$updateExpDate', `updated_at` = '$todayDT' WHERE `goods_receipt_detail`.`id` = '$id'");
                        if ($updatePaket) {

                            $arr[$iR]['success'] = $updatePaket;
                            // echo($updateIdRawMaterial);
                            $arr[$iR]['msg'] = "Update Berhasil Untuk id_raw :" . $updateIdRawMaterial;
                            $iR += 1;
                            $is_insert_raw = 0  ;
                        }
                    }
                }
            }

            if ($is_insert_menu == 1 && $updateIdMenu != 0) {
                // echo "insert menu";
                $insertPaket = mysqli_query($db_conn, "INSERT INTO `goods_receipt_detail` (`id_gr`, `id_menu`, `id_raw_material`, `qty`, `id_metric`, `expired_date`, `created_at`) VALUES ('$id_gr', '$updateIdMenu', '$updateIdRawMaterial', '$updateQty', '$updateMetric', '$updateExpDate', '$todayDT')");
                if ($insertPaket) {
                    $updateMenuStock = mysqli_query($db_conn, "UPDATE `menu` SET `stock` = `stock` + $updateQty WHERE `menu`.`id` = $updateIdMenu");
                    if ($updateMenuStock) {
                        $arr[$iR]['success'] = $updateMenuStock;
                        $arr[$iR]['msg'] = "Insert Berhasil Untuk id_menu :" . $updateIdMenu;
                        $iR += 1;
                    }
                }
            }
            if ($is_insert_raw == 1 && $updateIdRawMaterial != 0) {
                // echo "insert raw";
                $insertPaket = mysqli_query($db_conn, "INSERT INTO `goods_receipt_detail` (`id_gr`, `id_menu`, `id_raw_material`, `qty`, `id_metric`, `expired_date`, `created_at`) VALUES ('$id_gr', '$updateIdMenu', '$updateIdRawMaterial', '$updateQty', '$updateMetric', '$updateExpDate', '$todayDT')");
                $last_id = mysqli_insert_id($db_conn);
                if ($insertPaket) {
                    $insertStockRawPaket = mysqli_query($db_conn, "INSERT INTO `raw_material_stock` (`id_raw_material`, `stock`, `id_metric`, `exp_date`, `id_goods_receipt_detail`) VALUES ('$updateIdRawMaterial', '$updateQty', '$updateMetric', '$updateExpDate', '$last_id')");
                    if ($insertStockRawPaket) {
                        $arr[$iR]['success'] = $insertStockRawPaket;
                        $arr[$iR]['msg'] = "Insert Berhasil Untuk id_raw :" . $updateIdRawMaterial;
                        $iR += 1;
                    }
                }
            }
        }
        
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "resArray" => $arr]);