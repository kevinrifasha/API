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
$value = array();
$success=0;
$msg = 'Failed';
$idMaster = $token->id_master;
$response = array();
$arrayDone = array();

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    $res=array();
    $i=0;
    $bestSeller = array();
    $namaMenu = "";
    $qty=0;
    
    $getPartner = mysqli_query($db_conn,"SELECT id, name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL" );
    
    if(mysqli_num_rows($getPartner) > 0){
        
        $getPartner = mysqli_fetch_all($getPartner, MYSQLI_ASSOC);
    
        $j = 0;
        foreach($getPartner as $val){
            
            $id = $val["id"];
            $name = $val["name"];
            
            $getDepts = mysqli_query($db_conn, "SELECT id, name FROM departments WHERE partner_id='$id' AND deleted_at IS NULL");
    
            if(mysqli_num_rows($getDepts)>0){
                $depts = mysqli_fetch_all($getDepts, MYSQLI_ASSOC);
                foreach($depts as $x){
                    $deptID = $x['id'];
                    $deptName = $x['name'];
                    $query = "SELECT 
                      SUM(detail_transaksi.qty) AS qty, 
                      menu.nama, 
                      menu.id AS menu_id 
                    FROM 
                      detail_transaksi 
                      JOIN transaksi ON detail_transaksi.id_transaksi = transaksi.id 
                      JOIN menu ON menu.id = detail_transaksi.id_menu 
                      JOIN categories c ON menu.id_category=c.id
                      JOIN departments d ON d.id = c.department_id
                    WHERE 
                      transaksi.id_partner = '$id' 
                      AND transaksi.deleted_at IS NULL 
                      AND transaksi.status IN(1, 2) 
                      AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
                      AND '$dateTo' 
                      AND menu.deleted_at IS NULL
                      AND d.id='$deptID'
                    GROUP BY 
                      menu_id 
                    ORDER BY 
                      qty DESC 
                    LIMIT 
                      5
                    ";
                    $detail = mysqli_query($db_conn, $query);
                    if(mysqli_num_rows($detail)>0){
                        $data[$i]['deptName']=$deptName;
                        $data[$i]['deptID']=$deptID;
                        $data[$i]['details']= mysqli_fetch_all($detail, MYSQLI_ASSOC);
                        $i++;
                    }
                }
                $i = 0;
            }
            
            // $ageTransactionCount=$data;
            
            $arrayDone["id_partner"] = $id;
            $arrayDone["name"] = $name;
            $arrayDone["bestSeller"] = $data;
        
            $response[$j] = $arrayDone;
        
            $j++;
            
            $arrayDone = array();
            $data = array();
            
        }
        
        $status = 200;
        $success=1;
        $msg="Success";
    } else {
        $status = 204;
        $success=0;
        $msg="Data tidak ditemukan";
    }
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "bestSeller"=>$response]);
