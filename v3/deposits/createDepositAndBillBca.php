<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php"); 
require_once("../connection.php");
require '../../db_connection.php';
$permitted_chars = '0123456789';
date_default_timezone_set('Asia/Jakarta');
$headers = apache_request_headers();
$token = '';
    
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
    $success = 0;
    
}else{
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);

    $gross_amount = (int) $obj['gross_amount'];
    $bank = $obj['bank'];
    $master_id = $tokenDecoded->masterID;
    $master_name = "";
    $cus_name = "Deposit ".$master_name;
    
    $select = ($deposit = mysqli_query($db_conn, "SELECT master.name AS master_name, master.deposit_balance FROM master WHERE master.id='$master_id'"));
    $all = mysqli_fetch_all($deposit, MYSQLI_ASSOC);
    foreach ($all as $key) {
        $master_name = $key['master_name'];
        $balance_before = $key['deposit_balance'];
        $balance_after = (int)$key['deposit_balance'];
    }
    $balance_after += $gross_amount;
    
    function generate_string($input, $strength = 6) {
        $input_length = strlen($input);
        $random_string = '';
        for($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
        return $random_string;
    }
    $data1 = array();
    $random_string = generate_string($permitted_chars, 6);
    $va_num = '12791'.$master_id.$random_string;
    $cn = $master_id.$random_string;
    $is = '{"Indonesian": "Sukses","English": "Success"}';
    $today = date('Y-m-d H:i:s');
    $dep_code = "API-BCA-".$va_num;
    $data1['order_id']=$dep_code;
    $InsertDeposit = mysqli_query(
        $db_conn,
        "INSERT INTO `master_deposit` (`id_master`, `nominal_top_up`, `balance_before`, `balance_after`, `status`, `bank_code`,`payment_type`,`deposit_code`,`va_number`) 
        VALUES ('$master_id','$gross_amount','$balance_before','$balance_after','0','$bank','Virtual Account','$dep_code','$va_num')"
    );
    if($InsertDeposit){
        $strGross_amount = $gross_amount.".00";
        $InsertBill = mysqli_query(
            $db_conn,
            "INSERT INTO `bills`(`company_code`, `customer_number`, `inquiry_status`, `inquiry_reason`, `request_id`, `sub_company`, `customer_name`, `currency_code`, `total_amount`, `detail_bills`, `free_texts`, `additional_data`, `transaction_date`, `reference`, `paid_amount`, `flag_advice`, `paid`) 
            VALUES ('12791','$cn','00','$is','00','00000','$cus_name','IDR','$strGross_amount','[]','[]',' ','$today',' ',0,' ',0)"
        );
            
            if ($InsertBill) {
               $success = 1; 
               $msg = "Bill Success Insert"; 
               $no_va = $va_num;
               $order_id = $dep_code;
               $status = 200;
            } else {
                $success = 0; 
                $msg = "Bill Failed"; 
                $no_va = mysqli_error($db_conn);
                $order_id = mysqli_error($db_conn);
                $status = 204;
            }
    }else{
        $success = 0; 
        $msg = "Deposit Fail Insert"; 
        $no_va = mysqli_error($db_conn);
        $order_id = mysqli_error($db_conn);
        $status = 400;
    }

    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "history"=>$res, "no_va"=>$no_va, "order_id"=>$order_id]);  

?>