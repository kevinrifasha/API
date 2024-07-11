<?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: access");
// header("Access-Control-Allow-Methods: GET");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// require_once("./../tokenModels/tokenManager.php");
// require_once("../connection.php");
// require '../../db_connection.php';
// require  __DIR__ . '/../../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
// $dotenv->load();

// $headers = array();
//     $rx_http = '/\AHTTP_/';
//     foreach($_SERVER as $key => $val) {
//       if( preg_match($rx_http, $key) ) {
//         $arh_key = preg_replace($rx_http, '', $key);
//         $rx_matches = array();
//         // do some nasty string manipulations to restore the original letter case
//         // this should work in most cases
//         $rx_matches = explode('_', $arh_key);
//         if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
//           foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
//           $arh_key = implode('-', $rx_matches);
//         }
//         $headers[$arh_key] = $val;
//       }
//     }
// $token = '';

// foreach ($headers as $header => $value) {
//     if($header=="Authorization" || $header=="AUTHORIZATION"){
//         $token=substr($value,7);
//     }
// }

// $db = connectBase();
// $tokenizer = new TokenManager($db);
// $tokens = $tokenizer->validate($token);
// $tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
// $value = array();
// $success=0;
// $msg = 'Failed';
// if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

//     $status = $tokens['status'];
//     $msg = $tokens['msg'];
//     $success = 0;

// }else{
//     $arr = [];
//     $i=0;
//     $id = $_GET['id'];
//     $dateTo = $_GET['dateTo'];
//     $dateFrom = $_GET['dateFrom'];
//     $total = 0;
//     $totalS = 0;
//     $reportV = array();
//     $totalQty = 0;
//     $fav = mysqli_query($db_conn, "SELECT detail_transaksi.variant, menu.nama, detail_transaksi.qty FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE partner.id= '$id'  AND transaksi.status IN (1,2) AND DATE(transaksi.jam) BETWEEN '$dateFrom' AND '$dateTo' ");
//     if(mysqli_num_rows($fav)>0){
//         while ($rowMenu = mysqli_fetch_assoc($fav)) {
//             $variant = $rowMenu['variant'];
//             $namaMenu = $rowMenu['nama'];
//             $qty = (int)$rowMenu['qty'];
//             $variant = substr($variant, 1, -1);
//             $var = "{" . $variant . "}";
//             $var = str_replace("'", '"', $var);
//             $var1 = json_decode($var, true);

//             if (
//             isset($var1['variant'])
//             && !empty($var1['variant'])
//             ) {
//             $arrVar = $var1['variant'];
//             foreach ($arrVar as $arr) {
//                 $v_id=0;
//                 $vg_name = $arr['name'];
//                 $detail = $arr['detail'];
//                 $v_qty = 0;
//                 foreach ($detail as $det) {
//                 $v_name =  $det['name'];
//                 $v_qty += $qty;
//                 $idx = 0;
//                 foreach ($reportV as $value) {
//                     if($value['id']==$det['id'] && $value['name']==$v_name && $value['vg_name']==$vg_name && $value['menu_name']==$namaMenu){
//                         $idx=$idx;
//                         break;
//                     }else{
//                         $idx+=1;
//                     }
//                 }
//                 $v_id=$idx;
//                 $dic[$v_id]=$det['id'];
//                 $reportV[$v_id]['id'] = $det['id'];
//                 $reportV[$v_id]['qty']? $reportV[$v_id]['qty']+= $v_qty:$reportV[$v_id]['qty']=$v_qty;
//                 $reportV[$v_id]['name'] = $v_name;
//                 $reportV[$v_id]['vg_name'] = $vg_name;
//                 $reportV[$v_id]['menu_name'] = $namaMenu;
//                 $reportV[$v_id]['reportName'] = $namaMenu."-".$vg_name."-".$v_name;
//                 $totalQty+=$v_qty;
//                 }
//             }
//             foreach ($reportV as $key => $row) {
//                 $qty[$key]  = $row['qty'];
//                 $menu_name[$key] = $row['menu_name'];
//             }

//             // you can use array_column() instead of the above code
//             $qty  = array_column($reportV, 'qty');
//             $menu_name = array_column($reportV, 'menu_name');

//             // Sort the data with qty descending, menu_name ascending
//             // Add $data as the last parameter, to sort by the common key
//             array_multisort($qty, SORT_DESC, $menu_name, SORT_ASC, $reportV);
//             }

//         }
//         $success = 1;
//         $status = 200;
//         $msg = "Success";
//     }else{
//         $success = 0;
//         $status = 204;
//         $msg = "Data Not Found";
//     }
// }

// echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "variantSales"=>$reportV, "totalQty"=>$totalQty]);


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';
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
    $arr = [];
    $i=0;
    $id = $_GET['id'];
    $dateTo = $_GET['dateTo'];
    $dateFrom = $_GET['dateFrom'];

    $newDateFormat = 0;

    if(strlen($dateTo) !== 10 && strlen($dateFrom) !== 10){
        $dateTo = str_replace("%20"," ",$dateTo);
        $dateFrom = str_replace("%20"," ",$dateFrom);
        $newDateFormat = 1;
    }

    $total = 0;
    $totalS = 0;
    $reportV = array();
    $totalQty = 0;

    if($newDateFormat == 1)
    {
        $fav = mysqli_query($db_conn, "SELECT detail_transaksi.variant, menu.nama, detail_transaksi.qty FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE partner.id= '$id'  AND transaksi.status IN (1,2) AND transaksi.jam BETWEEN '$dateFrom' AND '$dateTo' ");
        if(mysqli_num_rows($fav)>0){
            while ($rowMenu = mysqli_fetch_assoc($fav)) {
                $variant = $rowMenu['variant'];
                $namaMenu = $rowMenu['nama'];
                $qty = (int)$rowMenu['qty'];
                $variant = substr($variant, 1, -1);
                $var = "{" . $variant . "}";
                $var = str_replace("'", '"', $var);
                $var1 = json_decode($var, true);
    
                if (
                isset($var1['variant'])
                && !empty($var1['variant'])
                ) {
                $arrVar = $var1['variant'];
                foreach ($arrVar as $arr) {
                    $v_id=0;
                    $vg_name = $arr['name'];
                    $detail = $arr['detail'];
                    $v_qty = 0;
                    foreach ($detail as $det) {
                    $v_name =  $det['name'];
                    $v_qty += $qty;
                    $idx = 0;
                    foreach ($reportV as $value) {
                        if($value['id']==$det['id'] && $value['name']==$v_name && $value['vg_name']==$vg_name && $value['menu_name']==$namaMenu){
                            $idx=$idx;
                            break;
                        }else{
                            $idx+=1;
                        }
                    }
                    $v_id=$idx;
                    $dic[$v_id]=$det['id'];
                    $reportV[$v_id]['id'] = $det['id'];
                    $reportV[$v_id]['qty']? $reportV[$v_id]['qty']+= $v_qty:$reportV[$v_id]['qty']=$v_qty;
                    $reportV[$v_id]['name'] = $v_name;
                    $reportV[$v_id]['vg_name'] = $vg_name;
                    $reportV[$v_id]['menu_name'] = $namaMenu;
                    $reportV[$v_id]['reportName'] = $namaMenu."-".$vg_name."-".$v_name;
                    $totalQty+=$v_qty;
                    }
                }
                foreach ($reportV as $key => $row) {
                    $qty[$key]  = $row['qty'];
                    $menu_name[$key] = $row['menu_name'];
                }
    
                // you can use array_column() instead of the above code
                $qty  = array_column($reportV, 'qty');
                $menu_name = array_column($reportV, 'menu_name');
    
                // Sort the data with qty descending, menu_name ascending
                // Add $data as the last parameter, to sort by the common key
                array_multisort($qty, SORT_DESC, $menu_name, SORT_ASC, $reportV);
                }
    
            }
            $success = 1;
            $status = 200;
            $msg = "Success";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    }   
    else
    {
        $fav = mysqli_query($db_conn, "SELECT detail_transaksi.variant, menu.nama, detail_transaksi.qty FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE partner.id= '$id'  AND transaksi.status IN (1,2) AND DATE(transaksi.jam) BETWEEN '$dateFrom' AND '$dateTo' ");
        if(mysqli_num_rows($fav)>0){
            while ($rowMenu = mysqli_fetch_assoc($fav)) {
                $variant = $rowMenu['variant'];
                $namaMenu = $rowMenu['nama'];
                $qty = (int)$rowMenu['qty'];
                $variant = substr($variant, 1, -1);
                $var = "{" . $variant . "}";
                $var = str_replace("'", '"', $var);
                $var1 = json_decode($var, true);
    
                if (
                isset($var1['variant'])
                && !empty($var1['variant'])
                ) {
                $arrVar = $var1['variant'];
                foreach ($arrVar as $arr) {
                    $v_id=0;
                    $vg_name = $arr['name'];
                    $detail = $arr['detail'];
                    $v_qty = 0;
                    foreach ($detail as $det) {
                    $v_name =  $det['name'];
                    $v_qty += $qty;
                    $idx = 0;
                    foreach ($reportV as $value) {
                        if($value['id']==$det['id'] && $value['name']==$v_name && $value['vg_name']==$vg_name && $value['menu_name']==$namaMenu){
                            $idx=$idx;
                            break;
                        }else{
                            $idx+=1;
                        }
                    }
                    $v_id=$idx;
                    $dic[$v_id]=$det['id'];
                    $reportV[$v_id]['id'] = $det['id'];
                    $reportV[$v_id]['qty']? $reportV[$v_id]['qty']+= $v_qty:$reportV[$v_id]['qty']=$v_qty;
                    $reportV[$v_id]['name'] = $v_name;
                    $reportV[$v_id]['vg_name'] = $vg_name;
                    $reportV[$v_id]['menu_name'] = $namaMenu;
                    $reportV[$v_id]['reportName'] = $namaMenu."-".$vg_name."-".$v_name;
                    $totalQty+=$v_qty;
                    }
                }
                foreach ($reportV as $key => $row) {
                    $qty[$key]  = $row['qty'];
                    $menu_name[$key] = $row['menu_name'];
                }
    
                // you can use array_column() instead of the above code
                $qty  = array_column($reportV, 'qty');
                $menu_name = array_column($reportV, 'menu_name');
    
                // Sort the data with qty descending, menu_name ascending
                // Add $data as the last parameter, to sort by the common key
                array_multisort($qty, SORT_DESC, $menu_name, SORT_ASC, $reportV);
                }
    
            }
            $success = 1;
            $status = 200;
            $msg = "Success";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    }


}

echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "variantSales"=>$reportV, "totalQty"=>$totalQty]);
?>