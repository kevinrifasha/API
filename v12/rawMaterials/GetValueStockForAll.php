<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../rawMaterialModels/rawMaterialManager.php"); 
require_once("./../metricModels/metricManager.php"); 
require_once("./../rawMaterialStockModels/rawMaterialStockManager.php");
require_once("./../metricConvertModels/metricConvertManager.php");

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
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$idMaster = $token->masterID;
$today = date("Y-m-d H:i:s");
$res=array();
$sorted = array();
$success=0;
$msg = "failed";
$array = [];

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $msg = $tokens['msg']; 
    $success = 0; 
}else{
    $now = date("Y-m-d H:i:s");
    $date = date("Y-m-d");
    $date1 = $date;
    $before = date('Y-m-d',strtotime($date1 . "+3 days"));
    
    $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
    if(mysqli_num_rows($sqlPartner) > 0) {
        $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
        foreach($getPartners as $partner) {
            $id = $partner['partner_id'];
            $res1=array();
            $all_raw1=array();
            
            $qRaw1 = "SELECT menu.nama as name, hpp, stock,hpp*stock AS value FROM `menu` WHERE id_partner='$id' AND deleted_at IS NULL AND is_recipe='0' ORDER BY value DESC";
            $qRaw2 = "SELECT raw_material.id, raw_material.name, SUM(raw_material_stock.stock*raw_material.unit_price) as value, SUM(raw_material_stock.stock) as stock, raw_material.unit_price, raw_material_stock.id_metric, metric.name AS metric_name, raw_material.id_metric_price FROM `raw_material_stock` JOIN raw_material ON raw_material.id=raw_material_stock.id_raw_material LEFT JOIN metric ON metric.id=raw_material.id_metric_price WHERE raw_material_stock.exp_date>='$date' AND raw_material.id_partner='$id' GROUP BY raw_material_stock.id_metric, raw_material.id ORDER BY raw_material.id DESC";
            
        
            $allRaw1 = mysqli_query($db_conn, $qRaw1);
            $allRaw2 = mysqli_query($db_conn, $qRaw2);
            if (mysqli_num_rows($allRaw1) > 0 || mysqli_num_rows($allRaw2) > 0) {
                if (mysqli_num_rows($allRaw1) > 0) {
                    $all_raw1 = mysqli_fetch_all($allRaw1, MYSQLI_ASSOC);
                }
                if (mysqli_num_rows($allRaw2) > 0) {
                    $res = mysqli_fetch_all($allRaw2, MYSQLI_ASSOC);
                    $data = array();
                    $data['id'] = 0;
                    $data['name'] = "";
                    $data['value'] = "0";
                    $data['stock'] = "0";
                    $data['unit_price'] = "0";
                    $data['id_metric'] = "0";
                    $data['metric_name'] = "";
                    $i = 0;
                    $compare = 0;
                    foreach ($res as $value) {
                        if($compare==0){
                            if($value['id_metric']==$value['id_metric_price']){
                                $res1[$i]=$value;
                            }else{
                                $idm = $value['id_metric'];
                                $idmp = $value['id_metric_price'];
                                $mConv = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1='$idm' AND id_metric2='$idmp'"); 
                                if (mysqli_num_rows($mConv) > 0) {
                                    $resMC = mysqli_fetch_all($mConv, MYSQLI_ASSOC);
                                    $value['stock']=$value['stock']*$resMC[0]['value'];
                                    $value['value']=$value['stock']*$value['unit_price'];
                                    $value['id_metric']=$idmp;
                                }else{
                                    $mConv = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1='$idmp' AND id_metric2='$idm'"); 
                                    if (mysqli_num_rows($mConv) > 0) {
                                        $resMC = mysqli_fetch_all($mConv, MYSQLI_ASSOC);
                                        $value['stock']=$value['stock']/$resMC[0]['value'];
                                        $value['value']=$value['stock']*$value['unit_price'];
                                        $value['id_metric']=$idmp;
                                    }
                                    $res1[$i]=$value;
                                }
                            }
                        }else{
                            if($compare==$value['id']){
                               if($value['id_metric']==$value['id_metric_price']){
                                    $res1[$i]['stock']+=$value['stock'];
                                    $res1[$i]['value']+=$value['stock']*$value['unit_price'];
                                }else{
                                    $idm = $value['id_metric'];
                                    $idmp = $value['id_metric_price'];
                                    $mConv = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1='$idm' AND id_metric2='$idmp'"); 
                                    if (mysqli_num_rows($mConv) > 0) {
                                        $resMC = mysqli_fetch_all($mConv, MYSQLI_ASSOC);
                                        $value['stock']=$value['stock']*$resMC[0]['value'];
                                        $value['value']=$value['stock']*$value['unit_price'];
                                        $value['id_metric']=$idmp;
                                    }else{
                                        $mConv = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1='$idmp' AND id_metric2='$idm'"); 
                                        if (mysqli_num_rows($mConv) > 0) {
                                            $resMC = mysqli_fetch_all($mConv, MYSQLI_ASSOC);
                                            $value['stock']=$value['stock']/$resMC[0]['value'];
                                            $value['value']=$value['stock']*$value['unit_price'];
                                            $value['id_metric']=$idmp;
                                        }
                                    }
                                    $res1[$i]['stock']+=$value['stock'];
                                    $res1[$i]['value']+=$value['value'];
                                } 
                            }else{
                                $i++;
                                if($value['id_metric']==$value['id_metric_price']){
                                    $res1[$i]=$value;
                                }else{
                                    $idm = $value['id_metric'];
                                    $idmp = $value['id_metric_price'];
                                    $mConv = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1='$idm' AND id_metric2='$idmp'"); 
                                    if (mysqli_num_rows($mConv) > 0) {
                                        $resMC = mysqli_fetch_all($mConv, MYSQLI_ASSOC);
                                        $value['stock']=$value['stock']*$resMC[0]['value'];
                                        $value['value']=$value['stock']*$value['unit_price'];
                                        $value['id_metric']=$idmp;
                                    }else{
                                        $mConv = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1='$idmp' AND id_metric2='$idm'"); 
                                        if (mysqli_num_rows($mConv) > 0) {
                                            $resMC = mysqli_fetch_all($mConv, MYSQLI_ASSOC);
                                            $value['stock']=$value['stock']/$resMC[0]['value'];
                                            $value['value']=$value['stock']*$value['unit_price'];
                                            $value['id_metric']=$idmp;
                                        }
                                    }
                                    $res1[$i]=$value;
                                }
                            }
                        }
                        $compare=$value['id'];
                    }
                    foreach ($res1 as $key => $row) {
                        $valueS[$key]  = $row['value'];
                        $label[$key] = $row['name'];
                    }
                    
                    // you can use array_column() instead of the above code
                    $valueS  = array_column($res1, 'value');
                    $label = array_column($res1, 'name');
                    
                    // Sort the data with qty descending, label ascending
                    // Add $data as the last parameter, to sort by the common key
                    array_multisort($valueS, SORT_DESC, $label, SORT_ASC, $res1);
                }
            }
            
            $partner['raw'] = $res1;
            $partner['menu'] = $all_raw1;
            
            if(count($res1) > 0 || count($all_raw1) > 0) {
                array_push($array, $partner);
            }
        }
        
        $success=1;
        $status=200;
        $msg="Success";
    } else {
        $success=0;
        $status=203;
        $msg="Data not found";
    }
}

http_response_code($status);

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "data"=>$array]);
?>