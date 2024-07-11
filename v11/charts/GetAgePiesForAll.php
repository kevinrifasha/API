<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');

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
$arrayDone = array();
$response = array();
$res=array();

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    
    $getPartner = mysqli_query($db_conn,"SELECT id, name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL" );
    
    if(mysqli_num_rows($getPartner) > 0){
        
        $getPartner = mysqli_fetch_all($getPartner, MYSQLI_ASSOC);
    
        $j = 0;
        foreach($getPartner as $val){
            
            $id = $val["id"];
            $name = $val["name"];
            
            $query = "
            SELECT 
              COUNT(DISTINCT users.phone) AS count, 
              '11-20' as category 
            from 
              users 
              join transaksi ON users.phone = transaksi.phone 
            where 
              (
                YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 11 
                AND 20
              ) 
              AND transaksi.id_partner = '$id' 
              AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
              AND '$dateTo' 
            UNION ALL 
            SELECT 
              COUNT(DISTINCT users.phone) AS count, 
              '21-30' as category 
            from 
              users 
              join transaksi ON users.phone = transaksi.phone 
            where 
              (
                YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 21 
                AND 30
              ) 
              AND transaksi.id_partner = '$id' 
              AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
              AND '$dateTo' 
            UNION ALL 
            SELECT 
              COUNT(DISTINCT users.phone) AS count, 
              '31-40' as category 
            from 
              users 
              join transaksi ON users.phone = transaksi.phone 
            where 
              (
                YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 31 
                AND 40
              ) 
              AND transaksi.id_partner = '$id' 
              AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
              AND '$dateTo' 
            UNION ALL 
            SELECT 
              COUNT(DISTINCT users.phone) AS count, 
              '41-50' as category 
            from 
              users 
              join transaksi ON users.phone = transaksi.phone 
            where 
              (
                YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 41 
                AND 50
              ) 
              AND transaksi.id_partner = '$id' 
              AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
              AND '$dateTo' 
            UNION ALL 
            SELECT 
              COUNT(DISTINCT users.phone) AS count, 
              '51+' as category 
            from 
              users 
              left join transaksi ON users.phone = transaksi.phone 
            where 
              (
                YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) > 51
              ) 
              AND transaksi.id_partner = '$id' 
              AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
              AND '$dateTo'
              ";
        
            $transaksi = mysqli_query($db_conn, $query);
            $values = array();
            $total = 0;
            $result = array();
           
            if(mysqli_num_rows($transaksi)>0){
                
                while($row=mysqli_fetch_assoc($transaksi)){
                    
                    $total += (int) $row['count'];
                    $temp = (int) $row['count'];
        
                array_push($values, array("label" => $row['category'], "count" => $row['count']));
                }
    
                
                $data = array();
                if(count($values) > 0) {
                    
                    
                    $k = 0;
                    foreach($values as $val) {
                        
                        // $val["value"] = (int)$val["count"] / $total * 100;
                        $valTotal = 0;
                        
                        if($total == "0" && $val["count"] == 0){
                            $valTotal = 0;
                        }else{
                            $valTotal = (int)$val["count"] / (int)$total * 100;;
                        }
                        
                        $val["value"] = $valTotal;
                        
                        $data[$k] = $val;
                        
                        $k++;
                        
                    }
                    
                    $result = $data;
                    
                } else {
                    $result = array();
                }
            }
            

            
            // $ageTransactionCount=$data;
            
            $arrayDone["id_partner"] = $id;
            $arrayDone["name"] = $name;
            $arrayDone["ageTransactionCount"] = $result;
        
            $response[$j] = $arrayDone;
        
            $j++;
            
            $arrayDone = array();
            
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
?>
