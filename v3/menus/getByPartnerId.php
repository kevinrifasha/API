<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../tokenModels/tokenManager.php");
require_once("./../menuModels/menuManager.php");
require_once("./../categoryModels/categoryManager.php");

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
$all = "0";

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
    $db = connectBase();
    $tokenizer = new TokenManager($db);
    $tokens = $tokenizer->validate($token);
    $token = json_decode($tokenizer->stringEncryption('decrypt',$token));
    $idMaster = $token->masterID;
    $res = array();

    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
        $status = $tokens['status'];
        $signupMsg = $tokens['msg'];
        $success = 0;
    }else{
        if(isset($_GET['all'])) {
            $all = $_GET['all'];
        }

        $Tmanager = new MenuManager($db);
        if(isset($_GET['partnerId']) && !empty($_GET['partnerId'])){
            $partnerId = $_GET['partnerId'];

            if($all == "1") {
                $menus = $Tmanager->getByMasterId($idMaster);
            } else {
                $menus = $Tmanager->getByPartnerId($partnerId);
            }
            
            $res = array();
        
            if($menus !== false) {
                foreach($menus as $menu){
                    $data = $menu->getDetails();
                    $Cmanager = new CategoryManager($db);
                    $category = $Cmanager->getById($data['id_category']);
                    $idMenu = $data['id'];
                    if($category==false){
                        $data['category_name'] = "Wrong Category";
                    }else{
                        $data['category_name'] = $category->getName();
                    }
    
                    // get menu surcharges
                    $surcharge_types = array();
    
                    // ambil surcharge id dari menuid dan partnerid
                    // $query = "SELECT mst.surcharge_id AS surcharge_id FROM menu_surcharge_types mst WHERE partner_id = '$partnerId' AND menu_id ='$idMenu' AND deleted_at IS NULL ORDER BY `id` DESC;";
                    $query = "SELECT mst.id, mst.surcharge_id, mst.surcharge_id, mst.partner_id, mst.price FROM menu_surcharge_types mst WHERE partner_id = '$partnerId' AND menu_id ='$idMenu' AND deleted_at IS NULL ORDER BY `id` DESC";
                    $sql = mysqli_query($db_conn, $query);
    
                    if(mysqli_num_rows($sql) > 0) {
                        $dataGet = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                        $surcharge_types = $dataGet;
    
                        // foreach($dataGet as $val) {
                        //     $surcharge = array();
                        //     $surcharge_id = $val['surcharge_id'];
                        //     $queryGetSurcharge = "SELECT s.id, s.name, s.surcharge, s.type, s.tax, s.service, s.additional_charge_name, s.additional_charge_value FROM surcharges s WHERE partner_id = '$partnerId' AND id = '$surcharge_id' AND deleted_at IS NULL ORDER BY `id` DESC";
                        //     $getSurcharge = mysqli_query($db_conn, $queryGetSurcharge);
    
                        //     if(mysqli_num_rows($getSurcharge) > 0) {
                        //         $dataSurcharge = mysqli_fetch_all($getSurcharge, MYSQLI_ASSOC);
                        //         array_push($surcharge_types,$dataSurcharge[0]);
                        //     }
                        // }
                    }
                    $data['surcharges'] = $surcharge_types;
                    // get menu surcharges end
    
                    array_push($res,$data);
                }
            }
            
            if(count($res)>0){
                $success = 1;
                $signupMsg = "Success";
                $status=200;
            }else{
                $success = 0;
                $signupMsg = "Data Not Found";
                $status=204;
            }

        }else if(isset($_GET['parentId']) && !empty($_GET['parentId'])){
            $parentId = $_GET['parentId'];

            $menus = $Tmanager->getByParentId($parentId);
            $res = array();
            foreach($menus as $menu){
                $data = $menu->getDetails();
                $Cmanager = new CategoryManager($db);
                $category = $Cmanager->getById($data['id_category']);
                if($category==false){
                    $data['category_name'] = "Wrong Category";
                }else{
                    $data['category_name'] = $category->getName();
                }
                array_push($res,$data);
            }

            if(count($res)>0){
                $success = 1;
                $signupMsg = "Success";
                $status=200;
            }else{
                $success = 0;
                $signupMsg = "Data Not Found";
                $status=204;
                $res = array();
            }

        }else{
            $success=0;
            $msg="Missing require field's";
            $status = 400;
            $res = array();

        }
    }
    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$signupMsg, "menus"=>$res]);

    echo $signupJson;
 ?>
