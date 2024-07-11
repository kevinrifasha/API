<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
    $id = $token->id_partner;
    $query = "";
    if(!empty($data->type)){
        if($data->type==1 || $data->type==2 || $data->type==8){
            $query="UPDATE partner SET partner_type='$data->type',is_table_required=1 WHERE id='$id'";
        }
        
         else if($data->type == 6){
            $query = "UPDATE partner SET partner_type='$data->type', is_foodcourt=1, is_centralized=1,is_table_required=0 WHERE id='$id'";
        }
        else{
            $query="UPDATE partner SET partner_type='$data->type', is_table_required=0 WHERE id='$id'";
        }
      
        // }else{
        //     $query="UPDATE partner SET partner_type='$data->type' WHERE id='$id'";
        // }
        $sql = mysqli_query($db_conn, $query);
     if($sql){
         $success = 1;
         $status = 200;
         $msg = "Berhasil Ubah Data";
     }else{
         $success = 0;
         $status = 200;
         $msg="Gagal Ubah Data";
     }
    }else{
        $success = 0;
         $status = 200;
         $msg="Data tidak lengkap";
    }

}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>

