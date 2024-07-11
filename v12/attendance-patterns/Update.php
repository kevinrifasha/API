<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';

$headers = apache_request_headers();
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
    $obj = json_decode(file_get_contents('php://input'));
    if(!empty($obj->name) && !empty($obj->partnerID) ){
    if($obj->mondayIn==null){
        $monday="monday_in=null, monday_out=null";
    }else{
        $monday="monday_in='$obj->mondayIn', monday_out='$obj->mondayOut'";
    }
    if($obj->tuesdayIn==null){
        $tuesday="tuesday_in=null, tuesday_out=null";
    }else{
        $tuesday="tuesday_in='$obj->tuesdayIn', tuesday_out='$obj->tuesdayOut'";
    }
    if($obj->wednesdayIn==null){
        $wednesday="wednesday_in=null, wednesday_out=null";
    }else{
        $wednesday="wednesday_in='$obj->wednesdayIn', wednesday_out='$obj->wednesdayOut'";
    }
    if($obj->thursdayIn==null){
        $thursday="thursday_in=null, thursday_out=null";
    }else{
        $thursday="thursday_in='$obj->thursdayIn', thursday_out='$obj->thursdayOut'";
    }
    if($obj->fridayIn==null){
        $friday="friday_in=null, friday_out=null";
    }else{
        $friday="friday_in='$obj->fridayIn', friday_out='$obj->fridayOut'";
    }
    if($obj->saturdayIn==null){
        $saturday="saturday_in=null, saturday_out=null";
    }else{
        $saturday="saturday_in='$obj->saturdayIn', saturday_out='$obj->saturdayOut'";
    }
    if($obj->sundayIn==null){
        $sunday="sunday_in=null, sunday_out=null";
    }else{
        $sunday="sunday_in='$obj->sundayIn', sunday_out='$obj->sundayOut'";
    }
    $sql = mysqli_query($db_conn, "UPDATE `attendance_patterns` SET name='$obj->name', partner_id='$obj->partnerID', master_id='$tokenDecoded->masterID',".$monday.",".$tuesday.",".$wednesday.",".$thursday.",".$friday.",".$saturday.",".$sunday." WHERE id='$obj->id'");
   
    if($sql) {
        $success = 1;
        $status = 200;
        $msg = "Berhasil mengubah data";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Gagal ubah data. mohon coba lagi";
    }
    }else{
        $success = 0;
        $status = 204;
        $msg = "Mohon lengkapi form";
    }
    
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);  

?>