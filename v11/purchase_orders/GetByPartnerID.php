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
    $id_partner = $token->id_partner;
    
    $q = mysqli_query($db_conn, "SELECT po.id, po.no, po.master_id, po.partner_id, po.supplier_id, po.total, po.created_at, s.name AS supplier_name FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id WHERE po.partner_id='$id_partner' AND po.deleted_at IS NULL ORDER BY po.id DESC");
    
    if (mysqli_num_rows($q) > 0) {
        $i = 0;
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        foreach ($res as $value) {
            $find = $value['id'];
            $qD = mysqli_query($db_conn, "SELECT pod.id, pod.raw_id ,pod.menu_id, pod.qty, pod.metric_id, pod.price, m.name AS metric_name FROM purchase_orders_details pod JOIN metric m ON m.id=pod.metric_id WHERE pod.purchase_order_id='$find'");
            
            $qGR = mysqli_query($db_conn, "SELECT id FROM `goods_receipt` WHERE purchase_order_id='$find' AND deleted_at IS NULL");
            if (mysqli_num_rows($qGR) > 0) {
                $res[$i]['delete']=false;
            }else{
                $res[$i]['delete']=true;

            }

            if (mysqli_num_rows($qD) > 0) {
                $j=0;
                $resD = mysqli_fetch_all($qD, MYSQLI_ASSOC);
                $res[$i]['details']=$resD;
                foreach ($resD as $valueD) {
                    if(is_null($valueD['menu_id']) || $valueD['menu_id']=="0"){
                        $findD = $valueD['raw_id'];
                        $qI = mysqli_query($db_conn, "SELECT raw_material.name AS item_name FROM raw_material WHERE id='$findD'");
                        if (mysqli_num_rows($qI) > 0) {
                            $resI = mysqli_fetch_all($qI, MYSQLI_ASSOC);
                            $res[$i]['details'][$j]['itemName']=$resI[0]['item_name'];
                        }else{
                            $res[$i]['details'][$j]['itemName']="Wrong Raw";
                        }
                    }else{
                        $findD = $valueD['menu_id'];
                        $qI = mysqli_query($db_conn, "SELECT menu.nama AS item_name FROM menu WHERE id='$findD'");
                        if (mysqli_num_rows($qI) > 0) {
                            $resI = mysqli_fetch_all($qI, MYSQLI_ASSOC);
                            $res[$i]['details'][$j]['itemName']=$resI[0]['item_name'];
                        }else{
                            $res[$i]['details'][$j]['itemName']="Wrong Menu";
                        }
                    }
                    $j+=1;
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
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "purchaseOrders"=>$res]);
?>