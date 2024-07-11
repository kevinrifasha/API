<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
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
    $partnerID = $token->id_partner;
    $obj = json_decode(json_encode($_POST));
    if(
        isset($obj->inTime) && !empty($obj->inTime) && isset($obj->employeeID) && !empty($obj->employeeID)
        // && isset($obj->url) && !empty($obj->url)
    ){
        $schedule_in_time = "";
        $schedule_out_time = "";
        $scheduleQ = mysqli_query($db_conn, "SELECT ap.monday_in, ap.monday_out, ap.tuesday_in, ap.tuesday_out, ap.wednesday_in, ap.wednesday_out, ap.thursday_in, ap.thursday_out, ap.friday_in, ap.friday_out, ap.saturday_in, ap.saturday_out, ap.sunday_in, ap.sunday_out FROM employees e LEFT JOIN attendance_patterns ap ON ap.id=e.pattern_id WHERE e.id='$obj->employeeID'");
        if(mysqli_num_rows($scheduleQ)>0){
            $res = mysqli_fetch_assoc($scheduleQ);
            $date = strtotime('now');
            $day = date("l", $date);
            if($day=="Monday"){
                $schedule_in_time = $res['monday_in'];
                $schedule_out_time = $res['monday_out'];
            }else if($day=="Tuesday"){
                $schedule_in_time = $res['tuesday_in'];
                $schedule_out_time = $res['tuesday_out'];
            }else if($day=="Wednesday"){
                $schedule_in_time = $res['wednesday_in'];
                $schedule_out_time = $res['wednesday_out'];
            }else if($day=="Thursday"){
                $schedule_in_time = $res['thursday_in'];
                $schedule_out_time = $res['thursday_out'];
            }else if($day=="Friday"){
                $schedule_in_time = $res['friday_in'];
                $schedule_out_time = $res['friday_out'];
            }else if($day=="Saturday"){
                $schedule_in_time = $res['saturday_in'];
                $schedule_out_time = $res['saturday_out'];
            }else if($day=="Sunday"){
                $schedule_in_time = $res['sunday_in'];
                $schedule_out_time = $res['sunday_out'];
            }
        }
        if($schedule_in_time!="" && $schedule_out_time!=""){
            $q = mysqli_query($db_conn, "INSERT INTO `attendance`(`employee_id`, `in_time`, `in_image`, `schedule_in_time`, `schedule_out_time`, `partner_id`) VALUES ('$obj->employeeID',NOW(), '$obj->url', '$schedule_in_time', '$schedule_out_time', '$partnerID')");
        }else if($schedule_in_time!="" && $schedule_out_time==""){
            $q = mysqli_query($db_conn, "INSERT INTO `attendance`(`employee_id`, `in_time`, `in_image`, `schedule_in_time`, `partner_id`) VALUES ('$obj->employeeID',NOW(), '$obj->url', '$schedule_in_time', '$partnerID')");
        }else if($schedule_in_time=="" && $schedule_out_time!=""){
            $q = mysqli_query($db_conn, "INSERT INTO `attendance`(`employee_id`, `in_time`, `in_image`, `schedule_out_time`,`partner_id`) VALUES ('$obj->employeeID',NOW(), '$obj->url', '$schedule_out_time','$partnerID')");
        }else{
            $q = mysqli_query($db_conn, "INSERT INTO `attendance`(`employee_id`, `in_time`, `in_image`, `partner_id`) VALUES ('$obj->employeeID',NOW(), '$obj->url', '$partnerID')");
        }
            if ($q) {
                $success =1;
                $status =200;
                $msg = "Success";
            } else {
                $success =0;
                $status =204;
                $msg = "Insert Failed";
            }

    // } else {
    //     $success =0;
    //     $status =204;
    //     $msg = "Anda Belum Memiliki Pola Kerja";
    // }
    }else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>
