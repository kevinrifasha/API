<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';


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
    
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{

    // POST DATA
    $json = file_get_contents('php://input');
    $data = json_decode($json,true);
    if( 
        ( 
            (isset($data['menu_id']) && !empty($data['menu_id']))
            ||
            (isset($data['raw_material_id']) && !empty($data['raw_material_id']))
        ) 
        && isset($data['metric_id'])
        && isset($data['id'])
        && isset($data['qty'])
        && isset($data['notes'])
        && !empty($data['metric_id'])
        && !empty($data['id'])
        && !empty($data['qty'])
        && !empty($data['notes'])
        ){
        
            $raw_material_id=0;
            if(isset($data['raw_material_id']) && !empty($data['raw_material_id'])){
                $raw_material_id = $data['raw_material_id'];
            }
            $menu_id=0;
            if(isset($data['menu_id']) && !empty($data['menu_id'])){
                $menu_id = $data['menu_id'];
            }
            $metric_id = $data['metric_id'];
            $qty = $data['qty'];
            $notes = $data['notes'];
            $id = $data['id'];

            $insert = mysqli_query($db_conn,"UPDATE `stock_changes` SET `raw_material_id`='$raw_material_id',`menu_id`='$menu_id',`metric_id`='$metric_id',`qty`='$qty',`notes`='$notes' WHERE id='$id'");
            
            if($insert){
                $success =1;
                $status =200;
                $msg = "Success";
            }else{
                $success =0;
                $status =204;
                $msg = "Failed";
            }
    }else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>