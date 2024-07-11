<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
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
    $obj = json_decode(file_get_contents('php://input'));
    if(
        !empty($obj->area)
        && !empty($obj->target)
        && !empty($obj->date)
        && !empty($obj->visitPlan)
    ){
        $dateValidator = date('Y-m-d', strtotime($obj->date));
        $sqlValidate="SELECT id FROM sa_work_plans WHERE sa_work_plans.date='$dateValidator' AND sales_id='$token->id' AND deleted_at IS NULL";
        $validate = mysqli_query($db_conn, $sqlValidate);
        if(mysqli_num_rows($validate)>0){
          $msg = "Sudah ada rencana kerja di tanggal yang sama";
          $success = 0;
          $status=204;
        }else{
          $qInsert = "INSERT INTO sa_work_plans SET sales_id='$token->id', area='$obj->area', target='$obj->target', date='$obj->date'";
          $insert = mysqli_query($db_conn,$qInsert);
          $iid = mysqli_insert_id($db_conn);
          if($insert){
            $i=0;
            $dataLength = 0;
              $sqlDetail = "INSERT INTO sa_work_plan_details (work_plan_id, name, address, hour) VALUES ";
              $dataLength = count($obj->visitPlan);
              foreach($obj->visitPlan as $plan){
                // $name = $plan->name;
                $name = mysqli_real_escape_string($db_conn, $plan->name);
                // $address = $plan->address;
                $address = mysqli_real_escape_string($db_conn, $plan->address);
                $hour = $plan->hour;
                $sqlDetail .=" ('$iid', '$name', '$address', '$hour')";
                if($dataLength-1==$i){
                  $sqlDetail .=";";
                }else{
                  $sqlDetail .=",";
                }
                $i++;
              }
              $insertDetail = mysqli_query($db_conn, $sqlDetail);
              if($insertDetail){
                $msg = "Berhasil tambah data";
                $success = 1;
                $status=200;
              }else{
                $msg = "Gagal tambah rencana kunjungan. Mohon coba lagi";
                $success = 0;
                $status=204;
              }

          }else{
              $msg = "Gagal tambah data";
              $success = 0;
              $status=204;
          }
        }

    }else{
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 204;
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);

?>