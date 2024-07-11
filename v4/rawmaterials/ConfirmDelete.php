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

    // POST DATA
    // $json = file_get_contents('php://input');
    // $data = json_decode($json,true);
    
    // if( 
    //     isset($data['id'])
    //     && !empty($data['id'])
    //     ){
            $id = $_GET['id'];
            $associatedMenus="";
            $getMenus = mysqli_query($db_conn, "SELECT m.nama AS menuName FROM recipe r JOIN menu m ON m.id=r.id_menu WHERE r.id_raw='$id' AND r.deleted_at IS NULL AND m.deleted_at IS NULL AND m.id_partner='$token->id_partner'");
            if(mysqli_num_rows($getMenus)>0){
                $menus = mysqli_fetch_all($getMenus, MYSQLI_ASSOC);
                foreach($menus as $x){
                    $associatedMenus .=$x['menuName'].", ";
                }
                    $success =1;
                $status =200;
                $msg = "Success";
            }else{
                $menus = [];
                    $success =0;
                $status =204;
                $msg = "Data tidak ditemukan";
            }
           
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "associatedMenus"=>$menus]);
?>