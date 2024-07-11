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
$arr = array();
$arrRes = array();

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
    $q = mysqli_query($db_conn, "SELECT po.id, po.no, po.partner_id, po.supplier_id, po.total, p.name AS partnerName, s.name AS supplierName, po.created_at FROM purchase_orders po JOIN partner p ON p.id=po.partner_id JOIN suppliers s ON po.supplier_id=s.id WHERE po.partner_id='$token->id_partner' AND po.deleted_at IS NULL AND po.received=0 ORDER BY po.id DESC");

    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        foreach ($res as $r) {
            $poID = $r['id'];
            $data = $r;
            $qGR = mysqli_query($db_conn, "SELECT id FROM `goods_receipt` WHERE purchase_order_id='$poID' AND deleted_at IS NULL");
            $qD = mysqli_query($db_conn, "SELECT id, purchase_order_id, raw_id, menu_id, qty, metric_id,price FROM `purchase_orders_details` WHERE purchase_order_id='$poID' AND deleted_at IS NULL");
            $resD = mysqli_fetch_all($qD, MYSQLI_ASSOC);
            if (mysqli_num_rows($qGR) > 0) {
                $resGR = mysqli_fetch_all($qGR, MYSQLI_ASSOC);
                foreach ($resGR as $rGR) {
                    $grID = $rGR['id'];
                    $indexD = 0;
                    foreach ($resD as $rD) {
                        $name = "";
                        $metricName = "";
                        $metricID = $rD['metric_id'];

                        if($rD['menu_id']==null  || $rD['menu_id'] == 0){
                            $rawID = $rD['raw_id'];
                            $qGRD = mysqli_query($db_conn, "SELECT qty FROM `goods_receipt_detail` WHERE id_gr='$grID' AND id_raw_material='$rawID'");
                            $qName = mysqli_query($db_conn, "SELECT r.name, m.name as metric_name FROM raw_material r left join metric m on r.id_metric = m.id WHERE r.id='$rawID'");
                            $qMetric = mysqli_query($db_conn, "SELECT m.name as metric_name FROM metric m WHERE m.id='$metricID'");
                            if (mysqli_num_rows($qName) > 0 || mysqli_num_rows($qMetric) > 0) {
                                $resName = mysqli_fetch_all($qName, MYSQLI_ASSOC);
                                $resMetric = mysqli_fetch_all($qMetric, MYSQLI_ASSOC);
                                $name = $resName[0]['name'];
                                $metricName = $resMetric[0]['metric_name'];
                            }
                        }else{
                            $menuID = $rD['menu_id'];
                            $qGRD = mysqli_query($db_conn, "SELECT qty FROM `goods_receipt_detail` WHERE id_gr='$grID' AND id_menu='$menuID'");
                            $qName = mysqli_query($db_conn, "SELECT nama AS name FROM `menu` WHERE id='$menuID'");
                            if (mysqli_num_rows($qName) > 0) {
                                $resName = mysqli_fetch_all($qName, MYSQLI_ASSOC);
                                $name = $resName[0]['name'];
                                $metricName = "PCS";
                            }
                        }

                        if (mysqli_num_rows($qGRD) > 0) {
                            $resGRD = mysqli_fetch_all($qGRD, MYSQLI_ASSOC);
                            $qty=(int)$rD['qty'];
                            foreach($resGRD as $rGD){
                                $qty=$qty-(int)$rGD['qty'];
                            }
                            $data['detail'][$indexD]=$rD;
                            $data['detail'][$indexD]['sisa']=$qty;
                        }else{
                            $data['detail'][$indexD]=$rD;
                            $data['detail'][$indexD]['sisa']=(int)$rD['qty'];
                        }
                        $data['detail'][$indexD]['itemName']=$name;
                        $data['detail'][$indexD]['metricName']=$metricName;
                        $indexD+=1;
                    }
                }
            }else{
                $indexD = 0;
                foreach ($resD as $rD) {
                    $name = "";
                    $metricName = "";
                    $metricID = $rD['metric_id'];
                    $data['detail'][$indexD]=$rD;
                    $data['detail'][$indexD]['sisa']=(int)$rD['qty'];
                    if($rD['menu_id']==null  || $rD['menu_id'] == 0){
                        $rawID = $rD['raw_id'];
                        $qName = mysqli_query($db_conn, "SELECT r.name, m.name as metric_name FROM raw_material r left join metric m on r.id_metric = m.id WHERE r.id='$rawID'");
                        $qMetric = mysqli_query($db_conn, "SELECT m.name as metric_name FROM metric m WHERE m.id='$metricID'");
                        if (mysqli_num_rows($qName) > 0) {
                            $resName = mysqli_fetch_all($qName, MYSQLI_ASSOC);
                            $name = $resName[0]['name'];
                                $resMetric = mysqli_fetch_all($qMetric, MYSQLI_ASSOC);
                                $metricName = $resMetric[0]['metric_name'];
                        }
                    }else{
                        $menuID = $rD['menu_id'];
                        $qName = mysqli_query($db_conn, "SELECT nama AS name FROM `menu` WHERE id='$menuID'");
                        if (mysqli_num_rows($qName) > 0 || mysqli_num_rows($qMetric) > 0) {
                            $resName = mysqli_fetch_all($qName, MYSQLI_ASSOC);
                            $name = $resName[0]['name'];
                            $metricName = "PCS";
                        }
                    }
                    $data['detail'][$indexD]['itemName']=$name;
                    $data['detail'][$indexD]['metricName']=$metricName;
                    $indexD+=1;
                }
            }
            array_push($arr, $data);
        }
        foreach ($arr as $value) {
            $add = true;
            $i=0;
            $detail = array();
            foreach ($value['detail'] as $value1) {
                if((int) $value1['sisa']==0){
                    $add = false;
                }else{
                    $detail[$i] = $value1;
                    $value['detail'] = $detail;
                    $i+=1;
                }
            }
            if($add==true || count($detail)>0){
                array_push($arrRes, $value);
            }
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
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "purchaseOrders"=>$arrRes]);
?>