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
    $totalQty = 0;
    $totalServed = 0;
    $servedPercentage = 0;
    $partnerID=$token->partnerID;
    $id=$token->id;
    $dateFrom = $_GET['dateFrom'];
    $dateTo = $_GET['dateTo'];
    $q="SELECT dt.id, dt.qty, dt.server_id FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi = t.id LEFT JOIN shift s ON s.id=t.shift_id WHERE t.deleted_at IS NULL AND t.status IN(1,2) AND dt.deleted_at IS NULL AND dt.status!=4 AND t.id_partner='$partnerID' AND ((DATE(s.start) BETWEEN '$dateFrom' AND '$dateTo') OR DATE(s.end) BETWEEN '$dateFrom' AND '$dateTo')";
    $getItems = mysqli_query($db_conn, $q);
   

    if (mysqli_num_rows($getItems) > 0) {
        while($rowItems=mysqli_fetch_assoc($getItems)){
            $totalQty += (int)$rowItems['qty'];
            if($rowItems['server_id']==$id){
                $totalServed +=(int)$rowItems['qty'];
            }
        }
        $servedPercentage = $totalServed/$totalQty*100;
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "totalQty"=>$totalQty, "totalServed"=>$totalServed, "servedPercentage"=>$servedPercentage]);
?>