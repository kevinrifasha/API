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
    $grID = $_GET['grID'];
    $id_partner = $token->id_partner;
    $allGR = mysqli_query($db_conn, "SELECT gr.id, gr.delivery_order_number, gr.sender, gr.recieve_date, gr.created_at, po.no AS poNo, po.created_at AS poCreatedAt, e.nama AS receiver FROM goods_receipt gr JOIN purchase_orders po ON gr.purchase_order_id=po.id JOIN employees e ON e.id=gr.receiver_id WHERE gr.id='$grID' ORDER BY gr.id DESC");
    $all_GR = mysqli_fetch_all($allGR, MYSQLI_ASSOC);
    $arr = array();
    $index = 0;
    $indexR = 0;

    foreach ($allGR as $value) {
        $arr[$index] = $value;
        $arr[0]['rawSubtotal'] = 0;
        $arr[0]['menuSubtotal'] = 0;
        $id_gr = $value['id'];
        // echo($value['delivery_order_number']);
        // echo($value['sender']);
        // echo($value['recieve_date']);

        $allGRD = mysqli_query($db_conn, "SELECT goods_receipt_detail.id, goods_receipt_detail.qty, goods_receipt_detail.unit_price, goods_receipt_detail.created_at, metric.name AS metric_name, raw_material.name AS name, (goods_receipt_detail.unit_price * goods_receipt_detail.qty) AS subtotal FROM `goods_receipt_detail`
                                        JOIN goods_receipt ON goods_receipt.id=goods_receipt_detail.id_gr JOIN metric ON metric.id=goods_receipt_detail.id_metric JOIN raw_material ON raw_material.id = goods_receipt_detail.id_raw_material
                                        WHERE goods_receipt_detail.id_gr=$id_gr");

        $allMenu = mysqli_query($db_conn, "SELECT goods_receipt_detail.id, goods_receipt_detail.qty, goods_receipt_detail.id_menu, goods_receipt_detail.unit_price, goods_receipt_detail.created_at, menu.nama AS name, (goods_receipt_detail.unit_price * goods_receipt_detail.qty) AS subtotal, 'porsi' AS metric_name FROM `goods_receipt_detail`
        JOIN goods_receipt ON goods_receipt.id=goods_receipt_detail.id_gr JOIN menu ON menu.id = goods_receipt_detail.id_menu
        WHERE goods_receipt_detail.id_gr=$id_gr");
        $indexR = 0;
        $all_GRD = mysqli_fetch_all($allGRD, MYSQLI_ASSOC);
        $all_Menu = mysqli_fetch_all($allMenu, MYSQLI_ASSOC);
        $indexM = 0;
        if (mysqli_num_rows($allGRD) == 0 && mysqli_num_rows($allMenu) == 0 ) {
            $arr[$index]['rawSubtotal'] = array();
            $arr[$index]['detail_raw'] = array();
            $arr[$index]['menuSubtotal'] = array();
            $arr[$index]['detail_finished'] = array();
            $indexR += 1;
            $indexM +=1;
            $index += 1;
        } else {

            foreach ($all_GRD as $value1) {
                // $arr[$index]['rawSubtotal']+=$value1['subtotal'];
                ($arr[$index]['rawSubtotal'] ?? $arr[$index]['rawSubtotal'] = 0) ? $arr[$index]['rawSubtotal'] += $value1['subtotal'] : $arr[$index]['rawSubtotal'] = $value1['subtotal'];
                
                $arr[$index]['detail_raw'][$indexR] = $value1;
                $indexR += 1;
            }
            foreach ($all_Menu as $value2) {
                // $arr[$index]['menuSubtotal']+=$value2['subtotal'];
                ($arr[$index]['menuSubtotal'] ?? $arr[$index]['menuSubtotal'] = 0) ? $arr[$index]['menuSubtotal'] += $value2['subtotal'] : $arr[$index]['menuSubtotal'] = $value2['subtotal'];
                
                $arr[$index]['detail_finished'][$indexM] = $value2;
                $indexM += 1;
            }
            $index += 1;
        }
    }
    $arr[0]['grandTotal'] = $arr[0]['rawSubtotal'] + $arr[0]['menuSubtotal'];
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
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "good_receipt" => $arr]);
