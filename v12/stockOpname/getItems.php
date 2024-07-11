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
    $q = mysqli_query($db_conn, "SELECT m.nama AS label, m.id, m.id AS value, m.hpp AS cogs FROM menu m JOIN categories c ON m.id_category=c.id WHERE m.id_partner='$partner_id' AND m.is_recipe=0 AND m.deleted_at IS NULL");
    $q1 = mysqli_query($db_conn, "SELECT r.id as value ,r.id, r.name as label, r.unit_price as cogs, m.id as metric_id, m.name as metric_name FROM `raw_material` r JOIN metric m ON r.id_metric = m.id  WHERE r.id_partner='$partner_id' AND r.deleted_at IS NULL AND r.level=0");
    if (mysqli_num_rows($q) > 0 || mysqli_num_rows($q1) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        $i = 0;
        foreach ($res as $r) {
            $arr[$i] = $r;
            $arr[$i]['relevant_metrics']=array();
            $arr[$i]['name']='items';
            $arr[$i]['type']='menu';
            $arr[$i]['target']=$arr[$i];
            $i+=1;
        }
        foreach ($res1 as $r) {
            $arr[$i] = $r;
            $id = $r['metric_id'];
            $allRaw = mysqli_query($db_conn, "SELECT metric_convert.id_metric2, metric.name FROM metric_convert
                    JOIN metric on metric.id=metric_convert.id_metric2 WHERE metric_convert.id_metric1='$id'");
                    $all_raw = mysqli_fetch_all($allRaw, MYSQLI_ASSOC);
                    $arr1 = array();
                    $indexI = 0;
                    foreach($all_raw as $key){
                        $arr1[$indexI]['id']= $key['id_metric2'];
                        $arr1[$indexI]['value']= $key['id_metric2'];
                        $arr1[$indexI]['name'] = "id_metric";
                        $arr1[$indexI]['label'] = $key['name'];
                        $arr1[$indexI]['target'] = $arr1[$indexI];
                        $indexI+=1;
                    }
                    $allRaw1 = mysqli_query($db_conn, "SELECT metric_convert.id_metric1, metric.name FROM metric_convert
                    JOIN metric on metric.id=metric_convert.id_metric1 WHERE metric_convert.id_metric2='$id'");
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                    foreach($all_raw1 as $key){
                        $arr1[$indexI]['id']= $key['id_metric1'];
                        $arr1[$indexI]['value']= $key['id_metric1'];
                        $arr1[$indexI]['name'] = "id_metric";
                        $arr1[$indexI]['label'] = $key['name'];
                        $arr1[$indexI]['target'] = $arr1[$indexI];
                        $indexI+=1;
                    }


                    $allRaw1 = mysqli_query($db_conn, "SELECT id,name FROM `metric` WHERE id='$id'");
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                    foreach($all_raw1 as $key){
                        $arr1[$indexI]['id']= $key['id'];
                        $arr1[$indexI]['value']= $key['id'];
                        $arr1[$indexI]['name'] = "id_metric";
                        $arr1[$indexI]['label'] = $key['name'];
                        $arr1[$indexI]['target'] = $arr1[$indexI];
                        $indexI+=1;
                    }

                    $arr[$i]['relevant_metrics']=$arr1;
                    $arr[$i]['name']='items';
                    $arr[$i]['type']='raw';
                    $arr[$i]['target']=$arr[$i];
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