<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
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

    // POST DATA
    $data = json_decode(file_get_contents('php://input'));

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
    } elseif(
        isset($data->id) && !empty($data->id)
        && isset($data->name) && !empty($data->name)
    ){
        if(empty($data->table_group_id)){
            $data->table_group_id=0;
          }
                $update = mysqli_query($db_conn,"UPDATE `for_reservation` SET `partner_id`='$data->partner_id',`meja_id`='$data->table_id',`table_group_id`='$data->table_group_id',`name`='$data->name',`description`='$data->description',`capacity`='$data->capacity',`minimum_transaction`='$data->minimum_transaction',`booking_price`='$data->booking_price',`duration`='$data->duration',`duration_metric`='$data->duration_metric', `image`='$data->image', `updated_at`=NOW() WHERE `id`='$data->id'");
                if($update){
                    $deleteExisting = mysqli_query($db_conn, "DELETE FROM booking_table_price WHERE for_reservation_id='$data->id'");
                    if($deleteExisting){
                        $query = "INSERT INTO `booking_table_price`(`for_reservation_id`, `price`, `date_from`, `date_to`) VALUES";
                        $i = 0;
                        foreach($data->schedules as $value){
                            $query .= " ('$data->id', '$value->minTransaction', '$value->validFrom', '$value->validTo')";
                            if(count($data->schedules)-1==$i){
                                $query .= ";";
                            }else{
                                $query .= ",";
                            }
                            $i +=1;
                        }
                        $insert = mysqli_query($db_conn,$query);
                }else{
                    $msg = "Berhasil menghapus data existing";
                    $success = 1;
                    $status=200;
                }
                    $msg = "Berhasil ubah data";
                    $success = 1;
                    $status=200;
                }else{
                    $msg = "Gagal ubah data";
                    $success = 0;
                    $status=204;
                }
    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;
    }

}


echo json_encode(["msg"=>$msg, "status"=>$status, "success"=>$success]);

 ?>
