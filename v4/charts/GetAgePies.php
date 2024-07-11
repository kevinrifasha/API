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
$all = "0";

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
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($all == "1") {
        $query = "
        SELECT 
          COUNT(DISTINCT users.phone) AS count, 
          '11-20' as category 
        from 
          users 
          join transaksi ON users.phone = transaksi.phone 
          join partner p ON p.id = transaksi.id_partner
        where 
          (
            YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 11 
            AND 20
          ) 
          AND p.id_master = '$idMaster' 
          AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
          AND '$dateTo' 
        UNION ALL 
        SELECT 
          COUNT(DISTINCT users.phone) AS count, 
          '21-30' as category 
        from 
          users 
          join transaksi ON users.phone = transaksi.phone 
          join partner p ON p.id = transaksi.id_partner
        where 
          (
            YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 21 
            AND 30
          ) 
          AND p.id_master = '$idMaster' 
          AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
          AND '$dateTo' 
        UNION ALL 
        SELECT 
          COUNT(DISTINCT users.phone) AS count, 
          '31-40' as category 
        from 
          users 
          join transaksi ON users.phone = transaksi.phone 
          join partner p ON p.id = transaksi.id_partner
        where 
          (
            YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 31 
            AND 40
          ) 
          AND p.id_master = '$idMaster' 
          AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
          AND '$dateTo' 
        UNION ALL 
        SELECT 
          COUNT(DISTINCT users.phone) AS count, 
          '41-50' as category 
        from 
          users 
          join transaksi ON users.phone = transaksi.phone 
          join partner p ON p.id = transaksi.id_partner
        where 
          (
            YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) BETWEEN 41 
            AND 50
          ) 
          AND p.id_master = '$idMaster' 
          AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
          AND '$dateTo' 
        UNION ALL 
        SELECT 
          COUNT(DISTINCT users.phone) AS count, 
          '51+' as category 
        from 
          users 
          left join transaksi ON users.phone = transaksi.phone 
          join partner p ON p.id = transaksi.id_partner
        where 
          (
            YEAR(CURRENT_TIMESTAMP) - YEAR(users.TglLahir) > 51
          ) 
          AND p.id_master = '$idMaster' 
          AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' 
          AND '$dateTo'
          ";
    } else {
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
        
    }

    $transaksi = mysqli_query($db_conn, $query);
    $values = array();
    $total = 0;
    $data = [];
    // $all_transaksi = mysqli_fetch_all($transaksi, MYSQLI_ASSOC);
   
    if(mysqli_num_rows($transaksi)>0){
        $status = 200;
        $success=1;
        $msg="Success";
        
        while($row=mysqli_fetch_assoc($transaksi)){
            $total += (int) $row['count'];
            $temp = (int) $row['count'];

        array_push($values, array("label" => $row['category'], "count" => $row['count']));
        }
        
        if(count($values) > 0) {
            foreach($values as $val) {
                if($total > 0){
                    $val['value'] = $val['count'] / $total * 100;
                } else {
                    $val['value'] = 0;
                }
                
                array_push($data, $val);
            }
        } else {
            $data = [];
        }
    }else{
        $status = 204;
        $success=0;
        $msg="Data tidak ditemukan";
    }   

    $ageTransactionCount=$data;
    
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "ageTransactionCount"=>$ageTransactionCount]);
?>
