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
} else {
    $partner_id = $token->id_partner;
    $attendance = mysqli_query($db_conn, "SELECT a.id, a.employee_id, a.in_time, a.out_time, a.in_image, a.out_image, k.nama as name, DATE(a.in_time) as date FROM attendance a JOIN employees k ON k.id=a.employee_id WHERE k.id_partner='$partner_id' AND DATE(a.in_time) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY DATE(a.in_time) DESC ");
    
    if(mysqli_num_rows($attendance) > 0) {
        $all_attendance = mysqli_fetch_all($attendance, MYSQLI_ASSOC);
        $res = array();
        $i = 0;
        $j = 0;
        $firts = true;
        foreach ($all_attendance as  $value) {
            if($firts==true){
                $firts = false;
                $res[$i]['date']=$value['date'];
                $res[$i]['employee_count']=1;
                $res[$i]['detail'][$j]=$value;
            }else{
                if($res[$i]['detail'][$j]['date']==$value['date']){
                    $j+=1;
                    $res[$i]['detail'][$j]=$value;
                    $res[$i]['employee_count']+=1;
                }else{
                    $i+=1;
                    $j=0;
                    $res[$i]['employee_count']=1;
                    $res[$i]['date']=$value['date'];
                    $res[$i]['detail'][$j]=$value;
                }
            }
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }
    
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "attendance"=>$res]);  
// Echo the message.
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
?>