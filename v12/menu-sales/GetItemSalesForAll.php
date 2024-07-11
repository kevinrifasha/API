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
// $rx_http = '/\AHTTP_/';
// foreach ($_SERVER as $key => $val) {
//   if (preg_match($rx_http, $key)) {
//     $arh_key = preg_replace($rx_http, '', $key);
//     $rx_matches = array();
//     // do some nasty string manipulations to restore the original letter case
//     // this should work in most cases
//     $rx_matches = explode('_', $arh_key);
//     if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
//       foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
//       $arh_key = implode('-', $rx_matches);
//     }
//     $headers[$arh_key] = $val;
//   }
// }
// $token = '';

// foreach ($headers as $header => $value) {
//   if ($header == "Authorization" || $header == "AUTHORIZATION") {
//     $token = substr($value, 7);
//   }
// }

// $db = connectBase();
// $tokenizer = new TokenManager($db);
// $tokens = $tokenizer->validate($token);
// $tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt', $token));
// $idMaster = $tokenDecoded->masterID;
// $value = array();
// $success = 0;
// $msg = 'Failed';

// if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

//   $status = $tokens['status'];
//   $msg = $tokens['msg'];
//   $success = 0;
//   $getMenu;
// } else {
//   $i = 0;
//   $id = $_GET['id'];
//   $dateTo = $_GET['dateTo'];
//   $dateFrom = $_GET['dateFrom'];
//   $total = 0;
//   $totalS = 0;
//   $dataIDT = "";
//   $qMenu = "";
  
//     $data = [];
    
//     $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
//     if(mysqli_num_rows($sqlPartner) > 0) {
//         $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
        
//         foreach($getPartners as $partner) {
//             $id = $partner['partner_id'];
//             $arr = [];
//             $diskon_promo = 0;
//             $diskon_karyawan = 0;
//             $diskon_spesial = 0;
//             $diskon_program = 0;
            
//             $qMenu = "SELECT m.id, m.nama, m.hpp, c.name AS cName, m.sku, m.id_partner FROM menu m JOIN categories c ON m.id_category = c.id WHERE m.id_partner = '$id' AND EXISTS (SELECT dt.id_menu FROM detail_transaksi dt
//                 JOIN transaksi t ON dt.id_transaksi = t.id 
//                 WHERE dt.id_menu = m.id
//                 AND DATE(t.paid_date) BETWEEN '$dateFrom'
//                 AND '$dateTo'
//                 AND dt.deleted_at IS NULL
//                 AND dt.status != 4
//                 AND t.status IN(1,2,3,4))";
              
//               $getMenu = mysqli_query($db_conn, $qMenu);
//               while ($row = mysqli_fetch_assoc($getMenu)) {
//                 $cancellation = 0;
//                 $portion = 0;
//                 $empDisc = 0;
//                 $discount = 0.00;
//                 $sales = 0;
//                 $qty = 0;
//                 $unitPrice = 0;
//                 $menuID = $row['id'];
//                 $partnerID = $row['id_partner'];
//                 $sales = 0;
//                 $grossMargin = 0;
//                 $grossProfit = 0;
//                 $cogs = 0;
//                 $refund = 0;
            
//                 $getTransactions = mysqli_query($db_conn, "SELECT dt.harga AS sales, dt.qty AS qty, dt.harga_satuan AS unitPrice, t.employee_discount AS empDisc, t.status AS transc_status, t.total FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id WHERE t.id_partner='$partnerID' AND t.deleted_at IS NULL AND dt.deleted_at IS NULL AND dt.status!=4 AND t.status IN(1,2) AND dt.id_menu='$menuID' AND DATE(t.paid_date) BETWEEN '$dateFrom' AND '$dateTo'");
//                 while ($rowTrx = mysqli_fetch_assoc($getTransactions)) {
//                   $portion = $rowTrx['sales'] / $rowTrx['total'];
//                   $empDisc = (float)$rowTrx['empDisc'];
//                   // sementara (int) dulu saja nanti cari cara supaya bisa (double)
//                   $discount += (int)($portion * $empDisc);
//                   $sales += $rowTrx['sales'];
//                   $qty += $rowTrx['qty'];
//                   $unitPrice = $rowTrx['unitPrice'];
//                   if ($rowTrx['transc_status'] == 4) {
//                     $refund += $rowTrx['sales'];
//                   }
//                 }
            
//                 $i++;
//                 $getVoided = mysqli_query($db_conn, "SELECT
//                   IFNULL(SUM(tc.qty),0) AS qty
//                 FROM
//                   transaction_cancellation tc
//                   JOIN detail_transaksi dt ON dt.id = tc.detail_transaction_id
//                   JOIN shift s ON s.id = tc.shift_id
//                   LEFT JOIN transaksi t ON dt.id_transaksi = t.id
//                 WHERE
//                   tc.deleted_at IS NULL
//                   AND dt.id_menu='$menuID'
//                   AND tc.transaction_id IS NOT NULL
//                   AND DATE(tc.created_at) BETWEEN '$dateFrom'
//                   AND '$dateTo'
//                   AND t.status NOT IN (5,6,7)
//                   AND s.partner_id = '$partnerID'
//                 UNION ALL
//                 SELECT
//                   IFNULL(SUM(dt.qty),0) AS qty
//                 FROM
//                   transaction_cancellation tc
//                   JOIN transaksi t ON t.id = tc.transaction_id
//                   JOIN detail_transaksi dt ON dt.id_transaksi = t.id
//                   JOIN shift s ON s.id = tc.shift_id
//                 WHERE
//                   tc.deleted_at IS NULL
//                   AND dt.id_menu='$menuID'
//                   AND t.status=4
//                   AND DATE(tc.created_at) BETWEEN '$dateFrom'
//                   AND '$dateTo'
//                   AND s.partner_id = '$partnerID'");
//                 $voided = mysqli_fetch_all($getVoided, MYSQLI_ASSOC);
//                 $cancellation = ($voided[0]['qty']) + ($voided[1]['qty']);
//                 while ($rowV = mysqli_fetch_assoc($getVoided)) {
//                   $cancellation += (int)$rowV['qty'];
//                 }
//                 $cancellation = (int)$voided[0]['qty'] + (int)$voided[1]['qty'];
//                 //   $totalRefund = $cancellation * $unitPrice;
//                 $totalRefund = $refund;
//                 // get cancellation end
//                 $cogs = $row['hpp'] * ($qty - $cancellation);
//                 $grossProfit = $sales - $discount - ($cancellation * $unitPrice) - $cogs;
//                 $grossMarginDivider = $sales - $discount - ($cancellation * $unitPrice);
//                 //   $grossMargin = $grossProfit/($sales-$discount-($cancellation*$unitPrice)) * 100;
//                 if ($grossMarginDivider == 0 || $grossMarginDivider < 0) {
//                   $grossMarginDivider = 1;
//                 }
//                 $grossMargin = $grossProfit / ($grossMarginDivider) * 100;
            
            
//                 array_push($arr, array("name" => $row['nama'], "category" => $row['cName'], "qty" => $qty, "sales" => $sales, "id_menu" => $menuID, "sku" => $row['sku'], "diskon_promo" => $diskon_promo, "diskon_karyawan" => $diskon_karyawan, "diskon_spesial" => $diskon_spesial, "hpp_menu" => $row['hpp'], "harga_satuan" => $unitPrice, "totalRefund" => $totalRefund, "refundQty" => $cancellation, "totalDiskon" => $discount, "diskon_program" => $diskon_program, "grossProfit" => $grossProfit, "grossMargin" => $grossMargin, "cogs" => $cogs));
//               }
              
//               $partner['itemSales'] = $arr;
              
//               if(count($arr) > 0) {
//                 array_push($data, $partner);
//               }
            
//         }
        
//       $success = 1;
//       $status = 200;
//       $msg = "success";
//     } else {
//       $success = 0;
//       $status = 203;
//       $msg = "Data not found";
//     }
// }

// echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "data" => $data]);

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
foreach ($_SERVER as $key => $val) {
  if (preg_match($rx_http, $key)) {
    $arh_key = preg_replace($rx_http, '', $key);
    $rx_matches = array();
    // do some nasty string manipulations to restore the original letter case
    // this should work in most cases
    $rx_matches = explode('_', $arh_key);
    if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
      foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
      $arh_key = implode('-', $rx_matches);
    }
    $headers[$arh_key] = $val;
  }
}
$token = '';

foreach ($headers as $header => $value) {
  if ($header == "Authorization" || $header == "AUTHORIZATION") {
    $token = substr($value, 7);
  }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt', $token));
$idMaster = $tokenDecoded->masterID;
$value = array();
$success = 0;
$msg = 'Failed';

if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

  $status = $tokens['status'];
  $msg = $tokens['msg'];
  $success = 0;
  $getMenu;
} else {
  $i = 0;
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
    $dataIDT = "";
    $qMenu = "";

    $data = [];
    
    if($newDateFormat == 1)
    {
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $id = $partner['partner_id'];
                $arr = [];
                $diskon_promo = 0;
                $diskon_karyawan = 0;
                $diskon_spesial = 0;
                $diskon_program = 0;
                
                $qMenu = "SELECT m.id, m.nama, m.hpp, c.name AS cName, m.sku, m.id_partner FROM menu m JOIN categories c ON m.id_category = c.id WHERE m.id_partner = '$id' AND EXISTS (SELECT dt.id_menu FROM detail_transaksi dt
                    JOIN transaksi t ON dt.id_transaksi = t.id 
                    WHERE dt.id_menu = m.id
                    AND t.paid_date BETWEEN '$dateFrom'
                    AND '$dateTo'
                    AND dt.deleted_at IS NULL
                    AND dt.status != 4
                    AND t.status IN(1,2,3,4))";
                  
                  $getMenu = mysqli_query($db_conn, $qMenu);
                  while ($row = mysqli_fetch_assoc($getMenu)) {
                    $cancellation = 0;
                    $portion = 0;
                    $empDisc = 0;
                    $discount = 0.00;
                    $sales = 0;
                    $qty = 0;
                    $unitPrice = 0;
                    $menuID = $row['id'];
                    $partnerID = $row['id_partner'];
                    $sales = 0;
                    $grossMargin = 0;
                    $grossProfit = 0;
                    $cogs = 0;
                    $refund = 0;
                
                    $getTransactions = mysqli_query($db_conn, "SELECT dt.harga AS sales, dt.qty AS qty, dt.harga_satuan AS unitPrice, t.employee_discount AS empDisc, t.status AS transc_status, t.total FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id WHERE t.id_partner='$partnerID' AND t.deleted_at IS NULL AND dt.deleted_at IS NULL AND dt.status!=4 AND t.status IN(1,2) AND dt.id_menu='$menuID' AND t.paid_date BETWEEN '$dateFrom' AND '$dateTo'");
                    while ($rowTrx = mysqli_fetch_assoc($getTransactions)) {
                      $portion = $rowTrx['sales'] / $rowTrx['total'];
                      $empDisc = (float)$rowTrx['empDisc'];
                      // sementara (int) dulu saja nanti cari cara supaya bisa (double)
                      $discount += (int)($portion * $empDisc);
                      $sales += $rowTrx['sales'];
                      $qty += $rowTrx['qty'];
                      $unitPrice = $rowTrx['unitPrice'];
                      if ($rowTrx['transc_status'] == 4) {
                        $refund += $rowTrx['sales'];
                      }
                    }
                
                    $i++;
                    $getVoided = mysqli_query($db_conn, "SELECT
                      IFNULL(SUM(tc.qty),0) AS qty
                    FROM
                      transaction_cancellation tc
                      JOIN detail_transaksi dt ON dt.id = tc.detail_transaction_id
                      JOIN shift s ON s.id = tc.shift_id
                      LEFT JOIN transaksi t ON dt.id_transaksi = t.id
                    WHERE
                      tc.deleted_at IS NULL
                      AND dt.id_menu='$menuID'
                      AND tc.transaction_id IS NOT NULL
                      AND tc.created_at BETWEEN '$dateFrom'
                      AND '$dateTo'
                      AND t.status NOT IN (5,6,7)
                      AND s.partner_id = '$partnerID'
                    UNION ALL
                    SELECT
                      IFNULL(SUM(dt.qty),0) AS qty
                    FROM
                      transaction_cancellation tc
                      JOIN transaksi t ON t.id = tc.transaction_id
                      JOIN detail_transaksi dt ON dt.id_transaksi = t.id
                      JOIN shift s ON s.id = tc.shift_id
                    WHERE
                      tc.deleted_at IS NULL
                      AND dt.id_menu='$menuID'
                      AND t.status=4
                      AND tc.created_at BETWEEN '$dateFrom'
                      AND '$dateTo'
                      AND s.partner_id = '$partnerID'");
                    $voided = mysqli_fetch_all($getVoided, MYSQLI_ASSOC);
                    $cancellation = ($voided[0]['qty']) + ($voided[1]['qty']);
                    while ($rowV = mysqli_fetch_assoc($getVoided)) {
                      $cancellation += (int)$rowV['qty'];
                    }
                    $cancellation = (int)$voided[0]['qty'] + (int)$voided[1]['qty'];
                    //   $totalRefund = $cancellation * $unitPrice;
                    $totalRefund = $refund;
                    // get cancellation end
                    $cogs = $row['hpp'] * ($qty - $cancellation);
                    $grossProfit = $sales - $discount - ($cancellation * $unitPrice) - $cogs;
                    $grossMarginDivider = $sales - $discount - ($cancellation * $unitPrice);
                    //   $grossMargin = $grossProfit/($sales-$discount-($cancellation*$unitPrice)) * 100;
                    if ($grossMarginDivider == 0 || $grossMarginDivider < 0) {
                      $grossMarginDivider = 1;
                    }
                    $grossMargin = $grossProfit / ($grossMarginDivider) * 100;
                
                
                    array_push($arr, array("name" => $row['nama'], "category" => $row['cName'], "qty" => $qty, "sales" => $sales, "id_menu" => $menuID, "sku" => $row['sku'], "diskon_promo" => $diskon_promo, "diskon_karyawan" => $diskon_karyawan, "diskon_spesial" => $diskon_spesial, "hpp_menu" => $row['hpp'], "harga_satuan" => $unitPrice, "totalRefund" => $totalRefund, "refundQty" => $cancellation, "totalDiskon" => $discount, "diskon_program" => $diskon_program, "grossProfit" => $grossProfit, "grossMargin" => $grossMargin, "cogs" => $cogs));
                  }
                  
                  $partner['itemSales'] = $arr;
                  
                  if(count($arr) > 0) {
                    array_push($data, $partner);
                  }
                
            }
            
          $success = 1;
          $status = 200;
          $msg = "success";
        } else {
          $success = 0;
          $status = 203;
          $msg = "Data not found";
        }
    }

    else
    {
        $sqlPartner = mysqli_query($db_conn, "SELECT id AS partner_id, name AS partner_name FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        if(mysqli_num_rows($sqlPartner) > 0) {
            $getPartners = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            
            foreach($getPartners as $partner) {
                $id = $partner['partner_id'];
                $arr = [];
                $diskon_promo = 0;
                $diskon_karyawan = 0;
                $diskon_spesial = 0;
                $diskon_program = 0;
                
                $qMenu = "SELECT m.id, m.nama, m.hpp, c.name AS cName, m.sku, m.id_partner FROM menu m JOIN categories c ON m.id_category = c.id WHERE m.id_partner = '$id' AND EXISTS (SELECT dt.id_menu FROM detail_transaksi dt
                    JOIN transaksi t ON dt.id_transaksi = t.id 
                    WHERE dt.id_menu = m.id
                    AND DATE(t.paid_date) BETWEEN '$dateFrom'
                    AND '$dateTo'
                    AND dt.deleted_at IS NULL
                    AND dt.status != 4
                    AND t.status IN(1,2,3,4))";
                
                $getMenu = mysqli_query($db_conn, $qMenu);
                while ($row = mysqli_fetch_assoc($getMenu)) {
                    $cancellation = 0;
                    $portion = 0;
                    $empDisc = 0;
                    $discount = 0.00;
                    $sales = 0;
                    $qty = 0;
                    $unitPrice = 0;
                    $menuID = $row['id'];
                    $partnerID = $row['id_partner'];
                    $sales = 0;
                    $grossMargin = 0;
                    $grossProfit = 0;
                    $cogs = 0;
                    $refund = 0;
                
                    $getTransactions = mysqli_query($db_conn, "SELECT dt.harga AS sales, dt.qty AS qty, dt.harga_satuan AS unitPrice, t.employee_discount AS empDisc, t.status AS transc_status, t.total FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi=t.id WHERE t.id_partner='$partnerID' AND t.deleted_at IS NULL AND dt.deleted_at IS NULL AND dt.status!=4 AND t.status IN(1,2) AND dt.id_menu='$menuID' AND DATE(t.paid_date) BETWEEN '$dateFrom' AND '$dateTo'");
                    while ($rowTrx = mysqli_fetch_assoc($getTransactions)) {
                    $portion = $rowTrx['sales'] / $rowTrx['total'];
                    $empDisc = (float)$rowTrx['empDisc'];
                    // sementara (int) dulu saja nanti cari cara supaya bisa (double)
                    $discount += (int)($portion * $empDisc);
                    $sales += $rowTrx['sales'];
                    $qty += $rowTrx['qty'];
                    $unitPrice = $rowTrx['unitPrice'];
                    if ($rowTrx['transc_status'] == 4) {
                        $refund += $rowTrx['sales'];
                    }
                    }
                
                    $i++;
                    $getVoided = mysqli_query($db_conn, "SELECT
                    IFNULL(SUM(tc.qty),0) AS qty
                    FROM
                    transaction_cancellation tc
                    JOIN detail_transaksi dt ON dt.id = tc.detail_transaction_id
                    JOIN shift s ON s.id = tc.shift_id
                    LEFT JOIN transaksi t ON dt.id_transaksi = t.id
                    WHERE
                    tc.deleted_at IS NULL
                    AND dt.id_menu='$menuID'
                    AND tc.transaction_id IS NOT NULL
                    AND DATE(tc.created_at) BETWEEN '$dateFrom'
                    AND '$dateTo'
                    AND t.status NOT IN (5,6,7)
                    AND s.partner_id = '$partnerID'
                    UNION ALL
                    SELECT
                    IFNULL(SUM(dt.qty),0) AS qty
                    FROM
                    transaction_cancellation tc
                    JOIN transaksi t ON t.id = tc.transaction_id
                    JOIN detail_transaksi dt ON dt.id_transaksi = t.id
                    JOIN shift s ON s.id = tc.shift_id
                    WHERE
                    tc.deleted_at IS NULL
                    AND dt.id_menu='$menuID'
                    AND t.status=4
                    AND DATE(tc.created_at) BETWEEN '$dateFrom'
                    AND '$dateTo'
                    AND s.partner_id = '$partnerID'");
                    $voided = mysqli_fetch_all($getVoided, MYSQLI_ASSOC);
                    $cancellation = ($voided[0]['qty']) + ($voided[1]['qty']);
                    while ($rowV = mysqli_fetch_assoc($getVoided)) {
                    $cancellation += (int)$rowV['qty'];
                    }
                    $cancellation = (int)$voided[0]['qty'] + (int)$voided[1]['qty'];
                    //   $totalRefund = $cancellation * $unitPrice;
                    $totalRefund = $refund;
                    // get cancellation end
                    $cogs = $row['hpp'] * ($qty - $cancellation);
                    $grossProfit = $sales - $discount - ($cancellation * $unitPrice) - $cogs;
                    $grossMarginDivider = $sales - $discount - ($cancellation * $unitPrice);
                    //   $grossMargin = $grossProfit/($sales-$discount-($cancellation*$unitPrice)) * 100;
                    if ($grossMarginDivider == 0 || $grossMarginDivider < 0) {
                    $grossMarginDivider = 1;
                    }
                    $grossMargin = $grossProfit / ($grossMarginDivider) * 100;
                
                
                    array_push($arr, array("name" => $row['nama'], "category" => $row['cName'], "qty" => $qty, "sales" => $sales, "id_menu" => $menuID, "sku" => $row['sku'], "diskon_promo" => $diskon_promo, "diskon_karyawan" => $diskon_karyawan, "diskon_spesial" => $diskon_spesial, "hpp_menu" => $row['hpp'], "harga_satuan" => $unitPrice, "totalRefund" => $totalRefund, "refundQty" => $cancellation, "totalDiskon" => $discount, "diskon_program" => $diskon_program, "grossProfit" => $grossProfit, "grossMargin" => $grossMargin, "cogs" => $cogs));
                }
                
                $partner['itemSales'] = $arr;
                
                if(count($arr) > 0) {
                    array_push($data, $partner);
                }
                
            }
            
        $success = 1;
        $status = 200;
        $msg = "success";
        } else {
        $success = 0;
        $status = 203;
        $msg = "Data not found";
        }
    }


}

echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "data" => $data]);
