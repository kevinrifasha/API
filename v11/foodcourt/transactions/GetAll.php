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
$totalPending = "0";

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$total_data = 0;
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    if(isset($_GET['page'])&&!empty($_GET['page']) && isset($_GET['load'])&&!empty($_GET['load'])){

        $page = $_GET['page'];
        $load = $_GET['load'];
        $finish = $load * $page;
        $start = $finish - $load;
        $paramTotal="";
        if((int)$_GET['is_tenant']==1){
            if($_GET['is_centralized']==1){
                $param = " JOIN detail_transaksi ON detail_transaksi.id_transaksi=t.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$token->id_partner' AND t.deleted_at IS NULL AND (t.status < 2 AND t.tipe_bayar in (5,7,8,9) OR t.status = 1 AND t.tipe_bayar in (1,2,3,4,10)) AND detail_transaksi.status!=2 GROUP BY t.id";
                $paramTotal = " JOIN detail_transaksi ON detail_transaksi.id_transaksi=t.id JOIN menu ON menu.id=detail_transaksi.id_menu WHERE menu.id_partner='$token->id_partner' AND t.deleted_at IS NULL AND (t.status < 2 AND t.tipe_bayar in (5,7,8,9) OR t.status = 1 AND t.tipe_bayar in (1,2,3,4,10)) AND detail_transaksi.status!=2";
            }else{
                $param = " WHERE t.tenant_id='$token->id_partner' AND t.deleted_at IS NULL AND (t.status < 2 AND t.tipe_bayar in (5,7,8,9) OR t.status = 1 AND t.tipe_bayar in (1,2,3,4,10))";
                $paramTotal = $param;
            }
        }else{
            $param = " WHERE p.id='$token->id_partner' AND t.deleted_at IS NULL AND t.tenant_id='0' AND t.status=0 AND t.tipe_bayar in(5,7,8,9)";
            $paramTotal = $param;
        }
        
        $q1 = mysqli_query($db_conn, "SELECT COUNT(DISTINCT(t.id)) as total_data FROM transaksi t ".$paramTotal." ORDER BY jam DESC");
        
        
        $sql = "SELECT t.id, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.employee_discount, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, pm.nama as payment_method, case when u.name is null then t.customer_name else u.name end AS uname, case when u.name is null then 1 else 0 end AS is_pos, t.program_discount, t.customer_email FROM transaksi t JOIN payment_method pm ON t.tipe_bayar = pm.id LEFT JOIN users u ON u.phone=t.phone JOIN partner p ON p.id = t.id_partner".$param." ORDER BY jam DESC LIMIT $start,$load";
        $q = mysqli_query($db_conn, $sql);
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
            $total_data = $res1[0]['total_data'];
            $i =0;
            foreach($res as $r){
                $find = $r['id'];
                $delivery_fee=0;
                $allDeliv = mysqli_query($db_conn, "SELECT ongkir as delivery_fee FROM `delivery` WHERE transaksi_id='$find'");
                if(mysqli_num_rows($allDeliv)>0) {
                    $deliv = mysqli_fetch_all($allDeliv, MYSQLI_ASSOC);
                    $delivery_fee=$deliv[0]['delivery_fee'];
                }
                $res[$i]['delivery_fee']=$delivery_fee;
                $is_program=0;
                $isP = mysqli_query($db_conn, "SELECT is_program FROM `detail_transaksi` WHERE id_transaksi='$find' AND deleted_at IS NULL ORDER BY is_program DESC LIMIT 1");
                if(mysqli_num_rows($isP)>0) {
                    $deliv = mysqli_fetch_all($isP, MYSQLI_ASSOC);
                    $is_program=$deliv[0]['is_program'];
                }
                $res[$i]['is_program']=$is_program;
                $i+=1;
            }
            $success =1;
            $status =200;
            $msg = "Success";
        } else {
            $success =0;
            $status =200;
            $msg = "Data Not Found";
        }
    }else{
        $success =0;
        $status =204;
        $msg = "400 Missing Required Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "orders"=>$res, "total_data"=>$total_data]);
?>