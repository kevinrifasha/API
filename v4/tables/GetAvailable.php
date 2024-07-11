<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
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

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $trxDate = date("ymd");
    $q = mysqli_query($db_conn, "SELECT meja.`id`, `idmeja`, `is_queue`, meja.is_seated, t.status, t.id as transaction_id, CASE WHEN  t.group_id=null THEN 0 ELSE t.group_id END AS groupID, CASE WHEN (DATE(r.reservation_time) = DATE(NOW())) THEN '1' ELSE '0' END AS reservationStatus FROM `meja` LEFT JOIN `transaksi` t ON t.no_meja=meja.idmeja AND t.status NOT IN (2,3,4,7) AND t.id_partner=meja.idpartner AND t.deleted_at IS NULL AND t.id_partner='$token->id_partner' LEFT OUTER JOIN reservations r ON r.table_id=meja.id AND r.status IN('Approved','Waiting_For_Approval') WHERE meja.idpartner='$token->id_partner' AND meja.deleted_at IS NULL AND meja.is_queue=0 ORDER BY `meja`.`id` ASC");
    if (mysqli_num_rows($q) > 0) {
        $res1 = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $length = count($res1);
        
        $id = 0;
        $data = array();
        $i = 0;
        $j = 0;
        $detail = array();
        foreach($res1 as $value){
            
            if(($value['status'] == "5" || $value['status'] == "6")){
                array_push($detail, $value["transaction_id"]);
            }
            
            if($res1[$j]['id'] != $res1[$j+1]['id']){
                
               $res[$i] = $res1[$j];
            
              if($detail == []){
                  $res[$i]["available"] = "Tersedia"; 
              } else {
                  $res[$i]["available"] = "Tidak Tersedia"; 
              }
              $res[$i]["detail"] = $detail;    
                
              $i++;
               
              $detail = array();
            }
            
            $j++;
        }
        
        $res = array_values($res);
        
        
        // $res = array_values($res);
        // $i = 0;
        // $j = 0;
        // $temp = "";
        // $tempV = [];
        // foreach ($res1 as $value) {
        //     if($temp!="" && $temp!=$value['id']){
        //         $i+=1;
        //     }
        //     if($temp!="" && $temp==$value['id']){
        //         // $j+=1;
                
        //     }else{
        //         $j=0;
        //     }
            
        //     // if($value['id'] == "461"){
        //     //     $test = "hit 3" . " DI/230103/162/000002";
        //     // }
            
        //     if($value['status']=="5" || $value['status']=="6"){
        //         if($j==0){
        //             $res[$i]=$value;
        //         }
        //         $res[$i]['available']="Tidak Tersedia";
        //         $res[$i]['detail'][$j]=$value['transaction_id'];
        //         $j +=1;
        //     } else{
        //         if($j==0){
        //             $res[$i]=$value;
        //         }
        //         $res[$i]['available']="Tersedia";
        //         $res[$i]['detail']=array();
        //         // $res[$i]['detail'][$j]=$value['transaction_id'];
        //     }
        //     // if($i>0){
        //     //     if(is_null($res[$i]['detail'][0])){
        //     //         $res[$i]['detail']=array();
        //     //     }
        //     // }
        //     $temp = $value['id'];
        //     $tempV = $value;
        // };
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "tables"=>$res]);
