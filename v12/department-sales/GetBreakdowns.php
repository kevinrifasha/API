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
//     if (preg_match($rx_http, $key)) {
//         $arh_key = preg_replace($rx_http, '', $key);
//         $rx_matches = array();
//         // do some nasty string manipulations to restore the original letter case
//         // this should work in most cases
//         $rx_matches = explode('_', $arh_key);
//         if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
//             foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
//             $arh_key = implode('-', $rx_matches);
//         }
//         $headers[$arh_key] = $val;
//     }
// }
// $token = '';

// foreach ($headers as $header => $value) {
//     if ($header == "Authorization" || $header == "AUTHORIZATION") {
//         $token = substr($value, 7);
//     }
// }

// $db = connectBase();
// $tokenizer = new TokenManager($db);
// $tokens = $tokenizer->validate($token);
// $tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt', $token));
// $idMaster = $tokenDecoded->masterID;
// $value = array();
// $success = 0;
// $msg = 'Failed';
// $all = 0;
// $result = [];
// $test = [];
// if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

//     $status = $tokens['status'];
//     $msg = $tokens['msg'];
//     $success = 0;
// } else {
//     $data = [];
//     $i = 0;
//     $id = $_GET['id'];
//     $dateTo = $_GET['dateTo'];
//     $dateFrom = $_GET['dateFrom'];
//     $total = 0;
//     if (isset($_GET['all'])) {
//         $all = $_GET['all'];
//     }
//     // if ($all !== "1") {
//     //     $idMaster = null;
//     // }
//     $query = "";

//     if ($all == "1") {
//         $query = "SELECT d.id, d.name, p.id AS id_partner FROM departments d JOIN partner p ON p.id = d.partner_id WHERE p.id_master = '$idMaster' AND d.deleted_at IS NULL";
//     } else {
//         // $query = "SELECT id, name FROM departments WHERE partner_id='$id' AND deleted_at IS NULL";
//         $query = "SELECT id, name FROM departments WHERE master_id='$idMaster' AND deleted_at IS NULL";
//     }
//     $qDepts = mysqli_query($db_conn, $query);
//     if (mysqli_num_rows($qDepts) > 0) {
//         $depts = mysqli_fetch_all($qDepts, MYSQLI_ASSOC);
//         $i = 0;
//         foreach ($depts as $x) {
//             $deptID = $x['id'];
//             $data[$i]['deptName'] = $x['name'];
//             if ($all == "1") {
//                 $id = $x['id_partner'];
//             }
//             $qSales = "SELECT SUM(detail_transaksi.harga) AS sales, SUM(detail_transaksi.qty) AS qty, menu.nama AS nama, c.name AS categoryName FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories c ON c.id=menu.id_category JOIN departments d ON d.id=c.department_id  WHERE transaksi.id_partner='$id' AND detail_transaksi.deleted_at IS NULL AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' AND d.id='$deptID' AND transaksi.id_partner='$id' AND detail_transaksi.status!=4 GROUP BY menu.id ORDER BY sales DESC";
//             $getSales = mysqli_query($db_conn, $qSales);
//             $sales = mysqli_fetch_all($getSales, MYSQLI_ASSOC);
//             $data[$i]['details'] = $sales;
//             $j = 0;
//             $i++;
//             $result = [];
//             if ($all == "1") {
//                 $result = array_reduce($data, function ($carry, $item) {
//                     if (!isset($carry[$item['deptName']])) {
//                         $carry[$item['deptName']] = ['deptName' => $item['deptName'], 'details' => $item['details']];
//                     } else {
//                         array_push($carry[$item['deptName']]['details'], ...$item['details']);
//                     }
//                     return $carry;
//                 });
//                 $data = [];
//                 foreach ($result as $obj) {
//                     array_push($data, $obj);
//                 }
//             }
//         }
        
//         $arrData = [];
//         foreach($data as $val) {
//             if(count($val['details']) > 0) {
//                 array_push($arrData, $val);
//             }
//         }
        
//         $success = 1;
//         $status = 200;
//         $msg = "Success";
//     } else {
//         $success = 0;
//         $status = 204;
//         $msg = "Data Not Found";
//     }
// }
// echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "breakdowns" => $arrData]);

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
$all = 0;
$result = [];
$test = [];
if (isset($tokens['success']) && $tokens['success'] == '0' || isset($tokens['success']) && $tokens['success'] == 0) {

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
} else {
    $data = [];
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
    if (isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    // if ($all !== "1") {
    //     $idMaster = null;
    // }
    $query = "";

    if($newDateFormat == 1)
    {
        if ($all == "1") {
            $query = "SELECT d.id, d.name, p.id AS id_partner FROM departments d JOIN partner p ON p.id = d.partner_id WHERE p.id_master = '$idMaster' AND d.deleted_at IS NULL";
        } else {
            // $query = "SELECT id, name FROM departments WHERE partner_id='$id' AND deleted_at IS NULL";
            $query = "SELECT id, name FROM departments WHERE master_id='$idMaster' AND deleted_at IS NULL";
        }
        $qDepts = mysqli_query($db_conn, $query);
        if (mysqli_num_rows($qDepts) > 0) {
            $depts = mysqli_fetch_all($qDepts, MYSQLI_ASSOC);
            $i = 0;
            foreach ($depts as $x) {
                $deptID = $x['id'];
                $data[$i]['deptName'] = $x['name'];
                if ($all == "1") {
                    $id = $x['id_partner'];
                }
                $qSales = "SELECT SUM(detail_transaksi.harga) AS sales, SUM(detail_transaksi.qty) AS qty, menu.nama AS nama, c.name AS categoryName FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories c ON c.id=menu.id_category JOIN departments d ON d.id=c.department_id  WHERE transaksi.id_partner='$id' AND detail_transaksi.deleted_at IS NULL AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND transaksi.paid_date BETWEEN '$dateFrom' AND '$dateTo' AND d.id='$deptID' AND transaksi.id_partner='$id' AND detail_transaksi.status!=4 GROUP BY menu.id ORDER BY sales DESC";
                $getSales = mysqli_query($db_conn, $qSales);
                $sales = mysqli_fetch_all($getSales, MYSQLI_ASSOC);
                $data[$i]['details'] = $sales;
                $j = 0;
                $i++;
                $result = [];
                if ($all == "1") {
                    $result = array_reduce($data, function ($carry, $item) {
                        if (!isset($carry[$item['deptName']])) {
                            $carry[$item['deptName']] = ['deptName' => $item['deptName'], 'details' => $item['details']];
                        } else {
                            array_push($carry[$item['deptName']]['details'], ...$item['details']);
                        }
                        return $carry;
                    });
                    $data = [];
                    foreach ($result as $obj) {
                        array_push($data, $obj);
                    }
                }
            }
            
            $arrData = [];
            foreach($data as $val) {
                if(count($val['details']) > 0) {
                    array_push($arrData, $val);
                }
            }
            
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    } else {
        if ($all == "1") {
        $query = "SELECT d.id, d.name, p.id AS id_partner FROM departments d JOIN partner p ON p.id = d.partner_id WHERE p.id_master = '$idMaster' AND d.deleted_at IS NULL";
        } else {
            // $query = "SELECT id, name FROM departments WHERE partner_id='$id' AND deleted_at IS NULL";
            $query = "SELECT id, name FROM departments WHERE master_id='$idMaster' AND deleted_at IS NULL";
        }
        $qDepts = mysqli_query($db_conn, $query);
        if (mysqli_num_rows($qDepts) > 0) {
            $depts = mysqli_fetch_all($qDepts, MYSQLI_ASSOC);
            $i = 0;
            foreach ($depts as $x) {
                $deptID = $x['id'];
                $data[$i]['deptName'] = $x['name'];
                if ($all == "1") {
                    $id = $x['id_partner'];
                }
                $qSales = "SELECT SUM(detail_transaksi.harga) AS sales, SUM(detail_transaksi.qty) AS qty, menu.nama AS nama, c.name AS categoryName FROM detail_transaksi JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id JOIN menu ON menu.id=detail_transaksi.id_menu JOIN categories c ON c.id=menu.id_category JOIN departments d ON d.id=c.department_id  WHERE transaksi.id_partner='$id' AND detail_transaksi.deleted_at IS NULL AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND DATE(transaksi.paid_date)  BETWEEN '$dateFrom' AND '$dateTo' AND d.id='$deptID' AND transaksi.id_partner='$id' AND detail_transaksi.status!=4 GROUP BY menu.id ORDER BY sales DESC";
                $getSales = mysqli_query($db_conn, $qSales);
                $sales = mysqli_fetch_all($getSales, MYSQLI_ASSOC);
                $data[$i]['details'] = $sales;
                $j = 0;
                $i++;
                $result = [];
                if ($all == "1") {
                    $result = array_reduce($data, function ($carry, $item) {
                        if (!isset($carry[$item['deptName']])) {
                            $carry[$item['deptName']] = ['deptName' => $item['deptName'], 'details' => $item['details']];
                        } else {
                            array_push($carry[$item['deptName']]['details'], ...$item['details']);
                        }
                        return $carry;
                    });
                    $data = [];
                    foreach ($result as $obj) {
                        array_push($data, $obj);
                    }
                }
            }
            
            $arrData = [];
            foreach($data as $val) {
                if(count($val['details']) > 0) {
                    array_push($arrData, $val);
                }
            }
            
            $success = 1;
            $status = 200;
            $msg = "Success";
        } else {
            $success = 0;
            $status = 204;
            $msg = "Data Not Found";
        }
    }


}
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "breakdowns" => $arrData]);

