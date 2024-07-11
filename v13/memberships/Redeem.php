<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

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

function minUserPoint($phone, $id_voucher_r, $id_master ,$db_conn){
    $q = mysqli_query($db_conn,"SELECT vr.point FROM membership_voucher vr WHERE vr.code='$id_voucher_r' AND vr.deleted_at IS NULL AND vr.id_master='$id_master' AND vr.deleted_at IS NULL");
    $q1 = mysqli_query($db_conn,"SELECT m.id, point FROM memberships m WHERE m.user_phone='$phone' AND m.master_id='$id_master' ORDER BY m.id DESC LIMIT 1");
    if (mysqli_num_rows($q) > 0 && mysqli_num_rows($q1) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $pPay = (int)$res[0]['point'];
        $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        $uPoint = (int)$res1[0]['point'];
        $uID = $res1[0]['id'];
        $point = $uPoint-$pPay;
        $update = mysqli_query($db_conn,"UPDATE `memberships` SET point='$point' WHERE id='$uID'");
        $insertPoints = mysqli_query($db_conn,"INSERT INTO `points`(`master_id`, `user_phone`, `point`, `description`, `created_at`) VALUES ('$id_master', '$phone', '-$pPay', 'Redeem Voucher $id_voucher_r', NOW())");
        return $uID;
    }else{
        return 0;
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
    if(isset($data->redeemByCode) && !empty($data->redeemByCode)){
        $id_voucher_redeemables = explode(", ",$data->id_voucher_redeemable);
        foreach($id_voucher_redeemables as $id_voucher_redeemable){
            $q = mysqli_query($db_conn, "SELECT master_id AS id_master FROM `redeemable_voucher` WHERE code='$id_voucher_redeemable' AND enabled='1' AND deleted_at IS NULL");
            if (mysqli_num_rows($q) > 0) {
                $qCO = mysqli_query($db_conn, "SELECT id FROM `user_voucher_ownership` WHERE userid='$token->phone' AND voucherid='$id_voucher_redeemable' AND obtained='1'");
                if (mysqli_num_rows($qCO) > 0) {
                    $success =0;
                    $status =200;
                    $msg = "Anda Sudah Melakukan Redeem Untuk Kode Ini";
                }else{
                    $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                    $id_master = $res[0]['id_master'];
                    $insert = mysqli_query($db_conn, "INSERT INTO `user_voucher_ownership`(`userid`, `voucherid`, `obtained`) VALUES ('$token->phone', '$id_voucher_redeemable', '1')");
                    $success =1;
                    if($insert){
                        $success =1;
                        $status =200;
                        $msg = "Redeem Berhasil";
                    } else {
                        $success =0;
                        $status =200;
                        $msg = "Redeem Gagal";
                    }
                }
            }else {
                $success =0;
                $status =200;
                $msg = "Kode Tidak Ditemukan";
            }
        }
    }else{
        $id_voucher_redeemables = explode(", ",$data->id_voucher_redeemable);
        foreach($id_voucher_redeemables as $id_voucher_redeemable){
            $q = mysqli_query($db_conn, "SELECT master_id FROM `membership_voucher` WHERE code='$id_voucher_redeemable' AND enabled='1' AND deleted_at IS NULL");
            if (mysqli_num_rows($q) > 0) {
                $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                $master_id = $res[0]['master_id'];
                $insert = mysqli_query($db_conn, "INSERT INTO `user_voucher_ownership`(`userid`, `voucherid`) VALUES ('$token->phone', '$id_voucher_redeemable')");
                $mID = minUserPoint($token->phone, $id_voucher_redeemable, $master_id ,$db_conn);
                $success =1;
            }
        }
        if($success ==1){
            $success =1;
            $status =200;
            $msg = "Redeem Success";
        } else {
            $success =0;
            $status =200;
            $msg = "Redeem Failed";
        }
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>