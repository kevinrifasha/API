<?php    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../purchaseOrdersModels/purchaseOrdersManager.php");

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
$tokenDcrpt = json_decode($tokenizer->stringEncryption('decrypt',$token));
$success=0;
$msg = 'Failed'; 
$status = 200;
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
}else{
    $obj = json_decode(json_encode($_POST));
            
            'id' => $this->id,
			'supplier_id' => $this->supplier_id,
			'raw_id' => $this->raw_id,
			'menu_id' => $this->menu_id,
			'qty' => $this->qty,
			'metric_id' => $this->metric_id,
			'price' => $this->price,
			'partner_id' => $this->partner_id,
			'master_id' => $this->master_id,

            $poManager = new PurchaseOrdersManager($db);
            foreach ($variable as $key => $value) {
                # code...
            }
            $po = new PurchaseOrders(array("id"=>0,"id_master"=>$tokenDcrpt->masterID,"name"=>$obj->name,"sequence"=>$obj->sequence));
            $insert = $poManager->add($po);

        if($insert!=false){
            $success=1;
            $msg="Success";
            $status=200;
        }else{
            $success=0;
            $msg="Failed";
            $status=503;
        }
    
}

$signupJson = json_encode(["msg"=>$msg, "success"=>$success,"status"=>$status]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;
?>
 