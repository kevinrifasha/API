<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
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
    $data = json_decode(file_get_contents('php://input'));
    $tableCount = count($data->meja_id);
    $isError = false;
    
    function checkIfOverlapped($ranges) {
    
        $overlapp = [];
        
        for($i = 0; $i < count($ranges); $i++){
            
            for($j= ($i + 1); $j < count($ranges); $j++){
    
                $start_a = strtotime($ranges[$i]['validFrom']);
                $end_a = strtotime($ranges[$i]['validTo']);
    
                $start_b = strtotime($ranges[$j]['validFrom']);
                $end_b = strtotime($ranges[$j]['validTo']);
    
                if( $start_b <= $end_a && $end_b >= $start_a ) {
                    $overlapp[] = "i:$i j:$j " .$ranges[$i]['validFrom'] ." - " .$ranges[$i]['validTo'] ." overlap with " .$ranges[$j]['validFrom'] ." - " .$ranges[$j]['validTo'];
                    break;
                }
                
            }
            
        }
        
        return count($overlapp);
    }
    
    $isOverlapped = checkIfOverlapped(json_decode(json_encode($data->schedules), true));
    $countSchedules = count(json_decode(json_encode($data->schedules), true));

    if($isOverlapped > 0 && $countSchedules > 1) {
        $msg = "Rentang tanggal tidak boleh ada yang bertabrakan!";
        $success = 0;
        $status=201;
    } elseif (
        isset($data->name) && !empty($data->name) && $tableCount > 0
    ){
      foreach($data->meja_id as $value) {
          $insert = mysqli_query($db_conn,"INSERT INTO `for_reservation`(`partner_id`, `meja_id`, `table_group_id`, `name`, `description`, `capacity`, `minimum_transaction`, `booking_price`, `duration`, `duration_metric`, `image`, `created_at`) VALUES ('$data->partner_id', '$value', '$data->table_group_id', '$data->name', '$data->description', '$data->capacity', '$data->minimum_transaction', '$data->booking_price', '$data->duration', '$data->duration_metric', '$data->image', NOW())");
          if($insert) {
            if(count($data->schedules) > 0) {
              $idInsert = mysqli_insert_id($db_conn);
              $i = 0;
              $query = "INSERT INTO `booking_table_price`(`for_reservation_id`, `price`, `date_from`, `date_to`) VALUES";

              foreach($data->schedules as $value){
                $query .= " ('$idInsert', '$value->minTransaction', '$value->validFrom', '$value->validTo')";
                if(count($data->schedules)-1==$i){
                  $query .= ";";
                }else{
                  $query .= ",";
                }
                $i +=1;
              }

              $insert = mysqli_query($db_conn,$query);

              if(!$insert) {
                $isError = true;
              }
            }
          } else {
              $isError = true;
          }
      }
        if($isError == false){
          $msg = "Berhasil tambah data";
          $success = 1;
          $status=200;
        }else{
          $msg = "Gagal tambah data";
          $success = 0;
          $status=204;
        }
    } else {
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;  
    }

}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
    http_response_code(200);
  }else{
    http_response_code($status);
  }   
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]); 
    
?>
     
