<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../tokenModels/tokenManager.php");

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
$res = array();
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
$raws;
$donnees;

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
}else{
    if(isset($_GET['partnerId']) && !empty($_GET['partnerId'])){
        $partnerID = $_GET['partnerId'];
        $sql = mysqli_query($db_conn, "SELECT rm.id, rm.id_master, rm.id_partner, rm.name FROM raw_material rm WHERE rm.id_partner='$partnerID' AND rm.deleted_at IS NULL ORDER BY rm.id DESC");
        $donnees = mysqli_fetch_all($sql, MYSQLI_ASSOC);
        $raws = $donnees;
        
        if ($raws!=false){
            
            foreach ($raws as $raw) {
                // get raw material yang terpakai di menu
                $rawID = $raw['id'];
                $sqlMenu = mysqli_query($db_conn, "SELECT rm.id, rm.name, r.id_menu, m.nama, m.deleted_at, m.is_recipe, rm.yield FROM raw_material rm JOIN recipe r ON r.id_raw = rm.id JOIN menu m ON m.id=r.id_menu WHERE rm.id = '$rawID' AND rm.id_partner='$partnerID' AND r.id_menu != '0' AND rm.deleted_at IS NULL AND m.deleted_at IS NULL AND r.deleted_at IS NULL AND m.is_recipe='1' ORDER BY rm.id DESC;");
                
                $datas = mysqli_fetch_all($sqlMenu, MYSQLI_ASSOC);

                $menus = array();
                
                if(count($datas) != 0) {
                    foreach($datas as $data) {
                        $menuName = $data['nama'];
                        array_push($menus, $menuName);
                    }
                    $raw['menus'] = implode(", ", $menus);
                } else {
                    $raw['menus'] = "";
                }
                $raw['yield'] = $datas[0]['yield'];
                array_push($res, $raw);
            }
            
            $success = 1;
            $msg = "success";
            $status = 200;
            
        } else{
            $success = 0;
            $msg = "Data Not Found";
            $status = 204;
        }
        
    }else{
        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;

    }

}


echo json_encode(["msg"=>$msg, "status"=>$status,"success"=>$success,"rawMaterials"=>$res]);

?>