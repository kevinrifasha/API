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
$idMaster = $token->id_master;

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
} else {
    $partner_id = $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $partner_id = $_GET['partnerID']; 
    }
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    
    if($all == "1") {
        $qAttendance = "SELECT a.id, a.employee_id, a.in_time, a.out_time, a.in_image, a.out_image, k.nama as name, DATE(a.in_time) as date,HOUR(a.in_time) as in_hour, MINUTE(a.in_time) as in_minute, SECOND(a.in_time) as in_second, HOUR(a.out_time) as out_hour, MINUTE(a.out_time) as out_minute, SECOND(a.out_time) as out_second, a.schedule_in_time, a.schedule_out_time, CASE WHEN out_time IS NOT NULL THEN CONCAT(SUBSTRING_INDEX(TIMEDIFF(out_time, in_time), ':', 1), ' jam ', SUBSTR(TIMEDIFF(out_time, in_time), INSTR(TIMEDIFF(out_time, in_time), ':')+1, 2), ' menit') ELSE 'Belum Selesai' END As duration, p.name as partner_in, o.name as partner_out FROM attendance a JOIN employees k ON k.id=a.employee_id LEFT JOIN partner p ON (p.id = a.partner_id) LEFT JOIN partner o ON (o.id = a.partner_out) WHERE p.id_master='$idMaster' AND DATE(a.in_time) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY DATE(a.in_time) DESC";
    } else {
        $qAttendance = "SELECT a.id, a.employee_id, a.in_time, a.out_time, a.in_image, a.out_image, k.nama as name, DATE(a.in_time) as date,HOUR(a.in_time) as in_hour, MINUTE(a.in_time) as in_minute, SECOND(a.in_time) as in_second, HOUR(a.out_time) as out_hour, MINUTE(a.out_time) as out_minute, SECOND(a.out_time) as out_second, a.schedule_in_time, a.schedule_out_time, CASE WHEN out_time IS NOT NULL THEN CONCAT(SUBSTRING_INDEX(TIMEDIFF(out_time, in_time), ':', 1), ' jam ', SUBSTR(TIMEDIFF(out_time, in_time), INSTR(TIMEDIFF(out_time, in_time), ':')+1, 2), ' menit') ELSE 'Belum Selesai' END As duration, p.name as partner_in, o.name as partner_out FROM attendance a JOIN employees k ON k.id=a.employee_id LEFT JOIN partner p ON (p.id = a.partner_id) LEFT JOIN partner o ON (o.id = a.partner_out) WHERE (k.id_partner='$partner_id' OR a.partner_id='$partner_id' OR a.partner_out='$partner_id') AND DATE(a.in_time) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY DATE(a.in_time) DESC";
    }
    
    $attendance = mysqli_query($db_conn, $qAttendance);
    
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
        $status = 200;
        $msg = "Data Not Found";
    }
    
}
$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "attendance"=>$res]);  

echo $signupJson;
?>