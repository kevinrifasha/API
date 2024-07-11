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
    
     $id = $_GET['id'];
    $q = mysqli_query($db_conn, "SELECT v.id, v.code, v.title, v.description, v.type_id, v.is_percent, v.discount, v.enabled, v.valid_from, v.valid_until, v.total_usage, v.master_id, v.partner_id, v.img, vt.name, v.prerequisite, v.point FROM membership_voucher v JOIN voucher_types vt ON v.type_id=vt.id WHERE v.deleted_at IS NULL AND v.id='$id'");
    
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $i=0;
        foreach ($res as $r) {
            if($r['type_id']=='2'){
                $menu_id = json_decode($r['prerequisite']);
                $menu_id=$menu_id->menu_id;
                $q1 = mysqli_query($db_conn, "SELECT id, nama as name FROM `menu` WHERE id='$menu_id'");
                $res[$i]['menu']=mysqli_fetch_all($q1, MYSQLI_ASSOC);
            }
            if($r['type_id']=='3'){
                $category_id = json_decode($r['prerequisite']);
                $category_id=$category_id->category_id;
                $a = explode(",",$category_id);
                $j=0;
                foreach ($a as $value) {
                    $q1 = mysqli_query($db_conn, "SELECT id, name FROM `categories` WHERE id='$value'");
                    $res[$i]['categories'][$j]=mysqli_fetch_all($q1, MYSQLI_ASSOC);
                    $j+=1;
                }
            }
            if(strpos($r['prerequisite'], "payment_method") !== false){
                $payment_method = json_decode($r['prerequisite']);
                $payment_method=$payment_method->payment_method;
                $q1 = mysqli_query($db_conn, "SELECT id, nama as name FROM `payment_method` WHERE id='$payment_method'");
                $res[$i]['payment_method']=mysqli_fetch_all($q1, MYSQLI_ASSOC);
            }else{
                $res[$i]['payment_method'][0]['id']="0";
                $res[$i]['payment_method'][0]['name']="SEMUA";
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
    
        
$signupJson = json_encode(["msg"=>$msg, "status"=>$status, "success"=>$success, "vouchers"=>$res]);  
http_response_code($status);
echo $signupJson;

 ?>
 