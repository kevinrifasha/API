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
    $month = $_GET['month'];
    $year = $_GET['year'];
    $date = $year."-".$month."-"."01";
    $getTargets = mysqli_query($db_conn, "SELECT id, attributes, value, period FROM sa_targets WHERE deleted_at IS NULL AND DATE(validity_period)='$date' AND period='Monthly'");
    $sqlVisits = "SELECT COUNT(id) AS count FROM sa_visitations WHERE MONTH(created_at)='$month' AND YEAR(created_at)='$year'";
    $visits = mysqli_query($db_conn, $sqlVisits);
    $sqlEffectiveCalls = "SELECT COUNT(id) AS count FROM partner p WHERE MONTH(created_at)='$month' AND YEAR(created_at)='$year' AND refferal REGEXP '^[0-9]+$'";
    $ec = mysqli_query($db_conn, $sqlEffectiveCalls);
    // $sqlActiveFree = mysqli_query($db_conn, "SELECT COUNT(p.id) ");
    if(mysqli_num_rows($visits) > 0) {
        $resVisits = mysqli_fetch_all($visits, MYSQLI_ASSOC);
        $resEC = mysqli_fetch_all($ec, MYSQLI_ASSOC);
        
        $visitCount = $resVisits[0]['count'];
        $ecCount = $resEC[0]['count'];
        $targets = mysqli_fetch_all($getTargets, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "visitCount"=>$visitCount, "ecCount"=>$ecCount, "targets"=>$targets]);

?>
