<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
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

    // $json = file_get_contents('php://input');
    // $data = json_decode($json,true);
    // $id = $token->id_partner;

    $sql = mysqli_query($db_conn, "SELECT partner_subcategories.id, category_id as partner_type_id, pt.name as partner_type_name, partner_subcategories.name FROM partner_subcategories LEFT JOIN partner_types pt ON pt.id=partner_subcategories.category_id WHERE pt.deleted_at IS NULL AND  partner_subcategories.deleted_at IS NULL ORDER BY id ASC");
    if(mysqli_num_rows($sql)){
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $msg = "Berhasil";
        $success = 1;
        $status = 200;
    }else{
        $success = 0;
        $status = 203;
        $msg = "data tidak ditemukan";
    }
     

}
http_response_code($status);
echo json_encode(["success"=>$success, "msg"=>$msg, "status"=>$status, "types"=>$data]);
?>

