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
     $id=$_GET['id'];
    $find=$_GET['find'];
    $aos = 0;
    try{
        $getAOS = mysqli_query($db_conn, "SELECT allow_override_stock FROM partner WHERE id='$id'");
        if(mysqli_num_rows($getAOS)>0){
            $resAOS = mysqli_fetch_all($getAOS, MYSQLI_ASSOC);
            $aos = (int)$resAOS[0]['allow_override_stock'];
        }else{
            $success = 0;
            $msg = "data tidak ditemukan";
            $status = 400;
        }
    }catch(Exception $e){
        $msg=$e;
    }
   
    $q = mysqli_query($db_conn, "SELECT menu.id,id_partner,nama,harga,Deskripsi,category,id_category, img_data, enabled, stock, hpp, harga_diskon, is_variant, is_recommended, is_recipe, menu.thumbnail, menu.created_at, categories.name AS cname, partner.name, categories.is_consignment FROM menu JOIN categories ON menu.id_category=categories.id JOIN partner ON partner.id=menu.id_partner WHERE menu.id_partner='$id' AND menu.enabled='1' AND menu.nama LIKE '%$find%' AND menu.deleted_at IS NULL AND menu.show_in_sf=1 AND categories.is_consignment = 0");

    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $i=0;
        foreach ($res as $value) {
            $mID = $value['id'];
            $qF = mysqli_query($db_conn, "SELECT id FROM `favorites` WHERE phone='$token->phone' AND menu_id='$mID' AND deleted_at IS NULL");
            if (mysqli_num_rows($qF) > 0) {
                $res[$i]['is_favorite']=true;
            }else{
                $res[$i]['is_favorite']=false;
            }
            if($aos==1){
            $res[$i]['tempFlag']="1";
            $res[$i]['stock']="10000";
            }
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
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "menus"=>$res]);
?>