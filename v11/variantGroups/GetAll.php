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
$vals = array();

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
    $q = mysqli_query($db_conn, "SELECT `id`, `name`, `type` FROM `variant_group` WHERE (partner_id = '$token->id_partner' AND id_master = '$token->id_master') OR (partner_id = '' AND id_master = '$token->id_master') ");


    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $data = "";
        $i = 0;
        foreach ($res as $val) {
            $data = $val;
            $find = $val['id'];
            $qv = mysqli_query($db_conn, "SELECT `id`, `name`, `price`, `stock`, `is_recipe` FROM `variant` WHERE id_variant_group='$find'");
            $data['variants'] = array();
            if (mysqli_num_rows($qv) > 0) {
                $resQv = mysqli_fetch_all($qv, MYSQLI_ASSOC);
                $data['variants'] = $resQv;
                $j = 0;
                foreach ($data['variants'] as $value1) {
                    $finRW = $value1['id'];
                    if($value1['is_recipe']=='1'){
                        $arr=array();
                        $qR = mysqli_query($db_conn, "SELECT r.id, r.id_raw, r.qty, r.id_metric, rw.name as name_raw, m.name as name_metric FROM recipe r JOIN raw_material rw ON rw.id=r.id_raw JOIN metric m ON m.id=r.id_metric WHERE r.id_variant='$finRW'");
                        $resR = mysqli_fetch_all($qR, MYSQLI_ASSOC);
                        $data['variants'][$j]['recipes'] = $resR;

                        $k=0;
                        foreach ($data['variants'][$j]['recipes'] as $valueRec) {
                            $findMC = $valueRec['id_metric'];
                            $allRaw = mysqli_query($db_conn, "SELECT metric_convert.id_metric2, metric.name FROM metric_convert
                            JOIN metric on metric.id=metric_convert.id_metric2 WHERE metric_convert.id_metric1='$findMC'");
                            $all_raw = mysqli_fetch_all($allRaw, MYSQLI_ASSOC);
                            $arr = array();
                            $indexRecipe = 0;
                            foreach($all_raw as $key){
                                $arr[$indexRecipe]['id']= $key['id_metric2'];
                                $arr[$indexRecipe]['name'] = $key['name'];
                                $indexRecipe+=1;
                            }
                            $allRaw1 = mysqli_query($db_conn, "SELECT metric_convert.id_metric1, metric.name FROM metric_convert
                            JOIN metric on metric.id=metric_convert.id_metric1 WHERE metric_convert.id_metric2='$findMC'");
                            $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                            foreach($all_raw1 as $key){
                                $arr[$indexRecipe]['id']= $key['id_metric1'];
                                $arr[$indexRecipe]['name'] = $key['name'];
                                $indexRecipe+=1;
                            }

                            $allRaw1 = mysqli_query($db_conn, "SELECT id,name FROM `metric` WHERE id='$findMC'");
                            $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                            foreach($all_raw1 as $key){
                                $arr[$indexRecipe]['id']= $key['id'];
                                $arr[$indexRecipe]['name'] = $key['name'];
                                $indexRecipe+=1;
                            }
                            $data['variants'][$j]['recipes'][$k]['relevant_metrics']=$arr;
                            $k+=1;
                        }
                            $j +=1;
                        }else{
                            $data['variants'][$j]['recipes'] = array();
                            $j +=1;

                    }
                }
            }
            array_push($vals, $data);
        }
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =203;
        $msg = "Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "variantGroups"=>$vals]);
