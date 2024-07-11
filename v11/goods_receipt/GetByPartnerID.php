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
    //  $id_partner= "000100";
    $id_partner = $token->id_partner;
    $allGR = mysqli_query($db_conn, "SELECT gr.id, gr.delivery_order_number, gr.sender, gr.recieve_date, gr.created_at, po.no AS poNo  FROM goods_receipt gr JOIN purchase_orders po ON gr.purchase_order_id=po.id WHERE gr.id_partner='$id_partner' ORDER BY gr.id DESC");
    $all_GR = mysqli_fetch_all($allGR, MYSQLI_ASSOC);
    $arr = array();
    $index = 0;
    $indexR = 0;

    foreach ($allGR as $value) {
        $arr[$index] = $value;
        $id_gr = $value['id'];
        // echo($value['delivery_order_number']);
        // echo($value['sender']);
        // echo($value['recieve_date']);

        $allGRD = mysqli_query($db_conn, "SELECT goods_receipt_detail.id, metric.id AS id_metric, raw_material.id AS id_raw, goods_receipt_detail.qty, goods_receipt_detail.id_menu, goods_receipt_detail.expired_date, goods_receipt_detail.created_at, metric.name AS metric_name, raw_material.name AS name FROM `goods_receipt_detail`
                                        JOIN goods_receipt ON goods_receipt.id=goods_receipt_detail.id_gr JOIN metric ON metric.id=goods_receipt_detail.id_metric JOIN raw_material ON raw_material.id = goods_receipt_detail.id_raw_material
                                        WHERE goods_receipt_detail.id_gr=$id_gr");
        $allMenu = mysqli_query($db_conn, "SELECT goods_receipt_detail.id, goods_receipt_detail.id_raw_material AS id_raw, goods_receipt_detail.qty, goods_receipt_detail.id_menu, goods_receipt_detail.expired_date, goods_receipt_detail.created_at, menu.nama AS name FROM `goods_receipt_detail`
        JOIN goods_receipt ON goods_receipt.id=goods_receipt_detail.id_gr JOIN menu ON menu.id = goods_receipt_detail.id_menu
        WHERE goods_receipt_detail.id_gr=$id_gr");
        $indexR = 0;
        $all_GRD = mysqli_fetch_all($allGRD, MYSQLI_ASSOC);
        $all_Menu = mysqli_fetch_all($allMenu, MYSQLI_ASSOC);

        if (mysqli_num_rows($allGRD) == 0 && mysqli_num_rows($allMenu) == 0 ) {
            $arr[$index]['good_receipt_detail'] = array();
            $indexR += 1;
            $index += 1;
        } else {

            foreach ($all_GRD as $value1) {
                $arr[$index]['good_receipt_detail'][$indexR] = $value1;
                $indexR += 1;
            }
            foreach ($all_Menu as $value2) {
                $arr[$index]['good_receipt_detail'][$indexR] = $value2;
                $indexR += 1;
            }
            $index += 1;
        }
    }

    // echo($id_partner);

    // if (mysqli_num_rows($allGR) > 0) {
    //     $all_GR = mysqli_fetch_all($allGR, MYSQLI_ASSOC);
    //     echo json_encode(["success" => 1, "goods_receipt_detail" => $all_GR]);
    // } else {
    //     echo json_encode(["success" => 0]);
    // }

    if (count($arr) > 0) {
        $success =1;
            $status =200;
            $msg = "Success";
    }else{
            $success =0;
            $status =204;
            $msg = "Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "good_receipt" => $arr]);
