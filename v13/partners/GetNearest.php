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
$long = $_GET['longitude'];
$lat = $_GET['latitude'];
$arr = array();

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
    $q = mysqli_query($db_conn, "SELECT * FROM (
    SELECT id, name, address, desc_map as description, is_temporary_close, longitude, latitude, is_delivery, is_takeaway, img_map as image, is_open, jam_buka as open_hour, status ,jam_tutup as close_hour , is_testing, thumbnail,
        (
            (
                (
                    acos(
                        sin(( '$lat' * pi() / 180))
                        *
                        sin(( `latitude` * pi() / 180)) + cos(( '$lat' * pi() /180 ))
                        *
                        cos(( `latitude` * pi() / 180)) * cos((( '$long' - longitude) * pi()/180)))
                ) * 180/pi()
            ) * 60 * 1.1515 * 1.609344
        )
    as distance FROM `partner` WHERE organization='Natta'
) partner WHERE longitude !='' AND latitude!='' AND status='1' AND is_testing='0' ORDER BY distance ASC");
// $q1 = mysqli_query($db_conn, "SELECT * FROM (
//     SELECT id, name, address, description, longitude, latitude, img as image, is_open,  open_hour, close_hour , thumbnail,
//         (
//             (
//                 (
//                     acos(
//                         sin(( -6.8873375 * pi() / 180))
//                         *
//                         sin(( `latitude` * pi() / 180)) + cos(( -6.8873375 * pi() /180 ))
//                         *
//                         cos(( `latitude` * pi() / 180)) * cos((( '107.6062392,17' - longitude) * pi()/180)))
//                 ) * 180/pi()
//             ) * 60 * 1.1515 * 1.609344
//         )
//     as distance FROM travel_partners
// ) travel_partners WHERE longitude !='' AND latitude!='' ORDER BY distance ASC");

    if (mysqli_num_rows($q) > 0 || mysqli_num_rows($q1) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        // $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        $i = 0;
        foreach($res as $r){
            if(strpos($r['id'], 'Q') !== false){
                $res[$i]['type']='Queue';
            } else{
                $res[$i]['type']='Resto';
            }
            array_push($arr, $res[$i]);
            $i+=1;
        }
        $i=0;
        // foreach($res1 as $r){
        //     $res[$i]['type']='Travel';
        //     array_push($arr, $res[$i]);
        //     $i+=1;
        // }
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "partners"=>$arr]);
?>