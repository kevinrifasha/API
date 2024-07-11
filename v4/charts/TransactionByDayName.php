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
$tot = [];
$array = array();
$array1 = array();
$idMaster = $token->id_master;

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $id = $token->id_partner;
    if(isset($_GET['id'])){
        $id = $_GET['id'];
    }
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    $all = 0;
    
    if(isset($all)){
        $all = $_GET['all'];
    }
    
    if($all == "1" || $all == 1){
        $getPartner = mysqli_query($db_conn,"SELECT id, name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL" );
    
        if(mysqli_num_rows($getPartner) > 0){
    
            $getPartner = mysqli_fetch_all($getPartner, MYSQLI_ASSOC);
                
            for ($i=0; $i < 7; $i++) {
                $array[$i]['value']=0;
                if($i==0){
                    $array[$i]['label']="Senin";
                }else if($i==1){
                    $array[$i]['label']="Selasa";
                }else if($i==2){
                    $array[$i]['label']="Rabu";
                }else if($i==3){
                    $array[$i]['label']="Kamis";
                }else if($i==4){
                    $array[$i]['label']="Jumat";
                }else if($i==5){
                    $array[$i]['label']="Sabtu";
                }else if($i==6){
                    $array[$i]['label']="Minggu";
                }
            }
            
            foreach($getPartner as $val){
                $id_partner = $val["id"];
                
                $value = $cf->getByDay($id_partner, $dateFrom, $dateTo);
                foreach ($value as $val) {
                    if($val['day']=="Monday"){
                        $array[0]['value']+=$val['sales'];
                    }else if($val['day']=="Tuesday"){
                        $array[1]['value']+=$val['sales'];
                    }else if($val['day']=="Wednesday"){
                        $array[2]['value']+=$val['sales'];
                    }else if($val['day']=="Thursday"){
                        $array[3]['value']+=$val['sales'];
                    }else if($val['day']=="Friday"){
                        $array[4]['value']+=$val['sales'];
                    }else if($val['day']=="Saturday"){
                        $array[5]['value']+=$val['sales'];
                    }else if($val['day']=="Sunday"){
                        $array[6]['value']+=$val['sales'];
                    }
                }
                
            }
        
            
        }
    }else {
        $value = $cf->getByDay($id, $dateFrom, $dateTo);
        for ($i=0; $i < 7; $i++) {
            $array[$i]['value']=0;
            if($i==0){
                $array[$i]['label']="Senin";
            }else if($i==1){
                $array[$i]['label']="Selasa";
            }else if($i==2){
                $array[$i]['label']="Rabu";
            }else if($i==3){
                $array[$i]['label']="Kamis";
            }else if($i==4){
                $array[$i]['label']="Jumat";
            }else if($i==5){
                $array[$i]['label']="Sabtu";
            }else if($i==6){
                $array[$i]['label']="Minggu";
            }
        }
        foreach ($value as $val) {
            if($val['day']=="Monday"){
                $array[0]['value']=$val['sales'];
            }else if($val['day']=="Tuesday"){
                $array[1]['value']=$val['sales'];
            }else if($val['day']=="Wednesday"){
                $array[2]['value']=$val['sales'];
            }else if($val['day']=="Thursday"){
                $array[3]['value']=$val['sales'];
            }else if($val['day']=="Friday"){
                $array[4]['value']=$val['sales'];
            }else if($val['day']=="Saturday"){
                $array[5]['value']=$val['sales'];
            }else if($val['day']=="Sunday"){
                $array[6]['value']=$val['sales'];
            }
        }
    }
    
    $success=1;
    $status=200;
    $msg="Success";
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$array, "test"=>$test]);
