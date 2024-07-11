<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require  __DIR__ . '/../../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
$dotenv->load();
//import require file
require '../../../db_connection.php';
require_once('../../auth/Token.php');

//init var
date_default_timezone_set('Asia/Jakarta');
$now = date('Y-m-d H:i:s', time());
$today = date('Y-m-d', time());
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
$ewallet_response = array();
$newID = "";

function generateTransactionID($db_conn, $id){
    $b = (explode("/",$id));
    $code = $b[0]."/".$b[1]."/".$b[2];
    $code1 = $b[0]."/".$b[1]."/".$b[2]."/".$b[3];
    $q = mysqli_query($db_conn,"SELECT id FROM `transaksi` WHERE id LIKE '%$code1%' AND transaksi.deleted_at IS NULL ORDER BY jam DESC LIMIT 1");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id1 = $res[0]['id'];
        $b = (explode("/",$id1));
        $c = (explode("-",$b[3]));
        if(isset($c[1]) && !empty($c[1])){
            $c[1] = (int) $c[1]+1;
        }else{
            $c[1]=1;
        }
        $index = (int) $c[0];
        if($index<10){
            $index = "00000".$index;
        }else if($index<100){
            $index = "0000".$index;
        }else if($index<1000){
            $index = "000".$index;
        }else if($index<10000){
            $index = "00".$index;
        }else if($index<100000){
            $index = "0".$index;
        }else{
            $index = $index;
        }
        $code = $code."/".$index."-".$c[1];
        return $code;
    }
}
function generateInvoiceID($db_conn, $id){
    $b = (explode("/",$id));
    $code = $b[0]."/".$b[1]."/".$b[2];
    $code1 = $b[0]."/".$b[1]."/".$b[2]."/".$b[3];
    $q = mysqli_query($db_conn,"SELECT invoice_code as id FROM `invoices` WHERE invoice_code LIKE '%$code%' AND invoices.deleted_at IS NULL ORDER BY created_at DESC LIMIT 1");
    // $q = mysqli_query($db_conn,"SELECT id FROM `transaksi` WHERE id LIKE '%$code1%' AND transaksi.deleted_at IS NULL ORDER BY jam DESC LIMIT 1");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        $id1 = $res[0]['id'];
        $b = (explode("/",$id1));
        $c = (explode("-",$b[3]));
        if(isset($c[1]) && !empty($c[1])){
            $c[1] = (int) $c[1]+1;
        }else{
            $c[1]=1;
        }
        $index = (int) $c[0];
        if($index<10){
            $index = "00000".$index;
        }else if($index<100){
            $index = "0000".$index;
        }else if($index<1000){
            $index = "000".$index;
        }else if($index<10000){
            $index = "00".$index;
        }else if($index<100000){
            $index = "0".$index;
        }else{
            $index = $index;
        }
        $code = $code."/".$index."-".$c[1];
        return $code;
    }
}


function getShiftID($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT MAX(id) as id FROM `shift` WHERE partner_id='$id' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['id'];
    }else{
        return 0;
    }
  }

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$newIDs = "";
$program_discount =0;
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    $obj = json_decode(json_encode($_POST));
    if(
        isset($obj->transactionID) && !empty($obj->transactionID)
        && isset($obj->paymentMethod) && !empty($obj->paymentMethod)
    ){
        
        $new_invoice_code = "";
        $items = array();
        $gamount = 0;
        $loop = 0;
        $trasactionID = explode(',', $obj->transactionID);
        foreach ($trasactionID as $value) {
            if(!empty($value)){
                
                $id = $value;
                $newID = generateTransactionID($db_conn, $id);
                $newIDs .= $newID.",";
                
                $allTrans = mysqli_query($db_conn, "SELECT t.id, t.id_partner, t.queue, t.status, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.program_discount, t.diskon_spesial, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, p.nama AS payment_method, u.name AS uname, t.diskon_spesial, invoices.id AS invoice_id, invoices.invoice_code, t.tenant_id, invoice_details.id AS invoice_details_id FROM transaksi t JOIN payment_method p ON p.id=t.tipe_bayar LEFT JOIN users u ON t.phone=u.phone LEFT JOIN invoice_details ON invoice_details.transaction_id=t.id LEFT JOIN invoices ON invoices.id=invoice_details.invoice_id WHERE t.id='$id'");
                $invoice_id = "0";
                $invoice_code = "";
                $tenant_id = "";
                $trans = mysqli_fetch_all($allTrans, MYSQLI_ASSOC);
                if(!empty($trans[0]['invoice_id'])){
                    $invoice_id = $trans[0]['invoice_id'];
                    if(!empty($trans[0]['invoice_code'])){
                        $invoice_code = $trans[0]['invoice_code'];
                        if($loop==0){
                            $new_invoice_code = generateInvoiceID($db_conn, $invoice_code);
                            $updateCodeInvoices = mysqli_query($db_conn, "UPDATE `invoices` SET invoice_code='$new_invoice_code' WHERE id='$invoice_id'");
                        }
                        $loop+=1;
                    }
                }
                if(!empty($trans[0]['invoice_details_id'])){
                    $invoice_details_id = $trans[0]['invoice_details_id'];
                    $updateDetailInvoice = mysqli_query($db_conn, "UPDATE `invoice_details` SET `transaction_id`='$newID' WHERE id='$invoice_details_id'");
                }
                if(!empty($trans[0]['tenant_id'])){
                    $tenant_id = $trans[0]['tenant_id'];
                }
                $total = $trans[0]['total'];
                $promo = $trans[0]['promo'];
                $service = $trans[0]['service'];
                $charge_ur = $trans[0]['charge_ur'];
                $tax = $trans[0]['tax'];
                $program_discount = $trans[0]['program_discount'];
                $phone = $trans[0]['phone'];
                $partnerID = $trans[0]['id_partner'];
                $status = $trans[0]['status'];
                $shiftID="0";
                if($obj->paymentMethod=='11' || $obj->paymentMethod==11 ){
                    $status = 5;
                    $shiftID =(int)getShiftID($partnerID, $db_conn);
                }else{
                    $status = 0;
                }
                if( $obj->paymentMethod=='1' || $obj->paymentMethod==1 || $obj->paymentMethod=='2' || $obj->paymentMethod==2 || $obj->paymentMethod=='3' || $obj->paymentMethod==3 || $obj->paymentMethod=='4' || $obj->paymentMethod==4 || $obj->paymentMethod=='6' || $obj->paymentMethod==6 || $obj->paymentMethod=='10' || $obj->paymentMethod==10){
                    $shiftID =(int)getShiftID($partnerID, $db_conn);
                }
        
                $delivery_fee=0;
                $allDeliv = mysqli_query($db_conn, "SELECT ongkir as delivery_fee FROM `delivery` WHERE transaksi_id='$id'");
                if(mysqli_num_rows($allDeliv)>0) {
                    $deliv = mysqli_fetch_all($allDeliv, MYSQLI_ASSOC);
                    $delivery_fee=$deliv[0]['delivery_fee'];
                }
                
                $diskon_spesial = $trans[0]['diskon_spesial'];
                $qDetails = mysqli_query($db_conn, "SELECT dt.qty AS quantity, dt.harga_satuan AS price, dt.id_menu AS id, m.nama AS name FROM detail_transaksi dt JOIN menu m ON dt.id_menu=m.id WHERE dt.id_transaksi='$id'");
                $details = mysqli_fetch_all($qDetails, MYSQLI_ASSOC);
                $il=0;
                    foreach ($details as $cart) {
                        $items[$il]= new \stdClass();
                        $items[$il]->id = $cart['id'];
                        $items[$il]->quantity = $cart['quantity'];
                        $items[$il]->name = $cart['name'];
                        $items[$il]->price = $cart['price'];
                         $il+=1; 
                    }
                
                
                
                    $COMMIT = mysqli_query($db_conn,"
                    START TRANSACTION;
                    SAVEPOINT '$newID';
                    COMMIT;
                    ");
                    $sql = "START TRANSACTION; ";
                    if($shiftID!="0"){
                        $sql .= "UPDATE transaksi SET tipe_bayar='$obj->paymentMethod', id='$newID', status='$status', shift_id='$shiftID' WHERE id='$obj->transactionID' ; ";
                    }else{
                        $sql .= "UPDATE transaksi SET tipe_bayar='$obj->paymentMethod', id='$newID', status='$status' WHERE id='$obj->transactionID' ; ";
                    }
                    $updateNotif = mysqli_query($db_conn,"UPDATE pending_notification SET id_trans='$newID' WHERE id_trans='$obj->transactionID'");
                    $sql .= "UPDATE `detail_transaksi` SET `id_transaksi`='$newID' WHERE id_transaksi='$obj->transactionID'; ";
                    $sql .= "UPDATE `delivery` SET transaksi_id='$newID' WHERE transaksi_id='$obj->transactionID'; ";
                    $sql .=" COMMIT;";
                    
                    if (mysqli_multi_query($db_conn,$sql)) {
                        do {
                            if ($r = mysqli_store_result($db_conn)) {
                                mysqli_free_result($r);
                        
                            }
                            } while (mysqli_more_results($db_conn) && mysqli_next_result($db_conn));
                    $paymentMethod = $obj->paymentMethod;
                    if($paymentMethod==1 || $paymentMethod=="1" || $paymentMethod==2 || $paymentMethod=="2" || $paymentMethod==3 || $paymentMethod=="3" || $paymentMethod==4 || $paymentMethod=="4" || $paymentMethod==10 || $paymentMethod=="10"){
                        $pmq = mysqli_query($db_conn, "SELECT nama FROM `payment_method` WHERE id='$paymentMethod'");
                        $pm = mysqli_fetch_all($pmq, MYSQLI_ASSOC);
                        $ewallet_type= $pm[0]['nama'];
                        $tservice = ceil(($total-$promo-$diskon_spesial)*$service/100);
                        $ttax = ceil(($total-$promo-$diskon_spesial+$tservice+$charge_ur)*$tax/100);
                        
                        $amount = (int) ceil($total-$promo-$diskon_spesial+$tservice+$ttax+$delivery_fee+$charge_ur-$program_discount);
                        $gamount+=$amount;
                        $phone1 = substr($phone, 1);
                        $phone1 = '+62'.$phone1;
                        if(empty($tenant_id)){
                            if($paymentMethod=="1" || $paymentMethod==1 || $paymentMethod=="3" || $paymentMethod==3 ||$paymentMethod=="4" || $paymentMethod==4 ||$paymentMethod=="10" || $paymentMethod==10){
                            if($paymentMethod=="1" || $paymentMethod==1){
                                $params = [
                                    'external_id' => $newID,
                                    'reference_id' => $newID,
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
                            }else if($paymentMethod=="3" || $paymentMethod==3){
                                $params = [
                                    'external_id' => $newID,
                                    'reference_id' => $newID,
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
                                }else if($paymentMethod=="4" || $paymentMethod==4){
                                    $params = [
                                        'external_id' => $newID,
                                        'reference_id' => $newID,
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
                                }else if($paymentMethod=="10" || $paymentMethod==10){
                                    $params = [
                                        'reference_id' => $newID,
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
                                    
                                    
                                    // curl_setopt($ch, CURLOPT_URL, $url);
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
                                    $ewallet_response = $result;
                                    curl_close($ch);
                                } else if($paymentMethod=="2" || $paymentMethod==2){
                                    $auth = $_ENV['MIDTRANS_KEY'];
                                    $params = [
                                        'payment_type' => 'gopay',
                                        'transaction_details' => [
                                            'order_id' => $id,
                                            'gross_amount' => $amount,
                                        ],
                                        'customer_details' => [
                                            'phone'=> $phone
                                        ]
    
                                    ];
                                    $ch = curl_init();
                                    $timestamp = new DateTime();
                                    $body = json_encode($params);
                                    curl_setopt($ch, CURLOPT_URL, $_ENV['MIDTRANS_URL']);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                    curl_setopt($ch, CURLOPT_POST, 1);
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                                                
                                    $headers = array();
                                    $headers[] = 'Content-Type: application/json';
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                        "Accept: application/json",
                                        "Content-Type: application/json",
                                        "Authorization: $auth"
                                      ));
                                    $result = curl_exec($ch);
                                    if (curl_errno($ch)) {
                                        echo 'Error:' . curl_error($ch);
                                    }
                                    $ewallet_response = $result;
                                    curl_close($ch);
                                }
                            }
                        }
                            $msg = "Berhasil mengubah metode pembayaran";
                            $success = 1;
                            $status=200;
                        }else{
                                
                        $sql = "START TRANSACTION; ROLLBACK TO '$newID';";
                        $act= mysqli_multi_query($db_conn,$sql)or die(mysqli_error($db_conn));
                        $msg = "Gagal ubah metode pembayaran";
                        $success = 0;
                        $status=400;
                    }

            }
        }
        $newIDs = rtrim($newIDs, ", ");
        if(!empty($tenant_id) && !empty($phone)){

                            
            if($paymentMethod=="1" || $paymentMethod==1){
                $params = [
                    'external_id' => $new_invoice_code,
                    'reference_id' => $new_invoice_code,
                    'currency' => 'IDR',
                    'amount' => $gamount,
                    'checkout_method'=>'ONE_TIME_PAYMENT',
                    'channel_code'=> 'ID_OVO',
                    'channel_properties'=> [
                        'mobile_number'=> $phone1,
                    ],
                    'ewallet_type' => 'ID_OVO',
                    'phone' => $phone,
                    'items' => $items
                    
                ];
            }else if($paymentMethod=="3" || $paymentMethod==3){
                $params = [
                    'external_id' => $new_invoice_code,
                    'reference_id' => $new_invoice_code,
                    'currency' => 'IDR',
                    'amount' => $gamount,
                    'checkout_method'=>'ONE_TIME_PAYMENT',
                    'channel_code'=> 'ID_DANA',
                    'channel_properties'=> [
                        'success_redirect_url'=> 'https://ur-hub.com/',
                    ],
                        'ewallet_type' => 'ID_DANA',
                        'phone' => $phone,
                        'items' => $items
                        
                    ];
                }else if($paymentMethod=="4" || $paymentMethod==4){
                    $params = [
                        'external_id' => $new_invoice_code,
                        'reference_id' => $new_invoice_code,
                        'currency' => 'IDR',
                        'amount' => $gamount,
                        'checkout_method'=>'ONE_TIME_PAYMENT',
                        'channel_code'=> 'ID_LINKAJA',
                        'channel_properties'=> [
                            'success_redirect_url'=> 'https://ur-hub.com/',
                        ],
                        'ewallet_type' => 'ID_LINKAJA',
                        'phone' => $phone,
                        'items' => $items
                        
                    ];
                }else if($paymentMethod=="10" || $paymentMethod==10){
                    $params = [
                        'reference_id' => $new_invoice_code,
                        'currency' => 'IDR',
                        'amount' => $gamount,
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
                    // curl_setopt($ch, CURLOPT_URL, $url);
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
                    $ewallet_response = $result;
                    curl_close($ch);
            }

    }else{
        $success=0;
        $msg="Missing required fields";
        $status=400;
    }
}
if($status==204){
    http_response_code(200);
}else{
    http_response_code($status);
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "newID"=>$newIDs, "ewallet_response"=>$ewallet_response]);
?>