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
$resto=array();
$antri=array();
$travel=array();
$arr=array();

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
    $map = mysqli_query($db_conn, "SELECT id,name,longitude, latitude,img_map,desc_map,phone, is_booked, booked_before,address FROM partner WHERE longitude <>'' AND is_testing='0' AND organization='Natta'");
    while($row=mysqli_fetch_assoc($map)){
        $id=$row['id'];
        $name=$row['name'];
        $longitude=$row['longitude'];
        $latitude=$row['latitude'];
        $img_map=$row['img_map'];
        $desc_map=$row['desc_map'];
        $phone=$row['phone'];
        $address=$row['address'];
        $booked_before=$row['booked_before'];
        $is_booked=$row['is_booked'];
        if($id[0]=='Q'){
            $antri[]=["coordinate" => ['latitude'=> $latitude,'longitude'=>$longitude,'latitudeDelta'=>0.0922,'longitudeDelta'=>0.0421],'id'=>$id,'title'=>$name,'image'=>$img_map,'description'=>$desc_map,'phone'=>$phone,'address'=>$address,'is_booked'=>$is_booked,'booked_before'=>$booked_before];
        }else{
            $resto[]=["coordinate" => ['latitude'=> $latitude,'longitude'=>$longitude,'latitudeDelta'=>0.0922,'longitudeDelta'=>0.0421],'id'=>$id,'title'=>$name,'image'=>$img_map,'description'=>$desc_map,'phone'=>$phone,'address'=>$address];
        }
    }

    $map = mysqli_query($db_conn, "SELECT id_partner AS id,name,longitude, latitude,img,description,phone, address FROM travel_partners WHERE longitude <>''");
    while($row=mysqli_fetch_assoc($map)){
        $id=$row['id'];
        $name=$row['name'];
        $longitude=$row['longitude'];
        $latitude=$row['latitude'];
        $img=$row['img'];
        $description=$row['description'];
        $phone=$row['phone'];
        $address=$row['address'];
        $travel[]=["coordinate" => ['latitude'=> $latitude,'longitude'=>$longitude,'latitudeDelta'=>0.0922,'longitudeDelta'=>0.0421],'id'=>$id,'title'=>$name,'image'=>$img,'description'=>$description,'phone'=>$phone,'address'=>$address];
    }
    $arr['antri']=$antri;
    $arr['resto']=$resto;
    $arr['travel']=$travel;

    if (count($antri)>0 || count($resto)>0 || count($travel)>0) {
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        $status =204;
        $msg = "Data Not Found";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "maps"=>$arr]);
?>