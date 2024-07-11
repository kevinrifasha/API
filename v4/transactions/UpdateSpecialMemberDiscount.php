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
        && !empty($obj->percent) ){
            
            $percent = $obj->percent;
            $transactionID = $obj->transactionID;
            $phone = getPhone($transactionID, $db_conn);
            $masterID = getMasterID($transactionID, $db_conn);
            $maxDisc = checkMaxDiscount($masterID, $phone, $db_conn);
            
            
            if($maxDisc>0){
                if($percent<=$maxDisc){
                    $total = getTotal($transactionID, $db_conn);
                    $promo = $total*$percent/100;
                    $sql = mysqli_query($db_conn, "UPDATE transaksi SET diskon_spesial='$promo' WHERE id='$transactionID'");
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
                    $msg = "Melewati Batas Diskon Spesial ( ".$maxDisc."% )";
                    $success = 0;
                    $status=204;
                }
            }else{
                $msg = "Tidak Terdaftar pada Spesial Member";
                $success = 0;
                $status=204;
            }
            
    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;  
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "unavailable"=>$res1, "transaction_id"=>$id]);
?>