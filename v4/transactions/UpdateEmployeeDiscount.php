<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');
require_once '../../includes/DbOperation.php';

// date_default_timezone_set('Asia/Jakarta');

// POST DATA
$db = new DbOperation();

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
$res1 = array();
$id = "";

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

//function
function getMasterID($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT p.id_master FROM transaksi t JOIN partner p ON p.id=t.id_partner WHERE t.id LIKE '$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['id_master'];
    }else{
        return 0;
    }
}

function getPhone($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT phone  FROM `transaksi` WHERE `id` LIKE '$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return $res[0]['phone'];
    }else{
        return 0;
    }
}

function getTotal($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT total  FROM `transaksi` WHERE `id` LIKE '$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int)$res[0]['total'];
    }else{
        return 0;
    }
}

function checkMaxDiscount($id, $phone, $db_conn){
    $q = mysqli_query($db_conn,"SELECT max_disc FROM `special_member` WHERE id_master='$id' AND phone='$phone' ");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['max_disc'];
    }else{
        return 0;
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $obj = json_decode(json_encode($_POST));
    if( isset($obj->transactionID)
        && isset($obj->percent)
        && !empty($obj->transactionID)
        ){
            $fixed_discount= $obj->fixed_discount;
            $percent = $obj->percent;
            // $transactionID = $obj->transactionID;
            $transactionID = explode(',', $obj->transactionID);
            $i = 0;
            foreach ($transactionID as $value) {
              
                $getPromo = mysqli_query($db_conn, "SELECT promo FROM transaksi WHERE id='$value' AND deleted_at IS NULL AND id_partner='$token->id_partner'");
                $resPromo = mysqli_fetch_all($getPromo, MYSQLI_ASSOC);
                if((int)$resPromo[0]['promo']==0){
                    $total = getTotal($value, $db_conn);
                    $totals=$total;
            
                    if($percent<=0){
                        
                        $promo+=$fixed_discount;
                    }
                    else if($percent>0 && $fixed_discount==null){
                        $promo=$total*$percent/100;
                    }
                    
                    $sql = mysqli_query($db_conn, "UPDATE transaksi SET employee_discount='$promo', employee_discount_percent='$percent'  WHERE id='$value'");
                    if($sql){
                        $msg = "Berhasil";
                        $success = 1;
                        $status=200;
                    }else{
                        $msg = "Kesalahan Sistem Silahkan Coba Lagi";
                        $success = 0;
                        $status=204;
    
                    }
                }else{
                    $success = 0;
                    $msg = "Transaksi ini sudah menggunakan voucher. Mohon hapus voucher terlebih dahulu untuk menambahkan diskon";
                    $status = 400;
                }
                
            }

    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>