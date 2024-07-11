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
    $id_partner = $token->id_partner;
    $q = mysqli_query($db_conn, "SELECT r.*, m.nama AS menuName, m.img_data AS menuImage,rw.name AS rawName, me.name AS metricName FROM recipe r JOIN menu m ON m.id=r.id_menu JOIN raw_material rw ON rw.id=r.id_raw JOIN metric me ON me.id=r.id_metric WHERE m.id_partner='$id_partner' AND r.id_raw!=0 ORDER BY r.id_menu");
    $res1 = array();
    if (mysqli_num_rows($q) > 0) {
        $res1 = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $i = 0;
        $j = 0;
        $firstLoop = true;
        foreach ($res1 as $value) {
          if($firstLoop==true){
            $res[$i]['menuName']=$value['menuName'];
            $res[$i]['menuImage']=$value['menuImage'];
            $res[$i]['id_menu']=$value['id_menu'];
            $res[$i]['menuID']=$value['id_menu'];
            $res[$i]['recipe'][$j]['ID']=$value['id'];
            $res[$i]['recipe'][$j]['raw_material']['value']=$value['id_raw'];
            $res[$i]['recipe'][$j]['raw_material']['label']=$value['rawName'];
            $res[$i]['recipe'][$j]['raw_material']['id_metric']=$value['id_metric'];
            $res[$i]['recipe'][$j]['raw_material']['metricName']=$value['metricName'];
            $res[$i]['recipe'][$j]['raw_material']['qty']=$value['qty'];
            $res[$i]['recipe'][$j]['raw_material']['id_raw']=$value['id_raw'];
            
            $findMC = $value['id_metric'];

            $id = $findMC;
            $allRaw = mysqli_query($db_conn, "SELECT metric_convert.id_metric2, metric.name FROM metric_convert 
            JOIN metric on metric.id=metric_convert.id_metric2 WHERE metric_convert.id_metric1='$id'");
            $all_raw = mysqli_fetch_all($allRaw, MYSQLI_ASSOC);
            $arr1 = array();
            $iRecipe = 0;
            foreach($all_raw as $key){
                $arr1[$iRecipe]['id']= $key['id_metric2'];
                $arr1[$iRecipe]['name'] = $key['name'];
                $iRecipe+=1;
            }
            $allRaw1 = mysqli_query($db_conn, "SELECT metric_convert.id_metric1, metric.name FROM metric_convert 
            JOIN metric on metric.id=metric_convert.id_metric1 WHERE metric_convert.id_metric2='$id'");
            $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
            foreach($all_raw1 as $key){
                $arr1[$iRecipe]['id']= $key['id_metric1'];
                $arr1[$iRecipe]['name'] = $key['name'];
                $iRecipe+=1;
            }
            $allRaw1 = mysqli_query($db_conn, "SELECT id,name FROM `metric` WHERE id='$id'");
            $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
            foreach($all_raw1 as $key){
                $arr1[$iRecipe]['id']= $key['id'];
                $arr1[$iRecipe]['name'] = $key['name'];
                $iRecipe+=1;
            }

            $res[$i]['recipe'][$j]['relevant_metrics']= $arr1;
            
        $firstLoop = false;
          }else{
            if($res[$i]['menuID']==$value['id_menu']){
              $j+=1;
              $res[$i]['recipe'][$j]['ID']=$value['id'];
              $res[$i]['recipe'][$j]['raw_material']['value']=$value['id_raw'];
              $res[$i]['recipe'][$j]['raw_material']['label']=$value['rawName'];
              $res[$i]['recipe'][$j]['raw_material']['id_metric']=$value['id_metric'];
              $res[$i]['recipe'][$j]['raw_material']['metricName']=$value['metricName'];
              $res[$i]['recipe'][$j]['raw_material']['qty']=$value['qty'];
              $res[$i]['recipe'][$j]['raw_material']['id_raw']=$value['id_raw'];
            
              $findMC = $value['id_metric'];
  
              $id = $findMC;
              $allRaw = mysqli_query($db_conn, "SELECT metric_convert.id_metric2, metric.name FROM metric_convert 
              JOIN metric on metric.id=metric_convert.id_metric2 WHERE metric_convert.id_metric1='$id'");
              $all_raw = mysqli_fetch_all($allRaw, MYSQLI_ASSOC);
              $arr1 = array();
              $iRecipe = 0;
              foreach($all_raw as $key){
                  $arr1[$iRecipe]['id']= $key['id_metric2'];
                  $arr1[$iRecipe]['name'] = $key['name'];
                  $iRecipe+=1;
              }
              $allRaw1 = mysqli_query($db_conn, "SELECT metric_convert.id_metric1, metric.name FROM metric_convert 
              JOIN metric on metric.id=metric_convert.id_metric1 WHERE metric_convert.id_metric2='$id'");
              $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
              foreach($all_raw1 as $key){
                  $arr1[$iRecipe]['id']= $key['id_metric1'];
                  $arr1[$iRecipe]['name'] = $key['name'];
                  $iRecipe+=1;
              }
              $allRaw1 = mysqli_query($db_conn, "SELECT id,name FROM `metric` WHERE id='$id'");
              $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
              foreach($all_raw1 as $key){
                  $arr1[$iRecipe]['id']= $key['id'];
                  $arr1[$iRecipe]['name'] = $key['name'];
                  $iRecipe+=1;
              }
  
              $res[$i]['recipe'][$j]['relevant_metrics']= $arr1;
            }else{
              $j=0;
              $i+=1;
              $res[$i]['menuName']=$value['menuName'];
              $res[$i]['menuImage']=$value['menuImage'];
              $res[$i]['id_menu']=$value['id_menu'];
              $res[$i]['menuID']=$value['id_menu'];
              $res[$i]['recipe'][$j]['ID']=$value['id'];
              $res[$i]['recipe'][$j]['raw_material']['value']=$value['id_raw'];
              $res[$i]['recipe'][$j]['raw_material']['label']=$value['rawName'];
              $res[$i]['recipe'][$j]['raw_material']['id_metric']=$value['id_metric'];
              $res[$i]['recipe'][$j]['raw_material']['metricName']=$value['metricName'];
              $res[$i]['recipe'][$j]['raw_material']['qty']=$value['qty'];
              $res[$i]['recipe'][$j]['raw_material']['id_raw']=$value['id_raw'];
            
              $findMC = $value['id_metric'];
  
              $id = $findMC;
              $allRaw = mysqli_query($db_conn, "SELECT metric_convert.id_metric2, metric.name FROM metric_convert 
              JOIN metric on metric.id=metric_convert.id_metric2 WHERE metric_convert.id_metric1='$id'");
              $all_raw = mysqli_fetch_all($allRaw, MYSQLI_ASSOC);
              $arr1 = array();
              $iRecipe = 0;
              foreach($all_raw as $key){
                  $arr1[$iRecipe]['id']= $key['id_metric2'];
                  $arr1[$iRecipe]['name'] = $key['name'];
                  $iRecipe+=1;
              }
              $allRaw1 = mysqli_query($db_conn, "SELECT metric_convert.id_metric1, metric.name FROM metric_convert 
              JOIN metric on metric.id=metric_convert.id_metric1 WHERE metric_convert.id_metric2='$id'");
              $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
              foreach($all_raw1 as $key){
                  $arr1[$iRecipe]['id']= $key['id_metric1'];
                  $arr1[$iRecipe]['name'] = $key['name'];
                  $iRecipe+=1;
              }
              $allRaw1 = mysqli_query($db_conn, "SELECT id,name FROM `metric` WHERE id='$id'");
              $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
              foreach($all_raw1 as $key){
                  $arr1[$iRecipe]['id']= $key['id'];
                  $arr1[$iRecipe]['name'] = $key['name'];
                  $iRecipe+=1;
              }
  
              $res[$i]['recipe'][$j]['relevant_metrics']= $arr1;
            }
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
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "recipes"=>$res]);
?>