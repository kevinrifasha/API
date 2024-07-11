<?php    
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
require_once("../connection.php");
require_once("./../transactionModels/transactionManager.php"); 
require_once("./../userModels/userManager.php"); 
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../transactionDetailModels/transactionDetailManager.php"); 
require_once("./../menuModels/menuManager.php"); 

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

    $partnerId = $_GET['partnerId'];
    $categoryId = $_GET['categoryId'];
    $page = $_GET['page'];
    $load = $_GET['load'];
    $finish = $load * $page;
    $start = $finish - $load;

    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
        $signupMsg = $tokens['msg']; 
        $success = 0; 
    }else{
        $Tmanager = new TransactionManager($db);
        $Umanager = new UserManager($db);
        $orders = $Tmanager->getOrderByCategoryId($partnerId, $start, $load, $categoryId);
        $ordersLength = $Tmanager->getOrderLength($partnerId);
        $i = 0;
        
        $counter['total_order']=0;
        $counter['total_page']=0;
        
        $res = array();
        foreach ($orders as $val) {
            //get customer name
            $phone = $val['phone'];
            $user = $Umanager->getUser($phone);
            $user = $user->getName();
            $val['customer_name']=$user;
            array_push($res, $val);
            $counter['total_order']=count($res);
            $counter['total_page']=ceil(count($res)/$load);
            }
            $i+=1;
        }
        if(count($res)>0){
            $success = 1;
            $status=200;
            $signupMsg = "Success";
        }else{
            $success = 0;
            $status=204;
            $signupMsg = "Not Found";
        }
    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$signupMsg, "orders"=>$res, "counter"=>$counter]);

    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;
 ?>
 