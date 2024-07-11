<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
// require_once("../connection.php");
require_once('../auth/Token.php');
require_once '../../includes/CalculateFunctions.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$cf = new CalculateFunction();
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

$tokenizer = new Token();
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
$values = [];
$tot = [];
$arrayDone = array();
$response = array();
$idMaster = $token->id_master;

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    
    $getPartner = mysqli_query($db_conn,"SELECT id, name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL" );
    
    if(mysqli_num_rows($getPartner) > 0){

        $getPartner = mysqli_fetch_all($getPartner, MYSQLI_ASSOC);
    
        $j = 0;
        foreach($getPartner as $val){
                    
            $id = $val["id"];
            $name = $val["name"];
            
            $values1 = $cf->getByHour($id, $dateFrom, $dateTo);
            $i = 0;
            foreach ($values1 as $value) {
                $values[$i]['label']=$value['hour'];
                $values[$i]['value']=$value['sales'];
                $i+=1;
            }
            
            $arrayDone["id_partner"] = $id;
            $arrayDone["name"] = $name;
            $arrayDone["transactions"] = $values;
        
            $response[$j] = $arrayDone;
        
            $j++;
            
            $arrayDone = array();
            $values = array();
            
        }
        
        $success=1;
        $status=200;
        $msg="Success";
    } else {
        $success=0;
        $status=204;
        $msg="Data Tidak Ditemukan";
    }
    
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$response]);
