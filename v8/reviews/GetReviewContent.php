<?php
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
$partnerID = "";
$tokenValidate = $tokenizer->validate($token);
$trxID = '';
$phone = '';
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
  }else{
     $trxID = $_GET['transactionID'];
    if(!empty($trxID)){
        $qTrxDetail = "SELECT m.nama, CASE WHEN m.thumbnail IS NULL THEN m.img_data ELSE m.thumbnail END AS image, t.status, t.id_partner, t.phone FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi = t.id JOIN menu m ON m.id=dt.id_menu WHERE dt.deleted_at IS NULL AND dt.status!=4 AND t.deleted_at IS NULL AND t.id='$trxID'";
        $getTrxDetail = mysqli_query($db_conn, $qTrxDetail);
        if(mysqli_num_rows($getTrxDetail)>0){
            $trxDetail = mysqli_fetch_all($getTrxDetail, MYSQLI_ASSOC);
            $partnerID = $trxDetail[0]['id_partner'];
            $phone = $trxDetail[0]['phone'];
            if($trxDetail[0]['status']=="4"||$trxDetail[0]['status']=="3"){
                $success = 0;
                $status = 400;
                $msg = "Tidak bisa memberi ulasan untuk transaksi yang dibatalkan";
            }else{
                $qPartnerDetail = "SELECT id, name, address, phone, CASE WHEN thumbnail IS NULL AND thumbnail NOT LIKE '%imagekit%' THEN img_map ELSE thumbnail END AS image FROM partner WHERE id='$partnerID'";
                $getPartnerDetail = mysqli_query($db_conn, $qPartnerDetail);
                if(mysqli_num_rows($getPartnerDetail)>0){
                    $partnerDetail = mysqli_fetch_assoc($getPartnerDetail);
                }else{
                    $partnerDetail = [];
                }
            }
        }else{
            $trxDetail = [];
            $success = 0;
            $msg = "Transaksi ini masih berlangsung. Selesaikan pesanan terlebih dahulu agar dapat memberi penilaian";
            $status = 400;
        }
        // $qPartnerDetail = mysqli_query($db_conn)
    }else{
        $status=400;
        $msg = "Mohon lengkapi form";
        $success = 0;
    }
    

}

echo json_encode(["success"=>$success, "status"=>$status,"phone"=>$phone, "msg"=>$msg, "trxDetail"=>$trxDetail, "partnerDetail"=>$partnerDetail, "trxID"=>$trxID]);