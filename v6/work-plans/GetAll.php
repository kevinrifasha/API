<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
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
    $dateFrom =$_GET['dateFrom'];
    $dateTo=$_GET['dateTo'];
    $roleID=$_GET['roleID'];
    // if((int)$roleID==5){
    //     $q="SELECT wp.id, wp.area, wp.target, wp.date, u.name as salesName FROM sa_work_plans wp JOIN sa_users u ON u.id=wp.sales_id WHERE wp.date BETWEEN '$dateFrom' AND '$dateTo' AND wp.deleted_at IS NULL AND wp.sales_id='$token->id' ORDER BY wp.id DESC";
    // }else{
    //     $q="SELECT wp.id, wp.area, wp.target, wp.date, u.name as salesName FROM sa_work_plans wp JOIN sa_users u ON u.id=wp.sales_id WHERE wp.date BETWEEN '$dateFrom' AND '$dateTo' AND wp.deleted_at IS NULL AND u.role_id>='$roleID' ORDER BY wp.id DESC";
    // }
    if(isset($roleID) && !empty($roleID)){
        if((int)$roleID==5){
            $q="SELECT wp.id, wp.area, wp.target, wp.date, u.name as salesName FROM sa_work_plans wp JOIN sa_users u ON u.id=wp.sales_id WHERE wp.date BETWEEN '$dateFrom' AND '$dateTo' AND wp.deleted_at IS NULL AND wp.sales_id='$token->id' ORDER BY wp.id DESC";
        }else{
            $q="SELECT wp.id, wp.area, wp.target, wp.date, u.name as salesName FROM sa_work_plans wp JOIN sa_users u ON u.id=wp.sales_id WHERE wp.date BETWEEN '$dateFrom' AND '$dateTo' AND wp.deleted_at IS NULL AND u.role_id>='$roleID' ORDER BY wp.id DESC";
        }

    }else{
        $q="SELECT wp.id, wp.area, wp.target, wp.date, u.name as salesName FROM sa_work_plans wp JOIN sa_users u ON u.id=wp.sales_id WHERE wp.date BETWEEN '$dateFrom' AND '$dateTo' AND wp.deleted_at IS NULL AND wp.sales_id='$token->id' ORDER BY wp.id DESC";
    }

    $sql = mysqli_query($db_conn, $q);
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $data = array();
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data, "q"=>$q]);

?>
