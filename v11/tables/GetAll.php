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
    $id_partner=$token->id_partner;
    if(isset($_GET['id_partner']) && !empty($_GET['id_partner'])){
        $id_partner = $_GET['id_partner'];
    }
    $is_reservation = 0;
    if(isset($_GET['is_reservation']) && !empty($_GET['is_reservation'])){
        $is_reservation = $_GET['is_reservation'];
    }
    
    if($is_reservation == 0 || $is_reservation == "0"){
        $q = mysqli_query($db_conn, "SELECT `id`, `idmeja`, `is_queue` FROM `meja` WHERE idpartner='$id_partner' AND deleted_at IS NULL order by (idmeja regexp '^[A-Z]') desc, (case when idmeja regexp '^[A-Z]' then left(idmeja, 1) end), length(idmeja) asc, idmeja");
    } else {
        $q = mysqli_query($db_conn, "SELECT `id`, `idmeja`, `is_queue` FROM `meja` WHERE idpartner='$id_partner' AND deleted_at IS NULL AND 
            
            id NOT IN(SELECT meja_id FROM for_reservation fr WHERE deleted_at IS NULL) AND id NOT IN(SELECT m.id FROM for_reservation fr LEFT JOIN table_group_details tgd ON tgd.table_group_id = fr.table_group_id LEFT JOIN meja m ON m.id = tgd.table_id WHERE m.idpartner = '$id_partner' GROUP BY m.id)
        
            order by (idmeja regexp '^[A-Z]') desc, (case when idmeja regexp '^[A-Z]' then left(idmeja, 1) end), length(idmeja) asc, idmeja");
    }
    
    
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "tables"=>$res]);
?>