<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
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
$today1 = date('Y-m-d');
$tokenizer = new Token();
$token = '';
$res = array();
$res1 = array();
$xenditResponse = array();
$id = "";

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
    $obj = json_decode(file_get_contents('php://input'));
    $packageID = $obj->packageID;
    $isMonthly = $obj->isMonthly;
    $redirectURL = $obj->redirectURL;
    $total = $obj->total;
    $phone="";

    $getPackage = mysqli_query($db_conn, "SELECT name, description, price, type FROM subscription_packages WHERE id='$packageID'");
    $package = mysqli_fetch_all($getPackage, MYSQLI_ASSOC);

    $getCredentials = mysqli_query($db_conn, "SELECT nama, phone, email FROM employees WHERE id='$token->id'");
    $credentials = mysqli_fetch_all($getCredentials, MYSQLI_ASSOC);
    if($credentials[0]['phone'][0]=="0"){
        $phone = "+62".substr($credentials[0]['phone'][0],1);
    }
    $price = (double)$total;
    $packageName = $package[0]['name'];
    $packageDescription = $package[0]['description'];
    $amountBilled = $price;
    $subtotal=$price;
    $surcharge=0;
    $tax=0;
    $discount=0;
    $grandTotal=$subtotal+$surcharge+$tax-$discount;
    $qty=1;
    $detailPrice = $price*$qty;
    $createTrx = mysqli_query($db_conn,"INSERT INTO subscription_transactions SET partner_id='$token->id_partner', subtotal='$price', surcharge='$surcharge', tax='$tax', grand_total='$grandTotal'");
    $trxID = mysqli_insert_id($db_conn);
    $insertDetail = mysqli_query($db_conn, "INSERT INTO subscription_transaction_details SET transaction_id='$trxID', item_id='$packageID', unit_price='$price', qty='$qty', price='$detailPrice'");
$params = [
    'external_id' => 'subscription_invoice_'.$token->id_partner.'_'.$trxID,
    'amount' => $amountBilled,
    'description' => 'Subscription Invoice #'.$trxID.' for '.$token->id_partner,
    'invoice_duration' => 86400,
    'customer' => [
        'given_names' => $credentials[0]['nama']??"",
        'email' => $credentials[0]['email']??"",
        'mobile_number' => $phone??""
        // 'address' => [
        //     [
        //         'city' => 'Jakarta Selatan',
        //         'country' => 'Indonesia',
        //         'postal_code' => '12345',
        //         'state' => 'Daerah Khusus Ibukota Jakarta',
        //         'street_line1' => 'Jalan Makan',
        //         'street_line2' => 'Kecamatan Kebayoran Baru'
        //     ]
        // ]
    ],
    'customer_notification_preference' => [
        'invoice_created' => [
            'whatsapp',
            'sms',
            'email',
            'viber'
        ],
        'invoice_reminder' => [
            'whatsapp',
            'sms',
            'email',
            'viber'
        ],
        'invoice_paid' => [
            'whatsapp',
            'sms',
            'email',
            'viber'
        ],
        'invoice_expired' => [
            'whatsapp',
            'sms',
            'email',
            'viber'
        ]
    ],
    'success_redirect_url' => 'https://ur-dev.codeontop.com/qr/snippets/InvoicePaid.php',
    'failure_redirect_url' => 'https://www.ur-hub.com',
    'currency' => 'IDR',
    'items' => [
        [
            'name' => $packageName,
            'quantity' => 1,
            'price' => $price
            // 'category' => 'Electronic',
            // 'url' => 'https=>//yourcompany.com/example_item'
        ]
    ]
    // 'fees' => [
    //     [
    //         'type' => 'ADMIN',
    //         'value' => 5000
    //     ]
    // ]
  ];
    $ch = curl_init();
    $timestamp = new DateTime();
    $body = json_encode($params);
    curl_setopt($ch, CURLOPT_URL, 'https://'.$_ENV['XENDIT_URL'].'/v2/invoices');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_USERPWD, $_ENV['XENDIT_KEY']. ':' . '');

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $curlResult = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    $insertCallback = mysqli_query($db_conn,"INSERT INTO xendit_service_callback SET content='$curlResult', type='Invoice', invoice_id='$trxID'");
    if($createTrx&&$insertDetail&&$insertCallback){
        $success=1;
        $msg="Transaksi berhasil";
        $status=200;

        $title="Menunggu Pembayaran Perpanjangan Langganan UR";
      $content= $partnerName.", mohon lakukan pembayaran sebesar ".$amountBilled." agar anda dapat menikmati layanan UR tanpa gangguan";
        $insertMessage = mysqli_query($db_conn,"INSERT INTO partner_messages SET partner_id='$partnerID', title='$title', content='$content'");
    $getEmployees = mysqli_query($db_conn, "SELECT id FROM employees WHERE id_partner='$partnerID' AND deleted_at IS NULL");
  while($emp = mysqli_fetch_assoc($getEmployees)){
      $employeeID = $emp['id'];
      $getToken = mysqli_query($db_conn, "SELECT tokens FROM device_tokens WHERE employee_id='$employeeID' AND deleted_at IS NULL");
      while($devID = mysqli_fetch_assoc($getToken)){
          $devToken = $devID['tokens'];
          $sendNotif=mysqli_query($db_conn, "INSERT INTO pending_notification SET title='$title', message='$content', dev_token='$devToken'");
      }
  }
    }else{
        $success=0;
        $msg="Transaksi gagal";
        $status=204;
    }
}
// validasi token
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "transaction_id"=>$trxID, "xendit_response"=>$curlResult]);
?>