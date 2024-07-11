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

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    // if(isset($_GET['page'])&&!empty($_GET['page']) && isset($_GET['load'])&&!empty($_GET['load'])){
        // $page = $_GET['page'];
        // $load = $_GET['load'];
        // $finish = $load * $page;
        // $start = $finish - $load;
        $q = mysqli_query($db_conn, "SELECT t.id, t.jam, t.phone, t.no_meja, t.pax, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.employee_discount, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, pm.nama as payment_method, t.customer_name AS uname, t.program_discount, t.partner_note,  t.is_pos, t.customer_email, t.is_helper, CASE WHEN  t.group_id=null THEN 0 ELSE t.group_id END AS groupID,t.employee_discount_percent, t.rounding FROM transaksi t JOIN payment_method pm ON t.tipe_bayar = pm.id 
        WHERE t.id_partner='$token->id_partner' AND t.deleted_at IS NULL AND (t.status=5 OR t.status=6)  ORDER BY t.jam DESC");
        // LIMIT $start,$load
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $i =0;
            foreach($res as $r){
                $find = $r['id'];
                $res[$i]['delivery_fee']=0;
                $res[$i]['sales']=0;
                $res[$i]['gross_profit']=0;
                $is_program=0;
                $isP = mysqli_query($db_conn, "SELECT is_program, name FROM ( SELECT dt.is_program, mp.name FROM `detail_transaksi` dt LEFT JOIN `programs` p ON p.id = dt.is_program LEFT JOIN `master_programs` mp ON mp.id = p.master_program_id WHERE dt.deleted_at IS NULL AND dt.id_transaksi='$find' ORDER BY dt.is_program DESC ) AS tmp ORDER BY is_program DESC LIMIT 1 ");
                if(mysqli_num_rows($isP)>0) {
                    $deliv = mysqli_fetch_all($isP, MYSQLI_ASSOC);
                    $is_program=$deliv[0]['is_program'];
                    $program_name=$deliv[0]['name'];
                }
                $res[$i]['is_program']=$is_program;
                $res[$i]['program_name']=$program_name;
                $i+=1;
            }

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
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "orders"=>$res]);
?>