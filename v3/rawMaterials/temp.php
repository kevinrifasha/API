<?php    
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../tokenModels/tokenManager.php"); 
// require_once("./../partnerModels/partnerManager.php");
// require_once("./../variantGroupModels/variantGroupManager.php");
// require_once("./../variantModels/variantManager.php");


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
    $tokens = $tokenizer->getTokens($token);

    $id = $_GET['partnerId'];
    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
        $msg = $tokens['msg']; 
    }else{
        $charge = mysqli_query($db_conn, "SELECT r.*, m.* FROM `raw_material` r JOIN metric m ON r.id_metric = m.id WHERE`id_master` = '109' AND `id_partner` = '000025'");

        if (mysqli_num_rows($charge) > 0) {
            $all = mysqli_fetch_all($charge, MYSQLI_ASSOC);
            // echo json_encode(["success" => 1, "ewallet" => $all]);
        } else {
            // echo json_encode(["success" => 0]);
        }
    }
    
$signupJson = json_encode(["rawMaterials"=>$all, "msg"=>$msg, "success"=>$success]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    // Echo the message.
    echo $signupJson;
 ?>
 