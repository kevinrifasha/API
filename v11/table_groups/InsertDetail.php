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
$idInsert="";
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    // POST DATA
    $obj = json_decode(file_get_contents('php://input'));
    if(
        isset($obj->name) && !empty($obj->name)
    ){
        $query = "INSERT INTO `table_group_details`(`table_group_id`, `table_id`, `created_at`) VALUES";
        $i = 0;
        foreach($obj->tables as $value){
            $query .= " ('$obj->table_group_id', '$value', NOW())";
            if(count($obj->tables)-1==$i){
                $query .= ";";
            }else{
                $query .= ",";
            }
            $i +=1;
        }
        $insert = mysqli_query($db_conn,$query);
        if($insert){
            $idInsert = mysqli_insert_id($db_conn);
            $msg = "Berhasil menambahkan data";
            $success = 1;
            $status=200;
        }else{
            $msg = "Gagal menambahkan data";
            $success = 0;
            $status=204;
        }
    }else{
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 400;  
    }

}
http_response_code($status);    
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg, "id"=>$idInsert]); 
    
?>
     