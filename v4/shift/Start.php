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

function checkShiftIDActive($db_conn,$token)
{
    $active = false;
    $msg    = "Tidak ada pesan";
    $query = "SELECT id FROM shift WHERE partner_id = '$token->id_partner' AND end IS NULL";
    $trxQ = mysqli_query($db_conn, $query);
    if(mysqli_num_rows($trxQ) > 0){
        $active = true;
        $msg = "Maaf anda tidak bisa memulai shift baru, masih ada shift yang berjalan";
    }
    if($active == true){
        echo json_encode(["success"=>0, "status"=>204, "msg"=>$msg]);
        exit();
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
$iid = 0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    checkShiftIDActive($db_conn, $token);
    $obj = json_decode(json_encode($_POST));
    if(isset($obj->start) && !empty($obj->start)){
        $q = mysqli_query($db_conn, "INSERT INTO `shift`(`partner_id`, `employee_id`, `start`, `petty_cash`) VALUES ('$token->id_partner', '$obj->employee_id','$obj->start','$obj->petty_cash')");

        if ($q) {
            $iid = mysqli_insert_id($db_conn);
            //end all shiftid berjalan
            $q = mysqli_query($db_conn, "UPDATE shift SET end=NOW() WHERE partner_id='$token->id_partner' AND id < '$iid' AND end IS NULL ");

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
        $status =400;
        $msg = "Missing Require Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "insertedID"=>$iid]);
?>