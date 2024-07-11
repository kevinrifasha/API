<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
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
$status = 400;
$msg = "error"; 
$success = 0; 

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    $obj = json_decode(file_get_contents('php://input'));
    if(
        isset($obj->idmeja) && !empty($obj->idmeja)
        && isset($obj->transactionsID) && !empty($obj->transactionsID)
    ){
        $trxDate = date("ymd");
        $q = mysqli_query($db_conn, "SELECT meja.`id`, `idmeja`, `is_queue`, t.status, t.id as transaction_id
        FROM `meja` 
        LEFT JOIN `transaksi` t ON t.no_meja=meja.idmeja AND t.id_partner=meja.idpartner AND t.id LIKE ('%$trxDate%')
        WHERE meja.idpartner='$token->id_partner' AND meja.deleted_at IS NULL AND meja.is_queue=0  AND meja.idmeja='$obj->idmeja'
        ORDER BY `meja`.`id` ASC");
    
        if (mysqli_num_rows($q) > 0) {
            $res1 = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $i = 0;
            $temp = "";
            foreach ($res1 as $value) {
                if($temp!="" && $temp!=$value['id']){
                    $i+=1;
                }
                if($value['status']=="1" || $value['status']=="5" || $value['status']=="6"){
                    $res[$i]=$value;
                    $res[$i]['available']="Tidak Tersedia";
                }else{
                    if($i == 0) {
                        // if($res[0]['available']!="Tidak Tersedia"){
                            $res[$i]=$value;
                            $res[$i]['available']="Tersedia";
                        // }
                    } else {
                        if($res[$i]['available']!="Tidak Tersedia"){
                            $res[$i]=$value;
                            $res[$i]['available']="Tersedia";
                        }
                    }
                }
                $temp = $value['id'];
            }
            
            if($res[0]['available']=="Tersedia"){
                $query = "UPDATE `transaksi` SET `no_meja`='$obj->idmeja' WHERE id IN (";
                $trasactionID = explode(',', $obj->transactionsID);
                $i=0;
                    foreach ($trasactionID as $value) {
                        if($i>0){
                            $query.=",";
                        }
                        $query .= "'$value'";
                        $i+=1;
                    }
                    $query .= " )";
                $insert = mysqli_query($db_conn,$query);
                if($insert){
                    $idInsert = mysqli_insert_id($db_conn);
                    $msg = "Berhasil mengubah data";
                    $success = 1;
                    $status=200;
                }else{
                    $msg = "Gagal mengubah data";
                    $success = 0;
                    $status=200;
                }
            }else{
                    $msg = "Meja Tidak Tersedia";
                    $success = 0;
                    $status=200;
                
            }
        } else {
            $success =0;
            $status =200;
            $msg = "Data Not Found";
        }
        
    }else{
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 400;  
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>