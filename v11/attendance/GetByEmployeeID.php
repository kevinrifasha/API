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
    $employee_id = $_GET['employee_id'];
    if(!empty($employee_id)){
        $validate = mysqli_query($db_conn, "SELECT id FROM employees WHERE id='$employee_id' AND id_master='$token->id_master'");
        if(mysqli_num_rows($validate)>0){
                $q = mysqli_query($db_conn, "SELECT employees.id as karyawan_id, employees.nik,employees.nama, attendance.id,
            CASE WHEN attendance2.in_time IS NOT NULL AND attendance2.out_time IS NOT NULL THEN 'masuk'
            WHEN attendance2.in_time IS NOT NULL AND attendance2.out_time IS NULL THEN 'keluar' ELSE 'masuk' END as attendance_status
            FROM employees
            LEFT JOIN (select attendance.id as id, attendance.employee_id from attendance WHERE attendance.employee_id = '$employee_id' ORDER by attendance.id desc limit 1) as attendance on employees.id = attendance.employee_id
           	LEFT JOIN (select attendance.id, attendance.in_time, attendance.out_time from attendance) as attendance2 on attendance.id = attendance2.id
            WHERE employees.id = '$employee_id'");
            if (mysqli_num_rows($q) > 0) {
                $res = mysqli_fetch_assoc($q);
                $data = array(
                    "employee_id" => $res['karyawan_id'],
                    "nik" => $res['nik'],
                    "name" => $res['nama'],
                    "status" => $res['attendance_status'],
                );
                $success =1;
                $status =200;
                $msg = "Success";
            } else {
                $success =0;
                $status =204;
                $msg = "Data Not Found";
            }
        }else{
            $success =0;
            $status =204;
            $msg = "Data tidak ditemukan";
        }
        
    }else{
        $success =0;
        $status =400;
        $msg = "Missing Required Field";
    }

}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "data"=>$data]);