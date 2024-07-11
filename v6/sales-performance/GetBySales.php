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
    $totalVisits=0;
    $totalDeal = 0;
    $totalPackage = 0;
    $totalPOS = 0;
    $res=[];
    $resDeal=[];
    $resPackage=[];
    $resPOS=[];
    $resWP=[];
    $i=1;
    $extraQuery.=" AND DATE(v.created_at) BETWEEN '$from' AND '$to' AND v.created_by='$id' ";

    $sql = mysqli_query($db_conn, "SELECT v.id, v.merchant_name, v.business_type, v.address, v.pic_name, v.pic_phone, v.visit_no, v.customer_input, v.latitude, v.longitude, v.is_mock, v.image, u.name AS salesName, v.created_at, v.package_type, v.current_pos, v.other_pos FROM `sa_visitations`v JOIN sa_users u ON u.id=v.created_by WHERE v.deleted_at IS NULL".$extraQuery." ORDER BY v.id DESC");

    $sqlPackage = mysqli_query($db_conn, "SELECT COUNT(v.package_type) AS count, v.package_type  FROM sa_visitations v WHERE v.deleted_at IS NULL ".$extraQuery." GROUP BY v.package_type ORDER BY count DESC");

    $sqlPOS = mysqli_query($db_conn, "SELECT COUNT(v.current_pos) AS count, v.current_pos  FROM sa_visitations v WHERE v.deleted_at IS NULL ".$extraQuery." GROUP BY v.current_pos ORDER BY count DESC");
    $sqlByDate = mysqli_query($db_conn, "SELECT COUNT(v.id) AS counter,DATE(v.created_at) AS date FROM sa_visitations v WHERE v.deleted_at IS NULL ".$extraQuery." GROUP BY DATE(v.created_at) ORDER BY DATE(v.created_at) DESC");
    $sqlWP = mysqli_query($db_conn, "SELECT wp.id, wp.area, wp.target, wp.date, COUNT(wpd.id) as visited FROM sa_work_plans wp JOIN sa_work_plan_details wpd ON wp.id=wpd.work_plan_id WHERE wp.date BETWEEN '$from' AND '$to' AND wp.deleted_at IS NULL AND wp.sales_id='$id' GROUP BY wp.id ORDER BY wp.id DESC");
    $sqlM = mysqli_query($db_conn, "SELECT COUNT(p.id) AS partners FROM partner p WHERE p.referral='$id' AND DATE(p.created_at) BETWEEN '$from' AND '$to' AND p.deleted_at IS NULL AND p.status=1");
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $package = mysqli_fetch_all($sqlPackage, MYSQLI_ASSOC);
        $pos = mysqli_fetch_all($sqlPOS, MYSQLI_ASSOC);
        $visitByDate = mysqli_fetch_all($sqlByDate, MYSQLI_ASSOC);
        $wp = mysqli_fetch_all($sqlWP, MYSQLI_ASSOC);
        $totalVisits = count($data);
        $merchants = mysqli_fetch_assoc($sqlM);
        $totalMerchant = $merchants['partners'];
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
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$data, "total"=>$totalVisits, "package"=>$resPackage, "totalPackage"=>$totalPackage, "pos"=>$resPOS, "totalPOS"=>$totalPOS, "visitByDate"=>$visitByDate, "workPlans"=>$wp, "totalMerchant"=>$totalMerchant]);

?>
