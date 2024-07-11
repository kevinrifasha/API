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
    $from = $_GET['from'];
    $to = $_GET['to'];
    $id=$_GET['id'];
    $type = $_GET['type'];
    $totalVisits=0;
    $totalDeal = 0;
    $totalPackage = 0;
    $totalPOS = 0;
    $res=[];
    $resDeal=[];
    $resPackage=[];
    $resPOS=[];
    $i=1;
    $extraQuery=" AND DATE(v.created_at) BETWEEN '$from' AND '$to' AND v.created_by='$id' ";
    $todayVisit = 0;
    $today = date('Y-m-d');
    if($type=="Monthly"){
      $query = "SELECT COUNT(v.id) AS counter,DATE(v.created_at) AS date FROM sa_visitations v WHERE v.deleted_at IS NULL ".$extraQuery." GROUP BY DATE(v.created_at) ORDER BY DATE(v.created_at) DESC";
    }else{
      $query = "SELECT COUNT(v.id) AS counter,CONCAT(DATE_FORMAT(v.created_at, '%Y-%m'),'-01') AS date FROM sa_visitations  v WHERE v.deleted_at IS NULL AND v.created_by='$id' GROUP BY MONTH(v.created_at) ORDER BY MONTH(v.created_at) DESC";
    }
    $sqlByDate = mysqli_query($db_conn, $query);
    $sqlTodayVisit = mysqli_query($db_conn, "SELECT COUNT(v.id) AS count FROM sa_visitations v WHERE v.deleted_at IS NULL AND v.created_by='$id' AND DATE(v.created_at)='$today'");
    $resVisit = mysqli_fetch_all($sqlTodayVisit, MYSQLI_ASSOC);
    if(mysqli_num_rows($sqlTodayVisit)>0){
      $todayVisit = $resVisit[0]['count'];
    }else{
      $todayVisit="0";
    }
    if(mysqli_num_rows($sqlByDate) > 0) {
        $visitByDate = mysqli_fetch_all($sqlByDate, MYSQLI_ASSOC);
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
      $visitByDate = array();
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "visitByDate"=>$visitByDate, "todayVisit"=>$todayVisit]);

?>
