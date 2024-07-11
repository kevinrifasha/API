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
}else{
    $obj = json_decode(file_get_contents('php://input'));
    if(!empty($obj->name) && !empty($obj->id) ){
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
    $sql = mysqli_query($db_conn, "UPDATE `attendance_patterns` SET name='$obj->name', partner_id='$token->id_partner', master_id='$token->id_master',".$monday.",".$tuesday.",".$wednesday.",".$thursday.",".$friday.",".$saturday.",".$sunday." WHERE id='$obj->id'");
   
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