<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
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
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    // POST DATA
    $data = json_decode(file_get_contents('php://input'));
    $now = date("Y-m-d H:i:s");
    if(
        isset($data->phone) && !empty($data->phone)
        &&isset($data->maxDisc) && !empty($data->maxDisc)
    ){
    //    $validate = mysqli_query($db_conn, "SELECT name FROM users WHERE phone='$data->phone'");
    //    if(mysqli_num_rows($validate)>0 ){

            $validate1 = mysqli_query($db_conn, "SELECT phone FROM special_member WHERE phone='$data->phone' AND id_master='$tokenDecoded->masterID' AND deleted_at IS NULL");
            if(mysqli_num_rows($validate1)==0){
                $insert = mysqli_query($db_conn,"INSERT INTO special_member SET phone='$data->phone', max_disc='$data->maxDisc', id_master='$tokenDecoded->masterID'");
                if($insert){
                    $msg = "Berhasil menambahkan data";
                    $success = 1;
                    $status=200;
                }else{
                    $msg = "Gagal menambahkan data";
                    $success = 0;
                    $status=201;
                }
            }else{
                $success = 0;
                $msg = "Nomor HP Sudah Terdaftar";
                $status = 201;
            }
            
    //    }else{
    //         $success = 0;
    //         $msg = "Nomor HP tidak ditemukan";
    //         $status = 400;
    //    }
        
        

        
    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;  
    }

}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
    http_response_code(200);
}else{
    http_response_code($status);
}
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]); 
    
?>
     