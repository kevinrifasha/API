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

function getMasterID($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT p.id_master FROM transaksi t JOIN partner p ON p.id=t.id_partner WHERE t.id LIKE '$id' AND t.deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['id_master'];
    }else{
        return 0;
    }
}

function getPhone($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT phone  FROM `transaksi` WHERE `id` LIKE '$id' AND transaksi.deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return $res[0]['phone'];
    }else{
        return 0;
    }
}

function checkSM($id, $phone, $db_conn){
    $q = mysqli_query($db_conn,"SELECT max_disc FROM `special_member` WHERE id_master='$id' AND phone='$phone' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        return true;
    }else{
        return false;
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $data = json_decode(file_get_contents('php://input'));
        $transactionID = $data->id;
        $sql = "
        SELECT
            t.id,
            t.status AS transactionStatus,
            t.tipe_bayar AS paymentMethod,
            pm.nama AS paymentMethodName,
            t.qr_string AS qrString,
            CEILING(SUM((((t.total - (t.program_discount + t.diskon_spesial + t.promo + t.employee_discount + t.point)) * (1 + (t.service / 100)) + t.charge_ur ) * (1 + (t.tax / 100)) + t.rounding))) AS total,
            t.created_at AS createdAt
        FROM transaksi AS t
        LEFT JOIN payment_method AS pm
        	ON pm.id = t.tipe_bayar
        WHERE
            t.id IN ('" . implode("','", $transactionID) . "')
        ORDER BY t.created_at";
        $q = mysqli_query($db_conn, $sql);
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $success =1;
            $status =200;
            $msg = "Success";
        } else {
            $success =0;
            $status =204;
            $msg = "Data Not Found";
        }
    // }else{
    //     $success =0;
    //     $status =204;
    //     $msg = "400 Missing Required Field";
    // }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "data"=>$res[0]]);
?>