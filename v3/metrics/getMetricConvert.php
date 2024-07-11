<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require_once("./../metricModels/metricManager.php");
require_once("./../metricConvertModels/metricConvertManager.php");

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
$token = '';
$res = array();

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$metricManager = new MetricManager($db);
$metricConvertManager = new MetricConvertManager($db);
// $tokens = $tokenizer->validate($token);

// $success=0;
// $msg = 'Failed';
$metricId = $_GET['metricId'];

// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
//     $status = $tokens['status'];
//     $msg = $tokens['msg'];

// }else{

    if(isset($metricId) && !empty($metricId)){

        $ids = array();
        $metrics = $metricConvertManager->getByMetricsConvert($metricId);
        if($metrics!=false){

            foreach($metrics as $metric){
                $add1 = true;
                $add2 = true;
                $data = $metric->getDetails();
                foreach($ids as $id){
                    if($id===$data['id_metric1']){
                        $add1 = false;
                    }
                    if($id===$data['id_metric2']){
                        $add2 = false;
                    }
                }
                if($add1==true){
                    array_push($ids, $data['id_metric1']);
                }
                if($add2==true){
                    array_push($ids, $data['id_metric2']);
                }
            }

            $res = array();
            foreach($ids as $id){
                $metrics = $metricManager->getById($id);
                if($metrics!=false){
                    array_push($res,$metrics->getDetails());
                }
            }

            if(count($res)>0){
                $status = 200;
                $success = 1;
                $msg = "Success";
            }else{
                $status = 204;
                $success = 0;
                $msg = "Failed";
            }
            
        }else{

            $status = 400;
            $success = 0;
            $msg = "Data Not Registered";
        
        }

    }else{

        $status = 400;
        $success = 0;
        $msg = "Missing Require Field";
    
    }

// }

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "metrics"=>$res]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;

?>