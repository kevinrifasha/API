<?php
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

$headers = apache_request_headers();
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
$partnerID=$_GET['partnerID'];
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $query = "SELECT r.id, r.transaction_id, r.review, r.rating, r.attributes, r.anonymous, r.created_at, u.name, t.phone, t.id_partner, t.no_meja, t.total, t.id_voucher, t.promo, t.notes, t.tax, t.service, t.charge_ur, u.name AS customer_name, t.status, t.diskon_spesial, t.employee_discount FROM reviews r JOIN transaksi t ON r.transaction_id=t.id JOIN users u ON u.phone=t.phone WHERE r.deleted_at IS NULL AND t.id_partner='$partnerID' AND t.deleted_at IS NULL ORDER BY r.id DESC";

    $sql = mysqli_query($db_conn, $query);
    $i=0;
    if(mysqli_num_rows($sql) > 0) {
        $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        foreach($data as $row){
            $reviewID = $row['id'];
            $res[$i]=$row;
            $replies = mysqli_query($db_conn, "SELECT rr.id, rr.content, rr.created_at, u.name AS userName, 'Merchant' AS employeeName FROM review_replies rr LEFT JOIN users u ON rr.user_id=u.id WHERE rr.review_id='$reviewID' AND rr.deleted_at IS NULL");
            if(mysqli_num_rows($replies)>0){
                $res[$i]['replies']=mysqli_fetch_all($replies, MYSQLI_ASSOC);
            }else{
                $res[$i]['replies'] = [];
            }
            $i++;
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
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "reviews"=>$res]);

?>