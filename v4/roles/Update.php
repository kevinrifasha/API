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
    // POST DATA
    $data = json_decode(file_get_contents('php://input'));
    if(
        isset($data->id) && !empty($data->id)
        &&isset($data->name) && !empty($data->name)
    ){
        if(!isset($data->m20) || empty($data->m20)){
            $data->m20=0;
        }
        if(!isset($data->m21) || empty($data->m21)){
            $data->m21=0;
        }
        if(!isset($data->w16) || empty($data->w16)){
            $data->w16=0;
        }
        if(!isset($data->is_order_ntf) || empty($data->is_order_ntf)){
            $data->is_order_ntf=0;
        }
        if(!isset($data->is_reserv_ntf) || empty($data->is_reserv_ntf)){
            $data->is_reserv_ntf=0;
        }
        if(!isset($data->is_withdrawl_ntf) || empty($data->is_withdrawl_ntf)){
            $data->is_withdrawl_ntf=0;
        }
            $qupd = "UPDATE `roles` SET `name`='$data->name', updated_at=NOW(), `web`='$data->web',`mobile`='$data->app',`w1`='$data->w1',`w2`='$data->w2',`w3`='$data->w3',`w4`='$data->w4',`w5`='$data->w5',`w6`='$data->w6',`w7`='$data->w7',`w8`='$data->w8',`w9`='$data->w9',`w10`='$data->w10',`w11`='$data->w11',`w12`='$data->w12',`w13`='$data->w13',`w14`='$data->w14',`w15`='$data->w15',`w16`='$data->w16',`w17`='$data->w17',`w18`='$data->w18',`w19`='$data->w19',`w20`='$data->w20',`w21`='$data->w21',`w22`='$data->w22',`w23`='$data->w23',`w24`='$data->w24',`w25`='$data->w25',`w26`='$data->w26',`w27`='$data->w27',`w28`='$data->w28',`w29`='$data->w29',`w30`='$data->w30',`w31`='$data->w31',`w32`='$data->w32',`w33`='$data->w33',`w34`='$data->w34',`w35`='$data->w35',`w36`='$data->w36',`w37`='$data->w37',`w38`='$data->w38',`w39`='$data->w39',`w40`='$data->w40',`w41`='$data->w41',`w42`='$data->w42',`w43`='$data->w43',`w44`='$data->w44',`w45`='$data->w45',`w46`='$data->w46',`w47`='$data->w47',`m1`='$data->m1',`m2`='$data->m2',`m3`='$data->m3',`m4`='$data->m4',`m5`='$data->m5',`m6`='$data->m6',`m7`='$data->m7',`m8`='$data->m8',`m9`='$data->m9',`m10`='$data->m10',`m11`='$data->m11',`m12`='$data->m12',`m13`='$data->m13',`m14`='$data->m14',`m15`='$data->m15',`m16`='$data->m16',`m17`='$data->m17',`m18`='$data->m18',`m19`='$data->m19',`m20`='$data->m20',`m21`='$data->m21',`m22`='$data->m22',`m23`='$data->m23',`m24`='$data->m24',`m25`='$data->m25',`m26`='$data->m26',`m27`='$data->m27',`m28`='$data->m28',`m29`='$data->m29',`m30`='$data->m30',`m31`='$data->m31',`m32`='$data->m32',`m33`='$data->m33',`m34`='$data->m34',`m35`='$data->m35',`m36`='$data->m36',`m37`='$data->m37',`m38`='$data->m38',`m39`='$data->m39',`m40`='$data->m40',`m41`='$data->m41',`m42`='$data->m42',`m43`='$data->m43',`m44`='$data->m44',`m45`='$data->m45',`m46`='$data->m46',`m47`='$data->m47',`m48`='$data->m48',`m49`='$data->m49',`m50`='$data->m50',`m51`='$data->m51', `max_discount`='$data->max_discount',`is_order_notif`='$data->is_order_ntf',`is_reservation_notif`='$data->is_reserv_ntf',`is_withdrawal_notif`='$data->is_withdrawl_ntf',`department_access`='$data->department_access',`is_owner_mode`='$data->is_owner_mode' WHERE id='$data->id'";
                $insert = mysqli_query($db_conn,"UPDATE `roles` SET `name`='$data->name', updated_at=NOW(), `web`='$data->web',`mobile`='$data->app',`w1`='$data->w1',`w2`='$data->w2',`w3`='$data->w3',`w4`='$data->w4',`w5`='$data->w5',`w6`='$data->w6',`w7`='$data->w7',`w8`='$data->w8',`w9`='$data->w9',`w10`='$data->w10',`w11`='$data->w11',`w12`='$data->w12',`w13`='$data->w13',`w14`='$data->w14',`w15`='$data->w15',`w16`='$data->w16',`w17`='$data->w17',`w18`='$data->w18',`w19`='$data->w19',`w20`='$data->w20',`w21`='$data->w21',`w22`='$data->w22',`w23`='$data->w23',`w24`='$data->w24',`w25`='$data->w25',`w26`='$data->w26',`w27`='$data->w27',`w28`='$data->w28',`w29`='$data->w29',`w30`='$data->w30',`w31`='$data->w31',`w32`='$data->w32',`w33`='$data->w33',`w34`='$data->w34',`w35`='$data->w35',`w36`='$data->w36',`w37`='$data->w37',`w38`='$data->w38',`w39`='$data->w39',`w40`='$data->w40',`w41`='$data->w41',`w42`='$data->w42',`w43`='$data->w43',`w44`='$data->w44',`w45`='$data->w45',`w46`='$data->w46',`w47`='$data->w47',`m1`='$data->m1',`m2`='$data->m2',`m3`='$data->m3',`m4`='$data->m4',`m5`='$data->m5',`m6`='$data->m6',`m7`='$data->m7',`m8`='$data->m8',`m9`='$data->m9',`m10`='$data->m10',`m11`='$data->m11',`m12`='$data->m12',`m13`='$data->m13',`m14`='$data->m14',`m15`='$data->m15',`m16`='$data->m16',`m17`='$data->m17',`m18`='$data->m18',`m19`='$data->m19',`m20`='$data->m20',`m21`='$data->m21',`m22`='$data->m22',`m23`='$data->m23',`m24`='$data->m24',`m25`='$data->m25',`m26`='$data->m26',`m27`='$data->m27',`m28`='$data->m28',`m29`='$data->m29',`m30`='$data->m30',`m31`='$data->m31',`m32`='$data->m32',`m33`='$data->m33',`m34`='$data->m34',`m35`='$data->m35',`m36`='$data->m36',`m37`='$data->m37',`m38`='$data->m38',`m39`='$data->m39',`m40`='$data->m40',`m41`='$data->m41',`m42`='$data->m42',`m43`='$data->m43',`m44`='$data->m44',`m45`='$data->m45',`m46`='$data->m46',`m47`='$data->m47',`m48`='$data->m48',`m49`='$data->m49',`m50`='$data->m50',`m51`='$data->m51', `max_discount`='$data->max_discount',`is_order_notif`='$data->is_order_ntf',`is_reservation_notif`='$data->is_reserv_ntf',`is_withdrawal_notif`='$data->is_withdrawl_ntf',`department_access`='$data->department_access',`is_owner_mode`='$data->is_owner_mode' WHERE id='$data->id'");
                if($insert){
                    $msg = "Berhasil ubah data";
                    $success = 1;
                    $status=200;
                }else{
                    $msg = "Gagal ubah data";
                    $success = 0;
                    $status=204;
                }
    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;
    }

}
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg, "test"=>$qupd]);

?>
