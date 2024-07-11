<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
$token = '';
$res = array();
$result = array();

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
    $today = date("Y-m-d H:i:s");
    $obj = json_decode(file_get_contents('php://input'));
    if(isset($obj->accountID) && isset($obj->amount)){
        $getMinimum = mysqli_query($db_conn, "SELECT name, value FROM settings WHERE id=30");
        while($settings = mysqli_fetch_assoc($getMinimum)){
            $minimumTransaction=(int)$settings['value'];
        }
        
        $partnerID = $token->id_partner;
        if(isset($obj->partnerID)){
            $partnerID = $obj->partnerID;
        }
        
        $all = 0;
        if(isset($obj->all)){
            $all = $obj->all;
        }
        
        if((int)$obj->amount>=$minimumTransaction){
            $getSettings = mysqli_query($db_conn, "SELECT name, value FROM settings WHERE id =28");
            while($settings = mysqli_fetch_assoc($getSettings)){
                $charge=$settings['value'];
            }
            
            if($all != 1){
                $checkAmount="SELECT balance FROM ewallet_balances WHERE partner_id='$partnerID' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1";
                $qTB = mysqli_query($db_conn, $checkAmount);
                $resTB = mysqli_fetch_all($qTB, MYSQLI_ASSOC);
                $availableBalance = $resTB[0]['balance'];
                if((int)$availableBalance<$obj->amount){
                    $success =0;
                    $status =400;
                    $msg = "Nominal penarikan lebih besar daripada saldo";
                }else{
                    $amount = $obj->amount;
                    $query = "INSERT INTO withdrawal_requests SET partner_id='$partnerID', employee_id='$token->id', bank_account_id='$obj->accountID', amount='$obj->amount'";
                    $q = mysqli_query($db_conn, $query);
                    $iid = mysqli_insert_id($db_conn);
                    if ($q) {
                        $sqlGetBank = mysqli_query($db_conn,"SELECT name, account_no, account_name, xendit_code FROM partner_bank_accounts pba JOIN available_banks ab ON pba.bank_id=ab.id WHERE pba.id='$obj->accountID'");
                        while($row = mysqli_fetch_assoc($sqlGetBank)){
                            $bankName=$row['name'];
                            $accountNo=$row['account_no'];
                            $accountName = $row['account_name'];
                            $xenditCode=$row['xendit_code'];
                        }
                        $title = "Penarikan Saldo Penghasilan";
                        $description = "Penarikan ke ".$bankName." - ".$accountNo." - ".$accountName;
                        $remaining = $availableBalance-$amount;
                        $insertWithdrawal = mysqli_query($db_conn, "INSERT INTO ewallet_balances SET partner_id='$partnerID', type='Credit', amount='$obj->amount', balance='$remaining', title='$title', description='$description', reference_id='$iid'");
                        $iid2 = mysqli_insert_id($db_conn);
    
                        $title = "Withdrawal Fee";
                        $description= "Biaya penarikan saldo";
                        $remaining2 = $remaining-$charge;
                        $insertCharge = mysqli_query($db_conn, "INSERT INTO ewallet_balances SET partner_id='$partnerID', type='Credit', amount='$charge', balance='$remaining2', title='$title', description='$description', reference_id='$iid'");
    
                        if($insertWithdrawal){
                            $params = [
                                'external_id' => "Disbursement-".$today."-".$iid,
                                'amount' => $amount,
                                'bank_code'=>$xenditCode,
                                'account_holder_name'=> $accountName,
                                'account_number'=> $accountNo,
                                'description'=> 'Withdrawal partner '.$partnerID." ID:".$iid
                                ];
                            $ch = curl_init();
                            $timestamp = new DateTime();
                            // curl_setopt($ch, CURLOPT_URL, $url);
                            $body = json_encode($params);
    
                            curl_setopt($ch, CURLOPT_URL, 'https://'.$_ENV['XENDIT_URL'].'/disbursements');
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                            curl_setopt($ch, CURLOPT_USERPWD, $_ENV['XENDIT_DISBURSEMENT_KEY']. ':' . '');
    
                            $headers = array();
                            $headers[] = 'Content-Type: application/json';
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            $curlResult = curl_exec($ch);
                            if (curl_errno($ch)) {
                                echo 'Error:' . curl_error($ch);
                            }
                            curl_close($ch);
    
                            $email = "harrytanaka420@gmail.com";
                            $body = "Ada request withdrawal baru dengan ID ".$iid2.". Mohon cek database";
                            $insertEmail=mysqli_query($db_conn, "INSERT INTO pending_email SET email='$email', partner_id='$partnerID', subject='Withdrawal', body='$body'");
                            $title="Penarikan Dana Sedang Diproses";
                            $content="Penarikan dana ke ".$bankName." sedang diproses. Mohon tunggu 1x24 jam";
                            $insertCallback = mysqli_query($db_conn,"INSERT INTO xendit_service_callback SET content='$curlResult', withdrawal_id='$iid', type='Disbursement'");
                            $getEmployees = mysqli_query($db_conn, "SELECT id FROM employees WHERE withdraw_notification=1 AND id_partner='$partnerID' AND deleted_at IS NULL");
                            $insertMessage = mysqli_query($db_conn,"INSERT INTO partner_messages SET partner_id='$partnerID', title='$title', content='$content'");
                            while($emp = mysqli_fetch_assoc($getEmployees)){
                                $employeeID = $emp['id'];
                                $getToken = mysqli_query($db_conn, "SELECT tokens FROM device_tokens WHERE employee_id='$employeeID' AND deleted_at IS NULL");
                                while($devID = mysqli_fetch_assoc($getToken)){
                                    $devToken = $devID['tokens'];
                                    $sendNotif=mysqli_query($db_conn, "INSERT INTO pending_notification SET title='$title', message='$content', dev_token='$devToken'");
                                }
                            }
                            $success =1;
                            $status =200;
                            $msg = "Berhasil withdraw";
                        }else{
                            $success =0;
                            $status =400;
                            $msg = "Gagal withdraw";
                        }
    
                    } else {
                        $success =0;
                        $status =204;
                        $msg = "Gagal withdraw. Mohon coba lagi";
                    }
                }
            } else {
                $partners = "SELECT id FROM partner WHERE id_master='$token->id_master'";
                $queryPartners = mysqli_query($db_conn, $partners);
                $fetchPartners = mysqli_fetch_all($queryPartners, MYSQLI_ASSOC);
                if(count($fetchPartners) > 0){
                    $amountWithdrawn = 0;
                    foreach($fetchPartners as $p){
                        $pid = $p["id"];
                        $checkAmount="SELECT balance FROM ewallet_balances WHERE partner_id='$pid' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1";
                        $qTB = mysqli_query($db_conn, $checkAmount);
                        $resTB = mysqli_fetch_all($qTB, MYSQLI_ASSOC);
                        $availableBalance = $resTB[0]['balance'];
                        $amountWithdrawn += $availableBalance;
                        
                        if($availableBalance > 0){
                            $amount = $availableBalance;
                            $query = "INSERT INTO withdrawal_requests SET partner_id='$pid', employee_id='$token->id', bank_account_id='$obj->accountID', amount='$obj->amount'";
                            $q = mysqli_query($db_conn, $query);
                            $iid = mysqli_insert_id($db_conn);
                            if ($q) {
                                $sqlGetBank = mysqli_query($db_conn,"SELECT name, account_no, account_name, xendit_code FROM partner_bank_accounts pba JOIN available_banks ab ON pba.bank_id=ab.id WHERE pba.id='$obj->accountID'");
                                while($row = mysqli_fetch_assoc($sqlGetBank)){
                                    $bankName=$row['name'];
                                    $accountNo=$row['account_no'];
                                    $accountName = $row['account_name'];
                                    $xenditCode=$row['xendit_code'];
                                }
                                $title = "Penarikan Saldo Penghasilan";
                                $description = "Penarikan ke ".$bankName." - ".$accountNo." - ".$accountName;
                                $remaining = $availableBalance-$amount;
                                $insertWithdrawal = mysqli_query($db_conn, "INSERT INTO ewallet_balances SET partner_id='$pid', type='Credit', amount='$amount', balance='$remaining', title='$title', description='$description', reference_id='$iid'");
                                $iid2 = mysqli_insert_id($db_conn);
            
                                $title = "Withdrawal Fee";
                                $description= "Biaya penarikan saldo";
                                $remaining2 = $remaining-$charge;
                                $insertCharge = mysqli_query($db_conn, "INSERT INTO ewallet_balances SET partner_id='$pid', type='Credit', amount='$charge', balance='$remaining2', title='$title', description='$description', reference_id='$iid'");
            
                                if($insertWithdrawal){
                                    $params = [
                                        'external_id' => "Disbursement-".$today."-".$iid,
                                        'amount' => $amount,
                                        'bank_code'=>$xenditCode,
                                        'account_holder_name'=> $accountName,
                                        'account_number'=> $accountNo,
                                        'description'=> 'Withdrawal partner '.$pid." ID:".$iid
                                        ];
                                    $ch = curl_init();
                                    $timestamp = new DateTime();
                                    // curl_setopt($ch, CURLOPT_URL, $url);
                                    $body = json_encode($params);
            
                                    curl_setopt($ch, CURLOPT_URL, 'https://'.$_ENV['XENDIT_URL'].'/disbursements');
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                    curl_setopt($ch, CURLOPT_POST, 1);
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                                    curl_setopt($ch, CURLOPT_USERPWD, $_ENV['XENDIT_DISBURSEMENT_KEY']. ':' . '');
            
                                    $headers = array();
                                    $headers[] = 'Content-Type: application/json';
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                    $curlResult = curl_exec($ch);
                                    if (curl_errno($ch)) {
                                        echo 'Error:' . curl_error($ch);
                                    }
                                    curl_close($ch);
            
                                    $email = "harrytanaka420@gmail.com";
                                    $body = "Ada request withdrawal baru dengan ID ".$iid2.". Mohon cek database";
                                    $insertEmail=mysqli_query($db_conn, "INSERT INTO pending_email SET email='$email', partner_id='$pid', subject='Withdrawal', body='$body'");
                                    $title="Penarikan Dana Sedang Diproses";
                                    $content="Penarikan dana ke ".$bankName." sedang diproses. Mohon tunggu 1x24 jam";
                                    $insertCallback = mysqli_query($db_conn,"INSERT INTO xendit_service_callback SET content='$curlResult', withdrawal_id='$iid', type='Disbursement'");
                                    $getEmployees = mysqli_query($db_conn, "SELECT id FROM employees WHERE withdraw_notification=1 AND id_partner='$pid' AND deleted_at IS NULL");
                                    $insertMessage = mysqli_query($db_conn,"INSERT INTO partner_messages SET partner_id='$pid', title='$title', content='$content'");
                                    while($emp = mysqli_fetch_assoc($getEmployees)){
                                        $employeeID = $emp['id'];
                                        $getToken = mysqli_query($db_conn, "SELECT tokens FROM device_tokens WHERE employee_id='$employeeID' AND deleted_at IS NULL");
                                        while($devID = mysqli_fetch_assoc($getToken)){
                                            $devToken = $devID['tokens'];
                                            $sendNotif=mysqli_query($db_conn, "INSERT INTO pending_notification SET title='$title', message='$content', dev_token='$devToken'");
                                        }
                                    }
                                    $success =1;
                                    $status =200;
                                    $msg = "Berhasil withdraw";
                                }else{
                                    $success =0;
                                    $status =400;
                                    $msg = "Gagal withdraw";
                                }
            
                            } else {
                                $success =0;
                                $status =204;
                                $msg = "Gagal withdraw. Mohon coba lagi";
                            }
                        }
                        // if((int)$availableBalance<$obj->amount){
                            // $success =0;
                            // $status =400;
                            // $msg = "Nominal penarikan lebih besar daripada saldo";
                        // }else{
                        // }
                    }
                    $amount = $amountWithdrawn;
                }else{
                    $success =0;
                    $status =204;
                    $msg = "Penarikan Gagal";
                }
            }
        }else{
            $success =0;
            $status =204;
            $msg = "Minimal penarikan adalah ".$minimumTransaction;
        }

    }else{
        $success =0;
        $status =400;
        $msg = "Data tidak lengkap";
    }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "bankName"=>$bankName, "accountNo"=>$accountNo, "accountName"=>$accountName, "amount"=>$amount, "xenditResponse"=>$curlResult]);
?>