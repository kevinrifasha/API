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
$res1 = array();

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
    $q = mysqli_query($db_conn,"SELECT r.id, r.id_menu, r.id_raw, r.qty, r.id_metric, r.id_variant, m.nama AS menuName, m.img_data AS menuImage,rw.name AS rawName, me.name AS metricName FROM recipe r JOIN menu m ON m.id=r.id_menu JOIN raw_material rw ON rw.id=r.id_raw AND rw.deleted_at IS NULL JOIN metric me ON me.id=r.id_metric AND m.is_recipe='1' WHERE m.id_partner='{$token->id_partner}' ORDER BY r.id_menu");
      //aku ganti disini nambahin AND deleted_at
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
              $i = 0;
              $j = 0;
              $firstLoop = true;
            foreach ($q as $value) {
                if($firstLoop==true){
                  $res[$i]['menuName']=$value['menuName'];
                  $res[$i]['menuImage']=$value['menuImage'];
                  $res[$i]['menuID']=$value['id_menu'];
                  $res[$i]['recipe'][$j]['ID']=$value['id'];
                  $res[$i]['recipe'][$j]['raw_material']['value']=$value['id_raw'];
                  $res[$i]['recipe'][$j]['raw_material']['label']=$value['rawName'];
                  $res[$i]['recipe'][$j]['raw_material']['id_metric']=$value['id_metric'];
                  $res[$i]['recipe'][$j]['raw_material']['metricName']=$value['metricName'];
                  $res[$i]['recipe'][$j]['raw_material']['qty']=$value['qty'];
                  $res[$i]['recipe'][$j]['raw_material']['id_raw']=$value['id_raw'];
                  $firstLoop=false;
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
                  }else{
                    $j=0;
                    $i+=1;
                    $res[$i]['menuName']=$value['menuName'];
                    $res[$i]['menuImage']=$value['menuImage'];
                    $res[$i]['menuID']=$value['id_menu'];
                    $res[$i]['recipe'][$j]['ID']=$value['id'];
                    $res[$i]['recipe'][$j]['raw_material']['value']=$value['id_raw'];
                    $res[$i]['recipe'][$j]['raw_material']['label']=$value['rawName'];
                    $res[$i]['recipe'][$j]['raw_material']['id_metric']=$value['id_metric'];
                    $res[$i]['recipe'][$j]['raw_material']['metricName']=$value['metricName'];
                    $res[$i]['recipe'][$j]['raw_material']['qty']=$value['qty'];
                    $res[$i]['recipe'][$j]['raw_material']['id_raw']=$value['id_raw'];
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
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "menus"=>$res]);
?>