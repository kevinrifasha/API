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
$value = array();
$success=0;
$msg = 'Failed';

$partner_id = $_GET['partner_id'];
$dateFrom = $_GET['dateFrom'];
$dateTo = $_GET['dateTo'];
$all = $_GET['all'];

$newDateFormat = 0;

if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
    $dateTo = str_replace("%20"," ",$dateTo);
    $dateFrom = str_replace("%20"," ",$dateFrom);
    $newDateFormat = 1;
}

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
  if($newDateFormat == 1){
    if(isset($_GET['all']) && $all == "1"){
        $getMaster = mysqli_query($db_conn, "SELECT id_master FROM partner WHERE id='$partner_id'");
        $fetch = mysqli_fetch_all($getMaster, MYSQLI_ASSOC);
        $master_id = $fetch[0]["id_master"];
        
        $employees = mysqli_query($db_conn, "SELECT k.id, k.nama AS name FROM employees k LEFT JOIN partner p ON p.id='$partner_id' WHERE k.deleted_at IS NULL AND p.id_master='$master_id'");
    }else{
        $employees = mysqli_query($db_conn, "SELECT k.id, k.nama AS name FROM employees k WHERE k.deleted_at IS NULL AND k.id_partner='$partner_id'");
    }

    if(mysqli_num_rows($employees)>0){
        $inLate = 0;
        $outEarly = 0;
        $res = array();
        $all_employees = mysqli_fetch_all($employees, MYSQLI_ASSOC);
        $j=0;
        $n = 0;
        foreach($all_employees as $x){
            $kID = $x['id'];
                $attendance = mysqli_query($db_conn, "SELECT a.id, a.employee_id, a.in_time, a.out_time, a.in_image, a.out_image, k.nama as name, a.in_time as date,HOUR(a.in_time) as in_hour, MINUTE(a.in_time) as in_minute, SECOND(a.in_time) as in_second, HOUR(a.out_time) as out_hour, MINUTE(a.out_time) as out_minute, SECOND(a.out_time) as out_second, a.schedule_in_time, a.schedule_out_time, p.name as partner_name, o.name as partner_out,
    CASE
        WHEN out_time IS NOT NULL THEN CONCAT(
                  SUBSTRING_INDEX(TIMEDIFF(out_time, in_time), ':', 1), 
                  ' jam ', 
                  SUBSTR(TIMEDIFF(out_time, in_time), INSTR(TIMEDIFF(out_time, in_time), ':')+1, 2),  
                  ' menit')
        ELSE 'Belum Selesai'
    END As duration
    FROM attendance a JOIN employees k ON k.id=a.employee_id LEFT JOIN partner p ON (p.id = a.partner_id) LEFT JOIN partner o ON (o.id = a.partner_out) WHERE a.employee_id='$kID' AND a.in_time BETWEEN '$dateFrom' AND '$dateTo' ORDER BY a.in_time DESC");
     if(mysqli_num_rows($attendance) > 0) {
            $all_attendance = mysqli_fetch_all($attendance, MYSQLI_ASSOC);
            $j = 0;
            $first = true;
            foreach ($all_attendance as  $value) {
                if($value['in_hour']<10){
                    $value['in_hour']="0".$value['in_hour'];
                }
                if($value['in_minute']<10){
                    $value['in_minute']="0".$value['in_minute'];
                }
                if($value['in_second']<10){
                    $value['in_second']="0".$value['in_second'];
                }
                $in_time = $value['in_hour'].":".$value['in_minute'].":".$value['in_second'];
                $in_time = strtotime($in_time);
                $s_in_time = strtotime($value['schedule_in_time']);
                $status_a = "Tepat Waktu";
                if($in_time>$s_in_time){
                    $status_a = "Terlambat";
                    $inLate++;
                }
                $value['in_status']=$status_a;
                
                $status_a="Belum Keluar";
                if(!empty($value['out_time'])){
                    if($value['out_hour']<10){
                        $value['out_hour']="0".$value['out_hour'];
                    }
                    if($value['out_minute']<10){
                        $value['out_minute']="0".$value['out_minute'];
                    }
                    if($value['out_second']<10){
                        $value['out_second']="0".$value['out_second'];
                    }
                    $out_time = $value['out_hour'].":".$value['out_minute'].":".$value['out_second'];
                    $out_time = strtotime($out_time);
                    $s_out_time = strtotime($value['schedule_out_time']);
                    $status_a = "Tepat Waktu";
                    if($out_time<$s_out_time){
                        $status_a = "Pulang Cepat";
                        $outEarly++;
                    }
                }
                $value['out_status']=$status_a;
                if($first==true){
                    $first = false;
                    $res[$n]['eID'] = $value['employee_id'];
                    $res[$n]['name'] = $value['name'];
                    $res[$n]['detail'][$j]=$value;
                    $res[$n]['inLate']=$inLate;
                    $res[$n]['outEarly']=$outEarly;
                }else{
                    if($res[$n]['detail'][$j]['employee_id']==$value['employee_id']){
                        $j+=1;
                        $res[$n]['eID'] = $value['employee_id'];
                        $res[$n]['name'] = $value['name'];
                        $res[$n]['detail'][$j]=$value;
                        $res[$n]['inLate']=$inLate;
                        $res[$n]['outEarly']=$outEarly;
                        
                    }else{
                        $j=0;
                        $res[$n]['eID'] = $value['employee_id'];
                        $res[$n]['name'] = $value['name'];
                        $res[$n]['detail'][$j]=$value;
                        $res[$n]['inLate']=$inLate;
                        $res[$n]['outEarly']=$outEarly;
                        
                    }
                }
            }
            $inLate=0;
            $outEarly=0;
            $n++;
        }
  
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 203;
        $msg = "Data Not Found";
    }
  } 
  else 
  {
    $employees = mysqli_query($db_conn, "SELECT k.id, k.nama AS name FROM employees k WHERE k.deleted_at IS NULL AND k.id_partner='$partner_id'");
    if(mysqli_num_rows($employees)>0){
        $inLate = 0;
        $outEarly = 0;
        $res = array();
        $all_employees = mysqli_fetch_all($employees, MYSQLI_ASSOC);
        $j=0;
        $n = 0;
        foreach($all_employees as $x){
            $kID = $x['id'];
                $attendance = mysqli_query($db_conn, "SELECT a.id, a.employee_id, a.in_time, a.out_time, a.in_image, a.out_image, k.nama as name, DATE(a.in_time) as date,HOUR(a.in_time) as in_hour, MINUTE(a.in_time) as in_minute, SECOND(a.in_time) as in_second, HOUR(a.out_time) as out_hour, MINUTE(a.out_time) as out_minute, SECOND(a.out_time) as out_second, a.schedule_in_time, a.schedule_out_time,
    CASE
        WHEN out_time IS NOT NULL THEN CONCAT(
                  SUBSTRING_INDEX(TIMEDIFF(out_time, in_time), ':', 1), 
                  ' jam ', 
                  SUBSTR(TIMEDIFF(out_time, in_time), INSTR(TIMEDIFF(out_time, in_time), ':')+1, 2),  
                  ' menit')
        ELSE 'Belum Selesai'
    END As duration
    FROM attendance a JOIN employees k ON k.id=a.employee_id WHERE a.employee_id='$kID' AND DATE(a.in_time) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY DATE(a.in_time) DESC");
     if(mysqli_num_rows($attendance) > 0) {
            $all_attendance = mysqli_fetch_all($attendance, MYSQLI_ASSOC);
            $j = 0;
            $first = true;
            foreach ($all_attendance as  $value) {
                if($value['in_hour']<10){
                    $value['in_hour']="0".$value['in_hour'];
                }
                if($value['in_minute']<10){
                    $value['in_minute']="0".$value['in_minute'];
                }
                if($value['in_second']<10){
                    $value['in_second']="0".$value['in_second'];
                }
                $in_time = $value['in_hour'].":".$value['in_minute'].":".$value['in_second'];
                $in_time = strtotime($in_time);
                $s_in_time = strtotime($value['schedule_in_time']);
                $status_a = "Tepat Waktu";
                if($in_time>$s_in_time){
                    $status_a = "Terlambat";
                    $inLate++;
                }
                $value['in_status']=$status_a;
                
                $status_a="Belum Keluar";
                if(!empty($value['out_time'])){
                    if($value['out_hour']<10){
                        $value['out_hour']="0".$value['out_hour'];
                    }
                    if($value['out_minute']<10){
                        $value['out_minute']="0".$value['out_minute'];
                    }
                    if($value['out_second']<10){
                        $value['out_second']="0".$value['out_second'];
                    }
                    $out_time = $value['out_hour'].":".$value['out_minute'].":".$value['out_second'];
                    $out_time = strtotime($out_time);
                    $s_out_time = strtotime($value['schedule_out_time']);
                    $status_a = "Tepat Waktu";
                    if($out_time<$s_out_time){
                        $status_a = "Pulang Cepat";
                        $outEarly++;
                    }
                }
                    $value['out_status']=$status_a;
                if($first==true){
                    $first = false;
                    $res[$n]['eID'] = $value['employee_id'];
                    $res[$n]['name'] = $value['name'];
                    $res[$n]['detail'][$j]=$value;
                    $res[$n]['inLate']=$inLate;
                    $res[$n]['outEarly']=$outEarly;
                }else{
                    if($res[$n]['detail'][$j]['employee_id']==$value['employee_id']){
                        $j+=1;
                        $res[$n]['eID'] = $value['employee_id'];
                        $res[$n]['name'] = $value['name'];
                        $res[$n]['detail'][$j]=$value;
                        $res[$n]['inLate']=$inLate;
                        $res[$n]['outEarly']=$outEarly;
                        
                    }else{
                        $j=0;
                        $res[$n]['eID'] = $value['employee_id'];
                        $res[$n]['name'] = $value['name'];
                        $res[$n]['detail'][$j]=$value;
                        $res[$n]['inLate']=$inLate;
                        $res[$n]['outEarly']=$outEarly;
                        
                    }
                }
            }
            $inLate=0;
            $outEarly=0;
            $n++;
        }
     
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 203;
        $msg = "Data Not Found";
    }
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