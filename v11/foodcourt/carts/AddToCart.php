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
$totalPending = "0";

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$total = 0;
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    if(isset($obj->code)){
        $code = $obj->code;
        $sql = mysqli_query($db_conn, "SELECT id FROM temporary_qr WHERE code='$code' AND status=1 AND deleted_at IS NULL");
        if(mysqli_num_rows($sql)>0){
            $res = mysqli_fetch_all($sql, MYSQLI_ASSOC);
            $id = $res[0]['id'];
            $bool = true;
            foreach ($obj->data as $data) {
                
                $json = "'[''variant'':[";
                $i = 0;
                if (empty($data->variant)) {
                  $json = "''";
                } else {
                  $variant = $data->variant;
                  foreach ($variant as $vars) {
                    if ($i == 0) {
                      $json .= "{";
                    } else {
                      $json .= ",{";
                    }
                    $json .= "''name'':''" . $vars->name . "'',";
                    $json .= "''id'':''" . $vars->id_variant . "'',";
                    $json .= "''tipe'':''" . $vars->type . "'',";
                    $json .= "''detail'':[";
                    $dvariant = $vars->data_variant;
                    $i += 1;
                    $j = 0;
                    foreach ($dvariant as $detail) {
                      if ($j == 0) {
                        $json .= "{";
                      } else {
                        $json .= ",{";
                      }
        
                      $json .= "''id'':''" . $detail->id . "'',";
                      $json .= "''qty'':''" . $detail->qty . "'',";
                      $json .= "''name'':''" . $detail->name . "''}";
                      $j += 1;
                    }
                    $json .= "]}";
                  }
                  $json .= "]]'";
                  // $json = json_encode($json);
                }
                $insert = mysqli_query($db_conn, "INSERT INTO `temporary_qr_cart`(`qr_id`, `menu_id`, `unit_price`, `qty`, `price`, `notes`, `variant`, `status`, `created_at`, `tenant_id`) VALUES ('$id', '$data->menu_id', '$data->unit_price', '$data->qty', '$data->price', '$data->notes', $json, '1', NOW(), '$token->id_partner')");
                $total += $data->qty*$data->unit_price;
                if(!$insert){
                    $bool=false;
                }
            }
            if($bool){
                $success = 1;
                $status = 200;
                $msg = "Berhasil Menambahkan";
            }else{
                $success = 0;
                $status = 200;
                $msg = "Gagal ditambahkan";
            }
        }else{
            $success = 0;
            $status = 200;
            $msg = "Data tidak ditemukan";
        }
    }else{
        $success =0;
        $status =204;
        $msg = "400 Missing Required Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "total"=>$total]);
?>