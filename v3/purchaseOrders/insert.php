<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
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

$idInsert = 0;
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
}else{
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    $supplier_id = $obj['supplier_id'];
    $poNo = $obj['poNo'];
    $total = $obj['total'];
    $notes = $obj['notes'];
    $date = $obj['date'];
    $date_now = date("Y-m-d");
    $partnerID = $obj['partnerID'];
    
    // $validateNo = mysqli_query($db_conn, "SELECT no FROM purchase_orders WHERE master_id='$tokenDecoded->masterID' AND partner_id='$tokenDecoded->partnerID' AND no='$poNo' AND deleted_at IS NULL");
    $validateNo = mysqli_query($db_conn, "SELECT no FROM purchase_orders WHERE master_id='$tokenDecoded->masterID' AND partner_id='$partnerID' AND no='$poNo' AND deleted_at IS NULL");
    // validasi tanggal tidak boleh melebihi tanggal sekarang
    if ($date <= $date_now) {
        if(mysqli_num_rows($validateNo)==0){
            // $sql = mysqli_query($db_conn, "INSERT INTO `purchase_orders`(`master_id`, `partner_id`, `supplier_id`, `total`, `created_at`, `created_by`, `no`, `notes`) VALUES ('$tokenDecoded->masterID', '$tokenDecoded->partnerID', '$supplier_id', '$total', '$date', '$tokenDecoded->id', '$poNo', '$notes')");
            $sql = mysqli_query($db_conn, "INSERT INTO `purchase_orders`(`master_id`, `partner_id`, `supplier_id`, `total`, `created_at`, `created_by`, `no`, `notes`) VALUES ('$tokenDecoded->masterID', '$partnerID', '$supplier_id', '$total', '$date', '$tokenDecoded->id', '$poNo', '$notes')");
            if($sql){
                $id = mysqli_insert_id($db_conn);
                $idInsert = $id;
                $details = $obj['details'];
                foreach($details as $dt){
                    $metricID = $dt['metric_id'];
                    $qty = $dt['qty'];
                    $price = $dt['unit_price'];
                    if($dt['raw_id']==null){
                        $menuID = $dt['menu_id'];
                        $sql = mysqli_query($db_conn, "INSERT INTO `purchase_orders_details`(`purchase_order_id`, `menu_id`, `qty`, `metric_id`, `price`, `created_at`) VALUES ('$id', '$menuID', '$qty', '$metricID', '$price', NOW())");
                        // $poQ = mysqli_query($db_conn, "SELECT SUM(price/qty) AS total, COUNT(id) AS counter FROM `purchase_orders_details` WHERE menu_id='$menuID' GROUP BY menu_id");
                        // $resQ = mysqli_fetch_all($poQ, MYSQLI_ASSOC);
                        // $total = (float)$resQ[0]['total'];
                        // $counter = (int)$resQ[0]['counter'];
                        // $cogs = ceil($total/$counter);
                        // $updateMenu = mysqli_query($db_conn, "UPDATE `menu` SET `hpp`='$cogs' WHERE id='$menuID'");

                    }else{
                        $rawID = $dt['raw_id'];
                        $sql = mysqli_query($db_conn, "INSERT INTO `purchase_orders_details`(`purchase_order_id`, `raw_id`, `qty`, `metric_id`, `price`, `created_at`) VALUES ('$id', '$rawID', '$qty', '$metricID', '$price', NOW())");
                        // $getMenu = mysqli_query($db_conn, "SELECT id_menu FROM recipe WHERE id_raw='$rawID'");
                        // $menus = mysqli_fetch_all($getMenu, MYSQLI_ASSOC);
                        // foreach ($menus as $menu) {
                        //     $cogs = 0;
                        //     $rawPrices = 0;
                        //     $menuID = $menu['id_menu'];
                        //     $getRecipe = mysqli_query($db_conn, "SELECT id_raw, qty,id_metric FROM recipe WHERE id_menu='$menuID'");
                        //     $recipe = mysqli_fetch_all($getRecipe, MYSQLI_ASSOC);
                        //     foreach ($recipe as $raw) {
                        //         $rawPrice = 0;
                        //         $recipeRawID = $raw['id_raw'];
                        //         $getRaw = mysqli_query($db_conn, "SELECT unit_price, id_metric_price FROM `raw_material` WHERE id='$recipeRawID'");
                        //         $raws = mysqli_fetch_all($getRaw, MYSQLI_ASSOC);
                        //         $price = (float) $raws[0]['unit_price'];
                        //         $id_metric = $raws[0]['id_metric_price'];
                        //         $getPO = mysqli_query($db_conn, "SELECT qty, metric_id, price FROM `purchase_orders_details` WHERE raw_id='$recipeRawID' AND deleted_at IS NULL");
                        //         $po = mysqli_fetch_all($getPO, MYSQLI_ASSOC);
                        //         $i = 0;

                        //         $poMetricID = 0;
                        //         $poQty = 0;
                        //         $poPrice = 0;
                        //         foreach ($po as $item) {
                        //             if($i==0){
                        //                 $poMetricID = $item['metric_id'];
                        //                 $poQty =(float) $item['qty'];
                        //                 $poPrice =(float) $item['price'];
                        //             }else{
                        //                 $poPrice +=(float) $item['price'];
                        //                 if($poMetricID==$item['metric_id']){
                        //                     $poQty +=(float) $item['qty'];
                        //                 }else{
                        //                     $findMetricID = $item['metric_id'];
                        //                     $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$poMetricID' AND `id_metric2`='$findMetricID'");
                        //                     if (mysqli_num_rows($getMC) > 0) {
                        //                         $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                        //                         $poMetricID = $findMetricID;
                        //                         $conVal = (int) $mc[0]['value'];
                        //                         $poQty =$poQty*$conVal;
                        //                         $poQty +=(float) $item['qty'];
                        //                     }else{
                        //                         $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$findMetricID' AND `id_metric2`='$poMetricID'");
                        //                         $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                        //                         $conVal = (int) $mc[0]['value'];
                        //                         $item['qty'] =$item['qty']*$conVal;
                        //                         $poQty +=(float) $item['qty'];
                        //                     }
                        //                 }
                        //             }
                        //             $i+=1;
                        //         }
                        //         if($poQty>0 && $poPrice>0){
                        //             $rawPrice = $poPrice/$poQty;
                        //         }
                        //         $rawMetric = $poMetricID;
                        //         if($rawMetric==$id_metric || $poMetricID==0){
                        //             $rawPrice += $price;
                        //             if($poMetricID!=0){
                        //                 $rawPrice = $rawPrice/2;
                        //             }else{
                        //                 $rawMetric = $id_metric;
                        //             }
                        //         }else{
                        //             $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$rawMetric' AND `id_metric2`='$id_metric'");
                        //             if (mysqli_num_rows($getMC) > 0) {
                        //                 $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                        //                 $rawMetric = $id_metric;
                        //                 $conVal = (int) $mc[0]['value'];
                        //                 $rawPrice =$rawPrice/$conVal;
                        //                 $rawPrice+=$price;
                        //                 $rawPrice = $rawPrice/2;
                        //             }else{
                        //                 $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$id_metric' AND `id_metric2`='$rawMetric'");
                        //                 $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                        //                 $rawMetric = $id_metric;
                        //                 $conVal = (int) $mc[0]['value'];
                        //                 $price =$price/$conVal;
                        //                 $rawPrice+=$price;
                        //                 $rawPrice = $rawPrice/2;
                        //             }
                        //         }
                        //         if($rawMetric==$raw['id_metric']){
                        //             $rawPrices += $rawPrice*$raw['qty'];
                        //         }else{
                        //             $id_metric=$raw['id_metric'];
                        //             $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$rawMetric' AND `id_metric2`='$id_metric'");
                        //             if (mysqli_num_rows($getMC) > 0) {
                        //                 $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                        //                 $conVal = (int) $mc[0]['value'];
                        //                 $rawPrices +=$rawPrice/$conVal*$raw['qty'];
                        //             }else{
                        //                 $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$id_metric' AND `id_metric2`='$rawMetric'");
                        //                 $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                        //                 $conVal = (int) $mc[0]['value'];
                        //                 $rawPrices +=$rawPrice*$conVal*$raw['qty'];
                        //             }
                        //         }
                        //         $cogs=ceil($rawPrices);
                        //     }
                        //     $updateMenu = mysqli_query($db_conn, "UPDATE `menu` SET `hpp`='$cogs' WHERE id='$menuID'");
                        // }

                    }

                }
                $success=1;
                $msg="Berhasil buat PO";
                $status=200;
            }else{
                $success=0;
                $msg="Gagal buat PO. Mohon coba lagi";
                $status=400;
            }
        } else{
            $success=0;
            $msg="Nomor PO sudah pernah digunakan. Mohon gunakan nomor lain";
            $status=400;
        }
    } else {
        $success=0;
        $msg="Tanggal tidak boleh melebihi hari ini!";
        $status=400;
    }
}

echo json_encode(["msg"=>$msg, "status"=>$status,"success"=>$success, "idInsert"=>$idInsert]);

 ?>
