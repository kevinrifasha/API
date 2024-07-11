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

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$res = array();
$vals[0]['id']="";
$vals[0]['start']="";
$vals[0]['end']="";
$vals[0]['petty_cash']=0;
$vals[0]['name']="";
$vals[0]['cash_income']=0;
$vals[0]['menus']=array();
$vals[0]['payment_method_income']=array();
$vals[0]['shift_transactions']=array();
$today = date("Y-m-d");
// $value['date']=$today;
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$type=0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $query = "SELECT * FROM
    (
    SELECT DATE(transaksi.jam) AS date, 0 AS deposit, COUNT(transaksi.id) j_trans, (COUNT(did)*cud)+((COUNT(transaksi.id)-COUNT(did))*cu) charge
    FROM `transaksi`
    JOIN partner ON partner.id=transaksi.id_partner
    LEFT JOIN
            (
                SELECT delivery.transaksi_id as did
                FROM `delivery` WHERE delivery_detail NOT LIKE 'Kurir Pribadi'
            ) delivery ON delivery.did=transaksi.id
    JOIN
        (
            SELECT settings.value as cu
            FROM settings
            WHERE settings.name='charge_ur'
        ) settings
    JOIN
        (
            SELECT settings.value as cud
            FROM settings
            WHERE settings.name='charge_ur_shipper'
        ) settings1
    WHERE partner.id_master='$token->id_master' AND transaksi.deleted_at IS NULL AND(transaksi.status=1 OR transaksi.status=2) GROUP BY DATE(transaksi.jam)
    UNION
    SELECT DATE(master_deposit.created_at) AS date, master_deposit.nominal_top_up deposit, 0 j_trans, 0 charge FROM `master_deposit` JOIN partner ON master_deposit.id_master=partner.id_master WHERE partner.id_master='$token->id_master' AND master_deposit.status='1' GROUP BY master_deposit.id ";


    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
    while($row=mysqli_fetch_assoc($transaksi)){
        $table_name = explode("_",$row['table_name']);
        $transactions = $row['table_name'];
        $query .= "UNION ALL ";
        $query .= "SELECT DATE(`$transactions`.jam) AS date, 0 AS deposit, COUNT(`$transactions`.id) j_trans, (COUNT(did)*cud)+((COUNT(`$transactions`.id)-COUNT(did))*cu) charge
        FROM `$transactions`
        JOIN partner ON partner.id=`$transactions`.id_partner
        LEFT JOIN
                (
                    SELECT delivery.transaksi_id as did
                    FROM `delivery` WHERE delivery_detail NOT LIKE 'Kurir Pribadi'
                ) delivery ON delivery.did=`$transactions`.id
        JOIN
            (
                SELECT settings.value as cu
                FROM settings
                WHERE settings.name='charge_ur'
            ) settings
        JOIN
            (
                SELECT settings.value as cud
                FROM settings
                WHERE settings.name='charge_ur_shipper'
            ) settings1
        WHERE partner.id_master='$token->id_master' AND `$transactions`.deleted_at IS NULL AND(`$transactions`.status=1 OR `$transactions`.status=2) GROUP BY DATE(`$transactions`.jam) ";
    }
    $query .=") a ORDER BY date";
    $tots = mysqli_query($db_conn, $query);

if (mysqli_num_rows($tots) > 0) {
    $res = mysqli_fetch_all($tots, MYSQLI_ASSOC);
    $msg = "success";
    $status = 200;
    $success =1 ;
} else {
    $msg = "failed";
    // $status = 204;
    $success =0 ;
}
}
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "history"=>$res]);
