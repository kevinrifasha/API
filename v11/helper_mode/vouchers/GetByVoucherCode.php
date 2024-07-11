<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../../db_connection.php';
require_once('../../auth/Token.php');

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
$data = json_decode(json_encode($_POST));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    $data = json_decode(file_get_contents('php://input'));
    if(isset($data->partnerID) && !empty($data->partnerID) && isset($data->code) && !empty($data->code)){
        $partnerID = $data->partnerID;
        $code      = $data->code;
        $phone     = $data->phone;
        $q = mysqli_query($db_conn, "SELECT `id`, `code`, `title`, `description`, `type_id`, `is_percent`, `discount`, `category`, `enabled`, `valid_from`, `valid_until`, `prerequisite`, `master_id`, `partner_id` AS partnerID, `img`, 'Redeem Code' AS type, `total_usage`, (SELECT COUNT(id) as countTrx FROM transaksi t WHERE t.id_partner = redeemable_voucher.partner_id AND t.id_voucher_redeemable=redeemable_voucher.code AND t.status in (0,1,2,5,6)) as countTrx from redeemable_voucher WHERE valid_until >= NOW() AND enabled = 1 AND deleted_at IS NULL AND partner_id = '$partnerID' AND code = '$code' HAVING total_usage > countTrx LIMIT 1");
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $prerequisite = json_decode($res[0]['prerequisite']);
            $qCO = mysqli_query($db_conn, "SELECT id FROM `user_voucher_ownership` WHERE userid='$phone' AND voucherid='$code' AND obtained='1'");
            if (mysqli_num_rows($qCO) >= $prerequisite->order) {
                $success =0;
                $status =200;
                $msg = "Anda sudah mencapai limit redeem untuk kode voucher ini";
            }else{
                $success =1;
                $status =200;
                $msg = "Success";
            }
        }else{
            $success =0;
            $status =200;
            $msg = "Voucher tidak ditemukan";
        }
    } else {
        $success =0;
        $status =204;
        $msg ="Missing Mandatory Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "vouchers"=>$res]);
?>