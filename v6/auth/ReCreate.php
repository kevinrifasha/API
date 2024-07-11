<?php    
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


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

require_once('./Token.php');
foreach ($headers as $header => $value) {
  if($header=="Authorization" || $header=="AUTHORIZATION"){
    $token=substr($value,7);
  }
}       
$tokenizer = new Token();
// $tokens = $tokenizer->validate($token);
$tokens = $tokenizer->validate($token);

$status=200;
if( $tokens['status']=='403' ||  $tokens['success']==403){
  $status = $tokens['status'];
  http_response_code(403);
  $signupMsg = $tokens['msg']; 
  $success = 0; 
}else{
  $tkn = $tokenizer->reCreate($token);
  $success = 1;
  http_response_code(200);
  $signupMsg = "Success";
}
$signupJson = json_encode(["success"=>$success, "msg"=>$signupMsg, "status"=>$status, "token"=>$tkn]);

// Echo the message.
echo $signupJson;
?>
 
