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

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
$id = $_GET['id'];
$transaction_id = $_GET['transactionID'];
$query = "SELECT r.id, r.review, r.rating, u.name, r.anonymous, r.created_at, r.transaction_id FROM reviews r JOIN transaksi t ON t.id = r.transaction_id JOIN users u ON t.phone = u.phone WHERE t.id_partner='$id' AND r.deleted_at IS NULL AND t.deleted_at IS NULL AND t.id = '$transaction_id' ORDER BY id DESC";
$res=[];
$replies=[];
$i=0;
$data = mysqli_query($db_conn, $query);
while($row = mysqli_fetch_assoc($data)){
  $reviewID=$row['id'];
  $res[$i]['id']=$row['id'];
  $res[$i]['review']=$row['review'];
  $res[$i]['rating']=$row['rating'];
  $res[$i]['name']=$row['name'];
  $res[$i]['anonymous']=$row['anonymous'];
  $res[$i]['created_at']=$row['created_at'];
  $replies = mysqli_query($db_conn, "SELECT rr.id, rr.content, rr.created_at, u.name AS userName, 'Merchant' AS employeeName FROM review_replies rr LEFT JOIN users u ON rr.user_id=u.id LEFT JOIN employees e ON rr.employee_id=e.id WHERE rr.review_id='$reviewID' AND rr.deleted_at IS NULL ");
  $res[$i]['replies']=mysqli_fetch_all($replies, MYSQLI_ASSOC);

  $i++;
}
$success = 1;
$status = 200;
$msg = "Success";

}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "reviews"=>$res]);