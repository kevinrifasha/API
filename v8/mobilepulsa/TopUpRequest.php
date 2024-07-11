<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

//import require file
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
$token   = '';
$res     = array();
$success = 0;

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status  = $tokenValidate['status'];
    $msg     = $tokenValidate['msg'];
    $success = 0;
}else{
    $obj = json_decode(file_get_contents('php://input'));

    if(isset($obj->hp) && !empty($obj->hp)
        && isset($obj->pulsa_code) && !empty($obj->pulsa_code)){

        $username   = "085155053040";
        $apiKey     = "7296075190d9d5f7";
        $signature  = md5($username.$apiKey."bl");
        $json = array(
            "commands"   => "balance",
            "username"   => $username,
            "sign"       => $signature,
        );
        $json = json_encode($json);
        $url = "https://testprepaid.mobilepulsa.net/v1/legacy/index/";
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        if(curl_errno($ch)){
            $msg = 'Request Error:' . curl_error($ch);
        }
        curl_close($ch);
        $response   = json_decode($data);

        if((int) $response->data->balance >= (int) $obj->price){
            $ref_id     = "TOPUP".date("ymdhis");
            $type = $obj->type;
            $operator = $obj->operator;
            $price = $obj->price;
            $payment_method = $obj->payment_method;
            $packet = $obj->packet;
            $obj=json_encode($obj);
            $q = mysqli_query($db_conn, "INSERT INTO `transaction_mobilepulsa`(`tranasaction_code`, `phone`, `type`, `operator`, `price`, `status`, `created_at`, `data`, `payment_method`, `packet`) VALUES ('$ref_id', '$token->phone', '$type', '$operator', '$price', '0', NOW(), '$obj', '$payment_method', '$packet')");
            if($q){
                $items[0]= new \stdClass();
                $items[0]->id = $ref_id;
                $items[0]->quantity = '1';
                $items[0]->name = $operator.' - '.$type;
                $items[0]->price = $price;

                $pmq = mysqli_query($db_conn, "SELECT nama FROM `payment_method` WHERE id='$payment_method'");
                $pm = mysqli_fetch_all($pmq, MYSQLI_ASSOC);
                $ewallet_type= $pm[0]['nama'];
                $amount = (int) $price;

                $phone1 = substr($token->phone, 1);
                $phone1 = '+62'.$phone1;

                if($payment_method=="1" || $payment_method==1){
                    $params = [
                        'external_id' => $ref_id,
                        'reference_id' => $ref_id,
                        'id'=>$ref_id,
                        'currency' => 'IDR',
                        'amount' => $amount,
                        'checkout_method'=>'ONE_TIME_PAYMENT',
                        'channel_code'=> 'ID_OVO',
                        'channel_properties'=> [
                            'mobile_number'=> $phone1,
                                            ],
                        'ewallet_type' => 'ID_OVO',
                        'phone' => $phone,
                        'items' => $items
                    ];
                }else if($payment_method=="3" || $payment_method==3){
                    $params = [
                        'external_id' => $ref_id,
                        'reference_id' => $ref_id,
                        'id'=>$ref_id,
                        'currency' => 'IDR',
                        'amount' => $amount,
                        'checkout_method'=>'ONE_TIME_PAYMENT',
                        'channel_code'=> 'ID_DANA',
                        'channel_properties'=> [
                            'success_redirect_url'=> 'https://ur-hub.com/',
                                            ],
                        'ewallet_type' => 'ID_DANA',
                        'phone' => $phone,
                        'items' => $items
                    ];
                }else if($payment_method=="4" || $payment_method==4){
                    $params = [
                        'external_id' => $ref_id,
                        'reference_id' => $ref_id,
                        'id'=>$ref_id,
                        'currency' => 'IDR',
                        'amount' => $amount,
                        'checkout_method'=>'ONE_TIME_PAYMENT',
                        'channel_code'=> 'ID_LINKAJA',
                        'channel_properties'=> [
                            'success_redirect_url'=> 'https://ur-hub.com/',
                                            ],
                        'ewallet_type' => 'ID_LINKAJA',
                        'phone' => $phone,
                        'items' => $items
                    ];
                }else if($payment_method=="10" || $payment_method==10){
                    $params = [
                        'reference_id' => $ref_id,
                        'currency' => 'IDR',
                        'amount' => $amount,
                        'checkout_method'=>'ONE_TIME_PAYMENT',
                        'channel_code'=> 'ID_SHOPEEPAY',
                        'channel_properties'=> [
                            'success_redirect_url'=> 'https://ur-hub.com/',
                        ],
                        'metadata' => [
                            'branch_code' => 'tree_branch'
                        ]
                    ];
                }
                $ch = curl_init();
                $timestamp = new DateTime();
                $body = json_encode($params);

                curl_setopt($ch, CURLOPT_URL, 'https://'.$_ENV['XENDIT_URL'].'/ewallets/charges');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                curl_setopt($ch, CURLOPT_USERPWD, $_ENV['XENDIT_KEY']. ':' . '');

                $headers = array();
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo 'Error:' . curl_error($ch);
                }
                $res = $result;
                curl_close($ch);

                $status = 200;
                $success=1;
                $msg    = "Berhasil";
            }else{
                $status = 204;
                $msg    = "Failed";
            }
        }else{
            $status = 400;
            $msg    = "Operator Sedang Dalam gangguan";
        }

    } else {
        $status = 400;
        $msg    = "Missing Required Field";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "data"=>$res]);
?>
