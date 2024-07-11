<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../db_connection.php';
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
    $json = file_get_contents('php://input');
    $data = json_decode($json,true);
    $id = $token->id;
    if(
        isset($data['name']) && !empty($data['name'])
        && isset($data['phone']) && !empty($data['phone'])
    ){
        $nik = $data['nik'];
        $name = $data['name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $gender = $data['gender'];
        if(isset($data['password']) && !empty($data['password'])){
            $password = md5($data['password']);
            $oldPassword = md5($data['oldPassword']);
            $updateUser = mysqli_query($db_conn, "UPDATE `employees` SET nik='$nik', nama='$name', email='$email', phone='$phone', gender='$gender', pin='$password' WHERE id='$id' AND pin='$oldPassword'");
        }else{
            $updateUser = mysqli_query($db_conn, "UPDATE `employees` SET nik='$nik', nama='$name', email='$email', phone='$phone', gender='$gender' WHERE id='$id'");
        }
        if ($updateUser) {
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
        $msg = "Missing Required Field's";
    }

}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>

