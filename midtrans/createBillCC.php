<?php
//hanya untuk CC
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Accept: application/json");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../v2/db_connection.php';
//allow char for random order_id
$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

//define data from frontend to variable
$data = json_decode(json_encode($_POST));
$gross_amount = (int)mysqli_real_escape_string($db_conn, trim($data->gross_amount));
$master_id = mysqli_real_escape_string($db_conn, trim($data->master_id));
$token_id =  mysqli_real_escape_string($db_conn, trim($data->token_id));

$deposit = mysqli_query($db_conn, "SELECT name AS master_name, deposit_balance FROM master WHERE id='$master_id'");
$all = mysqli_fetch_all($deposit, MYSQLI_ASSOC);
foreach ($all as $key) {
    $balance_before = $key['deposit_balance'];
    $balance_after = (int)$key['deposit_balance'];
}
$balance_after += $gross_amount;
function generate_string($input, $strength = 10) {
    $input_length = strlen($input);
    $random_string = '';
    for($i = 0; $i < $strength; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }
    return $random_string;
}

$order_id = $master_id ."-";
$order_id .= generate_string($permitted_chars, 10);
//initial cURL
$ch = curl_init();

//set trasanction_detail, bank, expiry
$data1 = '{"payment_type": "credit_card"}';
$td['custom_field1'] = "deposit";
$td['transaction_details'] = array("order_id"=>"$order_id","gross_amount"=> $gross_amount);
$td['credit_card'] = array("token_id"=>"$token_id","authentication"=> true);
$td['expiry'] = array("unit"=>"day","duration"=> 1);

//merge set array diatas agar dapat di execute oleh API midtrans
$data1 = json_decode($data1,true);
$a = array_merge($data1,$td);
$a = json_encode($a);

//set option curl
curl_setopt($ch, CURLOPT_URL, 'https://api.sandbox.midtrans.com/v2/charge');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $a);

//set headers
$headers = array();
$headers[] = 'Accept: application/json';
//< adalah key server yang di ubah menggunakan base64>
$headers[] = 'Authorization: Basic <U0ItTWlkLXNlcnZlci0wSmUxU2FqUEJpVVgxclNXc283aERTcG8=>';
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

//execute
$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}else{
    //response json dari API midtrans di ubah menjadi array
    $result = json_decode($result);
    foreach ($result as $x => $value) {
        // echo gettype($value);
        if(gettype($value)=="array"){
            foreach ($value as $val) {
                $val = json_decode(json_encode($val));
                foreach($val as $y => $v){
                    $arr[$y] = $v;
                }
            }
        }else{
            $arr[$x] = $value;
        }
    }
}

//chek status code
if($arr['status_code'] == '201'){
    $created = $arr['transaction_time'];
    //membedakan action bni dan bca Atau permata karena response berbeda
    $InsertBill = mysqli_query(
        $db_conn,
        "INSERT INTO `master_deposit` (`id_master`, `nominal_top_up`, `balance_before`, `balance_after`, `status`,`payment_type`,`deposit_code`) 
        VALUES ('$master_id','$gross_amount','$balance_before','$balance_after','0','Credit Card','$order_id')"
    );
    
    if ($InsertBill) {
        echo json_encode(["success" => 1, "msg" => "Bill Success Insert", "arr"=>$arr]);
    } else {
        echo json_encode(["success" => 0, "msg" => "Bill Fail Insert", "arr"=>$arr]);
    }

}else{
    echo json_encode(["success" => 0, "msg" => "Bill Fail Insert"]);
}
curl_close($ch);
?>