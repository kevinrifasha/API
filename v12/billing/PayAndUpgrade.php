<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
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
$value = array();
$success=0;
$msg = 'Failed';
$cycle = $_GET['cycle'];

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    
    $amountBilled = 0;
    $duration = 2;
    $duration_metric = 'Hour';
    $data = json_decode(file_get_contents('php://input'));
    $date = date('Y-m-d', strtotime($data->reservation_time));
    $dateShow = date('d-m-Y', strtotime($data->reservation_time));
    $queryRsvData = "SELECT
      fr.id,
      m.idmeja,
      fr.meja_id,
      fr.table_group_id,
      fr.name,
      fr.description,
      fr.capacity,
      fr.minimum_transaction,
      fr.booking_price,
      fr.duration,
      fr.duration_metric,
      CASE WHEN (
        DATE(r.reservation_time) = '$date'
      ) THEN '1' ELSE '0' END AS reservationStatus
    FROM
      for_reservation fr
      LEFT OUTER JOIN reservations r ON r.for_reservation_id = fr.id
      AND r.partner_id = '$data->partner_id'
      AND DATE(r.reservation_time) = '$date'
      AND r.deleted_at IS NULL
      JOIN meja m ON m.id=fr.meja_id
    WHERE
      fr.id='$data->frID'
      AND fr.partner_id = '$data->partner_id'
      AND fr.deleted_at IS NULL";
    $getRsvData = mysqli_query($db_conn, $queryRsvData);
    $rsv = mysqli_fetch_assoc($getRsvData);
    $reservationStatus = $rsv['reservationStatus'];
    $duration = $rsv['duration'];
    $duration_metric = $rsv['duration_metric'];
    $minimumTransaction = $rsv['minimum_transaction'];
    $bookingPrice = $rsv['booking_price'];
    $capacity = $rsv['capacity'];
    $tableName = $rsv['idmeja'];
    $tableID = $rsv['meja_id'];
    
    if ((int)$reservationStatus == 1) {
        $success = 0;
        $msg = "Sudah ada yang book di meja ini. Mohon pilih meja lain";
        $status = 204;
    } else {
        $createRsv = "INSERT INTO `reservations`(phone, email, `partner_id`, `user_id`, `for_reservation_id`, `name`, `description`, `persons`, `minimum_transaction`, `booking_price`, `duration`, `duration_metric`, `reservation_time`, `end_time`, `table_id`,`status`, source) VALUES ('$data->phone','$data->email','$data->partner_id', '$data->user_id', '$data->frID', '$data->name', '$data->description', '$capacity', '$minimumTransaction', '$bookingPrice', '$duration', '$duration_metric', '$data->reservation_time', '$data->end_time', '$tableID', 'Pending', 'Self Order')";
        $q = mysqli_query($db_conn, $createRsv);
        $amountBilled = (int)$bookingPrice;
        if ($q) {
            $success = 1;
            $msg = "Berhasil buat reservasi";
            $status = 200;
            $iid = mysqli_insert_id($db_conn);
            if (isset($data->email) && !empty($data->email)) {
                $query = "SELECT template FROM `email_template` WHERE name='reservation-customer-natt'";
                $qp = "SELECT name, address, phone, email, latitude, longitude, reservation_notes FROM partner WHERE id='$data->partner_id'";
                $getPartnerData = mysqli_query($db_conn, $qp);
                $templateQ = mysqli_query($db_conn, $query);
                if (mysqli_num_rows($templateQ) > 0) {
                    $templates = mysqli_fetch_all($templateQ, MYSQLI_ASSOC);
                    $template = $templates[0]['template'];
                    $template = str_replace('$id', $iid, $template);
                    $template = str_replace('$customerName', $data->name, $template);
                    $template = str_replace('$dateTime', $data->reservation_time, $template);
                    $template = str_replace('$specialRequest', $data->description, $template);
                    $template = str_replace('$pax', $data->persons, $template);
                    if (mysqli_num_rows($getPartnerData) > 0) {
                        $partnerData = mysqli_fetch_assoc($getPartnerData);
                        $template = str_replace('$partnerName', $partnerData['name'], $template);
                        $template = str_replace('$address', $partnerData['address'], $template);
                        $template = str_replace('$phone', $partnerData['phone'], $template);
                        $template = str_replace('$email', $partnerData['email'], $template);
                        $template = str_replace('$partnerNotes', $partnerData['reservation_notes'], $template);
                    }
                    $emailSubject = "Konfirmasi Reservasi Baru Kamu di " . $partnerData['name'] . " - " . $data->name;
                    $email = "INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`) VALUES ('$data->email', '$data->partner_id', '$emailSubject', '$template')";
                    $insertTe = mysqli_query($db_conn, $email);
                }
                if ($email) {
                    $partnerTemplate = mysqli_query($db_conn, "SELECT template FROM email_template WHERE name='reservation-partner-natta'");
                    if (mysqli_num_rows($partnerTemplate) > 0) {
                        $templates = mysqli_fetch_all($partnerTemplate, MYSQLI_ASSOC);
                        $template = $templates[0]['template'];
                        $template = str_replace('$id', $iid, $template);
                        $template = str_replace('$customerName', $data->name, $template);
                        $template = str_replace('$dateTime', $data->reservation_time, $template);
                        $template = str_replace('$specialRequest', $data->description, $template);
                        $template = str_replace('$pax', $data->persons, $template);
                        if (mysqli_num_rows($getPartnerData) > 0) {
                            $partnerData = mysqli_fetch_assoc($getPartnerData);
                            $template = str_replace('$partnerName', $partnerData['name'], $template);
                        }
                        $emailSubject = "Reservasi Baru Dari " . $data->name;
                        $getEmails = mysqli_query($db_conn, "SELECT e.email FROM employees e WHERE e.deleted_at IS NULL AND e.id_partner='$data->partner_id'");
                        while ($row = mysqli_fetch_assoc($getEmails)) {
                            $emailAddress = $row['email'];
                            $emailPartner = "INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`) VALUES ('$emailAddress', '$data->partner_id', '$emailSubject', '$template')";
                            $insertTe = mysqli_query($db_conn, $emailPartner);
                        }
                    }
                }
            }
            if ((int)$amountBilled > 0) {
                $params = [
                    'external_id' => 'RSV/' . $iid,
                    'amount' => $amountBilled,
                    'description' => 'Reservasi Meja ' . $tableName . ' | Braga Sky 1957 ' . $dateShow,
                    'invoice_duration' => 900,
                    'customer' => [
                        'given_names' => $data->name ?? "",
                        'email' => $data->email ?? "",
                        'mobile_number' => $data->phone ?? ""
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
                    'success_redirect_url' => 'https://urhub.page.link/uC18',
                    'failure_redirect_url' => 'https://www.ur-hub.com',
                    'currency' => 'IDR',
                    'items' => [
                        [
                            'name' => 'Reservasi Meja ' . $tableName . ' | Braga Sky 1957 ' . $dateShow,
                            'quantity' => 1,
                            'price' => $amountBilled
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
                curl_setopt($ch, CURLOPT_URL, 'https://' . $_ENV['XENDIT_URL'] . '/v2/invoices');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                curl_setopt($ch, CURLOPT_USERPWD, $_ENV['XENDIT_KEY'] . ':' . '');

                $headers = array();
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $curlResult = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo 'Error:' . curl_error($ch);
                }
                curl_close($ch);
                $insertCallback = mysqli_query($db_conn, "INSERT INTO xendit_service_callback SET content='$curlResult', type='Reservation Invoice', reservation_id='$iid'");
            }
            
            if($curlResult) {
                $curlResult = json_decode($curlResult);
            }
            // if ($createTrx && $insertDetail) {
            //     $success = 1;
            //     $msg = "Berhasil";
            //     $status = 200;
            // } else {
            //     $success = 0;
            //     $msg = "Gagal. Mohon coba lagi";
            //     $status = 204;
            // }
        } else {
            $success = 0;
            $msg = "Gagal. Mohon coba lagi";
            $status = 204;
        }
    
    }
    
}

// echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "packages"=>$res]);
echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "iid" => $iid, "xendit_response" => $curlResult]);
?>