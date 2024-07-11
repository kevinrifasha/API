<?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: access");
// header("Access-Control-Allow-Methods: GET");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// //import require file
// require '../../db_connection.php';
// require_once('../auth/Token.php');
// require  __DIR__ . '/../../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
// $dotenv->load();


// // date_default_timezone_set('Asia/Jakarta');

// //init var
// $headers = array();
//     $rx_http = '/\AHTTP_/';
//     foreach($_SERVER as $key => $val) {
//       if( preg_match($rx_http, $key) ) {
//         $arh_key = preg_replace($rx_http, '', $key);
//         $rx_matches = array();
//         $rx_matches = explode('_', $arh_key);
//         if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
//           foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
//           $arh_key = implode('-', $rx_matches);
//         }
//         $headers[$arh_key] = $val;
//       }
//     }
// $today1 = date('Y-m-d');
// $tokenizer = new Token();
// $token = '';
// $id = "";
// $partnerID = $_GET['partnerID'];

// //get token
// foreach ($headers as $header => $value) {
//     if($header=="Authorization" || $header=="AUTHORIZATION"){
//         $token=substr($value,7);
//     }
// }

// $tokenValidate = $tokenizer->validate($token);
// $token = json_decode($tokenizer->stringEncryption('decrypt',$token));
// if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
//     $status = $tokenValidate['status'];
//     $msg = $tokenValidate['msg'];
//     $success = 0;
// echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
// }else{
    // $arrMenu = array();
    // $dateFirstDb = date('Y-m-d', strtotime('-1 week'));
    // $dateLastDb = date('Y-m-d');
    //     $qM = mysqli_query($db_conn,"SELECT  menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at, menu.sequence, partner.name, categories.name as cname, CASE WHEN menu.stock=0 THEN 0 ELSE 1 END AS tempFlag,
    //         categories.name as cname
    //         FROM menu
    //         JOIN partner ON menu.id_partner = partner.id
    //         JOIN categories ON categories.id = menu.id_category
    //         WHERE partner.id = '$partnerID' AND menu.deleted_at IS NULL AND menu.enabled=1 AND menu.is_suggestions!=0
    //         GROUP BY menu.id
    //         ORDER BY tempFlag DESC, menu.is_suggestions ASC");
    //     if (mysqli_num_rows($qM) > 0) {
    //         $arrMenu = mysqli_fetch_all($qM, MYSQLI_ASSOC);
    //     }
    //     if(count($arrMenu)<5){
    //         $f = 5 - count($arrMenu);
    //         $qM1 = mysqli_query($db_conn,"SELECT  menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at, partner.name, categories.name as cname, sum(detail_transaksi.qty) as qty, CASE WHEN stock=0 THEN 0 ELSE 1 END AS tempFlag FROM menu  JOIN partner ON menu.id_partner = partner.id JOIN categories ON categories.id = menu.id_category JOIN detail_transaksi ON detail_transaksi.id_menu=menu.id JOIN transaksi ON transaksi.id = detail_transaksi.id_transaksi WHERE partner.id = '$partnerID' AND menu.deleted_at IS NULL AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb' AND transaksi.status<=2 and transaksi.status>=1 AND menu.enabled='1' AND transaksi.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL GROUP BY menu.id ORDER BY tempFlag DESC, qty DESC LIMIT $f");
    //         if (mysqli_num_rows($qM1) > 0) {
    //             $arrMenu1 = mysqli_fetch_all($qM1, MYSQLI_ASSOC);
    //             $i = count($arrMenu);
    //             foreach ($arrMenu1 as $value) {
    //                 $arrMenu[$i]=$value;
    //                 $i+=1;
    //             }
    //         }

    //     }

//     echo json_encode(["success"=>$success, "recommended"=>$arrMenu]);
//     }
// }

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

//init var
$headers = array();
    $rx_http = '/\AHTTP_/';
    foreach($_SERVER as $key => $val) {
      if( preg_match($rx_http, $key) ) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
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
$status = 200;
$msg = "Success";
$success = 1;
$partnerID = $_GET['partnerID'];
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
  }else{
    $arrMenu = array();
    $dateFirstDb = date('Y-m-d', strtotime('-1 week'));
    $dateLastDb = date('Y-m-d');
        $qM = mysqli_query($db_conn,"SELECT  menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at, menu.sequence, partner.name, categories.name as cname, CASE WHEN menu.stock=0 THEN 0 ELSE 1 END AS tempFlag,
            categories.name as cname
            FROM menu
            JOIN partner ON menu.id_partner = partner.id
            JOIN categories ON categories.id = menu.id_category
            WHERE partner.id = '$partnerID' AND menu.deleted_at IS NULL AND menu.enabled=1 AND menu.is_suggestions!=0 AND categories.is_consignment != 1
            GROUP BY menu.id
            ORDER BY tempFlag DESC, menu.is_suggestions ASC");
        if (mysqli_num_rows($qM) > 0) {
            $arrMenu = mysqli_fetch_all($qM, MYSQLI_ASSOC);
        }
        if(count($arrMenu)<5){
            $f = 5 - count($arrMenu);
            $qM1 = mysqli_query($db_conn,"SELECT  menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at, partner.name, categories.name as cname, sum(detail_transaksi.qty) as qty, CASE WHEN stock=0 THEN 0 ELSE 1 END AS tempFlag FROM menu  JOIN partner ON menu.id_partner = partner.id JOIN categories ON categories.id = menu.id_category JOIN detail_transaksi ON detail_transaksi.id_menu=menu.id JOIN transaksi ON transaksi.id = detail_transaksi.id_transaksi WHERE partner.id = '$partnerID' AND categories.is_consignment != 1 AND menu.deleted_at IS NULL AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb' AND transaksi.status<=2 and transaksi.status>=1 AND menu.enabled='1' AND transaksi.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL GROUP BY menu.id ORDER BY tempFlag DESC, qty DESC LIMIT $f");
            if (mysqli_num_rows($qM1) > 0) {
                $arrMenu1 = mysqli_fetch_all($qM1, MYSQLI_ASSOC);
                $i = count($arrMenu);
                foreach ($arrMenu1 as $value) {
                    $arrMenu[$i]=$value;
                    $i+=1;
                }
            }

        }
}

    echo json_encode(["success"=>$success, "recommended"=>$arrMenu]);