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
$res1 = array();
$all_raw1 = array();
$all = "0";

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->id_master;

function arraySome($array, $callback) {
    return array_reduce($array, function ($carry, $item) use ($callback) {
        return $carry || $callback($item);
    }, false);
}

function arrayFilter($array, $callback) {
    $filtered = [];

    foreach ($array as $item) {
        if ($callback($item)) {
            $filtered[] = $item;
        }
    }

    return $filtered;
}

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $id = $token->id_partner;
    if(isset($_GET['partnerID'])) {
        $id = $_GET['partnerID']; 
    }
    
    $now = date("Y-m-d H:i:s");
    $date = date("Y-m-d");
    $date1 = str_replace('-', '/', $date);
    $before = date('Y-m-d',strtotime($date1 . "+3 days"));
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if($all == "1") {
        $qRawStock = "SELECT raw_material_stock.id, raw_material_stock.id_raw_material, raw_material_stock.stock, raw_material_stock.id_metric, raw_material_stock.exp_date, raw_material_stock.id_goods_receipt_detail, raw_material.name AS rmname, metric.name as mname FROM raw_material_stock JOIN raw_material ON raw_material_stock.id_raw_material=raw_material.id JOIN metric ON raw_material_stock.id_metric = metric.id WHERE raw_material_stock.exp_date>'$now' AND raw_material.id_master = '$idMaster' AND raw_material.deleted_at IS NULL";
        $qAllRaw1 = "SELECT raw_material_stock.id, raw_material_stock.id_raw_material, raw_material_stock.stock, raw_material_stock.id_metric, raw_material_stock.exp_date, raw_material_stock.id_goods_receipt_detail, raw_material.name AS rmname, metric.name as mname FROM raw_material_stock JOIN raw_material ON raw_material_stock.id_raw_material=raw_material.id JOIN metric ON raw_material_stock.id_metric = metric.id WHERE raw_material_stock.exp_date<='$before' AND raw_material.id_master='$idMaster' AND raw_material.deleted_at IS NULL";
    } else {
        $qRawStock = "SELECT raw_material_stock.id, raw_material_stock.id_raw_material, raw_material_stock.stock, raw_material_stock.id_metric, raw_material_stock.exp_date, raw_material_stock.id_goods_receipt_detail, raw_material.name AS rmname, metric.name as mname FROM raw_material_stock JOIN raw_material ON raw_material_stock.id_raw_material=raw_material.id JOIN metric ON raw_material_stock.id_metric = metric.id WHERE raw_material_stock.exp_date>'$now' AND raw_material.id_partner='$id' AND raw_material.deleted_at IS NULL";
        
        $qAllRaw1 = "SELECT raw_material_stock.id, raw_material_stock.id_raw_material, raw_material_stock.stock, raw_material_stock.id_metric, raw_material_stock.exp_date, raw_material_stock.id_goods_receipt_detail, raw_material.name AS rmname, metric.name as mname FROM raw_material_stock JOIN raw_material ON raw_material_stock.id_raw_material=raw_material.id JOIN metric ON raw_material_stock.id_metric = metric.id WHERE raw_material_stock.exp_date<='$before' AND raw_material.id_partner='$id' AND raw_material.deleted_at IS NULL";
    }
    
    $allRawStock = mysqli_query($db_conn, $qRawStock);
    $all_raw_stock = mysqli_fetch_all($allRawStock, MYSQLI_ASSOC);
    
    $allRaw1 = mysqli_query($db_conn, $qAllRaw1);
    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
    
    $arr = array();
    $index = 0;
    $j = 0;
    $counter = count($all_raw_stock);
    while($counter>0){
        if($counter==count($all_raw_stock)){
            $arr[$index]['id'] = $all_raw_stock[$j]['id'];
            $arr[$index]['id_raw_material'] = $all_raw_stock[$j]['id_raw_material'];
            $arr[$index]['rmname'] = $all_raw_stock[$j]['rmname'];
            $arr[$index]['mname'] = $all_raw_stock[$j]['mname'];
            $arr[$index]['stock'] = $all_raw_stock[$j]['stock'];
            $arr[$index]['id_metric'] = $all_raw_stock[$j]['id_metric'];
        }else{
            $cA = (int) $arr[$index]['id_raw_material'];
            $cB = (int) $all_raw_stock[$j]['id_raw_material'];
            if($cA==$cB){
                if($arr[$index]['id_metric'] == $all_raw_stock[$j]['id_metric']){
                    $arr[$index]['stock'] += $all_raw_stock[$j]['stock'];
                }else{
                    $a = $arr[$index]['id_metric'];
                    $b = $all_raw_stock[$j]['id_metric'];
                    $convert = mysqli_query($db_conn, "SELECT * FROM metric_convert WHERE id_metric1=$a AND id_metric2=$b");

                    if(mysqli_num_rows($convert)==0){
                        $convert = mysqli_query($db_conn, "SELECT * FROM metric_convert WHERE id_metric1=$b AND id_metric2=$a");
                        $converts = mysqli_fetch_all($convert, MYSQLI_ASSOC);
                        $add = $converts[0]['value']*$arr[$index]['stock'];
                        $metric = $a;
                        $nmetric = $arr[$index]['mname'];
                    }else{
                        $converts = mysqli_fetch_all($convert, MYSQLI_ASSOC);
                        $add = $converts[0]['value']*$all_raw_stock[$j]['stock'];
                        $metric = $b;
                        $nmetric = $all_raw_stock[$j]['mname'];
                    }
                    $arr[$index]['stock']+=$add;
                    $arr[$index]['id_metric']=$metric;
                    $arr[$index]['mname']=$nmetric;
                }

            }else{
                $index+=1;
                $arr[$index]['id'] = $all_raw_stock[$j]['id'];
                $arr[$index]['id_raw_material'] = $all_raw_stock[$j]['id_raw_material'];
                $arr[$index]['stock'] = $all_raw_stock[$j]['stock'];
                $arr[$index]['id_metric'] = $all_raw_stock[$j]['id_metric'];
                $arr[$index]['rmname'] = $all_raw_stock[$j]['rmname'];
                $arr[$index]['mname'] = $all_raw_stock[$j]['mname'];
            }
        }
        $counter-=1;
        $j+=1;
    }
    
    if($all == "1") {
        $qAllRaw = "SELECT raw_material.id, raw_material.id_master, raw_material.id_partner, raw_material.name, raw_material.reminder_allert, raw_material.id_metric, raw_material.unit_price, raw_material.id_metric_price, metric.name AS mname FROM `raw_material` JOIN metric ON metric.id=raw_material.id_metric WHERE id_master='$idMaster' AND raw_material.deleted_at IS NULL";
    } else {
        $qAllRaw = "SELECT raw_material.id, raw_material.id_master, raw_material.id_partner, raw_material.name, raw_material.reminder_allert, raw_material.id_metric, raw_material.unit_price, raw_material.id_metric_price, metric.name AS mname FROM `raw_material` JOIN metric ON metric.id=raw_material.id_metric WHERE id_partner='$id' AND raw_material.deleted_at IS NULL";
    }

    $allRaw = mysqli_query($db_conn, $qAllRaw);
    $all_raw = mysqli_fetch_all($allRaw, MYSQLI_ASSOC);
    $res = array();
    $i = 0;
    
    foreach($all_raw as $value){
        foreach($arr as $value1){
            if($value['id']==$value1['id_raw_material']){
                if($value['id_metric']==$value1['id_metric']){
                    if($value['reminder_allert']>=$value1['stock'] 
                    // && $value1['stock']>= 0
                    ){
                        $res[$i] = $value1;
                        $i+=1;
                    }
                }else{
                    $a = $value['id_metric'];
                    $b = $value1['id_metric'];
                    $convert = mysqli_query($db_conn, "SELECT * FROM metric_convert WHERE id_metric1=$a AND id_metric2=$b");
                    $converts = mysqli_fetch_all($convert, MYSQLI_ASSOC);
                    // $add = $converts[0]['value']*$value['reminder_allert'];
                    $add = 0;
                    if(count($converts) > 0) {
                        $add = $converts[0]['value']*$value['reminder_allert'];
                    }
                    $metric = $b;
                    $nmetric = $value1['mname'];
                    if(mysqli_num_rows($convert)==0){
                        $convert = mysqli_query($db_conn, "SELECT * FROM metric_convert WHERE id_metric1=$b AND id_metric2=$a");
                        $converts = mysqli_fetch_all($convert, MYSQLI_ASSOC);
                        $add = $converts[0]['value']*$value1['stock'];
                        if($value['reminder_allert']){
                            $add = $converts[0]['value']*$value1['reminder_allert'];
                        }
                        $metric = $a;
                        $nmetric = $value['mname'];
                    }

                    if($a==$metric && $add>=$value1['stock'] 
                    // && $value1['stock']>= 0 
                    ){
                        $res[$i] = $value1;
                        $res[$i]['mname'] = $nmetric;
                        $i+=1;
                    }
                }
            }
        }
    }
    
    //filtered if there is no record in raw_material_stock but there is record in raw_material
    $filtered = arrayFilter($all_raw, function ($item) use ($arr) {
        return !arraySome($arr, function ($itemArr) use ($item) {
            return $itemArr["id_raw_material"] == $item["id"];
        });
    });
    
    
    foreach($filtered as $item){
        $mappedItem = [];
        $mappedItem["id"] = "0";
        $mappedItem["id_raw_material"] = $item["id"];
        $mappedItem["stock"] = "0";
        $mappedItem["id_metric"] = $item["id_metric"];
        $mappedItem["rmname"] = $item["name"];
        $mappedItem["mname"] = $item["mname"]; 
        
        $res[$i] = $mappedItem;
        $i++;
    }

    if (count($res) > 0) {
        $success=1;
        $status=200;
        $msg="Success";
    } else {
        $success=0;
        $status=203;
        $msg="Data Not Found";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "raw"=>$res, "expired"=>$all_raw1]);
?>