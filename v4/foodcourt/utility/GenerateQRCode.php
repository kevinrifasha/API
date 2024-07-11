<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
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
$iid = 0;
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{

    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    
    $str = "TU_FC_".$token->id_partner."_";
    $trxDate = date("ymd");
    $str .= $trxDate;
    $str .= "_";
            $qV = mysqli_query($db_conn,"SELECT code FROM `temporary_qr` WHERE partner_id='$token->id_partner' AND deleted_at IS NULL AND `code` LIKE '%$str%' ORDER BY id DESC LIMIT 1");
            if(mysqli_num_rows($qV) > 0) {
                $resV = mysqli_fetch_all($qV, MYSQLI_ASSOC);
                $code = explode("_",$resV[0]['code']);
                $code = (int) $code[4];
                $code +=1;
            }else{
                $code =1;
            }
// $i=;
$count = (int) $obj->qty;
            for($i=0;$i<$count;$i++) {
                
                $strInput = $str;
                $strInput .= $i+$code;
                $insert = mysqli_query($db_conn, "INSERT INTO `temporary_qr`(`partner_id`, `code`, `status`, `created_at`) VALUES ('$token->id_partner', '$strInput', '1', NOW())");
                array_push($res, $strInput);
            }

        // if($insert){
            $success =1;
            $status =200;
            $msg = "Success";
        // }else{
        //     $success =0;
        //     $status =200;
        //     $msg = "Failed";
        // }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "res"=>$res]);
?>