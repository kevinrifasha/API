<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../db_connection.php';
require_once('../auth/Token.php');

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
    if(!empty($obj->partnerID)){
    $sql = mysqli_query($db_conn, "UPDATE partner_opening_hours SET monday_open='$obj->mondayOpen', tuesday_open='$obj->tuesdayOpen', wednesday_open='$obj->wednesdayOpen', thursday_open='$obj->thursdayOpen', friday_open='$obj->fridayOpen', saturday_open='$obj->saturdayOpen', sunday_open='$obj->sundayOpen', monday_closed='$obj->mondayClosed', tuesday_closed='$obj->tuesdayClosed', wednesday_closed='$obj->wednesdayClosed', thursday_closed='$obj->thursdayClosed', friday_closed='$obj->fridayClosed', saturday_closed='$obj->saturdayClosed', sunday_closed='$obj->sundayClosed',monday_last_order='$obj->mondayLastOrder', tuesday_last_order='$obj->tuesdayLastOrder', wednesday_last_order='$obj->wednesdayLastOrder', thursday_last_order='$obj->thursdayLastOrder', friday_last_order='$obj->fridayLastOrder', saturday_last_order='$obj->saturdayLastOrder', sunday_last_order='$obj->sundayLastOrder', updated_at=NOW() WHERE partner_id ='$obj->partnerID'");
    if($sql) {
        $all_users = mysqli_fetch_all($users, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Berhasil mengubah data";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Gagal tambah data. mohon coba lagi";
    }
    }else{
        $success = 0;
        $status = 204;
        $msg = "Mohon lengkapi form";
    }
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);  
?>