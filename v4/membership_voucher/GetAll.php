<?php  
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
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
    
    $master_id = $_GET['master_id'];
    $partner_id = $_GET['partner_id'];
    $q = mysqli_query($db_conn, "SELECT v.id, v.code, v.title, v.description, v.type_id, v.is_percent, v.discount, v.enabled, v.valid_from, v.valid_until, v.total_usage, v.master_id, v.partner_id, v.img, vt.name, v.prerequisite, v.point FROM membership_voucher v JOIN voucher_types vt ON v.type_id=vt.id WHERE v.deleted_at IS NULL AND (v.master_id='$master_id' OR v.partner_id='$partner_id') ORDER BY v.id DESC");
    
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        
        $index = 0;
        foreach ($res as $value) {
            $prerequisite = json_decode($value['prerequisite']);
            if(isset($prerequisite->menu_id) ){
                $find = $prerequisite->menu_id;
                $qMenu = mysqli_query($db_conn, "SELECT id, nama as name FROM `menu` WHERE id='$find'");
                $resMenu = mysqli_fetch_all($qMenu, MYSQLI_ASSOC);
                $res[$index]['menus']=$resMenu;
            }else{
                $res[$index]['menus']=array();
            }
            
            $arrC = array();
            if(isset($prerequisite->category_id) ){
                $a = explode(",",$prerequisite->category_id);
                foreach ($a as $value) {
                    $qCategories = mysqli_query($db_conn, "SELECT id, name FROM `categories` WHERE id='$value'");
                    if (mysqli_num_rows($qCategories) > 0) {
                        $resMenu = mysqli_fetch_all($qCategories, MYSQLI_ASSOC);
                        array_push($arrC, $resMenu[0]);
                    }
                }
                $res[$index]['categories']=$arrC;
            }else{
                $res[$index]['categories']=$arrC;
            }
            $index+=1;
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
    
        
$signupJson = json_encode(["msg"=>$msg, "status"=>$status, "success"=>$success, "vouchers"=>$res]);  
http_response_code($status);
echo $signupJson;

 ?>
 