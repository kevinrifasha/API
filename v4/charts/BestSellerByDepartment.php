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
$idMaster = $token->id_master;
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    
    $id = $token->id_partner;
    if(isset($_GET['partnerID']) && $_GET['partnerID'] != "null"){
        $id = $_GET['partnerID'];
    }
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    
    $all = "0";
    if(isset($_GET['all'])){
        $all = $_GET['all'];
    }
    $res=array();
    
    $i=0;
    $bestSeller = array();
    $namaMenu = "";
    $qty=0;
    
    if($all == "1"){
        $getDepts = mysqli_query($db_conn, "SELECT d.id, d.name, p.name as partnerName FROM departments d LEFT JOIN partner p ON d.partner_id = p.id WHERE p.id_master='$idMaster' AND d.deleted_at IS NULL");
        if(mysqli_num_rows($getDepts)>0){
            $depts = mysqli_fetch_all($getDepts, MYSQLI_ASSOC);
            foreach($depts as $x){
                $deptID = $x['id'];
                $deptName = $x['name'];
                $partnerName = $x['partnerName'];
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
                  DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
                  AND '$dateTo'
                  AND transaksi.deleted_at IS NULL 
                  AND transaksi.status IN(1, 2) 
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
                    $data[$i]['partnerName']=$partnerName;
                    $data[$i]['details']= mysqli_fetch_all($detail, MYSQLI_ASSOC);
                    $i++;
                }
            }
        }
    }else{
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
                  DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
                  AND '$dateTo'
                  AND transaksi.id_partner = '$id' 
                  AND transaksi.deleted_at IS NULL 
                  AND transaksi.status IN(1, 2) 
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
        }
    }
    
    if($i>0){
        $status = 200;
        $success=1;
        $msg="Success";
    }else{
        $status = 204;
        $success=0;
        $msg="Data tidak ditemukan";
    }
    // if(mysqli_num_rows($detail)>0){
    //     $status = 200;
    //     $success=1;
    //     $msg="Success";
    //     while($row=mysqli_fetch_assoc($detail)){
    //         $namaMenu = $row['nama'];
    //         $qty = $row['qty'];
    //         array_push($bestSeller, array("name" => "$namaMenu", "value" => $qty));
    //     }
    // }else{
    //     $status = 204;
    //     $success=0;
    //     $msg="Data tidak ditemukan";
    // }   
    
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "bestSeller"=>$data]);
