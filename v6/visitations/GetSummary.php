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
    $dateFrom = $_GET['from'];
    $dateTo = $_GET['to'];
    $totalVisits=0;
    $totalDeal = 0;
    $totalPackage = 0;
    $totalPOS = 0;
    $res=[];
    $resDeal=[];
    $resPackage=[];
    $resPOS=[];
    $i=0;
    $sql = mysqli_query($db_conn, "SELECT COUNT(v.id) AS visits, u.name AS salesName, u.id AS salesID FROM sa_visitations v JOIN sa_users u ON v.created_by=u.id WHERE DATE(v.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND v.deleted_at IS NULL GROUP BY v.created_by ORDER BY visits DESC");

    $sqlDeal = mysqli_query($db_conn, "SELECT COUNT(v.package_type) AS count, u.name AS salesName, u.id AS salesID FROM sa_visitations v JOIN sa_users u ON v.created_by=u.id WHERE DATE(v.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND v.deleted_at IS NULL AND v.package_type!=0 GROUP BY v.created_by ORDER BY count DESC");

    $sqlPackage = mysqli_query($db_conn, "SELECT COUNT(v.package_type) AS count, v.package_type  FROM sa_visitations v WHERE DATE(v.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND v.deleted_at IS NULL GROUP BY v.package_type ORDER BY count DESC");

    $sqlPOS = mysqli_query($db_conn, "SELECT COUNT(v.current_pos) AS count, v.current_pos  FROM sa_visitations v WHERE DATE(v.created_at) BETWEEN '$dateFrom' AND '$dateTo' AND v.deleted_at IS NULL GROUP BY v.current_pos ORDER BY count DESC");

    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        foreach($data as $x){
            $res[$i] =$x;
            $totalVisits += (int)$x['visits'];
            $i++;
        }
        $deal = mysqli_fetch_all($sqlDeal, MYSQLI_ASSOC);
        $package = mysqli_fetch_all($sqlPackage, MYSQLI_ASSOC);
        $pos = mysqli_fetch_all($sqlPOS, MYSQLI_ASSOC);

        $i=0;
        foreach($deal as $x){
            $resDeal[$i]=$x;
            $totalDeal +=(int)$x['count'];
            $i++;
        }
        $i=0;
        foreach($package as $x){
            $resPackage[$i]=$x;
            $totalPackage +=(int)$x['count'];
            $i++;
        }
        $i=0;
        foreach($pos as $x){
            $resPOS[$i]=$x;
            $totalPOS +=(int)$x['count'];
            $i++;
        }
        $success = 1;
        $status = 200;
        $msg = "Success";
    }else{
        $success = 0;
        $status = 204;
        $msg = "Data Not Found";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$res, "total"=>$totalVisits, "deals"=>$resDeal, "totalDeal"=>$totalDeal, "package"=>$resPackage, "totalPackage"=>$totalPackage, "pos"=>$resPOS, "totalPOS"=>$totalPOS]);

?>
