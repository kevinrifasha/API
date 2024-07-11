<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require '../../db_connection.php';

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
$token = '';

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];

}else{
    $partner_id = $_GET['partnerId'];
    $menu_id = $_GET['menuId'];
    $menu_price = $_GET['menuPrice'];
    $data = json_decode(file_get_contents('php://input'));
    $idList;
    
    if(isset($menu_id) && !empty($menu_id)){
        
        foreach($data as $val) {
            $id = $val->id;
            if($val->id) {
                $idList .= $val->id.",";
            }
            $surcharge_id = $val->surchargeId;
            $price = $val->price;
            if(!$val->price) {
                $price = $menu_price;
            }
            
            // update data jika id dari tiap $val nya ada
            if($id) {
                $queryUpd= "UPDATE menu_surcharge_types SET surcharge_id='$surcharge_id', price='$price', updated_at=NOW() WHERE id='$id' AND partner_id='$partner_id' AND menu_id='$menu_id' AND deleted_at IS NULL;";
                $update=mysqli_query($db_conn, $queryUpd);
                $test = $update;
                $q = $queryUpd;
                if(!$update) {
                    $success = 0;
                    $msg = "Gagal Update";
                    $status = 204;
                }
            } 
            
            if($id == "" && $surcharge_id) {
            // kalau $id kosong berarti dia data baru, maka tambahkan
                $queryAdd = "INSERT INTO menu_surcharge_types SET menu_id='$menu_id', surcharge_id='$surcharge_id', partner_id='$partner_id', price='$price', created_at=NOW()";
                $insertSurcharges = mysqli_query($db_conn, $queryAdd);
                if($insertSurcharges) { 
                    $newSurchargeID = mysqli_insert_id($db_conn);
                    $idList .= $newSurchargeID.",";
                } else {
                    $success = 0;
                    $msg = "Gagal menambahkan Menu Surcharge baru";
                    $status = 204;
                }
            }
        }
        
        // Periksa apakah ada surcharge yang dihapus
        //    Jika ada maka hapus dulu
        $listID = rtrim($idList, ",");
        $extraQuery=" AND partner_id='$partner_id' AND menu_id='$menu_id' AND deleted_at IS NULL;";
        $query = "UPDATE menu_surcharge_types SET deleted_at=NOW() WHERE id NOT IN($listID)".$extraQuery;
        $delete = mysqli_query($db_conn, $query);
        
        $success = 1;
        $msg = "Success update menu surcharge";
        $status = 200;
    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;
    }
}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);
echo $signupJson;
?>