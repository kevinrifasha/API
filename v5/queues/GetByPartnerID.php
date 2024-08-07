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
    $id = $_GET['id'];
    $res = array();

    $i = 0;

    $allCategories = mysqli_query($db_conn, "SELECT * FROM category_layanan WHERE id_partner='$id' AND deleted_at IS NULL  ORDER BY sequence  ASC");
    while($rowC=mysqli_fetch_assoc($allCategories)){
        $id_c = $rowC['id'];
        $allMenuCategory = mysqli_query($db_conn, "SELECT * FROM layanan WHERE
             category_id = '$id_c' AND deleted_at IS NULL AND status=1");
        $arr[$i]["category"] = $rowC['name'];
        $indexMenu = 0;
        while($rowMC=mysqli_fetch_assoc($allMenuCategory)){
            $arr[$i]["data"][$indexMenu] = $rowMC;
            $indexMenu+=1;
        }

        $i +=1;
    }

    if (mysqli_num_rows($allCategories) > 0) {
        $success = 1;

    } else {
        $success = 0;
    }

    echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "menus"=>$arr]);
}
