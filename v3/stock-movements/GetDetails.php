<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';


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

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
        $res=[];
        $partnerID = $_GET['partnerID'];
        $id = $_GET['id'];
        $type = $_GET['type'];
        $dateFrom = $_GET['dateFrom'];
        $dateTo = $_GET['dateTo'];

        $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }
    
    $i=0;
    if($type == "Bahan%20Jadi" || $type == "Bahan Jadi"){
        $menuID = $id;
        $rawID = 0;
    }else{
        $rawID = $id;
        $menuID = 0;
    }

    if($newDateFormat == 1){
        $q = "SELECT id, qty, gr, adjustment, initial, returned, produced, remaining, created_at FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND sm.created_at BETWEEN '$dateFrom' AND '$dateTo'";
        $getData = mysqli_query($db_conn, $q);
        if(mysqli_num_rows($getData)>0){
            $resData = mysqli_fetch_all($getData, MYSQLI_ASSOC);
            foreach($resData AS $x){
                if((double)$x['qty']!=0){
                    $res[$i]['type']="Pemakaian";
                    $res[$i]['out']=$x['qty'];
                    $res[$i]['in']="0";
                }else if((double)$x['gr']!=0){
                    $res[$i]['type']="PO";
                    $res[$i]['in']=$x['gr'];
                    $res[$i]['out']="0";
                }else if((double)$x['adjustment']!=0){
                    $res[$i]['type']="Adjustment";
                    if((double)$x['adjustment']>0){
                        $res[$i]['in']=$x['adjustment'];
                        $res[$i]['out']="0";
                    }else{
                        $res[$i]['out']=$x['adjustment'];
                        $res[$i]['in']="0";
                    }
                }else if((double)$x['initial']!=0){
                    $res[$i]['type']="Stok Awal";
                    $res[$i]['in']=$x['initial'];
                    $res[$i]['out']="0";
                }else if((double)$x['produced']!=0){
                    $res[$i]['type']="Hasil Produksi";
                    $res[$i]['in']=$x['produced'];
                    $res[$i]['out']="0";
                }else if((double)$x['returned']!=0){
                    $res[$i]['type']="Void";
                    $res[$i]['out']="0";
                    $res[$i]['in']=$x['returned'];
                }
                $res[$i]['created_at']=$x['created_at'];
                $res[$i]['remaining']=$x['remaining'];
                $i++;
            }
            $success=1;
            $msg="Data ditemukan";
            $status=200;
        }else{
            $success=0;
            $msg="Data tidak ditemukan";
            $status=204;
        }
    } 
    else 
    {
        $q = "SELECT id, qty, gr, adjustment, initial, returned, produced, remaining, created_at FROM stock_movements sm WHERE sm.raw_id='$rawID' AND sm.menu_id='$menuID' AND sm.deleted_at IS NULL AND DATE(sm.created_at) BETWEEN '$dateFrom' AND '$dateTo'";
        $getData = mysqli_query($db_conn, $q);
        if(mysqli_num_rows($getData)>0){
            $resData = mysqli_fetch_all($getData, MYSQLI_ASSOC);
            foreach($resData AS $x){
                if((double)$x['qty']!=0){
                    $res[$i]['type']="Pemakaian";
                    $res[$i]['out']=$x['qty'];
                    $res[$i]['in']="0";
                }else if((double)$x['gr']!=0){
                    $res[$i]['type']="PO";
                    $res[$i]['in']=$x['gr'];
                    $res[$i]['out']="0";
                }else if((double)$x['adjustment']!=0){
                    $res[$i]['type']="Adjustment";
                    if((double)$x['adjustment']>0){
                        $res[$i]['in']=$x['adjustment'];
                        $res[$i]['out']="0";
                    }else{
                        $res[$i]['out']=$x['adjustment'];
                        $res[$i]['in']="0";
                    }
                }else if((double)$x['initial']!=0){
                    $res[$i]['type']="Stok Awal";
                    $res[$i]['in']=$x['initial'];
                    $res[$i]['out']="0";
                }else if((double)$x['produced']!=0){
                    $res[$i]['type']="Hasil Produksi";
                    $res[$i]['in']=$x['produced'];
                    $res[$i]['out']="0";
                }else if((double)$x['returned']!=0){
                    $res[$i]['type']="Void";
                    $res[$i]['out']="0";
                    $res[$i]['in']=$x['returned'];
                }
                $res[$i]['created_at']=$x['created_at'];
                $res[$i]['remaining']=$x['remaining'];
                $i++;
            }
            $success=1;
            $msg="Data ditemukan";
            $status=200;
        }else{
            $success=0;
            $msg="Data tidak ditemukan";
            $status=204;
        }
    }


    }
    echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "details"=>$res]);
 ?>

