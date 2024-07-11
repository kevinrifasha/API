<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');
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
-
$tokenizer = new Token();
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';

$id = $token->id_partner;
$dateFrom=$_GET['dateFrom'];
$dateTo=$_GET['dateTo'];

$values = array();
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $namaMenu = "";
    $query = " SELECT SUM(detail_transaksi.qty) AS qty, menu.nama, menu.id as menu_id
    FROM detail_transaksi
    JOIN transaksi ON detail_transaksi.id_transaksi=transaksi.id
    JOIN menu ON menu.id=detail_transaksi.id_menu
    WHERE transaksi.id_partner='$id' AND transaksi.deleted_at IS NULL AND transaksi.status IN(1,2) AND transaksi.deleted_at IS NULL AND detail_transaksi.deleted_at IS NULL AND detail_transaksi.status!=4 AND DATE(transaksi.paid_date) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY menu_id ORDER BY qty DESC LIMIT 5 ";
    $detail = mysqli_query($db_conn, $query);
    while($row=mysqli_fetch_assoc($detail)){
        $namaMenu = $row['nama'];
        $qty = $row['qty'];
        array_push($values, array("name" => "$namaMenu", "value" => $qty));
    }
    $status=200;
    $success=1;
    $msg="Success";
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "data"=>$values]);
?>