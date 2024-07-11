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
$res1 = array();
$ewallet_response = array();
$id = "";

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

//function
function checkMembership($phone, $partner_id ,$db_conn){
    $q1 = mysqli_query($db_conn,"SELECT m.id FROM memberships m JOIN partner p ON p.id_master=m.master_id WHERE m.user_phone='$phone' AND p.id='$partner_id' ORDER BY m.id DESC LIMIT 1");
    if (mysqli_num_rows($q1) > 0) {
        $res = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        return $res[0]['id'];
    }else{
        return 0;
    }
}

function getMasterID($partner_id ,$db_conn){
    $q1 = mysqli_query($db_conn,"SELECT p.id_master FROM partner p WHERE p.id='$partner_id'");
    if (mysqli_num_rows($q1) > 0) {
        $res = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        return $res[0]['id_master'];
    }else{
        return 0;
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $data = json_decode(file_get_contents('php://input'));
    if( isset($data->partnerID) && !empty($data->partnerID)){
        $phone = $token->phone;
        if(isset($data->gender) && !empty($data->gender) && isset($data->birthDate) && !empty($data->birthDate)){
            $insert = mysqli_query($db_conn,"UPDATE users SET TglLahir='$data->birthDate', Gender='$data->gender' WHERE phone='$phone'");
        }
        $mID = checkMembership($phone, $data->partnerID ,$db_conn);
        if($mID==0){
            $mID = getMasterID($data->partnerID ,$db_conn);
            $insert = mysqli_query($db_conn,"INSERT INTO `memberships`(`user_phone`, `master_id`, `point`) VALUES ('$phone', '$mID', '0')");
            if($insert){
                $success = 1;
                $msg = "Success";
                $status = 200;
            }else{
                $success = 0;
                $msg = "Failed";
                $status = 204;
            }
        }else{
            $success = 0;
            $msg = "Already registered as member at this master";
            $status = 204;
        }
    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);