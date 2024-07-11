<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

//init var
$headers = apache_request_headers();
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

    $obj = json_decode(file_get_contents('php://input'));
    if(
        isset($obj->name) && !empty($obj->name)
        &&isset($obj->recipientName) && !empty($obj->recipientName)
        ){
            if($obj->isPrimary==1 || $obj->isPrimary=='1'){
                $update = mysqli_query($db_conn, "UPDATE `addresses` SET is_primary='0' WHERE phone='$token->phone'");

            }

            if(isset($obj->shipperLocation) && !empty($obj->shipperLocation)){
                $shipperLocation = $obj->shipperLocation;
            }else{
                $shipperLocation = '{"label":"Ciroyom, Andir, Bandung, Kota, 40182","value":"40182|12245|1249|478|9","city_name":"Bandung, Kota","suburb_name":"Andir","area_name":"Ciroyom","order_list":3}';
            }
        $q = mysqli_query($db_conn, "UPDATE `addresses` SET name='$obj->name', recipient_name='$obj->recipientName', recipient_phone='$obj->recipientPhone', address='$obj->address', note='$obj->note', latitude='$obj->latitude', longitude='$obj->longitude', is_primary='$obj->isPrimary', shipper_location='$shipperLocation' WHERE id='$obj->id'");

        if ($q) {
            $success =1;
            $status =200;
            $msg = "Berhasil mengubah alamat";
        } else {
            $success =0;
            $status =204;
            $msg = "Gagal mengubah alamat";
        }
    }else{
        $success =0;
        $status =400;
        $msg = "Mohon lengkapi form";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg]);
?>