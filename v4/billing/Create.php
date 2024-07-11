<?php    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../../v3/employeeModels/employeeManager.php"); 
require_once('../auth/Token.php');

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
$test = 'samlekom';
$data = [];
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$db = connectBase();
$tokenizer = new Token($db);
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
$success=0;
$msg = 'Failed'; 
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg']; 
}else{
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    $partnerId = $token->id_partner;
    $cycle = $obj['cycle'];
    $packageId = $obj['package_id'];
    if (isset($cycle) && isset($packageId)){
        $query = "
            INSERT INTO subscription_transactions(
                partner_id,
                package_id,
                subtotal,
                grand_total,
                status,
                payment_method,
                created_at
            )
            SELECT
            	'$partnerId' AS partner_id,
            	'$packageId' AS package_id,
                sp.price AS subtotal,
                sp.price AS grand_total,
                'PENDING' AS status,
                'QRIS UR' payment_method,
                NOW() AS created_at
            FROM subscription_packages AS sp
            WHERE sp.id = '$packageId'
            AND sp.type = '$cycle'
        ";
        $insert = mysqli_query($db_conn, $query);
        if ($insert){
            $insertId = $db_conn->insert_id;
            $refId = "SUBS/$partnerId/$insertId";
            $row = mysqli_query($db_conn, "SELECT grand_total FROM subscription_transactions WHERE id = $insertId");
            $row = mysqli_fetch_all($row, MYSQLI_ASSOC)[0];
            $total = (int) $row['grand_total'];
            $params = [
                "reference_id" => $refId,
                "currency" => "IDR",
                "amount" => $total,
                "type" => "DYNAMIC",
                "channel_code" => "ID_DANA",
                "metadata" => [
                    "branch_code" => "tree_branch",
                ],
            ];
            
            $ch = curl_init();
            $timestamp = new DateTime();
            // curl_setopt($ch, CURLOPT_URL, $url);
            $body = json_encode($params);
            curl_setopt(
            $ch,
                CURLOPT_URL,
                "https://" .
                    $_ENV["XENDIT_URL"] .
                    "/qr_codes"
            );
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_USERPWD, $_ENV['XENDIT_KEY']. ':' . '');
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = "api-version: 2022-07-31";
            $headers[] = "webhook-url: " . $_ENV["BASEURL"] . "xendit/qris/CallbackBilling.php";
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                $row = mysqli_query($db_conn, "UPDATE subscription_transactions SET status='EXPIRED', deleted_at=NOW() WHERE id = $insertId");
                
                $msg = "Gagal membuat transaksi";
                $success = 0;
                $status=204;
            } else {
                $ewallet_response = $result;
                $ewallet_response_returned = json_decode($ewallet_response);
                $qrString =  $ewallet_response_returned->qr_string;
                $expiryDate = explode('.', str_replace('T', ' ', $ewallet_response_returned->expires_at))[0];
                $updateQR = mysqli_query($db_conn, "UPDATE subscription_transactions SET qr_string='$qrString', expired_at='$expiryDate' WHERE id='$insertId'"); 
                curl_close($ch);
                $UpdateCallback = mysqli_query(
                    $db_conn,
                    "INSERT INTO `xendit_callbacks`(`transaction_id`, `value`, `created_at`) VALUES ('$refId', '$result', NOW())"
                );
                $data['total'] = $total;
                $data['qr_string'] = $qrString;
                $msg = "Berhasil membuat transaksi";
                $success = 1;
                $status=200;
            }
        } else {
            $msg = "Gagal membuat transaksi";
            $success = 0;
            $status=204;
        }
    } else {
        $msg = "Request body tidak lengkap";
        $success = 0;
        $status=400;
    }
}
    
        
$signupJson = json_encode([
    "msg"=>$msg, 
    "success"=>$success,
    "status"=>$status, 
    "data"=>$data
    ]);  
if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
echo $signupJson;

 ?>
 