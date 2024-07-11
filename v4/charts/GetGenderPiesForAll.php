<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

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
$token = '';

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenizer = new Token();
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;
$value = array();
$success=0;
$msg = 'Failed';
$all = "0";
$status = 200;
$success=1;
$msg="Success";

$arrayDone = array();
$response = array();
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $id = $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $id = $_GET['partnerID']; 
    }
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    $res=array();
    
    $getPartner = mysqli_query($db_conn,"SELECT id, name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL" );
    
    if(mysqli_num_rows($getPartner) > 0){
        
        $getPartner = mysqli_fetch_all($getPartner, MYSQLI_ASSOC);
    
        $j = 0;
        foreach($getPartner as $val){

            $id = $val["id"];
            $name = $val["name"];
            
            $query = "SELECT COUNT(`transaksi`.id) AS counted, CASE WHEN users.Gender IS NOT NULL AND users.Gender!='' THEN users.Gender ELSE 'Belum isi' END gender FROM `transaksi` LEFT JOIN users ON users.phone=`transaksi`.phone WHERE DATE(`transaksi`.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND `transaksi`.deleted_at IS NULL AND `transaksi`.id_partner='$id' AND transaksi.status IN(1,2) GROUP BY users.Gender ORDER BY users.Gender DESC";
            
            $transaksi = mysqli_query($db_conn, $query);
            $values = array();
        
            $pria = 0;
            $wanita = 0;
            $unassign = 0;
            $total = 0;
            $tmpTotal=0;
            while ($row = mysqli_fetch_assoc($transaksi)) {
                if($row['gender']=="Wanita"){
                    $wanita = (int)$row['counted'];
                }else if($row['gender']=="Pria"){
                    $pria = (int)$row['counted'];
                }else{
                    $unassign = (int)$row['counted'];
                }
                $tmpTotal += (int)$row['counted'];
            }
            $total = $tmpTotal;
            if($pria>0){
                $persenPria =(round((float)($pria/$total) * 100 ));
            }else{
                $persenPria = 0;
            }
            if($wanita>0){
                $persenWanita =(round((float)($wanita/$total) * 100 ));
            }else{
                $persenWanita = 0;
            }
            if($unassign>0){
                $persenUanassgin =100-$persenPria-$persenWanita;
            }else{
                $persenUanassgin = 0;
            }
            if($persenPria > 0){
            array_push($values, array("label" => 'Pria', "value" => $persenPria, "count"=>$pria));
            }
            if($persenWanita > 0){
            array_push($values, array("label" => 'Wanita', "value" => $persenWanita, "count"=>$wanita));
            }
            if($persenUanassgin > 0){
            array_push($values, array("label" => 'Belum Menentukan', "value" => $persenUanassgin, "count"=>$unassign));
            }
            
            $arrayDone["id_partner"] = $id;
            $arrayDone["name"] = $name;
            $arrayDone["genderTransactionPercentage"] = $values;
        
            $response[$j] = $arrayDone;
        
            $j++;
            
            $arrayDone = array();
            $values = array();
        }
        
        $success = 1;
        $status = 200;
        $msg = "Success";
    } else {
        $status = 204;
        $success=0;
        $msg="Data tidak ditemukan";
    }
    
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$response]);
