<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require_once("./../transactionModels/transactionManager.php");
require_once("./../userModels/userManager.php");
require_once("./../paymentModels/paymentManager.php");
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

    $partnerId = $_GET['partnerId'];
    $from = $_GET['from'];
    $to = $_GET['to'];
    $page = $_GET['page'];
    $load = $_GET['load'];
    $type=0;
    if(isset($_GET['type']) && !empty($_GET['type'])){
        $type=$_GET['type'];
    }
    $finish = $load * $page;
    $start = $finish - $load;


    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
        $signupMsg = $tokens['msg'];
        $success = 0;
    }else{
        $Tmanager = new TransactionManager($db);
        $Umanager = new UserManager($db);
        if( isset($page) &&!empty($page)
            && isset($load) &&!empty($load)
        ){
            $history = $Tmanager->getHistory($partnerId, $start, $load, $from, $to);
        }else{
            $history = $Tmanager->getByPartnerId($partnerId, $from, $to, $type);
        }
        $i = 0;

        foreach ($history as $val) {
            //get customer name
            if($val['takeaway']=='1'){
                $history[$i]['no_meja']="Take Away";

            }

            if($val['id'][0]=='P' && $val['id'][1]=='O' ){
                $history[$i]['no_meja']="Pre Order";

            }

            if($val['id'][0]!='P' && $val['id'][1]!='O' && $val['takeaway']=='0' && $val['no_meja']=="" ){
                $history[$i]['no_meja']="Delivery";

            }

            $phone = $val['phone'];
            $user = $Umanager->getUser($phone);
            if($user!=false){

                $user = $user->getName();
                $history[$i]['customer_name']=$user;
            }else{
                $history[$i]['customer_name']="Wrong";

            }
            $PaymentManager = new PaymentManager($db);
            $payment = $PaymentManager->getById($val['tipe_bayar']);
            if($payment!=false){
                $history[$i]['paymentName'] = $payment->getNama();
            }else{
                $history[$i]['paymentName'] = "Wrong";

            }

            $idT = $val['id'];
            $statusT = $val['status'];
            $sql = mysqli_query($db_conn, "SELECT order_status_trackings.created_at as created_at FROM `order_status_trackings` WHERE `transaction_id` LIKE '$idT' AND `status_after` = '$statusT' ORDER BY `id`  DESC");
            if(mysqli_num_rows($sql) > 0) {
                $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                $history[$i]['finish_date'] = $data[0]['created_at'];
            }else{
                $history[$i]['finish_date'] = $val['jam'];

            }

            $idS = $val['surcharge_id'];
            $sql = mysqli_query($db_conn, "SELECT name FROM `surcharges` WHERE `id`= '$idS'");
            if(mysqli_num_rows($sql) > 0) {
                $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                $history[$i]['surcharge_name'] = $data[0]['name'];
            }else{
                $history[$i]['surcharge_name'] = "";

            }

            $i+=1;
        }

        if(count($history)>0){
            $success = 1;
            $signupMsg = "Success";
            $status = 200;
        }else{
            $success = 0;
            $signupMsg = "Failed";
            $status = 204;
        }
        // $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$signupMsg, "history"=>$history]);
    }
    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$signupMsg, "history"=>$history]);
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;
 ?>
