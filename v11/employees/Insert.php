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
    $data = json_decode(file_get_contents('php://input'));
    if(
        isset($data->name) && !empty($data->name)
        &&isset($data->email) && !empty($data->email)
        // &&isset($data->phone) && !empty($data->phone)
        &&isset($data->pin) && !empty($data->pin)
    ){
        if(!isset($data->patternID)){
            $data->patternID=0;
        }

        $nik = "";
        $validateNik = "";
        $insert= "";
        $q = "";

        if($data->nik !== "") {
            $nik = $data->nik;
            $validateNik = mysqli_query($db_conn, "SELECT COUNT(id) FROM employees WHERE nik='$data->nik' AND master_id='$token->id_master'");

            if(mysqli_num_rows($validateNik)>0){
                $msg = "Terdapat NIK duplikat. Mohon gunakan NIK lain";
                $success=0;
                $status=204;
            }else{
                $pin = md5($data->pin);
                // show_as_server karena di app belum ada jadi di default kan dulu 1
                $q = "INSERT INTO `employees` SET `nama`='$data->name', id_master='$token->id_master', id_partner='$token->id_partner', email='$data->email', phone='$data->phone', nik='$data->nik', pin='$pin', role_id='$data->roleID', pattern_id='$data->patternID', show_as_server=1, `organization`='Natta'";
            }
        } else {
            $pin = md5($data->pin);
            // show_as_server karena di app belum ada jadi di default kan dulu 1
            $q = "INSERT INTO `employees` SET `nama`='$data->name', id_master='$token->id_master', id_partner='$token->id_partner', email='$data->email', phone='$data->phone', pin='$pin', role_id='$data->roleID', pattern_id='$data->patternID', show_as_server=1,
                `organization`='Natta'";
        }
        $insert = mysqli_query($db_conn,$q);

        if($insert){
                $msg = "Berhasil tambah karyawan";
                $success = 1;
                $status=200;
            }else{
                $msg = "Gagal tambah karyawan. Mohon coba lagi";
                $success = 0;
                $status=204;
            }

    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);
