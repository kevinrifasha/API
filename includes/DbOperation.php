<?php
date_default_timezone_set('Asia/Jakarta');
require  __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use Endroid\QrCode\QrCode;
use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;

class DbOperation
{
  private $conn;
  private $conn2;


  //Constructor
  function __construct()
  {
    require_once dirname(__FILE__) . '/Constants.php';
    require_once dirname(__FILE__) . '/DbConnect.php';
    require_once dirname(__FILE__) . '/Mailer.php';

    // opening db connection
    $db = new DbConnect();
    $this->conn = $db->connect();
    $this->conn2 = $db->connect();
  }

  //Function to create a new user
  public function createUser($username, $pass, $email, $name, $phone)
  {
    if (!$this->isUserExist($username, $email, $phone)) {
      $password = md5($pass);
      $stmt = $this->conn->prepare("INSERT INTO users (username, password, email, name, phone) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("sssss", $username, $password, $email, $name, $phone);
      if ($stmt->execute()) {
        return USER_CREATED;
      } else {
        return USER_NOT_CREATED;
      }
    } else {
      return USER_ALREADY_EXIST;
    }
  }

  public function savePaymentNotification($dev_token, $title, $message, $no_meja, $channel_id, $methodPay, $status, $queue, $id_trans, $id_partner, $action, $order, $gender, $birthDate, $isMembership, $delivery_fee, $type, $phone)
  {
    $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    
    $query = "SELECT t.rounding, t.program_discount from transaksi t WHERE t.id = '$id_trans'";
    $roundingQuery = mysqli_query($db_conn, $query);
    $fetchRounding = mysqli_fetch_assoc($roundingQuery);
    $order['rounding'] = $fetchRounding['rounding'];
    $order['program_discount'] = $fetchRounding['program_discount'];
    
    $order = json_encode($order);
    $orders = mysqli_real_escape_string($db_conn, $order);
    $isMembership = json_encode($isMembership);
    if (isset($birthDate) && !empty($birthDate)) {
    } else {
      $birthDate = "1564-01-01";
    }
    
    $qSelect = "SELECT e.id FROM employees e LEFT JOIN device_tokens dt ON dt.employee_id = e.id LEFT JOIN roles r ON r.id = e.role_id  WHERE r.is_order_notif='1' AND e.order_notification='1' AND e.deleted_at IS NULL AND dt.deleted_at IS NULL AND dt.tokens = '$dev_token' AND dt.id_partner = '$id_partner'";
    $selectForNotif = mysqli_query($db_conn, $qSelect);
    $delivery_fee = (int) $delivery_fee;
            
    if(mysqli_num_rows($selectForNotif) > 0 && $type == 'employee'){
        $queryPendingNotif = "INSERT INTO `pending_notification` (`phone`, `partner_id`, `dev_token`, `title`, `message`, `no_meja`, `channel_id`, `method_pay`, `status`, `queue`, `id_trans`, `action`, `orders`, `gender`, `birth_date`, `is_membership`, `delivery_fee`, `type`, `created_at`, `details`) VALUES ('$phone', '$id_partner', '$dev_token', '$title', '$message', '$no_meja', '$channel_id', '$methodPay', '$status', '$queue', '$id_trans', '$action', '$orders', '$gender', '$birthDate', '$isMembership', $delivery_fee, '$type', NOW(), '[]')";
        $notifInsert = mysqli_query($db_conn, $queryPendingNotif);
        return $notifInsert;
    } else if ($type == ''){
        $queryPendingNotif = "INSERT INTO `pending_notification` (`phone`, `partner_id`, `dev_token`, `title`, `message`, `no_meja`, `channel_id`, `method_pay`, `status`, `queue`, `id_trans`, `action`, `orders`, `gender`, `birth_date`, `is_membership`, `delivery_fee`, `type`, `created_at`, `details`) VALUES ('$phone', '$id_partner', '$dev_token', '$title', '$message', '$no_meja', '$channel_id', '$methodPay', '$status', '$queue', '$id_trans', '$action', '$orders', '$gender', '$birthDate', '$isMembership', $delivery_fee, '$type', NOW(), '[]')";
        $notifInsert = mysqli_query($db_conn, $queryPendingNotif);
        return $notifInsert;
    }
  }

  public function pushPaymentNotification($dev_token, $title, $message, $no_meja, $channel_id, $methodPay, $status, $queue, $id_trans, $id_partner, $action, $order, $gender, $birthDate, $isMembership, $delivery_fee, $type)
  {

    if ($dev_token != "TEMPORARY_TOKEN") {
      $fcm_token = $dev_token;

      $url = "https://fcm.googleapis.com/fcm/send";
      $header = [
        'authorization: key=AIzaSyDYqiHlqZWkBjin6jcMZnF4YXfzy7_T9SQ',
        'content-type: application/json'
      ];

      $notification = [
        'title' => $title,
        'body' => $message,
        'android_channel_id' => 'rn-push-notification-channel',
        'time_to_live' => 86400,
        'collapse_key' => 'new_message',
        'delay_while_idle' => false,
        'priority' => 'high',
        'content_available' => true,
        'message' => $message,
        'sound' => 'default',
        'high_priority' => 'high',
        'show_in_foreground' => true
      ];
      $extraNotificationData = [
        "status" => $status, "event" => "payment", "queue" => $queue, "message" => $message, "title" => $title, "action" => $action, "id_transaction" => $id_trans, "partnerID" => $id_partner, "methodPay" => $methodPay,  "soundAndroid" => "bell_new_order",
        "soundIos" => "bell_new_order", "order" => $order, "gender" => $gender, "birthDate" => $birthDate, "isMembership" => $isMembership, "delivery_fee" => $delivery_fee, "type" => $type
      ];

      $fcmNotification = [
        'to'        => $fcm_token,
        'notification' => $notification,
        'data' => $extraNotificationData
      ];

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
    }
  }

  public function getPartnerDeviceTokens($id)
  {
    $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    $tokens = mysqli_query($db_conn, "SELECT device_tokens.tokens FROM `device_tokens` JOIN `employees` ON `device_tokens`.`employee_id`=`employees`.`id` WHERE device_tokens.id_partner='$id' AND (device_tokens.deleted_at ='0000-00-00 00:00:00' OR device_tokens.deleted_at IS NULL) AND `employees`.`order_notification`='1'");

    $dev = array();
    $i = 0;
    while ($row = mysqli_fetch_assoc($tokens)) {
      $dev[$i]['token'] = $row['tokens'];
      $i++;
    }
    // return $menu;

    return $dev;
  }

  public function getUserDeviceTokens($id)
  {
    $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    $tokens = mysqli_query($db_conn, "SELECT tokens FROM `device_tokens` WHERE user_phone='$id' AND deleted_at ='0000-00-00 00:00:00' OR user_phone='$id' AND deleted_at IS NULL");
    // var_dump("SELECT tokens FROM `device_tokens` WHERE user_phone='$id' AND deleted_at ='0000-00-00 00:00:00' OR user_phone='$id' AND deleted_at IS NULL");
    $dev = array();
    $i = 0;
    while ($row = mysqli_fetch_assoc($tokens)) {
      $dev[$i]['token'] = $row['tokens'];
      $i++;
    }
    // return $menu;

    return $dev;
  }

  public function getBirthdate($phone)
  {
    $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    $q1 = mysqli_query($db_conn, "SELECT TglLahir as birth_date FROM `users` WHERE phone='$phone'");
    if (mysqli_num_rows($q1) > 0) {
      $res = mysqli_fetch_all($q1, MYSQLI_ASSOC);
      return $res[0]['birth_date'];
    } else {
      return "not registered";
    }
  }

  public function getGender($phone)
  {
    $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    $q1 = mysqli_query($db_conn, "SELECT Gender FROM `users` WHERE phone='$phone'");
    if (mysqli_num_rows($q1) > 0) {
      $res = mysqli_fetch_all($q1, MYSQLI_ASSOC);
      return $res[0]['Gender'];
    } else {
      return "not registered";
    }
  }

  public function getMembership($partner_id, $phone)
  {
    $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    $q1 = mysqli_query($db_conn, "SELECT m.id FROM memberships m JOIN partner p ON p.id_master=m.master_id WHERE m.user_phone='$phone' AND p.id='$partner_id' ORDER BY m.id DESC LIMIT 1");
    if (mysqli_num_rows($q1) > 0) {
      $res = mysqli_fetch_all($q1, MYSQLI_ASSOC);
      return $res[0]['id'];
    } else {
      return 0;
    }
  }

  //   public function addToken($email){
  //     date_default_timezone_set('Asia/Jakarta');
  //     $dates1 = date('Y-m-d', time());
  //     $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  //     $charactersLength = strlen($characters);
  //     $randomString = '';
  //     $tablename = 'partner';
  //     for ($i = 0; $i < 12; $i++) {
  //       $randomString .= $characters[rand(0, $charactersLength - 1)];
  //     }

  //     $stmt = $this->conn->prepare("INSERT INTO reset_password (tablename, token, created_at, email) VALUES (?, ?, ?, ?)");
  //     $stmt->bind_param("ssss", $tablename, $randomString, $dates1, $email);
  //     if ($stmt->execute()) {
  //             return $randomString;
  //     } else {
  //             return USER_NOT_CREATED;
  //     }

  // }

  public function forgotPassword($phone)
  {
    date_default_timezone_set('Asia/Jakarta');
    $dates1 = date('Y-m-d', time());
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    $tablename = 'users';
    for ($i = 0; $i < 12; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    $stmt1 = $this->conn->prepare("INSERT INTO reset_password (tablename, token, created_at, email) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("ssss", $tablename, $randomString, $dates1, $phone);
    $stmt1->execute();

    date_default_timezone_set('Asia/Jakarta');
    $dates1 = date('Y-m-d', time());
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    $tablename = 'user';
    for ($i = 0; $i < 12; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    $stmt1 = $this->conn->prepare("INSERT INTO reset_password (tablename, token, created_at, email) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("ssss", $tablename, $randomString, $dates1, $phone);
    $stmt1->execute();

    $stmt = $this->conn->prepare("SELECT phone, name, email FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    if ($stmt->execute()) {
      $stmt->bind_result($phone, $name, $email);
      $stmt->fetch();
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Lupa Password",
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html>
                  <head>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <meta name="x-apple-disable-message-reformatting" />
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title></title>
                    <style type="text/css" rel="stylesheet" media="all">

                    @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                    body {
                      width: 100% !important;
                      height: 100%;
                      margin: 0;
                      -webkit-text-size-adjust: none;
                    }

                    a {
                      color: #3869D4;
                    }

                    a img {
                      border: none;
                    }

                    td {
                      word-break: break-word;
                    }

                    .preheader {
                      display: none !important;
                      visibility: hidden;
                      mso-hide: all;
                      font-size: 1px;
                      line-height: 1px;
                      max-height: 0;
                      max-width: 0;
                      opacity: 0;
                      overflow: hidden;
                    }

                    body,
                    td,
                    th {
                      font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                    }

                    h1 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 22px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h2 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 16px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h3 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 14px;
                      font-weight: bold;
                      text-align: left;
                    }

                    td,
                    th {
                      font-size: 16px;
                    }

                    p,
                    ul,
                    ol,
                    blockquote {
                      margin: .4em 0 1.1875em;
                      font-size: 16px;
                      line-height: 1.625;
                    }

                    p.sub {
                      font-size: 13px;
                    }

                    .align-right {
                      text-align: right;
                    }

                    .align-left {
                      text-align: left;
                    }

                    .align-center {
                      text-align: center;
                    }

                    .button {
                      background-color: #3869D4;
                      border-top: 10px solid #3869D4;
                      border-right: 18px solid #3869D4;
                      border-bottom: 10px solid #3869D4;
                      border-left: 18px solid #3869D4;
                      display: inline-block;
                      color: #FFF;
                      text-decoration: none;
                      border-radius: 3px;
                      box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                      -webkit-text-size-adjust: none;
                      box-sizing: border-box;
                    }

                    .button--green {
                      background-color: #22BC66;
                      border-top: 10px solid #22BC66;
                      border-right: 18px solid #22BC66;
                      border-bottom: 10px solid #22BC66;
                      border-left: 18px solid #22BC66;
                    }

                    .button--red {
                      background-color: #FF6136;
                      border-top: 10px solid #FF6136;
                      border-right: 18px solid #FF6136;
                      border-bottom: 10px solid #FF6136;
                      border-left: 18px solid #FF6136;
                    }

                    @media only screen and (max-width: 500px) {
                      .button {
                        width: 100% !important;
                        text-align: center !important;
                      }
                    }

                    .attributes {
                      margin: 0 0 21px;
                    }

                    .attributes_content {
                      background-color: #F4F4F7;
                      padding: 16px;
                    }

                    .attributes_item {
                      padding: 0;
                    }

                    .related {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .related_item {
                      padding: 10px 0;
                      color: #CBCCCF;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .related_item-title {
                      display: block;
                      margin: .5em 0 0;
                    }

                    .related_item-thumb {
                      display: block;
                      padding-bottom: 10px;
                    }

                    .related_heading {
                      border-top: 1px solid #CBCCCF;
                      text-align: center;
                      padding: 25px 0 10px;
                    }

                    .discount {
                      width: 100%;
                      margin: 0;
                      padding: 24px;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F4F4F7;
                      border: 2px dashed #CBCCCF;
                    }

                    .discount_heading {
                      text-align: center;
                    }

                    .discount_body {
                      text-align: center;
                      font-size: 15px;
                    }

                    .social {
                      width: auto;
                    }

                    .social td {
                      padding: 0;
                      width: auto;
                    }

                    .social_icon {
                      height: 20px;
                      margin: 0 8px 10px 8px;
                      padding: 0;
                    }

                    .purchase {
                      width: 100%;
                      margin: 0;
                      padding: 35px 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_content {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_item {
                      padding: 10px 0;
                      color: #51545E;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .purchase_heading {
                      padding-bottom: 8px;
                      border-bottom: 1px solid #EAEAEC;
                    }

                    .purchase_heading p {
                      margin: 0;
                      color: #85878E;
                      font-size: 12px;
                    }

                    .purchase_footer {
                      padding-top: 15px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .purchase_total {
                      margin: 0;
                      text-align: right;
                      font-weight: bold;
                      color: #333333;
                    }

                    .purchase_total--label {
                      padding: 0 15px 0 0;
                    }

                    body {
                      background-color: #F2F4F6;
                      color: #51545E;
                    }

                    p {
                      color: #51545E;
                    }

                    .email-wrapper {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F2F4F6;
                    }

                    .email-content {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-masthead {
                      padding: 25px 0;
                      text-align: center;
                    }

                    .email-masthead_logo {
                      width: 94px;
                    }

                    .email-masthead_name {
                      font-size: 16px;
                      font-weight: bold;
                      color: #A8AAAF;
                      text-decoration: none;
                      text-shadow: 0 1px 0 white;
                    }

                    .email-body {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-body_inner {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #FFFFFF;
                    }

                    .email-footer {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .email-footer p {
                      color: #A8AAAF;
                    }

                    .body-action {
                      width: 100%;
                      margin: 30px auto;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .body-sub {
                      margin-top: 25px;
                      padding-top: 25px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .content-cell {
                      padding: 45px;
                    }

                    @media only screen and (max-width: 600px) {
                      .email-body_inner,
                      .email-footer {
                        width: 100% !important;
                      }
                    }

                    @media (prefers-color-scheme: dark) {
                      body,
                      .email-body,
                      .email-body_inner,
                      .email-content,
                      .email-wrapper,
                      .email-masthead,
                      .email-footer {
                        background-color: #333333 !important;
                        color: #FFF !important;
                      }
                      p,
                      ul,
                      ol,
                      blockquote,
                      h1,
                      h2,
                      h3 {
                        color: #FFF !important;
                      }
                      .attributes_content,
                      .discount {
                        background-color: #222 !important;
                      }
                      .email-masthead_name {
                        text-shadow: none !important;
                      }
                    }
                    </style>
                    <style type="text/css">
                      .f-fallback  {
                        font-family: Arial, sans-serif;
                      }
                    </style>
                  </head>
                  <body>
                    <span class="preheader">Use this link to reset your password. The link is only valid for 24 hours.</span>
                    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td align="center">
                          <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                                <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell">
                                      <div class="f-fallback">
                                        <h1>Hi ' . $name . ',</h1>
                                        <p>You recently requested to reset your password for your UR account. Use the button below to reset it.</p>
                                        <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                          <tr>
                                            <td align="center">
                                              <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                                <tr>
                                                  <td align="center">
                                                    <a href="https://apis.ur-hub.com/qr/pages/change-password.php?id=' . md5($randomString) . '" class="f-fallback button button--green" target="_blank" style = "color:#fff">Reset your password</a>
                                                    </td>
                                                </tr>
                                              </table>
                                            </td>
                                          </tr>
                                        </table>
                                        <p>If you did not request a password reset, please ignore this email or <a href="https://ur-hub.com/contact-us/">contact support</a> if you have questions.</p>
                                        <p>Terima Kasih,
                                          <br>UR - Easy & Quick Order</p>
                                        <!-- Sub copy -->
                                      </div>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                            <tr>
                              <td>
                                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell" align="center">
                                      <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                      <p class="f-fallback sub align-center">
                                        PT. Rahmat Tuhan Lestari
                                        <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir,
                                        <br>Kota Bandung, Jawa Barat 40221
                                        <br>Indonesia
                                      </p>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </body>
                </html>'
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }

  public function forgotPasswordPartner($email)
  {
    date_default_timezone_set('Asia/Jakarta');
    $dates1 = date('Y-m-d', time());
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    $tablename = 'partner';
    for ($i = 0; $i < 12; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    $stmt1 = $this->conn->prepare("INSERT INTO reset_password (tablename, token, created_at, email) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("ssss", $tablename, $randomString, $dates1, $email);
    $stmt1->execute();
    // if ($stmt->execute()) {
    //         return $randomString;
    // } else {
    //         return USER_NOT_CREATED;
    // }

    // $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // $random_string = '';
    // for($i = 0; $i < 12; $i++) {
    //     $random_character = $input[mt_rand(0, $input_length - 1)];
    //     $random_string .= $random_character;
    // }
    // $tkn - $this->con->prepare("INSERT INTO `tokenpartner`(`token`, `id_partner`) VALUES ('$random_string','$email')");
    // $tkn->execute();
    $stmt = $this->conn->prepare("SELECT name, phone, email FROM partner WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($name, $phone, $email);
      $stmt->fetch();
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Lupa Password",
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html>
                  <head>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <meta name="x-apple-disable-message-reformatting" />
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title></title>
                    <style type="text/css" rel="stylesheet" media="all">

                    @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                    body {
                      width: 100% !important;
                      height: 100%;
                      margin: 0;
                      -webkit-text-size-adjust: none;
                    }

                    a {
                      color: #3869D4;
                    }

                    a img {
                      border: none;
                    }

                    td {
                      word-break: break-word;
                    }

                    .preheader {
                      display: none !important;
                      visibility: hidden;
                      mso-hide: all;
                      font-size: 1px;
                      line-height: 1px;
                      max-height: 0;
                      max-width: 0;
                      opacity: 0;
                      overflow: hidden;
                    }

                    body,
                    td,
                    th {
                      font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                    }

                    h1 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 22px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h2 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 16px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h3 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 14px;
                      font-weight: bold;
                      text-align: left;
                    }

                    td,
                    th {
                      font-size: 16px;
                    }

                    p,
                    ul,
                    ol,
                    blockquote {
                      margin: .4em 0 1.1875em;
                      font-size: 16px;
                      line-height: 1.625;
                    }

                    p.sub {
                      font-size: 13px;
                    }

                    .align-right {
                      text-align: right;
                    }

                    .align-left {
                      text-align: left;
                    }

                    .align-center {
                      text-align: center;
                    }

                    .button {
                      background-color: #3869D4;
                      border-top: 10px solid #3869D4;
                      border-right: 18px solid #3869D4;
                      border-bottom: 10px solid #3869D4;
                      border-left: 18px solid #3869D4;
                      display: inline-block;
                      color: #FFF;
                      text-decoration: none;
                      border-radius: 3px;
                      box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                      -webkit-text-size-adjust: none;
                      box-sizing: border-box;
                    }

                    .button--green {
                      background-color: #22BC66;
                      border-top: 10px solid #22BC66;
                      border-right: 18px solid #22BC66;
                      border-bottom: 10px solid #22BC66;
                      border-left: 18px solid #22BC66;
                    }

                    .button--red {
                      background-color: #FF6136;
                      border-top: 10px solid #FF6136;
                      border-right: 18px solid #FF6136;
                      border-bottom: 10px solid #FF6136;
                      border-left: 18px solid #FF6136;
                    }

                    @media only screen and (max-width: 500px) {
                      .button {
                        width: 100% !important;
                        text-align: center !important;
                      }
                    }

                    .attributes {
                      margin: 0 0 21px;
                    }

                    .attributes_content {
                      background-color: #F4F4F7;
                      padding: 16px;
                    }

                    .attributes_item {
                      padding: 0;
                    }

                    .related {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .related_item {
                      padding: 10px 0;
                      color: #CBCCCF;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .related_item-title {
                      display: block;
                      margin: .5em 0 0;
                    }

                    .related_item-thumb {
                      display: block;
                      padding-bottom: 10px;
                    }

                    .related_heading {
                      border-top: 1px solid #CBCCCF;
                      text-align: center;
                      padding: 25px 0 10px;
                    }

                    .discount {
                      width: 100%;
                      margin: 0;
                      padding: 24px;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F4F4F7;
                      border: 2px dashed #CBCCCF;
                    }

                    .discount_heading {
                      text-align: center;
                    }

                    .discount_body {
                      text-align: center;
                      font-size: 15px;
                    }

                    .social {
                      width: auto;
                    }

                    .social td {
                      padding: 0;
                      width: auto;
                    }

                    .social_icon {
                      height: 20px;
                      margin: 0 8px 10px 8px;
                      padding: 0;
                    }

                    .purchase {
                      width: 100%;
                      margin: 0;
                      padding: 35px 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_content {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_item {
                      padding: 10px 0;
                      color: #51545E;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .purchase_heading {
                      padding-bottom: 8px;
                      border-bottom: 1px solid #EAEAEC;
                    }

                    .purchase_heading p {
                      margin: 0;
                      color: #85878E;
                      font-size: 12px;
                    }

                    .purchase_footer {
                      padding-top: 15px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .purchase_total {
                      margin: 0;
                      text-align: right;
                      font-weight: bold;
                      color: #333333;
                    }

                    .purchase_total--label {
                      padding: 0 15px 0 0;
                    }

                    body {
                      background-color: #F2F4F6;
                      color: #51545E;
                    }

                    p {
                      color: #51545E;
                    }

                    .email-wrapper {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F2F4F6;
                    }

                    .email-content {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-masthead {
                      padding: 25px 0;
                      text-align: center;
                    }

                    .email-masthead_logo {
                      width: 94px;
                    }

                    .email-masthead_name {
                      font-size: 16px;
                      font-weight: bold;
                      color: #A8AAAF;
                      text-decoration: none;
                      text-shadow: 0 1px 0 white;
                    }

                    .email-body {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-body_inner {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #FFFFFF;
                    }

                    .email-footer {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .email-footer p {
                      color: #A8AAAF;
                    }

                    .body-action {
                      width: 100%;
                      margin: 30px auto;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .body-sub {
                      margin-top: 25px;
                      padding-top: 25px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .content-cell {
                      padding: 45px;
                    }

                    @media only screen and (max-width: 600px) {
                      .email-body_inner,
                      .email-footer {
                        width: 100% !important;
                      }
                    }

                    @media (prefers-color-scheme: dark) {
                      body,
                      .email-body,
                      .email-body_inner,
                      .email-content,
                      .email-wrapper,
                      .email-masthead,
                      .email-footer {
                        background-color: #333333 !important;
                        color: #FFF !important;
                      }
                      p,
                      ul,
                      ol,
                      blockquote,
                      h1,
                      h2,
                      h3 {
                        color: #FFF !important;
                      }
                      .attributes_content,
                      .discount {
                        background-color: #222 !important;
                      }
                      .email-masthead_name {
                        text-shadow: none !important;
                      }
                    }
                    </style>
                    <style type="text/css">
                      .f-fallback  {
                        font-family: Arial, sans-serif;
                      }
                    </style>
                  </head>
                  <body>
                    <span class="preheader">Gunakan Link Di Bawah Ini Untuk Mengubah Password. Link hanya Berlaku 24 Jam.</span>
                    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td align="center">
                          <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                                <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell">
                                      <div class="f-fallback">
                                        <h1>Hai ' . $name . ',</h1>
                                        <p>Kamu Baru saja meminta untuk mengubah password UR-Partner account. gunakan tombol di bawah ini untuk mengubahnya.</p>
                                        <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                          <tr>
                                            <td align="center">
                                              <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                                <tr>
                                                  <td align="center">
                                                    <a href="https://apis.ur-hub.com/qr/v2/controller/changePassword.php?id=' . md5($randomString) . '" class="f-fallback button button--green" target="_blank" style = "color:#fff">Ubah Password</a>
                                                  </td>
                                                </tr>
                                              </table>
                                            </td>
                                          </tr>
                                        </table>
                                        <p>Jika kamu tidak meminta untuk mengubah password silahkan abaikan email ini, atau klik <a href="https://ur-hub.com/contact-us/">kontak bantuan</a> jika kamu memiliki pertanyaan.</p>
                                        <p>Thanks,
                                          <br>UR - Easy & Quick Order</p>
                                        <!-- Sub copy -->
                                      </div>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                            <tr>
                              <td>
                                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell" align="center">
                                      <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                      <p class="f-fallback sub align-center">
                                        PT. Rahmat Tuhan Lestari
                                        <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir,
                                        <br>Kota Bandung, Jawa Barat 40221
                                        <br>Indonesia
                                      </p>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </body>
                </html>'
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }
  public function forgotPasswordEmployee($email)
  {
    date_default_timezone_set('Asia/Jakarta');
    $dates1 = date('Y-m-d', time());
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    $tablename = 'employee';
    for ($i = 0; $i < 12; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    $stmt1 = $this->conn->prepare("INSERT INTO reset_password (tablename, token, created_at, email) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("ssss", $tablename, $randomString, $dates1, $email);
    $stmt1->execute();
    // if ($stmt->execute()) {
    //         return $randomString;
    // } else {
    //         return USER_NOT_CREATED;
    // }

    // $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // $random_string = '';
    // for($i = 0; $i < 12; $i++) {
    //     $random_character = $input[mt_rand(0, $input_length - 1)];
    //     $random_string .= $random_character;
    // }
    // $tkn - $this->con->prepare("INSERT INTO `tokenpartner`(`token`, `id_partner`) VALUES ('$random_string','$email')");
    // $tkn->execute();
    $stmt = $this->conn->prepare("SELECT nama, phone, email FROM employees WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($name, $phone, $email);
      $stmt->fetch();
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Lupa Password",
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html>
                  <head>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <meta name="x-apple-disable-message-reformatting" />
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title></title>
                    <style type="text/css" rel="stylesheet" media="all">

                    @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                    body {
                      width: 100% !important;
                      height: 100%;
                      margin: 0;
                      -webkit-text-size-adjust: none;
                    }

                    a {
                      color: #3869D4;
                    }

                    a img {
                      border: none;
                    }

                    td {
                      word-break: break-word;
                    }

                    .preheader {
                      display: none !important;
                      visibility: hidden;
                      mso-hide: all;
                      font-size: 1px;
                      line-height: 1px;
                      max-height: 0;
                      max-width: 0;
                      opacity: 0;
                      overflow: hidden;
                    }

                    body,
                    td,
                    th {
                      font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                    }

                    h1 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 22px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h2 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 16px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h3 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 14px;
                      font-weight: bold;
                      text-align: left;
                    }

                    td,
                    th {
                      font-size: 16px;
                    }

                    p,
                    ul,
                    ol,
                    blockquote {
                      margin: .4em 0 1.1875em;
                      font-size: 16px;
                      line-height: 1.625;
                    }

                    p.sub {
                      font-size: 13px;
                    }

                    .align-right {
                      text-align: right;
                    }

                    .align-left {
                      text-align: left;
                    }

                    .align-center {
                      text-align: center;
                    }

                    .button {
                      background-color: #3869D4;
                      border-top: 10px solid #3869D4;
                      border-right: 18px solid #3869D4;
                      border-bottom: 10px solid #3869D4;
                      border-left: 18px solid #3869D4;
                      display: inline-block;
                      color: #FFF;
                      text-decoration: none;
                      border-radius: 3px;
                      box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                      -webkit-text-size-adjust: none;
                      box-sizing: border-box;
                    }

                    .button--green {
                      background-color: #22BC66;
                      border-top: 10px solid #22BC66;
                      border-right: 18px solid #22BC66;
                      border-bottom: 10px solid #22BC66;
                      border-left: 18px solid #22BC66;
                    }

                    .button--red {
                      background-color: #FF6136;
                      border-top: 10px solid #FF6136;
                      border-right: 18px solid #FF6136;
                      border-bottom: 10px solid #FF6136;
                      border-left: 18px solid #FF6136;
                    }

                    @media only screen and (max-width: 500px) {
                      .button {
                        width: 100% !important;
                        text-align: center !important;
                      }
                    }

                    .attributes {
                      margin: 0 0 21px;
                    }

                    .attributes_content {
                      background-color: #F4F4F7;
                      padding: 16px;
                    }

                    .attributes_item {
                      padding: 0;
                    }

                    .related {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .related_item {
                      padding: 10px 0;
                      color: #CBCCCF;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .related_item-title {
                      display: block;
                      margin: .5em 0 0;
                    }

                    .related_item-thumb {
                      display: block;
                      padding-bottom: 10px;
                    }

                    .related_heading {
                      border-top: 1px solid #CBCCCF;
                      text-align: center;
                      padding: 25px 0 10px;
                    }

                    .discount {
                      width: 100%;
                      margin: 0;
                      padding: 24px;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F4F4F7;
                      border: 2px dashed #CBCCCF;
                    }

                    .discount_heading {
                      text-align: center;
                    }

                    .discount_body {
                      text-align: center;
                      font-size: 15px;
                    }

                    .social {
                      width: auto;
                    }

                    .social td {
                      padding: 0;
                      width: auto;
                    }

                    .social_icon {
                      height: 20px;
                      margin: 0 8px 10px 8px;
                      padding: 0;
                    }

                    .purchase {
                      width: 100%;
                      margin: 0;
                      padding: 35px 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_content {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_item {
                      padding: 10px 0;
                      color: #51545E;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .purchase_heading {
                      padding-bottom: 8px;
                      border-bottom: 1px solid #EAEAEC;
                    }

                    .purchase_heading p {
                      margin: 0;
                      color: #85878E;
                      font-size: 12px;
                    }

                    .purchase_footer {
                      padding-top: 15px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .purchase_total {
                      margin: 0;
                      text-align: right;
                      font-weight: bold;
                      color: #333333;
                    }

                    .purchase_total--label {
                      padding: 0 15px 0 0;
                    }

                    body {
                      background-color: #F2F4F6;
                      color: #51545E;
                    }

                    p {
                      color: #51545E;
                    }

                    .email-wrapper {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F2F4F6;
                    }

                    .email-content {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-masthead {
                      padding: 25px 0;
                      text-align: center;
                    }

                    .email-masthead_logo {
                      width: 94px;
                    }

                    .email-masthead_name {
                      font-size: 16px;
                      font-weight: bold;
                      color: #A8AAAF;
                      text-decoration: none;
                      text-shadow: 0 1px 0 white;
                    }

                    .email-body {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-body_inner {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #FFFFFF;
                    }

                    .email-footer {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .email-footer p {
                      color: #A8AAAF;
                    }

                    .body-action {
                      width: 100%;
                      margin: 30px auto;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .body-sub {
                      margin-top: 25px;
                      padding-top: 25px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .content-cell {
                      padding: 45px;
                    }

                    @media only screen and (max-width: 600px) {
                      .email-body_inner,
                      .email-footer {
                        width: 100% !important;
                      }
                    }

                    @media (prefers-color-scheme: dark) {
                      body,
                      .email-body,
                      .email-body_inner,
                      .email-content,
                      .email-wrapper,
                      .email-masthead,
                      .email-footer {
                        background-color: #333333 !important;
                        color: #FFF !important;
                      }
                      p,
                      ul,
                      ol,
                      blockquote,
                      h1,
                      h2,
                      h3 {
                        color: #FFF !important;
                      }
                      .attributes_content,
                      .discount {
                        background-color: #222 !important;
                      }
                      .email-masthead_name {
                        text-shadow: none !important;
                      }
                    }
                    </style>
                    <style type="text/css">
                      .f-fallback  {
                        font-family: Arial, sans-serif;
                      }
                    </style>
                  </head>
                  <body>
                    <span class="preheader">Gunakan Link Di Bawah Ini Untuk Mengubah Password. Link hanya Berlaku 24 Jam.</span>
                    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td align="center">
                          <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                                <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell">
                                      <div class="f-fallback">
                                        <h1>Hai ' . $name . ',</h1>
                                        <p>Kamu Baru saja meminta untuk mengubah password UR-Partner account. gunakan tombol di bawah ini untuk mengubahnya.</p>
                                        <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                          <tr>
                                            <td align="center">
                                              <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                                <tr>
                                                  <td align="center">
                                                    <a href="https://apis.ur-hub.com/qr/v2/controller/changePassword.php?id=' . $randomString . '" class="f-fallback button button--green" target="_blank" style = "color:#fff">Ubah Password</a>
                                                  </td>
                                                </tr>
                                              </table>
                                            </td>
                                          </tr>
                                        </table>
                                        <p>Jika kamu tidak meminta untuk mengubah password silahkan abaikan email ini, atau klik <a href="https://ur-hub.com/contact-us/">kontak bantuan</a> jika kamu memiliki pertanyaan.</p>
                                        <p>Thanks,
                                          <br>UR - Easy & Quick Order</p>
                                        <!-- Sub copy -->
                                      </div>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                            <tr>
                              <td>
                                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell" align="center">
                                      <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                      <p class="f-fallback sub align-center">
                                        PT. Rahmat Tuhan Lestari
                                        <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir,
                                        <br>Kota Bandung, Jawa Barat 40221
                                        <br>Indonesia
                                      </p>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </body>
                </html>'
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }
  public function forgotPasswordFoodcourt($email)
  {
    date_default_timezone_set('Asia/Jakarta');
    $dates1 = date('Y-m-d', time());
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    $tablename = 'foodcourt';
    for ($i = 0; $i < 12; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    $stmt1 = $this->conn->prepare("INSERT INTO reset_password (tablename, token, created_at, email) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("ssss", $tablename, $randomString, $dates1, $email);
    $stmt1->execute();
    // if ($stmt->execute()) {
    //         return $randomString;
    // } else {
    //         return USER_NOT_CREATED;
    // }

    // $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // $random_string = '';
    // for($i = 0; $i < 12; $i++) {
    //     $random_character = $input[mt_rand(0, $input_length - 1)];
    //     $random_string .= $random_character;
    // }
    // $tkn - $this->con->prepare("INSERT INTO `tokenpartner`(`token`, `id_partner`) VALUES ('$random_string','$email')");
    // $tkn->execute();
    $stmt = $this->conn->prepare("SELECT name, phone, email FROM foodcourt WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($name, $phone, $email);
      $stmt->fetch();
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Lupa Password",
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html>
                  <head>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <meta name="x-apple-disable-message-reformatting" />
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title></title>
                    <style type="text/css" rel="stylesheet" media="all">

                    @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                    body {
                      width: 100% !important;
                      height: 100%;
                      margin: 0;
                      -webkit-text-size-adjust: none;
                    }

                    a {
                      color: #3869D4;
                    }

                    a img {
                      border: none;
                    }

                    td {
                      word-break: break-word;
                    }

                    .preheader {
                      display: none !important;
                      visibility: hidden;
                      mso-hide: all;
                      font-size: 1px;
                      line-height: 1px;
                      max-height: 0;
                      max-width: 0;
                      opacity: 0;
                      overflow: hidden;
                    }

                    body,
                    td,
                    th {
                      font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                    }

                    h1 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 22px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h2 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 16px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h3 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 14px;
                      font-weight: bold;
                      text-align: left;
                    }

                    td,
                    th {
                      font-size: 16px;
                    }

                    p,
                    ul,
                    ol,
                    blockquote {
                      margin: .4em 0 1.1875em;
                      font-size: 16px;
                      line-height: 1.625;
                    }

                    p.sub {
                      font-size: 13px;
                    }

                    .align-right {
                      text-align: right;
                    }

                    .align-left {
                      text-align: left;
                    }

                    .align-center {
                      text-align: center;
                    }

                    .button {
                      background-color: #3869D4;
                      border-top: 10px solid #3869D4;
                      border-right: 18px solid #3869D4;
                      border-bottom: 10px solid #3869D4;
                      border-left: 18px solid #3869D4;
                      display: inline-block;
                      color: #FFF;
                      text-decoration: none;
                      border-radius: 3px;
                      box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                      -webkit-text-size-adjust: none;
                      box-sizing: border-box;
                    }

                    .button--green {
                      background-color: #22BC66;
                      border-top: 10px solid #22BC66;
                      border-right: 18px solid #22BC66;
                      border-bottom: 10px solid #22BC66;
                      border-left: 18px solid #22BC66;
                    }

                    .button--red {
                      background-color: #FF6136;
                      border-top: 10px solid #FF6136;
                      border-right: 18px solid #FF6136;
                      border-bottom: 10px solid #FF6136;
                      border-left: 18px solid #FF6136;
                    }

                    @media only screen and (max-width: 500px) {
                      .button {
                        width: 100% !important;
                        text-align: center !important;
                      }
                    }

                    .attributes {
                      margin: 0 0 21px;
                    }

                    .attributes_content {
                      background-color: #F4F4F7;
                      padding: 16px;
                    }

                    .attributes_item {
                      padding: 0;
                    }

                    .related {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .related_item {
                      padding: 10px 0;
                      color: #CBCCCF;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .related_item-title {
                      display: block;
                      margin: .5em 0 0;
                    }

                    .related_item-thumb {
                      display: block;
                      padding-bottom: 10px;
                    }

                    .related_heading {
                      border-top: 1px solid #CBCCCF;
                      text-align: center;
                      padding: 25px 0 10px;
                    }

                    .discount {
                      width: 100%;
                      margin: 0;
                      padding: 24px;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F4F4F7;
                      border: 2px dashed #CBCCCF;
                    }

                    .discount_heading {
                      text-align: center;
                    }

                    .discount_body {
                      text-align: center;
                      font-size: 15px;
                    }

                    .social {
                      width: auto;
                    }

                    .social td {
                      padding: 0;
                      width: auto;
                    }

                    .social_icon {
                      height: 20px;
                      margin: 0 8px 10px 8px;
                      padding: 0;
                    }

                    .purchase {
                      width: 100%;
                      margin: 0;
                      padding: 35px 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_content {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_item {
                      padding: 10px 0;
                      color: #51545E;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .purchase_heading {
                      padding-bottom: 8px;
                      border-bottom: 1px solid #EAEAEC;
                    }

                    .purchase_heading p {
                      margin: 0;
                      color: #85878E;
                      font-size: 12px;
                    }

                    .purchase_footer {
                      padding-top: 15px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .purchase_total {
                      margin: 0;
                      text-align: right;
                      font-weight: bold;
                      color: #333333;
                    }

                    .purchase_total--label {
                      padding: 0 15px 0 0;
                    }

                    body {
                      background-color: #F2F4F6;
                      color: #51545E;
                    }

                    p {
                      color: #51545E;
                    }

                    .email-wrapper {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F2F4F6;
                    }

                    .email-content {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-masthead {
                      padding: 25px 0;
                      text-align: center;
                    }

                    .email-masthead_logo {
                      width: 94px;
                    }

                    .email-masthead_name {
                      font-size: 16px;
                      font-weight: bold;
                      color: #A8AAAF;
                      text-decoration: none;
                      text-shadow: 0 1px 0 white;
                    }

                    .email-body {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-body_inner {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #FFFFFF;
                    }

                    .email-footer {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .email-footer p {
                      color: #A8AAAF;
                    }

                    .body-action {
                      width: 100%;
                      margin: 30px auto;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .body-sub {
                      margin-top: 25px;
                      padding-top: 25px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .content-cell {
                      padding: 45px;
                    }

                    @media only screen and (max-width: 600px) {
                      .email-body_inner,
                      .email-footer {
                        width: 100% !important;
                      }
                    }

                    @media (prefers-color-scheme: dark) {
                      body,
                      .email-body,
                      .email-body_inner,
                      .email-content,
                      .email-wrapper,
                      .email-masthead,
                      .email-footer {
                        background-color: #333333 !important;
                        color: #FFF !important;
                      }
                      p,
                      ul,
                      ol,
                      blockquote,
                      h1,
                      h2,
                      h3 {
                        color: #FFF !important;
                      }
                      .attributes_content,
                      .discount {
                        background-color: #222 !important;
                      }
                      .email-masthead_name {
                        text-shadow: none !important;
                      }
                    }
                    </style>
                    <style type="text/css">
                      .f-fallback  {
                        font-family: Arial, sans-serif;
                      }
                    </style>
                  </head>
                  <body>
                    <span class="preheader">Gunakan Link Di Bawah Ini Untuk Mengubah Password. Link hanya Berlaku 24 Jam.</span>
                    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td align="center">
                          <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                                <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell">
                                      <div class="f-fallback">
                                        <h1>Hai ' . $name . ',</h1>
                                        <p>Kamu Baru saja meminta untuk mengubah password UR-Partner account. gunakan tombol di bawah ini untuk mengubahnya.</p>
                                        <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                          <tr>
                                            <td align="center">
                                              <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                                <tr>
                                                  <td align="center">
                                                    <a href="https://apis.ur-hub.com/qr/v2/controller/changePassword.php?id=' . md5($randomString) . '" class="f-fallback button button--green" target="_blank" style = "color:#fff">Ubah Password</a>
                                                  </td>
                                                </tr>
                                              </table>
                                            </td>
                                          </tr>
                                        </table>
                                        <p>Jika kamu tidak meminta untuk mengubah password silahkan abaikan email ini, atau klik <a href="https://ur-hub.com/contact-us/">kontak bantuan</a> jika kamu memiliki pertanyaan.</p>
                                        <p>Thanks,
                                          <br>UR - Easy & Quick Order</p>
                                        <!-- Sub copy -->
                                      </div>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                            <tr>
                              <td>
                                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell" align="center">
                                      <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                      <p class="f-fallback sub align-center">
                                        PT. Rahmat Tuhan Lestari
                                        <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir,
                                        <br>Kota Bandung, Jawa Barat 40221
                                        <br>Indonesia
                                      </p>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </body>
                </html>'
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }

  public function forgotPasswordMaster($email)
  {
    date_default_timezone_set('Asia/Jakarta');
    $dates1 = date('Y-m-d', time());
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    $tablename = 'master';
    for ($i = 0; $i < 12; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    $stmt1 = $this->conn->prepare("INSERT INTO reset_password (tablename, token, created_at, email) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("ssss", $tablename, $randomString, $dates1, $email);
    $stmt1->execute();
    // if ($stmt->execute()) {
    //         return $randomString;
    // } else {
    //         return USER_NOT_CREATED;
    // }

    // $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // $random_string = '';
    // for($i = 0; $i < 12; $i++) {
    //     $random_character = $input[mt_rand(0, $input_length - 1)];
    //     $random_string .= $random_character;
    // }
    // $tkn - $this->con->prepare("INSERT INTO `tokenpartner`(`token`, `id_partner`) VALUES ('$random_string','$email')");
    // $tkn->execute();
    $stmt = $this->conn->prepare("SELECT name, phone, email FROM master WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($name, $phone, $email);
      $stmt->fetch();
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Lupa Password",
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html>
                  <head>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <meta name="x-apple-disable-message-reformatting" />
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title></title>
                    <style type="text/css" rel="stylesheet" media="all">

                    @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                    body {
                      width: 100% !important;
                      height: 100%;
                      margin: 0;
                      -webkit-text-size-adjust: none;
                    }

                    a {
                      color: #3869D4;
                    }

                    a img {
                      border: none;
                    }

                    td {
                      word-break: break-word;
                    }

                    .preheader {
                      display: none !important;
                      visibility: hidden;
                      mso-hide: all;
                      font-size: 1px;
                      line-height: 1px;
                      max-height: 0;
                      max-width: 0;
                      opacity: 0;
                      overflow: hidden;
                    }

                    body,
                    td,
                    th {
                      font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                    }

                    h1 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 22px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h2 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 16px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h3 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 14px;
                      font-weight: bold;
                      text-align: left;
                    }

                    td,
                    th {
                      font-size: 16px;
                    }

                    p,
                    ul,
                    ol,
                    blockquote {
                      margin: .4em 0 1.1875em;
                      font-size: 16px;
                      line-height: 1.625;
                    }

                    p.sub {
                      font-size: 13px;
                    }

                    .align-right {
                      text-align: right;
                    }

                    .align-left {
                      text-align: left;
                    }

                    .align-center {
                      text-align: center;
                    }

                    .button {
                      background-color: #3869D4;
                      border-top: 10px solid #3869D4;
                      border-right: 18px solid #3869D4;
                      border-bottom: 10px solid #3869D4;
                      border-left: 18px solid #3869D4;
                      display: inline-block;
                      color: #FFF;
                      text-decoration: none;
                      border-radius: 3px;
                      box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                      -webkit-text-size-adjust: none;
                      box-sizing: border-box;
                    }

                    .button--green {
                      background-color: #22BC66;
                      border-top: 10px solid #22BC66;
                      border-right: 18px solid #22BC66;
                      border-bottom: 10px solid #22BC66;
                      border-left: 18px solid #22BC66;
                    }

                    .button--red {
                      background-color: #FF6136;
                      border-top: 10px solid #FF6136;
                      border-right: 18px solid #FF6136;
                      border-bottom: 10px solid #FF6136;
                      border-left: 18px solid #FF6136;
                    }

                    @media only screen and (max-width: 500px) {
                      .button {
                        width: 100% !important;
                        text-align: center !important;
                      }
                    }

                    .attributes {
                      margin: 0 0 21px;
                    }

                    .attributes_content {
                      background-color: #F4F4F7;
                      padding: 16px;
                    }

                    .attributes_item {
                      padding: 0;
                    }

                    .related {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .related_item {
                      padding: 10px 0;
                      color: #CBCCCF;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .related_item-title {
                      display: block;
                      margin: .5em 0 0;
                    }

                    .related_item-thumb {
                      display: block;
                      padding-bottom: 10px;
                    }

                    .related_heading {
                      border-top: 1px solid #CBCCCF;
                      text-align: center;
                      padding: 25px 0 10px;
                    }

                    .discount {
                      width: 100%;
                      margin: 0;
                      padding: 24px;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F4F4F7;
                      border: 2px dashed #CBCCCF;
                    }

                    .discount_heading {
                      text-align: center;
                    }

                    .discount_body {
                      text-align: center;
                      font-size: 15px;
                    }

                    .social {
                      width: auto;
                    }

                    .social td {
                      padding: 0;
                      width: auto;
                    }

                    .social_icon {
                      height: 20px;
                      margin: 0 8px 10px 8px;
                      padding: 0;
                    }

                    .purchase {
                      width: 100%;
                      margin: 0;
                      padding: 35px 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_content {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_item {
                      padding: 10px 0;
                      color: #51545E;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .purchase_heading {
                      padding-bottom: 8px;
                      border-bottom: 1px solid #EAEAEC;
                    }

                    .purchase_heading p {
                      margin: 0;
                      color: #85878E;
                      font-size: 12px;
                    }

                    .purchase_footer {
                      padding-top: 15px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .purchase_total {
                      margin: 0;
                      text-align: right;
                      font-weight: bold;
                      color: #333333;
                    }

                    .purchase_total--label {
                      padding: 0 15px 0 0;
                    }

                    body {
                      background-color: #F2F4F6;
                      color: #51545E;
                    }

                    p {
                      color: #51545E;
                    }

                    .email-wrapper {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F2F4F6;
                    }

                    .email-content {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-masthead {
                      padding: 25px 0;
                      text-align: center;
                    }

                    .email-masthead_logo {
                      width: 94px;
                    }

                    .email-masthead_name {
                      font-size: 16px;
                      font-weight: bold;
                      color: #A8AAAF;
                      text-decoration: none;
                      text-shadow: 0 1px 0 white;
                    }

                    .email-body {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-body_inner {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #FFFFFF;
                    }

                    .email-footer {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .email-footer p {
                      color: #A8AAAF;
                    }

                    .body-action {
                      width: 100%;
                      margin: 30px auto;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .body-sub {
                      margin-top: 25px;
                      padding-top: 25px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .content-cell {
                      padding: 45px;
                    }

                    @media only screen and (max-width: 600px) {
                      .email-body_inner,
                      .email-footer {
                        width: 100% !important;
                      }
                    }

                    @media (prefers-color-scheme: dark) {
                      body,
                      .email-body,
                      .email-body_inner,
                      .email-content,
                      .email-wrapper,
                      .email-masthead,
                      .email-footer {
                        background-color: #333333 !important;
                        color: #FFF !important;
                      }
                      p,
                      ul,
                      ol,
                      blockquote,
                      h1,
                      h2,
                      h3 {
                        color: #FFF !important;
                      }
                      .attributes_content,
                      .discount {
                        background-color: #222 !important;
                      }
                      .email-masthead_name {
                        text-shadow: none !important;
                      }
                    }
                    </style>
                    <style type="text/css">
                      .f-fallback  {
                        font-family: Arial, sans-serif;
                      }
                    </style>
                  </head>
                  <body>
                    <span class="preheader">Gunakan Link Di Bawah Ini Untuk Mengubah Password. Link hanya Berlaku 24 Jam.</span>
                    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td align="center">
                          <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                                <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell">
                                      <div class="f-fallback">
                                        <h1>Hi ' . $name . ',</h1>
                                        <p>Kamu Baru saja meminta untuk mengubah password UR-Partner account. gunakan tombol di bawah ini untuk mengubahnya</p>
                                        <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                          <tr>
                                            <td align="center">
                                              <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                                <tr>
                                                  <td align="center">
                                                    <a href="https://apis.ur-hub.com/qr/v2/controller/master/changePasswordMaster.php?id=' . md5($randomString) . '" class="f-fallback button button--green" target="_blank" style = "color:#fff">Ubah Password</a>
                                                  </td>
                                                </tr>
                                              </table>
                                            </td>
                                          </tr>
                                        </table>
                                        <p>Jika kamu tidak meminta untuk mengubah password silahkan abaikan email ini, atau klik <a href="https://ur-hub.com/contact-us/">kontak bantuan</a> jika kamu memiliki pertanyaan.</p>
                                        <p>Terima Kasih,
                                          <br>UR - Easy & Quick Order</p>
                                        <!-- Sub copy -->
                                      </div>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                            <tr>
                              <td>
                                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell" align="center">
                                      <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                      <p class="f-fallback sub align-center">
                                        PT. Rahmat Tuhan Lestari
                                        <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir,
                                        <br>Kota Bandung, Jawa Barat 40221
                                        <br>Indonesia
                                      </p>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </body>
                </html>'
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }

  public function changePasswordPartner($email)
  {
    date_default_timezone_set('Asia/Jakarta');
    $dates1 = date('Y-m-d', time());
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    $tablename = 'partner';
    for ($i = 0; $i < 12; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    $stmt1 = $this->conn->prepare("INSERT INTO reset_password (tablename, token, created_at, email) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("ssss", $tablename, $randomString, $dates1, $email);
    $stmt1->execute();
    // if ($stmt->execute()) {
    //         return $randomString;
    // } else {
    //         return USER_NOT_CREATED;
    // }

    // $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // $random_string = '';
    // for($i = 0; $i < 12; $i++) {
    //     $random_character = $input[mt_rand(0, $input_length - 1)];
    //     $random_string .= $random_character;
    // }
    // $tkn - $this->con->prepare("INSERT INTO `tokenpartner`(`token`, `id_partner`) VALUES ('$random_string','$email')");
    // $tkn->execute();
    $stmt = $this->conn->prepare("SELECT name, phone, email FROM partner WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($name, $phone, $email);
      $stmt->fetch();
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Permintaan Ubah Password",
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html>
                  <head>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <meta name="x-apple-disable-message-reformatting" />
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title></title>
                    <style type="text/css" rel="stylesheet" media="all">

                    @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                    body {
                      width: 100% !important;
                      height: 100%;
                      margin: 0;
                      -webkit-text-size-adjust: none;
                    }

                    a {
                      color: #3869D4;
                    }

                    a img {
                      border: none;
                    }

                    td {
                      word-break: break-word;
                    }

                    .preheader {
                      display: none !important;
                      visibility: hidden;
                      mso-hide: all;
                      font-size: 1px;
                      line-height: 1px;
                      max-height: 0;
                      max-width: 0;
                      opacity: 0;
                      overflow: hidden;
                    }

                    body,
                    td,
                    th {
                      font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                    }

                    h1 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 22px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h2 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 16px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h3 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 14px;
                      font-weight: bold;
                      text-align: left;
                    }

                    td,
                    th {
                      font-size: 16px;
                    }

                    p,
                    ul,
                    ol,
                    blockquote {
                      margin: .4em 0 1.1875em;
                      font-size: 16px;
                      line-height: 1.625;
                    }

                    p.sub {
                      font-size: 13px;
                    }

                    .align-right {
                      text-align: right;
                    }

                    .align-left {
                      text-align: left;
                    }

                    .align-center {
                      text-align: center;
                    }

                    .button {
                      background-color: #3869D4;
                      border-top: 10px solid #3869D4;
                      border-right: 18px solid #3869D4;
                      border-bottom: 10px solid #3869D4;
                      border-left: 18px solid #3869D4;
                      display: inline-block;
                      color: #FFF;
                      text-decoration: none;
                      border-radius: 3px;
                      box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                      -webkit-text-size-adjust: none;
                      box-sizing: border-box;
                    }

                    .button--green {
                      background-color: #22BC66;
                      border-top: 10px solid #22BC66;
                      border-right: 18px solid #22BC66;
                      border-bottom: 10px solid #22BC66;
                      border-left: 18px solid #22BC66;
                    }

                    .button--red {
                      background-color: #FF6136;
                      border-top: 10px solid #FF6136;
                      border-right: 18px solid #FF6136;
                      border-bottom: 10px solid #FF6136;
                      border-left: 18px solid #FF6136;
                    }

                    @media only screen and (max-width: 500px) {
                      .button {
                        width: 100% !important;
                        text-align: center !important;
                      }
                    }

                    .attributes {
                      margin: 0 0 21px;
                    }

                    .attributes_content {
                      background-color: #F4F4F7;
                      padding: 16px;
                    }

                    .attributes_item {
                      padding: 0;
                    }

                    .related {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .related_item {
                      padding: 10px 0;
                      color: #CBCCCF;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .related_item-title {
                      display: block;
                      margin: .5em 0 0;
                    }

                    .related_item-thumb {
                      display: block;
                      padding-bottom: 10px;
                    }

                    .related_heading {
                      border-top: 1px solid #CBCCCF;
                      text-align: center;
                      padding: 25px 0 10px;
                    }

                    .discount {
                      width: 100%;
                      margin: 0;
                      padding: 24px;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F4F4F7;
                      border: 2px dashed #CBCCCF;
                    }

                    .discount_heading {
                      text-align: center;
                    }

                    .discount_body {
                      text-align: center;
                      font-size: 15px;
                    }

                    .social {
                      width: auto;
                    }

                    .social td {
                      padding: 0;
                      width: auto;
                    }

                    .social_icon {
                      height: 20px;
                      margin: 0 8px 10px 8px;
                      padding: 0;
                    }

                    .purchase {
                      width: 100%;
                      margin: 0;
                      padding: 35px 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_content {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_item {
                      padding: 10px 0;
                      color: #51545E;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .purchase_heading {
                      padding-bottom: 8px;
                      border-bottom: 1px solid #EAEAEC;
                    }

                    .purchase_heading p {
                      margin: 0;
                      color: #85878E;
                      font-size: 12px;
                    }

                    .purchase_footer {
                      padding-top: 15px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .purchase_total {
                      margin: 0;
                      text-align: right;
                      font-weight: bold;
                      color: #333333;
                    }

                    .purchase_total--label {
                      padding: 0 15px 0 0;
                    }

                    body {
                      background-color: #F2F4F6;
                      color: #51545E;
                    }

                    p {
                      color: #51545E;
                    }

                    .email-wrapper {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F2F4F6;
                    }

                    .email-content {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-masthead {
                      padding: 25px 0;
                      text-align: center;
                    }

                    .email-masthead_logo {
                      width: 94px;
                    }

                    .email-masthead_name {
                      font-size: 16px;
                      font-weight: bold;
                      color: #A8AAAF;
                      text-decoration: none;
                      text-shadow: 0 1px 0 white;
                    }

                    .email-body {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-body_inner {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #FFFFFF;
                    }

                    .email-footer {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .email-footer p {
                      color: #A8AAAF;
                    }

                    .body-action {
                      width: 100%;
                      margin: 30px auto;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .body-sub {
                      margin-top: 25px;
                      padding-top: 25px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .content-cell {
                      padding: 45px;
                    }

                    @media only screen and (max-width: 600px) {
                      .email-body_inner,
                      .email-footer {
                        width: 100% !important;
                      }
                    }

                    @media (prefers-color-scheme: dark) {
                      body,
                      .email-body,
                      .email-body_inner,
                      .email-content,
                      .email-wrapper,
                      .email-masthead,
                      .email-footer {
                        background-color: #333333 !important;
                        color: #FFF !important;
                      }
                      p,
                      ul,
                      ol,
                      blockquote,
                      h1,
                      h2,
                      h3 {
                        color: #FFF !important;
                      }
                      .attributes_content,
                      .discount {
                        background-color: #222 !important;
                      }
                      .email-masthead_name {
                        text-shadow: none !important;
                      }
                    }
                    </style>
                    <style type="text/css">
                      .f-fallback  {
                        font-family: Arial, sans-serif;
                      }
                    </style>
                  </head>
                  <body>
                    <span class="preheader">Gunakan Link Di Bawah Ini Untuk Mengubah Password. Link hanya Berlaku 24 Jam.</span>
                    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td align="center">
                          <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                                <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell">
                                      <div class="f-fallback">
                                        <h1>Hai ' . $name . ',</h1>
                                        <p>Kamu Baru saja meminta untuk mengubah password UR-Partner account. gunakan tombol di bawah ini untuk mengubahnya.</p>
                                        <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                          <tr>
                                            <td align="center">
                                              <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                                <tr>
                                                  <td align="center">
                                                    <a href="https://apis.ur-hub.com/qr/v2/controller/changePasswordProfile.php?id=' . md5($randomString) . '" class="f-fallback button button--green" target="_blank" style = "color:#fff">Ubah Password</a>
                                                  </td>
                                                </tr>
                                              </table>
                                            </td>
                                          </tr>
                                        </table>
                                        <p>Jika kamu tidak meminta untuk mengubah password silahkan abaikan email ini, atau klik <a href="https://ur-hub.com/contact-us/">kontak bantuan</a> jika kamu memiliki pertanyaan.</p>
                                        <p>Thanks,
                                          <br>UR - Easy & Quick Order</p>
                                        <!-- Sub copy -->
                                      </div>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                            <tr>
                              <td>
                                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell" align="center">
                                      <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                      <p class="f-fallback sub align-center">
                                        PT. Rahmat Tuhan Lestari
                                        <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir,
                                        <br>Kota Bandung, Jawa Barat 40221
                                        <br>Indonesia
                                      </p>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </body>
                </html>'
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }

  public function changePasswordMaster($email)
  {
    date_default_timezone_set('Asia/Jakarta');
    $dates1 = date('Y-m-d', time());
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    $tablename = 'master';
    for ($i = 0; $i < 12; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    $stmt1 = $this->conn->prepare("INSERT INTO reset_password (tablename, token, created_at, email) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("ssss", $tablename, $randomString, $dates1, $email);
    $stmt1->execute();
    // if ($stmt->execute()) {
    //         return $randomString;
    // } else {
    //         return USER_NOT_CREATED;
    // }

    // $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // $random_string = '';
    // for($i = 0; $i < 12; $i++) {
    //     $random_character = $input[mt_rand(0, $input_length - 1)];
    //     $random_string .= $random_character;
    // }
    // $tkn - $this->con->prepare("INSERT INTO `tokenpartner`(`token`, `id_partner`) VALUES ('$random_string','$email')");
    // $tkn->execute();
    $stmt = $this->conn->prepare("SELECT name, phone, email FROM master WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($name, $phone, $email);
      $stmt->fetch();
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Ubah Passwor",
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html>
                  <head>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <meta name="x-apple-disable-message-reformatting" />
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title></title>
                    <style type="text/css" rel="stylesheet" media="all">

                    @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                    body {
                      width: 100% !important;
                      height: 100%;
                      margin: 0;
                      -webkit-text-size-adjust: none;
                    }

                    a {
                      color: #3869D4;
                    }

                    a img {
                      border: none;
                    }

                    td {
                      word-break: break-word;
                    }

                    .preheader {
                      display: none !important;
                      visibility: hidden;
                      mso-hide: all;
                      font-size: 1px;
                      line-height: 1px;
                      max-height: 0;
                      max-width: 0;
                      opacity: 0;
                      overflow: hidden;
                    }

                    body,
                    td,
                    th {
                      font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                    }

                    h1 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 22px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h2 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 16px;
                      font-weight: bold;
                      text-align: left;
                    }

                    h3 {
                      margin-top: 0;
                      color: #333333;
                      font-size: 14px;
                      font-weight: bold;
                      text-align: left;
                    }

                    td,
                    th {
                      font-size: 16px;
                    }

                    p,
                    ul,
                    ol,
                    blockquote {
                      margin: .4em 0 1.1875em;
                      font-size: 16px;
                      line-height: 1.625;
                    }

                    p.sub {
                      font-size: 13px;
                    }

                    .align-right {
                      text-align: right;
                    }

                    .align-left {
                      text-align: left;
                    }

                    .align-center {
                      text-align: center;
                    }

                    .button {
                      background-color: #3869D4;
                      border-top: 10px solid #3869D4;
                      border-right: 18px solid #3869D4;
                      border-bottom: 10px solid #3869D4;
                      border-left: 18px solid #3869D4;
                      display: inline-block;
                      color: #FFF;
                      text-decoration: none;
                      border-radius: 3px;
                      box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                      -webkit-text-size-adjust: none;
                      box-sizing: border-box;
                    }

                    .button--green {
                      background-color: #22BC66;
                      border-top: 10px solid #22BC66;
                      border-right: 18px solid #22BC66;
                      border-bottom: 10px solid #22BC66;
                      border-left: 18px solid #22BC66;
                    }

                    .button--red {
                      background-color: #FF6136;
                      border-top: 10px solid #FF6136;
                      border-right: 18px solid #FF6136;
                      border-bottom: 10px solid #FF6136;
                      border-left: 18px solid #FF6136;
                    }

                    @media only screen and (max-width: 500px) {
                      .button {
                        width: 100% !important;
                        text-align: center !important;
                      }
                    }

                    .attributes {
                      margin: 0 0 21px;
                    }

                    .attributes_content {
                      background-color: #F4F4F7;
                      padding: 16px;
                    }

                    .attributes_item {
                      padding: 0;
                    }

                    .related {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .related_item {
                      padding: 10px 0;
                      color: #CBCCCF;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .related_item-title {
                      display: block;
                      margin: .5em 0 0;
                    }

                    .related_item-thumb {
                      display: block;
                      padding-bottom: 10px;
                    }

                    .related_heading {
                      border-top: 1px solid #CBCCCF;
                      text-align: center;
                      padding: 25px 0 10px;
                    }

                    .discount {
                      width: 100%;
                      margin: 0;
                      padding: 24px;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F4F4F7;
                      border: 2px dashed #CBCCCF;
                    }

                    .discount_heading {
                      text-align: center;
                    }

                    .discount_body {
                      text-align: center;
                      font-size: 15px;
                    }

                    .social {
                      width: auto;
                    }

                    .social td {
                      padding: 0;
                      width: auto;
                    }

                    .social_icon {
                      height: 20px;
                      margin: 0 8px 10px 8px;
                      padding: 0;
                    }

                    .purchase {
                      width: 100%;
                      margin: 0;
                      padding: 35px 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_content {
                      width: 100%;
                      margin: 0;
                      padding: 25px 0 0 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .purchase_item {
                      padding: 10px 0;
                      color: #51545E;
                      font-size: 15px;
                      line-height: 18px;
                    }

                    .purchase_heading {
                      padding-bottom: 8px;
                      border-bottom: 1px solid #EAEAEC;
                    }

                    .purchase_heading p {
                      margin: 0;
                      color: #85878E;
                      font-size: 12px;
                    }

                    .purchase_footer {
                      padding-top: 15px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .purchase_total {
                      margin: 0;
                      text-align: right;
                      font-weight: bold;
                      color: #333333;
                    }

                    .purchase_total--label {
                      padding: 0 15px 0 0;
                    }

                    body {
                      background-color: #F2F4F6;
                      color: #51545E;
                    }

                    p {
                      color: #51545E;
                    }

                    .email-wrapper {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #F2F4F6;
                    }

                    .email-content {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-masthead {
                      padding: 25px 0;
                      text-align: center;
                    }

                    .email-masthead_logo {
                      width: 94px;
                    }

                    .email-masthead_name {
                      font-size: 16px;
                      font-weight: bold;
                      color: #A8AAAF;
                      text-decoration: none;
                      text-shadow: 0 1px 0 white;
                    }

                    .email-body {
                      width: 100%;
                      margin: 0;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                    }

                    .email-body_inner {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      background-color: #FFFFFF;
                    }

                    .email-footer {
                      width: 570px;
                      margin: 0 auto;
                      padding: 0;
                      -premailer-width: 570px;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .email-footer p {
                      color: #A8AAAF;
                    }

                    .body-action {
                      width: 100%;
                      margin: 30px auto;
                      padding: 0;
                      -premailer-width: 100%;
                      -premailer-cellpadding: 0;
                      -premailer-cellspacing: 0;
                      text-align: center;
                    }

                    .body-sub {
                      margin-top: 25px;
                      padding-top: 25px;
                      border-top: 1px solid #EAEAEC;
                    }

                    .content-cell {
                      padding: 45px;
                    }

                    @media only screen and (max-width: 600px) {
                      .email-body_inner,
                      .email-footer {
                        width: 100% !important;
                      }
                    }

                    @media (prefers-color-scheme: dark) {
                      body,
                      .email-body,
                      .email-body_inner,
                      .email-content,
                      .email-wrapper,
                      .email-masthead,
                      .email-footer {
                        background-color: #333333 !important;
                        color: #FFF !important;
                      }
                      p,
                      ul,
                      ol,
                      blockquote,
                      h1,
                      h2,
                      h3 {
                        color: #FFF !important;
                      }
                      .attributes_content,
                      .discount {
                        background-color: #222 !important;
                      }
                      .email-masthead_name {
                        text-shadow: none !important;
                      }
                    }
                    </style>
                    <style type="text/css">
                      .f-fallback  {
                        font-family: Arial, sans-serif;
                      }
                    </style>
                  </head>
                  <body>
                    <span class="preheader">Gunakan Link Di Bawah Ini Untuk Mengubah Password. Link hanya Berlaku 24 Jam.</span>
                    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td align="center">
                          <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                              <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                                <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell">
                                      <div class="f-fallback">
                                        <h1>Hi ' . $name . ',</h1>
                                        <p>Kamu Baru saja meminta untuk mengubah password UR-Partner account. gunakan tombol di bawah ini untuk mengubahnya</p>
                                        <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                          <tr>
                                            <td align="center">
                                              <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                                <tr>
                                                  <td align="center">
                                                    <a href="https://apis.ur-hub.com/qr/v2/controller/master/changePasswordMasterProfile.php?id=' . md5($randomString) . '" class="f-fallback button button--green" target="_blank" style = "color:#fff">Ubah Password</a>
                                                  </td>
                                                </tr>
                                              </table>
                                            </td>
                                          </tr>
                                        </table>
                                        <p>Jika kamu tidak meminta untuk mengubah password silahkan abaikan email ini, atau klik <a href="https://ur-hub.com/contact-us/">kontak bantuan</a> jika kamu memiliki pertanyaan.</p>
                                        <p>Terima Kasih,
                                          <br>UR - Easy & Quick Order</p>
                                        <!-- Sub copy -->
                                      </div>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                            <tr>
                              <td>
                                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td class="content-cell" align="center">
                                      <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                      <p class="f-fallback sub align-center">
                                        PT. Rahmat Tuhan Lestari
                                        <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir,
                                        <br>Kota Bandung, Jawa Barat 40221
                                        <br>Indonesia
                                      </p>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </body>
                </html>'
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }


  public function mailingAccountingPartner($email)
  {
    $stmt = $this->conn->prepare("SELECT id,name,phone,tax,service,email FROM partner WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($id, $name, $phone, $tax, $service, $email);
      $stmt->fetch();
      function tgl_indo($tanggal)
      {
        $bulan = array(
          1 =>   'Januari',
          'Februari',
          'Maret',
          'April',
          'Mei',
          'Juni',
          'Juli',
          'Agustus',
          'September',
          'Oktober',
          'November',
          'Desember'
        );
        return $bulan[(int)$tanggal];
      }
      $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
      function rupiah($angka)
      {

        $hasil_rupiah = "Rp. " . number_format($angka, 0, ',', '.');
        return $hasil_rupiah;
      }
      $dateNow = date('d/M/Y');
      $first_day_last_month = date('01/M/Y', strtotime('-1 month'));
      $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
      $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
      $dateLastDb = date('Y-m-t', strtotime('-1 month'));
      // $first_day_last_month = date('01/M/Y');
      // $last_day_last_month  = date('t/M/Y');
      // $dateFirstDb = date('Y-m-01');
      // $dateLastDb = date('Y-m-t');
      $menu = mysqli_query($db_conn, "SELECT * FROM menu WHERE id_partner='$id';");
      $transaksi = mysqli_query($db_conn, "SELECT total,promo,tax,service,status,tipe_bayar,charge_ewallet,charge_ur FROM transaksi WHERE id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'");

      $total = 0;
      $promo = 0;
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $charge_ewallet = 0;
      $taxtype = 0;
      $sumCharge_ur = 0;
      while ($row = mysqli_fetch_assoc($transaksi)) {
        // if($row['tipe_bayar']=='5'|| $row['tipe_bayar']=='7' || $row['tipe_bayar']==5 || $row['tipe_bayar']==7){
        //   $total += ($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)));
        //   $promo += $row['promo'];
        // }else{
        //   $total += ($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))-ceil(ceil(($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))*($row['charge_ewallet']/100))+(ceil(ceil(($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))*($row['charge_ewallet']/100))*($row['tax']/100))));

        // }
        // $promo += $row['promo'];
        $sumCharge_ur += $row['charge_ur'];
        $charge_ewallet = $row['charge_ewallet'];
        $taxtype = $row['tax'];
        $servicetype = $row['service'];
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $withTax = 0;
        if ($row['tipe_bayar'] == 1 || $row['tipe_bayar'] == '1') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $ovo += $withTax;
        } else if ($row['tipe_bayar'] == 2 || $row['tipe_bayar'] == '2') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $gopay += $withTax;
        } else if ($row['tipe_bayar'] == 3 || $row['tipe_bayar'] == '3') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $dana += $withTax;
        } else if ($row['tipe_bayar'] == 4 || $row['tipe_bayar'] == '4') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $linkaja += $withTax;
        } else if ($row['tipe_bayar'] == 5 || $row['tipe_bayar'] == '5') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($row['tipe_bayar'] == 6 || $row['tipe_bayar'] == '6') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $sakuku += $withTax;
        } else if ($row['tipe_bayar'] == 7 || $row['tipe_bayar'] == '7') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($row['tipe_bayar'] == 8 || $row['tipe_bayar'] == '8') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
      }
      $hargaPokok = 0;
      $menu2 = mysqli_query($db_conn, "SELECT * FROM menu WHERE id_partner='$id';");
      while ($rowcheck1 = mysqli_fetch_assoc($menu2)) {
        $idMenuCheck = $rowcheck1['id'];
        $hargaPokokAwal = $rowcheck1['hpp'];
        $detailcheck = mysqli_query($db_conn, "SELECT SUM(qty) AS qtytotal FROM detail_transaksi join transaksi ON detail_transaksi.id_transaksi = transaksi.id WHERE id_menu='$idMenuCheck' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb';");
        while ($rowpph = mysqli_fetch_assoc($detailcheck)) {
          $qtyJual = $rowpph['qtytotal'];
        }
        $hargaPokok += $hargaPokokAwal * $qtyJual;
      }

      // $subtotal = (($total + ($total * 0.1)) - ($hargaPokok + $promo)) - ($total * 0.1);
      $subtotal = (($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) - ceil($sumCharge_ur);
      // if($tax==1){
      //   $subtotal = ($total + ($total * 0.1));
      // }
      // if($service!=0){
      //   $subtotal = ($total + ($total * ($service/100)));
      // }
      $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
              <head>
                <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                <meta name="x-apple-disable-message-reformatting" />
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <title></title>
                <style type="text/css" rel="stylesheet" media="all">

                @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                body {
                  width: 100% !important;
                  height: 100%;
                  margin: 0;
                  -webkit-text-size-adjust: none;
                }

                a {
                  color: #3869D4;
                }

                a img {
                  border: none;
                }

                td {
                  word-break: break-word;
                }

                .preheader {
                  display: none !important;
                  visibility: hidden;
                  mso-hide: all;
                  font-size: 1px;
                  line-height: 1px;
                  max-height: 0;
                  max-width: 0;
                  opacity: 0;
                  overflow: hidden;
                }

                body,
                td,
                th {
                  font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                }

                h1 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 22px;
                  font-weight: bold;
                  text-align: left;
                }

                h2 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 16px;
                  font-weight: bold;
                  text-align: left;
                }

                h3 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 14px;
                  font-weight: bold;
                  text-align: left;
                }

                td,
                th {
                  font-size: 16px;
                }

                p,
                ul,
                ol,
                blockquote {
                  margin: .4em 0 1.1875em;
                  font-size: 16px;
                  line-height: 1.625;
                }

                p.sub {
                  font-size: 13px;
                }

                .align-right {
                  text-align: right;
                }

                .align-left {
                  text-align: left;
                }

                .align-center {
                  text-align: center;
                }
                .button {
                  background-color: #3869D4;
                  border-top: 10px solid #3869D4;
                  border-right: 18px solid #3869D4;
                  border-bottom: 10px solid #3869D4;
                  border-left: 18px solid #3869D4;
                  display: inline-block;
                  text-decoration: none;
                  border-radius: 3px;
                  box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                  -webkit-text-size-adjust: none;
                  box-sizing: border-box;
                }

                .button--green {
                  background-color: #22BC66;
                  border-top: 10px solid #22BC66;
                  border-right: 18px solid #22BC66;
                  border-bottom: 10px solid #22BC66;
                  border-left: 18px solid #22BC66;
                }

                .button--red {
                  background-color: #FF6136;
                  border-top: 10px solid #FF6136;
                  border-right: 18px solid #FF6136;
                  border-bottom: 10px solid #FF6136;
                  border-left: 18px solid #FF6136;
                }

                @media only screen and (max-width: 500px) {
                  .button {
                    width: 100% !important;
                    text-align: center !important;
                  }
                }

                .attributes {
                  margin: 0 0 21px;
                }

                .attributes_content {
                  background-color: #F4F4F7;
                  padding: 16px;
                }

                .attributes_item {
                  padding: 0;
                }

                .related {
                  width: 100%;
                  margin: 0;
                  padding: 25px 0 0 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .related_item {
                  padding: 10px 0;
                  color: #CBCCCF;
                  font-size: 15px;
                  line-height: 18px;
                }

                .related_item-title {
                  display: block;
                  margin: .5em 0 0;
                }

                .related_item-thumb {
                  display: block;
                  padding-bottom: 10px;
                }

                .related_heading {
                  border-top: 1px solid #CBCCCF;
                  text-align: center;
                  padding: 25px 0 10px;
                }

                .discount {
                  width: 100%;
                  margin: 0;
                  padding: 24px;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #F4F4F7;
                  border: 2px dashed #CBCCCF;
                }

                .discount_heading {
                  text-align: center;
                }

                .discount_body {
                  text-align: center;
                  font-size: 15px;
                }

                .social {
                  width: auto;
                }

                .social td {
                  padding: 0;
                  width: auto;
                }

                .social_icon {
                  height: 20px;
                  margin: 0 8px 10px 8px;
                  padding: 0;
                }

                .purchase {
                  width: 100%;
                  margin: 0;
                  padding: 35px 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .purchase_content {
                  width: 100%;
                  margin: 0;
                  padding: 25px 0 0 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .purchase_item {
                  padding: 10px 0;
                  color: #51545E;
                  font-size: 15px;
                  line-height: 18px;
                }

                .purchase_heading {
                  padding-bottom: 8px;
                  border-bottom: 1px solid #EAEAEC;
                }

                .purchase_heading p {
                  margin: 0;
                  color: #85878E;
                  font-size: 12px;
                }

                .purchase_footer {
                  padding-top: 15px;
                  border-top: 1px solid #EAEAEC;
                }

                .purchase_total {
                  margin: 0;
                  text-align: right;
                  font-weight: bold;
                  color: #333333;
                }

                .purchase_total--label {
                  padding: 0 15px 0 0;
                }

                body {
                  background-color: #F4F4F7;
                  color: #51545E;
                }

                p {
                  color: #51545E;
                }

                p.sub {
                  color: #6B6E76;
                }

                .email-wrapper {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #F4F4F7;
                }

                .email-content {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }


                .email-masthead {
                  padding: 25px 0;
                  text-align: center;
                }

                .email-masthead_logo {
                  width: 94px;
                }

                .email-masthead_name {
                  font-size: 16px;
                  font-weight: bold;
                  color: #A8AAAF;
                  text-decoration: none;
                  text-shadow: 0 1px 0 white;
                }
                .email-body {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #FFFFFF;
                }

                .email-body_inner {
                  width: 570px;
                  margin: 0 auto;
                  padding: 0;
                  -premailer-width: 570px;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #FFFFFF;
                }

                .email-footer {
                  width: 570px;
                  margin: 0 auto;
                  padding: 0;
                  -premailer-width: 570px;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  text-align: center;
                }

                .email-footer p {
                  color: #6B6E76;
                }

                .body-action {
                  width: 100%;
                  margin: 30px auto;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  text-align: center;
                }

                .body-sub {
                  margin-top: 25px;
                  padding-top: 25px;
                  border-top: 1px solid #EAEAEC;
                }

                .content-cell {
                  padding: 35px;
                }

                @media only screen and (max-width: 600px) {
                  .email-body_inner,
                  .email-footer {
                    width: 100% !important;
                  }
                }

                @media (prefers-color-scheme: dark) {
                  body,
                  .email-body,
                  .email-body_inner,
                  .email-content,
                  .email-wrapper,
                  .email-masthead,
                  .email-footer {
                    background-color: #333333 !important;
                    color: #FFF !important;
                  }
                  p,
                  ul,
                  ol,
                  blockquote,
                  h1,
                  h2,
                  h3 {
                    color: #FFF !important;
                  }
                  .attributes_content,
                  .discount {
                    background-color: #222 !important;
                  }
                  .email-masthead_name {
                    text-shadow: none !important;
                  }
                }
                </style>
              </head>
              <body>
                <span class="preheader">Laporan Keuangan Bulanan Periode ' . $first_day_last_month . ' Sampai Dengan ' . $last_day_last_month . '</span>
                <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                  <tr>
                    <td align="center">
                      <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <!-- <tr>
                          <td class="email-masthead">
                            <a href="https://example.com" class="f-fallback email-masthead_name">
                            UR HUB
                          </a>
                          </td>
                        </tr> -->

                        <tr>
                          <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell">
                                <div class="align-center"><img  src="https://ur-hub.s3.us-west-2.amazonaws.com/assets/logo/logo.png"></div>
                                    <h3 class="align-right">Tanggal:' . $dateNow . '</h3>
                                  <div class="f-fallback">
                                    <h1>Hi ' . $name . ',</h1>

                                    <table class="discount" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                            <p>Pendapatan Bulan ' . tgl_indo(date('m', strtotime('-1 month'))) . '</p>
                                          <h1 class="f-fallback discount_heading">' . rupiah($subtotal) . '</h1>
                                          <p>Periode ' . $first_day_last_month . ' Sampai Dengan ' . $last_day_last_month . '</p>
                                        </td>
                                      </tr>
                                    </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                          <td>
                                            <h3>Rincian Pendapatan</h3></td>
                                          <td>
                                        </tr>
                                        <tr>
                                          <td colspan="2">

                                            <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                            ';
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $charge_ewallet = 0;
      $taxtype = 0;
      $sumCharge_ur = 0;
      $tipe_bayar = mysqli_query($db_conn, "SELECT total, promo ,tax,service,status,tipe_bayar,charge_ewallet,charge_ur FROM transaksi WHERE id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'");
      while ($rowtypeBayar = mysqli_fetch_assoc($tipe_bayar)) {
        // $totalType += $rowtypeBayar['total'];
        // $subtotal += $total;
        // if($tax==1){
        //   // $subtotal = ($total + ($total * 0.1));
        //   $totalType = ($rowtypeBayar['tottotal']+ ($rowtypeBayar['tottotal'] * 0.1));
        // }
        // if($service!=0){
        //   // $subtotal = ($total + ($total * ($service/100)));
        //   $totalType = ($rowtypeBayar['tottotal']+($rowtypeBayar['tottotal']*($service/100)));
        // }
        $sumCharge_ur = $rowtypeBayar['charge_ur'];
        $charge_ewallet = $rowtypeBayar['charge_ewallet'];
        $taxtype = $rowtypeBayar['tax'];
        $servicetype = $rowtypeBayar['service'];
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $withTax = 0;
        if ($rowtypeBayar['tipe_bayar'] == 1 || $rowtypeBayar['tipe_bayar'] == '1') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $ovo += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 2 || $rowtypeBayar['tipe_bayar'] == '2') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $gopay += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 3 || $rowtypeBayar['tipe_bayar'] == '3') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $dana += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 4 || $rowtypeBayar['tipe_bayar'] == '4') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $linkaja += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 5 || $rowtypeBayar['tipe_bayar'] == '5') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 6 || $rowtypeBayar['tipe_bayar'] == '6') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $sakuku += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 7 || $rowtypeBayar['tipe_bayar'] == '7') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 8 || $rowtypeBayar['tipe_bayar'] == '8') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
        // if($rowtypeBayar['type_bayar']=='5'|| $rowtypeBayar['type_bayar']=='7' || $rowtypeBayar['type_bayar']==5 || $rowtypeBayar['type_bayar']==7){
        //   $totalType += ($rowtypeBayar['total']+($rowtypeBayar['total']*($rowtypeBayar['tax']/100))+($rowtypeBayar['total']*($rowtypeBayar['service']/100)));
        //   $promoType += $rowtypeBayar['promo'];
        // }else{
        //   $totalType+= ($rowtypeBayar['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))+(($row['total']*($row['charge_ewallet']/100))*($row['tax']/100));
        //   $promoType += $rowtypeBayar['promo'];
        // }
        // $typeCode = $rowtypeBayar['tipe_bayar'];
        // switch ($typeCode) {
        //   case 1:
        //     $type = 'OVO';
        //     break;
        //   case 2:
        //     $type = 'GOPAY';
        //     break;
        //   case 3:
        //     $type = 'DANA';
        //     break;
        //   case 4:
        //     $type = 'T-CASH';
        //     break;
        //   case 5:
        //     $type = 'TUNAI/DEBIT';
        //     break;
        //   case 6:
        //     $type = 'SAKUKU';
        //     break;
        //   case 7:
        //     $type = 'CREDIT CARD';
        //     break;
        // }

      }

      //   <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">LINK AJA</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.rupiah($linkaja).'</span></td>
      // </tr>
      // <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">SAKUKU</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.rupiah($sakuku).'</span></td>
      // </tr>

      $html .= '<tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">E-wallet</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                      </tr>
                                                      <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">OVO</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($ovo) . '</span></td>
                                                      </tr>
                                                      <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">GOPAY</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($gopay) . '</span></td>
                                                      </tr>
                                                        <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($dana) . '</span></td>
                                                      </tr>
                                                        <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">LinkAja</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($linkaja) . '</span></td>
                                                      </tr>
                                                        <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($sakuku) . '</span></td>
                                                      </tr>
                                                      <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">Charge E-Wallet (' . $charge_ewallet . '% + ' . $taxtype . '%)</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah(ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $taxtype / 100))) . '</span></td>
                                                    </tr>
                                                    <tr>
                                                    <td width="80%" class="purchase_item"><span class="f-fallback">Total E-Wallet</span></td>
                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah(($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $taxtype / 100)))) . '</span></td>
                                                  </tr>
                                                      ';
      $html .= '
                                                  <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                      </tr>
                                                  <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">Non E-wallet</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                      </tr>
                                                      <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">CASH</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($tunaiDebit) . '</span></td>
                                                      </tr>
                                                      <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">CREDIT CARD</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($creditCard) . '</span></td>
                                                    </tr>
                                                    <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">DEBIT CARD</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($debitCard) . '</span></td>
                                                    </tr>
                                                    <tr>
                                                    <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                  </tr>
                                                  <tr>
                                                  <td width="80%" class="purchase_item"><span class="f-fallback">SUBTOTAL</span></td>
                                                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah(($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $taxtype / 100))) + $tunaiDebit + $creditCard + $debitCard) . '</span></td>
                                                </tr>
                                                <tr>
                                                  <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                </tr>
                                                <tr>
                                                <td width="80%" class="purchase_item"><span class="f-fallback">Convenience Fee</span></td>
                                                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah($sumCharge_ur) . '</span></td>
                                              </tr>
                                              <tr>
                                              <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                            </tr>
                                            <tr>
                                                <td width="80%" class="purchase_item"><span class="f-fallback">TOTAL</span></td>
                                                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah((($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) - ceil($sumCharge_ur)) . '</span></td>
                                              </tr>
                                                      ';


      $html .= '</table>
                                                                                </td>
                                                                              </tr>
                                                                            </table>

                                                                          <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                                                            <tr>
                                                                              <td>
                                                                                <h3>Menu Terlaris</h3></td>
                                                                              <td>
                                                                            </tr>
                                                                            <tr>
                                                                              <td colspan="2">

                                                                                <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                                                                  <tr>
                                                                                    <th class="purchase_heading" align="left">
                                                                                      <p class="f-fallback">Menu</p>
                                                                                    </th>
                                                                                    <th class="purchase_heading" align="right">
                                                                                      <p class="f-fallback">Amount</p>
                                                                                    </th>
                                                                                  </tr>';
      $fav = mysqli_query($db_conn, "SELECT menu.nama,SUM(detail_transaksi.qty) AS qty FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id JOIN partner ON transaksi.id_partner = partner.id WHERE partner.id= '$id' AND transaksi.status<=2 and transaksi.status>=1  AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb' GROUP BY menu.nama ORDER BY qty DESC LIMIT 5");
      while ($rowMenu = mysqli_fetch_assoc($fav)) {
        $namaMenu = $rowMenu['nama'];
        $qtyMenu = $rowMenu['qty'];
        $html .= '<tr>
                                                                                    <td width="80%" class="purchase_item"><span class="f-fallback">' . $namaMenu . '</span></td>
                                                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">x ' . $qtyMenu . '</span></td>
                                                                                  </tr>';
      }
      $html .= '</table>
                                                                              </td>
                                                                            </tr>
                                                                          </table>


                                    <p>Hormat Kami,
                                      <br>UR - Easy & Quick Order</p>

                                    <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                          <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                            <tr>
                                              <td align="center">
                                                <a href="https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdfPartner.php?id=' . md5($id) . '" class="f-fallback button button--blue" target="_blank" style = "color:#fff">Download Full PDF</a>
                                              </td>
                                            </tr>
                                          </table>
                                        </td>
                                      </tr>
                                    </table>
                                    <!-- Sub copy -->
                                    <table class="body-sub" role="presentation">
                                      <tr>
                                        <td>
                                        <p class="f-fallback sub">Need a printable copy for your records?</strong> You can <a href="https://apis.ur-hub.com/qr/v2/csv/xlsPartner.php?id=' . md5($id) . '">download a Xls version</a>.</p>
                                        </td>
                                      </tr>
                                    </table>
                                  </div>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell" align="center">
                                  <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                  <p class="f-fallback sub align-center">
                                    PT. Rahmat Tuhan Lestari
                                    <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir, Kota Bandung
                                    <br>Jawa Barat 40221
                                  </p>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
              </body>
            </html>';
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Laporan Keuangan",
        // "<div>Hai, " . $name . " </div>
        // // <>
        // <br>
        // <br>Laporan Keuangan
        // <br>
        // <br><a href='https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdf.php?id=" . md5(id) . "'>click here</a>
        // <br>
        // <br>
        // Jika anda merasa tidak melakukan request silahkan abaikan pesan ini.
        // <br>
        // <br>
        // Hormat Kami,
        // <br><br>
        // Ur Hub."
        $html
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }
  public function mailingAccountingMaster($email)
  {
    $stmt = $this->conn->prepare("SELECT id,name, phone, email FROM master WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($id, $name, $phone, $email);
      $stmt->fetch();

      $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
      function rupiah($angka)
      {

        $hasil_rupiah = "Rp. " . number_format($angka, 0, ',', '.');
        return $hasil_rupiah;
      }
      function tgl_indo($tanggal)
      {
        $bulan = array(
          1 =>   'Januari',
          'Februari',
          'Maret',
          'April',
          'Mei',
          'Juni',
          'Juli',
          'Agustus',
          'September',
          'Oktober',
          'November',
          'Desember'
        );
        return $bulan[(int)$tanggal];
      }
      $dateNow = date('d/M/Y');
      $first_day_last_month = date('01/M/Y', strtotime('-1 month'));
      $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
      $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
      $dateLastDb = date('Y-m-t', strtotime('-1 month'));
      // $first_day_last_month = date('01/M/Y');
      // $last_day_last_month  = date('t/M/Y');
      // $dateFirstDb = date('Y-m-01');
      // $dateLastDb = date('Y-m-t');


      $menu = mysqli_query($db_conn, "SELECT * FROM menu join partner ON menu.id_partner=partner.id WHERE partner.id_master='$id';");
      $transaksi = mysqli_query($db_conn, "SELECT transaksi.total,transaksi.promo,transaksi.tax,transaksi.service,transaksi.status,transaksi.tipe_bayar,transaksi.charge_ewallet,transaksi.charge_ur FROM transaksi join partner on transaksi.id_partner=partner.id WHERE id_master='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'");

      $total = 0;
      $promo = 0;
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $charge_ewallet = 0;
      $taxtype = 0;
      $sumCharge_ur = 0;
      while ($row = mysqli_fetch_assoc($transaksi)) {
        // if($row['tipe_bayar']=='5'|| $row['tipe_bayar']=='7' || $row['tipe_bayar']==5 || $row['tipe_bayar']==7){
        //   $total += ($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)));
        //   $promo += $row['promo'];
        // }else{
        //   $total += ($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))-ceil(ceil(($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))*($row['charge_ewallet']/100))+(ceil(ceil(($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))*($row['charge_ewallet']/100))*($row['tax']/100))));

        // }
        // $promo += $row['promo'];
        $sumCharge_ur += $row['charge_ur'];
        $charge_ewallet = $row['charge_ewallet'];
        $tax = $row['tax'];
        $service = $row['service'];
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $withTax = 0;
        if ($row['tipe_bayar'] == 1 || $row['tipe_bayar'] == '1') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $ovo += $withTax;
        } else if ($row['tipe_bayar'] == 2 || $row['tipe_bayar'] == '2') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $gopay += $withTax;
        } else if ($row['tipe_bayar'] == 3 || $row['tipe_bayar'] == '3') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $dana += $withTax;
        } else if ($row['tipe_bayar'] == 4 || $row['tipe_bayar'] == '4') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $linkaja += $withTax;
        } else if ($row['tipe_bayar'] == 5 || $row['tipe_bayar'] == '5') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($row['tipe_bayar'] == 6 || $row['tipe_bayar'] == '6') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $sakuku += $withTax;
        } else if ($row['tipe_bayar'] == 7 || $row['tipe_bayar'] == '7') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($row['tipe_bayar'] == 8 || $row['tipe_bayar'] == '8') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
      }

      $hargaPokok = 0;
      $menu2 = mysqli_query($db_conn, "SELECT * FROM menu join partner ON menu.id_partner=partner.id WHERE partner.id_master='$id';");
      while ($rowcheck1 = mysqli_fetch_assoc($menu2)) {
        $idMenuCheck = $rowcheck1['id'];
        $hargaPokokAwal = $rowcheck1['hpp'];
        $detailcheck = mysqli_query($db_conn, "SELECT SUM(qty) AS qtytotal FROM detail_transaksi join transaksi ON detail_transaksi.id_transaksi = transaksi.id WHERE id_menu='$idMenuCheck' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb';");
        while ($rowpph = mysqli_fetch_assoc($detailcheck)) {
          $qtyJual = $rowpph['qtytotal'];
        }
        $hargaPokok += $hargaPokokAwal * $qtyJual;
      }

      // $subtotal = (($total + ($total * 0.1)) - ($hargaPokok + $promo)) - ($total * 0.1);
      $subtotal = (($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) - ceil($sumCharge_ur);
      $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
              <head>
                <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                <meta name="x-apple-disable-message-reformatting" />
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <title></title>
                <style type="text/css" rel="stylesheet" media="all">

                @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                body {
                  width: 100% !important;
                  height: 100%;
                  margin: 0;
                  -webkit-text-size-adjust: none;
                }

                a {
                  color: #3869D4;
                }

                a img {
                  border: none;
                }

                td {
                  word-break: break-word;
                }

                .preheader {
                  display: none !important;
                  visibility: hidden;
                  mso-hide: all;
                  font-size: 1px;
                  line-height: 1px;
                  max-height: 0;
                  max-width: 0;
                  opacity: 0;
                  overflow: hidden;
                }

                body,
                td,
                th {
                  font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                }

                h1 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 22px;
                  font-weight: bold;
                  text-align: left;
                }

                h2 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 16px;
                  font-weight: bold;
                  text-align: left;
                }

                h3 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 14px;
                  font-weight: bold;
                  text-align: left;
                }

                td,
                th {
                  font-size: 16px;
                }

                p,
                ul,
                ol,
                blockquote {
                  margin: .4em 0 1.1875em;
                  font-size: 16px;
                  line-height: 1.625;
                }

                p.sub {
                  font-size: 13px;
                }

                .align-right {
                  text-align: right;
                }

                .align-left {
                  text-align: left;
                }

                .align-center {
                  text-align: center;
                }
                .button {
                  background-color: #3869D4;
                  border-top: 10px solid #3869D4;
                  border-right: 18px solid #3869D4;
                  border-bottom: 10px solid #3869D4;
                  border-left: 18px solid #3869D4;
                  display: inline-block;
                  text-decoration: none;
                  border-radius: 3px;
                  box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                  -webkit-text-size-adjust: none;
                  box-sizing: border-box;
                }

                .button--green {
                  background-color: #22BC66;
                  border-top: 10px solid #22BC66;
                  border-right: 18px solid #22BC66;
                  border-bottom: 10px solid #22BC66;
                  border-left: 18px solid #22BC66;
                }

                .button--red {
                  background-color: #FF6136;
                  border-top: 10px solid #FF6136;
                  border-right: 18px solid #FF6136;
                  border-bottom: 10px solid #FF6136;
                  border-left: 18px solid #FF6136;
                }

                @media only screen and (max-width: 500px) {
                  .button {
                    width: 100% !important;
                    text-align: center !important;
                  }
                }

                .attributes {
                  margin: 0 0 21px;
                }

                .attributes_content {
                  background-color: #F4F4F7;
                  padding: 16px;
                }

                .attributes_item {
                  padding: 0;
                }

                .related {
                  width: 100%;
                  margin: 0;
                  padding: 25px 0 0 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .related_item {
                  padding: 10px 0;
                  color: #CBCCCF;
                  font-size: 15px;
                  line-height: 18px;
                }

                .related_item-title {
                  display: block;
                  margin: .5em 0 0;
                }

                .related_item-thumb {
                  display: block;
                  padding-bottom: 10px;
                }

                .related_heading {
                  border-top: 1px solid #CBCCCF;
                  text-align: center;
                  padding: 25px 0 10px;
                }

                .discount {
                  width: 100%;
                  margin: 0;
                  padding: 24px;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #F4F4F7;
                  border: 2px dashed #CBCCCF;
                }

                .discount_heading {
                  text-align: center;
                }

                .discount_body {
                  text-align: center;
                  font-size: 15px;
                }

                .social {
                  width: auto;
                }

                .social td {
                  padding: 0;
                  width: auto;
                }

                .social_icon {
                  height: 20px;
                  margin: 0 8px 10px 8px;
                  padding: 0;
                }

                .purchase {
                  width: 100%;
                  margin: 0;
                  padding: 35px 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .purchase_content {
                  width: 100%;
                  margin: 0;
                  padding: 25px 0 0 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .purchase_item {
                  padding: 10px 0;
                  color: #51545E;
                  font-size: 15px;
                  line-height: 18px;
                }

                .purchase_heading {
                  padding-bottom: 8px;
                  border-bottom: 1px solid #EAEAEC;
                }

                .purchase_heading p {
                  margin: 0;
                  color: #85878E;
                  font-size: 12px;
                }

                .purchase_footer {
                  padding-top: 15px;
                  border-top: 1px solid #EAEAEC;
                }

                .purchase_total {
                  margin: 0;
                  text-align: right;
                  font-weight: bold;
                  color: #333333;
                }

                .purchase_total--label {
                  padding: 0 15px 0 0;
                }

                body {
                  background-color: #F4F4F7;
                  color: #51545E;
                }

                p {
                  color: #51545E;
                }

                p.sub {
                  color: #6B6E76;
                }

                .email-wrapper {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #F4F4F7;
                }

                .email-content {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }


                .email-masthead {
                  padding: 25px 0;
                  text-align: center;
                }

                .email-masthead_logo {
                  width: 94px;
                }

                .email-masthead_name {
                  font-size: 16px;
                  font-weight: bold;
                  color: #A8AAAF;
                  text-decoration: none;
                  text-shadow: 0 1px 0 white;
                }
                .email-body {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #FFFFFF;
                }

                .email-body_inner {
                  width: 570px;
                  margin: 0 auto;
                  padding: 0;
                  -premailer-width: 570px;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #FFFFFF;
                }

                .email-footer {
                  width: 570px;
                  margin: 0 auto;
                  padding: 0;
                  -premailer-width: 570px;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  text-align: center;
                }

                .email-footer p {
                  color: #6B6E76;
                }

                .body-action {
                  width: 100%;
                  margin: 30px auto;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  text-align: center;
                }

                .body-sub {
                  margin-top: 25px;
                  padding-top: 25px;
                  border-top: 1px solid #EAEAEC;
                }

                .content-cell {
                  padding: 35px;
                }

                @media only screen and (max-width: 600px) {
                  .email-body_inner,
                  .email-footer {
                    width: 100% !important;
                  }
                }

                @media (prefers-color-scheme: dark) {
                  body,
                  .email-body,
                  .email-body_inner,
                  .email-content,
                  .email-wrapper,
                  .email-masthead,
                  .email-footer {
                    background-color: #333333 !important;
                    color: #FFF !important;
                  }
                  p,
                  ul,
                  ol,
                  blockquote,
                  h1,
                  h2,
                  h3 {
                    color: #FFF !important;
                  }
                  .attributes_content,
                  .discount {
                    background-color: #222 !important;
                  }
                  .email-masthead_name {
                    text-shadow: none !important;
                  }
                }
                </style>
              </head>
              <body>
              <span class="preheader">Laporan Keuangan Bulanan Periode ' . $first_day_last_month . ' Sampai Dengan ' . $last_day_last_month . '</span>
                <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                  <tr>
                    <td align="center">
                      <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <!-- <tr>
                          <td class="email-masthead">
                            <a href="https://example.com" class="f-fallback email-masthead_name">
                            UR HUB
                          </a>
                          </td>
                        </tr> -->

                        <tr>
                          <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell">
                                  <div class="align-center"><img  src="https://ur-hub.s3.us-west-2.amazonaws.com/assets/logo/logo.png"></div>
                                    <h3 class="align-right">Tanggal:' . $dateNow . '</h3>
                                  <div class="f-fallback">
                                    <h1>Hi ' . $name . ',</h1>

                                    <table class="discount" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                            <p>Pendapatan Bulan ' . tgl_indo(date('m', strtotime('-1 month'))) . '</p>
                                          <h1 class="f-fallback discount_heading">' . rupiah($subtotal) . '</h1>
                                          <p>Periode ' . $first_day_last_month . ' Sampai Dengan ' . $last_day_last_month . '</p>
                                        </td>
                                      </tr>
                                    </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                          <td>
                                            <h3>Rincian Pendapatan</h3></td>
                                          <td>
                                        </tr>
                                        <tr>
                                          <td colspan="2">

                                            <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                            ';
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $charge_ewallet = 0;
      $taxtype = 0;
      $sumCharge_ur = 0;
      $tipe_bayar = mysqli_query($db_conn, "SELECT transaksi.total,transaksi.promo ,transaksi.tax,transaksi.service,transaksi.status,transaksi.tipe_bayar,transaksi.charge_ewallet,transaksi.charge_ur,partner.name AS namaPartner FROM transaksi join partner on transaksi.id_partner=partner.id WHERE partner.id_master=$id AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'");

      // while ($rowtypeBayar = mysqli_fetch_assoc($tipe_bayar)) {
      //   $totalType = $rowtypeBayar['tottotal'];
      //   $typeCode = $rowtypeBayar['tipe_bayar'];
      //   // $namaPartner =$rowtypeBayar['namaPartner'];
      //   switch ($typeCode) {
      //     case 1:
      //       $type = 'OVO';
      //       break;
      //     case 2:
      //       $type = 'GOPAY';
      //       break;
      //     case 3:
      //       $type = 'DANA';
      //       break;
      //     case 4:
      //       $type = 'T-CASH';
      //       break;
      //     case 5:
      //       $type = 'TUNAI/DEBIT';
      //       break;
      //     case 6:
      //       $type = 'SAKUKU';
      //       break;
      //     case 7:
      //       $type = 'CREDIT CARD';
      //       break;
      //   }
      //   $html .= '
      // <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">' . $type . '</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($totalType) . '</span></td>
      // </tr>';
      // }
      while ($rowtypeBayar = mysqli_fetch_assoc($tipe_bayar)) {
        // $totalType += $rowtypeBayar['total'];
        // $subtotal += $total;
        // if($tax==1){
        //   // $subtotal = ($total + ($total * 0.1));
        //   $totalType = ($rowtypeBayar['tottotal']+ ($rowtypeBayar['tottotal'] * 0.1));
        // }
        // if($service!=0){
        //   // $subtotal = ($total + ($total * ($service/100)));
        //   $totalType = ($rowtypeBayar['tottotal']+($rowtypeBayar['tottotal']*($service/100)));
        // }
        // $totalType += $rowtypeBayar['total'];
        // $subtotal += $total;
        // if($tax==1){
        //   // $subtotal = ($total + ($total * 0.1));
        //   $totalType = ($rowtypeBayar['tottotal']+ ($rowtypeBayar['tottotal'] * 0.1));
        // }
        // if($service!=0){
        //   // $subtotal = ($total + ($total * ($service/100)));
        //   $totalType = ($rowtypeBayar['tottotal']+($rowtypeBayar['tottotal']*($service/100)));
        // }
        $charge_ewallet = $rowtypeBayar['charge_ewallet'];
        $sumCharge_ur  += $rowtypeBayar['charge_ur'];
        $taxtype        = $rowtypeBayar['tax'];
        $servicetype    = $rowtypeBayar['service'];
        $countService   = 0;
        $withService    = 0;
        $countTax       = 0;
        $withTax        = 0;

        $subtotalTransaction = $rowtypeBayar['total'] - $rowtypeBayar['promo'];
        $countService = ceil($subtotalTransaction * ($rowtypeBayar['service'] / 100));
        $withService = $subtotalTransaction + $countService + $rowtypeBayar['charge_ur'];
        $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
        $withTax = $withService + $countTax;

        if ($rowtypeBayar['tipe_bayar'] == 1 || $rowtypeBayar['tipe_bayar'] == '1') {
          $ovo += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 2 || $rowtypeBayar['tipe_bayar'] == '2') {
          $gopay += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 3 || $rowtypeBayar['tipe_bayar'] == '3') {
          $dana += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 4 || $rowtypeBayar['tipe_bayar'] == '4') {
          $linkaja += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 5 || $rowtypeBayar['tipe_bayar'] == '5') {
          $tunaiDebit += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 6 || $rowtypeBayar['tipe_bayar'] == '6') {
          $sakuku += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 7 || $rowtypeBayar['tipe_bayar'] == '7') {
          $creditCard += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 8 || $rowtypeBayar['tipe_bayar'] == '8') {
          $debitCard += $withTax;
        }
        // if($rowtypeBayar['type_bayar']=='5'|| $rowtypeBayar['type_bayar']=='7' || $rowtypeBayar['type_bayar']==5 || $rowtypeBayar['type_bayar']==7){
        //   $totalType += ($rowtypeBayar['total']+($rowtypeBayar['total']*($rowtypeBayar['tax']/100))+($rowtypeBayar['total']*($rowtypeBayar['service']/100)));
        //   $promoType += $rowtypeBayar['promo'];
        // }else{
        //   $totalType+= ($rowtypeBayar['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))+(($row['total']*($row['charge_ewallet']/100))*($row['tax']/100));
        //   $promoType += $rowtypeBayar['promo'];
        // }
        // $typeCode = $rowtypeBayar['tipe_bayar'];
        // switch ($typeCode) {
        //   case 1:
        //     $type = 'OVO';
        //     break;
        //   case 2:
        //     $type = 'GOPAY';
        //     break;
        //   case 3:
        //     $type = 'DANA';
        //     break;
        //   case 4:
        //     $type = 'T-CASH';
        //     break;
        //   case 5:
        //     $type = 'TUNAI/DEBIT';
        //     break;
        //   case 6:
        //     $type = 'SAKUKU';
        //     break;
        //   case 7:
        //     $type = 'CREDIT CARD';
        //     break;
        // }

      }

      //   <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">LINK AJA</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.rupiah($linkaja).'</span></td>
      // </tr>
      // <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">SAKUKU</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.rupiah($sakuku).'</span></td>
      // </tr>

      $html .= '<tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">E-wallet</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                    </tr>
                                                    <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">OVO</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($ovo) . '</span></td>
                                                    </tr>
                                                    <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">GOPAY</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($gopay) . '</span></td>
                                                    </tr>
                                                      <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($dana) . '</span></td>
                                                    </tr>
                                                      <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">LinkAja</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($linkaja) . '</span></td>
                                                    </tr>
                                                      <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($sakuku) . '</span></td>
                                                    </tr>
                                                    <tr>
                                                    <td width="80%" class="purchase_item"><span class="f-fallback">Charge E-Wallet (' . $charge_ewallet . '% + ' . $taxtype . '%)</span></td>
                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah(ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $taxtype / 100))) . '</span></td>
                                                  </tr>
                                                  <tr>
                                                  <td width="80%" class="purchase_item"><span class="f-fallback">Total E-Wallet</span></td>
                                                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah(($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $taxtype / 100)))) . '</span></td>
                                                </tr>
                                                    ';
      $html .= '
                                                <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                    </tr>
                                                <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">Non E-wallet</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                    </tr>
                                                    <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">CASH</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($tunaiDebit) . '</span></td>
                                                    </tr>
                                                    <tr>
                                                    <td width="80%" class="purchase_item"><span class="f-fallback">CREDIT CARD</span></td>
                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($creditCard) . '</span></td>
                                                  </tr>
                                                  <tr>
                                                    <td width="80%" class="purchase_item"><span class="f-fallback">CREDIT CARD</span></td>
                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($debitCard) . '</span></td>
                                                  </tr>
                                                  <tr>
                                                  <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                </tr>
                                                <tr>
                                                <td width="80%" class="purchase_item"><span class="f-fallback">SUBTOTAL</span></td>
                                                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah(($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $taxtype / 100))) + $tunaiDebit + $creditCard + $debitCard) . '</span></td>
                                              </tr>
                                              <tr>
                                                  <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                </tr>
                                                <tr>
                                                <td width="80%" class="purchase_item"><span class="f-fallback">Convenience Fee</span></td>
                                                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah($sumCharge_ur) . '</span></td>
                                              </tr>
                                              <tr>
                                              <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                            </tr>
                                            <tr>
                                                <td width="80%" class="purchase_item"><span class="f-fallback">TOTAL</span></td>
                                                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah((($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) - ceil($sumCharge_ur)) . '</span></td>
                                              </tr>
                                                    ';
      $html .= '</table>
                                          </td>
                                        </tr>
                                      </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td>
                                          <h3>Menu Terlaris</h3></td>
                                        <td>
                                      </tr>
                                      <tr>
                                        <td colspan="2">

                                          <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                              <th class="purchase_heading" align="left">
                                                <p class="f-fallback">Menu</p>
                                              </th>
                                              <th class="purchase_heading" align="right">
                                                <p class="f-fallback">Amount</p>
                                              </th>
                                            </tr>';
      $fav = mysqli_query($db_conn, "SELECT  menu.nama,SUM(detail_transaksi.qty) AS qty FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id join partner on transaksi.id_partner=partner.id WHERE partner.id_master= '$id' AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'  GROUP BY menu.nama ORDER BY qty DESC LIMIT 5");
      while ($rowMenu = mysqli_fetch_assoc($fav)) {
        $namaMenu = $rowMenu['nama'];
        $qtyMenu = $rowMenu['qty'];
        $html .= '<tr>
                                              <td width="80%" class="purchase_item"><span class="f-fallback">' . $namaMenu . '</span></td>
                                              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">x ' . $qtyMenu . '</span></td>
                                            </tr>';
      }
      $html .= '</table>
                                        </td>
                                      </tr>
                                    </table>

                                    <p>Hormat Kami,
                                      <br>UR - Easy & Quick Order</p>

                                    <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                          <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                            <tr>
                                              <td align="center">
                                                <a href="https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdfMaster.php?id=' . md5($id) . '" class="f-fallback button button--blue" target="_blank" style = "color:#fff">Download Full PDF</a>
                                              </td>
                                            </tr>
                                          </table>
                                        </td>
                                      </tr>
                                    </table>
                                    <!-- Sub copy -->
                                    <table class="body-sub" role="presentation">
                                      <tr>
                                        <td>
                                        <p class="f-fallback sub">Need a printable copy for your records?</strong> You can <a href="https://apis.ur-hub.com/qr/v2/csv/xlsMaster.php?id=' . md5($id) . '">download a XLS version</a>.</p>
                                        </td>
                                      </tr>
                                    </table>
                                  </div>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell" align="center">
                                  <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                  <p class="f-fallback sub align-center">
                                    PT. Rahmat Tuhan Lestari
                                    <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir, Kota Bandung
                                    <br>Jawa Barat 40221
                                  </p>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
              </body>
            </html>';
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        'Laporan Keuangan',
        $html
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }

  public function receipt($phone, $transId)
  {
    $stmt = $this->conn->prepare("SELECT email FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    if ($stmt->execute()) {
      $stmt->bind_result($email);
      $stmt->fetch();

      $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
      function rupiah($angka)
      {
        $hasil_rupiah = "Rp. " . number_format($angka, 0, ',', '.');
        return $hasil_rupiah;
      }

      $dateNow = date('d M Y');
      $timeNow = date("h:i");
      // $first_day_last_month = date('01/M/Y', strtotime('-1 month'));
      // $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
      // $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
      // $dateLastDb = date('Y-m-t', strtotime('-1 month'));
      $totalHarga = 0;

      // $menu = mysqli_query($db_conn, "SELECT * FROM menu join partner ON menu.id_partner=partner.id WHERE partner.id_master='$id';");
      $transaksi = mysqli_query($db_conn, "SELECT
    users.name,
    transaksi.id,SUM(transaksi.total) AS tottotal,SUM(transaksi.promo) AS totpromo,transaksi.no_meja,transaksi.tipe_bayar,transaksi.queue,transaksi.takeaway, transaksi.charge_ur,
    partner.name AS partnerName,transaksi.tax,transaksi.service,partner.delivery_fee, transaksi.point
    FROM transaksi
    JOIN users ON transaksi.phone=users.phone
    JOIN partner ON transaksi.id_partner=partner.id
    WHERE transaksi.id='$transId' AND transaksi.status<=2 and transaksi.status>=1");
      while ($row = mysqli_fetch_assoc($transaksi)) {
        $name = $row['name']; //nama user
        $id = $row['id']; //id transaksi
        $tottotal = $row['tottotal'] - $row['totpromo']; //harga total di table tran
        $no_meja = $row['no_meja'];
        $tipe_bayar = $row['tipe_bayar']; //
        $queue = $row['queue'];
        $takeaway = $row['takeaway'];
        $partnerName = $row['partnerName'];
        $tax = $row['tax'];
        $service = $row['service'];
        $delivery_fee = $row['delivery_fee'];
        $charge_ur = $row['charge_ur'];
        $point = $row['point'];
      }

      $serviceTot = (($service / 100) * $tottotal);
      $afterservice = $tottotal + $serviceTot + $charge_ur;
      $taxTot = (($tax / 100) * $afterservice);
      $afterTax = $taxTot + $afterservice;
      $subtotal = $afterTax - $point;

      $strTottotal = rupiah($subtotal);
      $strChargeUR = rupiah($charge_ur);
      $strPoint = rupiah($point);
      //dibikin loop yah yg ini
      $detailTrans = mysqli_query($db_conn, "SELECT
    detail_transaksi.qty,detail_transaksi.harga,
    menu.nama,transaksi.promo
    FROM transaksi
    JOIN detail_transaksi ON transaksi.id=detail_transaksi.id_transaksi
    JOIN menu ON detail_transaksi.id_menu=menu.id
    WHERE transaksi.id='$transId' AND transaksi.status<=2 and transaksi.status>=1");

      //swicther buat type bayar
      switch ($tipe_bayar) {
        case 1:
          $type = 'OVO';
          break;
        case 2:
          $type = 'GOPAY';
          break;
        case 3:
          $type = 'DANA';
          break;
        case 4:
          $type = 'LINKAJA';
          break;
        case 5:
          $type = 'CASH';
          break;
        case 6:
          $type = 'SAKUKU';
          break;
        case 7:
          $type = 'CREDIT CARD';
          break;
        case 8:
          $type = 'DEBIT CARD';
          break;
        default:
          $type = 'OTHERS';
      }
      // while ($row = mysqli_fetch_assoc($transaksi)) {
      //   $total = $row['tottotal'];
      //   $promo = $row['totpromo'];
      // }
      // $hargaPokok = 0;
      // $menu2 = mysqli_query($db_conn, "SELECT * FROM menu join partner ON menu.id_partner=partner.id WHERE partner.id_master='$id';");
      // while ($rowcheck1 = mysqli_fetch_assoc($menu2)) {
      //   $idMenuCheck = $rowcheck1['id'];
      //   $hargaPokokAwal = $rowcheck1['hpp'];
      //   $detailcheck = mysqli_query($db_conn, "SELECT SUM(qty) AS qtytotal FROM detail_transaksi join transaksi ON detail_transaksi.id_transaksi = transaksi.id WHERE id_menu='$idMenuCheck' AND transaksi.status = 2 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb';");
      //   while ($rowpph = mysqli_fetch_assoc($detailcheck)) {
      //     $qtyJual = $rowpph['qtytotal'];
      //   }
      //   $hargaPokok += $hargaPokokAwal * $qtyJual;
      // }

      // $subtotal = (($total + ($total * 0.1)) - ($hargaPokok + $promo)) - ($total * 0.1);
      $html = '<!DOCTYPE html>
      <html>
      <head>

        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <title>UR E-Receipt</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style >
        /**
         * Google webfonts. Recommended to include the .woff version for cross-client compatibility.
         */
        @media screen {
          @font-face {
            font-family: "Source Sans Pro";
            font-style: normal;
            font-weight: 400;
            src: local("Source Sans Pro Regular"), local("SourceSansPro-Regular"), url(https://fonts.gstatic.com/s/sourcesanspro/v10/ODelI1aHBYDBqgeIAH2zlBM0YzuT7MdOe03otPbuUS0.woff) format("woff");
          }

          @font-face {
            font-family: "Source Sans Pro";
            font-style: normal;
            font-weight: 700;
            src: local("Source Sans Pro Bold"), local("SourceSansPro-Bold"), url(https://fonts.gstatic.com/s/sourcesanspro/v10/toadOcfmlt9b38dHJxOBGFkQc6VGVFSmCnC_l7QZG60.woff) format("woff");
          }
        }

        /**
         * Avoid browser level font resizing.
         * 1. Windows Mobile
         * 2. iOS / OSX
         */
        body,
        table,
        td,
        a {
          -ms-text-size-adjust: 100%; /* 1 */
          -webkit-text-size-adjust: 100%; /* 2 */
        }

        /**
         * Remove extra space added to tables and cells in Outlook.
         */
        table,
        td {
          mso-table-rspace: 0pt;
          mso-table-lspace: 0pt;
        }

        /**
         * Better fluid images in Internet Explorer.
         */
        img {
          -ms-interpolation-mode: bicubic;
        }

        /**
         * Remove blue links for iOS devices.
         */
        a[x-apple-data-detectors] {
          font-family: inherit !important;
          font-size: inherit !important;
          font-weight: inherit !important;
          line-height: inherit !important;
          color: inherit !important;
          text-decoration: none !important;
        }

        /**
         * Fix centering issues in Android 4.4.
         */
        div[style*="margin: 16px 0;"] {
          margin: 0 !important;
        }

        body {
          width: 100% !important;
          height: 100% !important;
          padding: 0 !important;
          margin: 0 !important;
        }

        /**
         * Collapse table borders to avoid space between cells.
         */
        table {
          border-collapse: collapse !important;
        }

        a {
          color: #1a82e2;
        }

        img {
          height: auto;
          line-height: 100%;
          text-decoration: none;
          border: 0;
          outline: none;
        }
        .fa {
          padding: 5px;
          font-size: 30px;
          width: 30px;
          text-align: center;
          text-decoration: none;
          margin: 5px 2px;
        }

        .fa:hover {
            opacity: 0.7;
        }

        .fa-facebook {
          background: #3B5998;
          color: white;
        }

          .fa-instagram {
          background: #125688;
          color: white;
          }

          .fa-linkedin {
          background: #007bb5;
          color: white;
        }
        </style>

      </head>
      <body >


        <!-- start body -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
          <!-- start hero -->
          <!-- start logo -->

          <!-- end logo -->
          <tr>
            <td align="center" bgcolor="#f2f4f6">
              <!--[if (gte mso 9)|(IE)]>
              <table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
              <tr>
              <td align="center" valign="top" width="600">
              <![endif]-->
              <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;">
                <tr>
                  <td align="center" bgcolor="#ffffff">
                    <a href="https://ur-hub.com" >
                      <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/logo-blue-boxless.png" alt="Hi ' . $name . '" border="0" width="48" style="width: 48px; max-width: 48px; min-width: 48px;">
                    </a>
                  </td>
                </tr>

                <tr>
                  <td align="left" bgcolor="#ffffff" style="padding:24px 24px 0; font-family: Source Sans Pro, Helvetica, Arial, sans-serif;">
                    <h1 style="margin: 0; font-size: 32px; font-weight: 600; letter-spacing: -1px; line-height: 48px;">Terima Kasih Untuk Pesananmu!</h1>
                  </td>
                </tr>
              </table>
              <!--[if (gte mso 9)|(IE)]>
              </td>
              </tr>
              </table>
              <![endif]-->
            </td>
          </tr>
          <!-- end hero -->

              <!-- start receipt address block -->
              <tr>
                <td align="center" bgcolor="#f2f4f6" valign="top" width="100%">
                  <!--[if (gte mso 9)|(IE)]>
                  <table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
                  <tr>
                  <td align="center" valign="top" width="600">
                  <![endif]-->
                  <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;">
                    <tr >
                      <td align="center" valign="top" style="font-size: 0;">
                        <!--[if (gte mso 9)|(IE)]>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
                        <tr>
                        <td align="left" valign="top" width="300">
                        <![endif]-->
                        <div style="display: inline-block; width: 100%; max-width: 50%; vertical-align: top;">
                          <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250px;">
                            <tr>
                              <td align="left" valign="top" style="padding-bottom: 24px; padding-left: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                                <p><strong>Total</strong></p>
                                <p>' . $strTottotal . '</p>
                              </td>
                            </tr>
                          </table>
                        </div>
                        <!--[if (gte mso 9)|(IE)]>
                        </td>
                        <td align="left" valign="top" width="300">
                        <![endif]-->
                        <div style="display: inline-block; width: 100%; max-width: 50%; vertical-align: top;">
                          <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 350px;">
                            <tr>
                              <td align="left" valign="top" style="padding-bottom: 24px; padding-left: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                                <p><strong>Tanggal | Waktu</strong></p>
                                <p> ' . $dateNow . ' | ' . $timeNow . ' </p>
                              </td>
                            </tr>
                          </table>
                        </div>
                        <div style="display: inline-block; width: 100%; max-width: 100%;vertical-align: top;">
                          <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;">
                            <tr>
                              <td align="left" bgcolor="#ffffff" style="padding: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                                <p style="margin: 0;">Berikut ini ringkasan pesanan terakhir Anda. Jika Anda memiliki pertanyaan atau masalah tentang pesanan Anda, silakan  <a href="https://ur-hub.com/contact-us/">hubungi kami.</a></p>
                              </td>
                            </tr>
                          </table>
                        </div>
                        <div  style="display: inline-block; width: 100%; max-width: 50%;vertical-align: top;">
                          <table  align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 350px;">
                            <tr>
                              <td bgcolor="#fff" align="left" valign="top" style="padding-bottom: 24px; padding-left: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 15px;">
                                <h4><strong>Detail Pesanan</strong></h4>
                                <subtitle>Diterbitkan Untuk</subtitle>
                                <p><strong>' . $name . '</strong></p>
                                <p>Diterbitkan Oleh</p>
                                <p><strong>' . $partnerName . '</strong></p>
                                <p>Jenis Transaksi</p>
                                <p><strong>Dine In</strong></p>
                                <p>No. Meja</p>
                                <p><strong>' . $no_meja . '</strong></p>
                              </td>
                            </tr>
                          </table>
                        </div>
                        <!--[if (gte mso 9)|(IE)]>
                        </td>
                        <td align="left" valign="top" width="300">
                        <![endif]-->
                        <div style="display: inline-block; width: 100%; max-width: 50%;; vertical-align: top;">
                          <table bgcolor="#fff" align="left" border="0" cellpadding="0" cellspacing="0" width="95%" style="max-width: 250px;">
                            <tr>
                              <td align="left" valign="top" style=" padding-left: 6px 12px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 15px;"><h4><strong>Detail Tagihan</strong></h4></td>
                            </tr>
                            <tr>
                              <td align="left" bgcolor="#ffffff" style=" font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                                <table border="0" cellpadding="0" cellspacing="0" width="50%" style =" border-left: 1px solid #1FB0E6; border-right:  1px solid #1FB0E6;">
                                  <tr>
                                    <td align="left" bgcolor="#1FB0E6" width="75%"  style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><strong>Pesanan Id</strong></td>
                                    <td align="left" bgcolor="#1FB0E6" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><strong>' . $id . '</strong></td>
                                  </tr>
                                  ';
      foreach ($detailTrans as $x => $val) {
        $totalHarga += $val['harga'];
        $promo = $val['promo'];
        $hargas = rupiah($val['harga']);
        $html .= '
                                    <tr>
                                      <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">' . $val['nama'] . ' X ' . $val['qty'] . '</td>
                                      <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">' . $hargas . '</td>
                                    </tr>

                                  ';
      }

      $serviceTot2 = ceil(($service / 100) * ($totalHarga - $promo));
      $afterservice2 = ceil(($totalHarga - $promo) + $serviceTot2 + $charge_ur);
      $taxTot2 = ceil(($tax / 100) * $afterservice2);
      $afterTax2 = ceil($taxTot2 + $afterservice2);
      $subtotal2 = ceil($afterTax2);


      // $serviceTot= (($service/100)*($totalHarga-$promo));
      // $taxTot=(($tax/100)*($totalHarga+$charge_ur+($service/100)*($totalHarga)));
      // $subtotal = (((($service/100)*($totalHarga-$promo+$charge_ur)))+(($tax/100)*(($service/100)*($totalHarga-$promo+$charge_ur)))+($totalHarga-$promo+$charge_ur));

      $strTot = rupiah($totalHarga - $promo);
      $strTax = rupiah($taxTot2);
      $strServ = rupiah($serviceTot2);
      $strSUbtot = rupiah($subtotal2 - $point);
      $html .= '
                                  <tr>
                                    <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Sub Total</td>
                                    <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">' . $strTot . '</td>
                                  </tr>
                                  <tr>
                                    <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Service</td>
                                    <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">' . $strServ . '</td>
                                  </tr>
                                  <tr>
                                    <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Biaya Layanan</td>
                                    <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">' . $strChargeUR . '</td>
                                  </tr>
                                  <tr>
                                    <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Tax</td>
                                    <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">' . $strTax . '</td>
                                  </tr>
                                  <tr>
                                    <td align="left" width="75%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">Point Pay</td>
                                    <td align="left" width="25%" style="padding: 6px 12px;font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">' . $strPoint . '</td>
                                  </tr>
                                  <tr>
                                    <td align="left" width="75%" style="padding: 12px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; border-top: 2px dashed #1FB0E6; border-bottom: 2px dashed #1FB0E6;"><strong>Total</strong></td>
                                    <td align="left" width="25%" style="padding: 12px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; border-top: 2px dashed #1FB0E6; border-bottom: 2px dashed #1FB0E6;"><strong>' . $strSUbtot . '</strong></td>
                                  </tr>
                                  <tr>
                                    <td align="left" width="75%" style="padding: 12px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; border-bottom: 1px solid #1FB0E6;"><strong>Jenis Pembayaran</strong></td>
                                    <td align="left" width="25%" style="padding: 12px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; border-bottom: 1px solid #1FB0E6;"><strong>' . $type . '</strong></td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                          </table>
                        </div>
                        <div style="display: inline-block; width: 100%; max-width: 100%;  vertical-align: top;">
                          <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:700px;">
                            <tr>
                              <td align="left" bgcolor="#fff" style="padding: 0px 24px 24px 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">

                              </td>
                            </tr>
                          </table>
                        </div>
                        <!--[if (gte mso 9)|(IE)]>
                        </td>
                        </tr>
                        </table>
                        <![endif]-->
                      </td>
                    </tr>
                  </table>
                  <!--[if (gte mso 9)|(IE)]>
                  </td>
                  </tr>
                  </table>
                  <![endif]-->
                </td>
              </tr>
              <!-- end receipt address block -->


              <!--[if (gte mso 9)|(IE)]>
              </td>
              </tr>
              </table>
              <![endif]-->
            </td>
          </tr>
          <!-- end copy block -->

          <!-- start footer -->
          <tr>
      <td align="center" bgcolor="#f2f4f6">
        <!--[if (gte mso 9)|(IE)]>
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
        <tr>
        <td align="center" valign="top" width="600">
        <![endif]-->
        <table align="center"border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;">
          <tr >
            <td align="center" valign="top" style="font-size: 0;">
              <!-- <p style="margin: 0;">To stop receiving these emails, you can <a href="https://sendgrid.com" target="_blank">unsubscribe</a> at any time.</p> -->
              <div style="display: inline-block; width: 100%; max-width: 50%;  vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250;">
                  <tr>
                    <td align="left" border="1" valign="top" style="padding-bottom: 24px; padding-left: 0px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                      <!-- <p><strong></strong></p> -->
                      <p>Copyright 2020 © PT. Rahmat Tuhan Lestari</p>
                      <p>Hak Cipta Dilindungi Undang-undang</p>
                    </td>
                  </tr>
                </table>
              </div>
              <!--[if (gte mso 9)|(IE)]>
              </td>
              <td align="left" valign="top" width="300">
              <![endif]-->
              <div style="display: inline-block; width: 100%; max-width: 50%; vertical-align: top;">
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250px;">
                  <tr>
                    <td align="left" valign="top" style="padding-bottom: 24px; padding-left: 24px; font-family: Source Sans Pro, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                      <p><strong>Ketahui Promo terbaru UR</strong></p>
                      <a href="https://www.facebook.com/UR-Easy-Quick-Order-101245531496672/" >
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/fb.png" alt="UR Facebook" border="0" width="20px" style="width: 20px; max-width: 20px; min-width: 20px; padding-right: 10px;">
                      </a>
                      <a href="https://www.instagram.com/ur.hub/" >
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/ig.png" alt="UR Instagram" border="0" width="20px" style="width: 20px; max-width: 20px; min-width: 20px; padding-right: 10px;">
                      </a>
                      <a href="https://id.linkedin.com/company/ur-hub?trk=public_profile_topcard_current_company" >
                        <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/linkedin.png" alt="UR Linkedin" border="0" width="20px" style="width: 20px; max-width: 20px; min-width: 20px;">
                      </a>
                    </td>
                  </tr>
                </table>
              </div>
            </td>
          </tr>
          <!-- end unsubscribe -->

        </table>
        <!--[if (gte mso 9)|(IE)]>
        </td>
        </tr>
        </table>
        <![endif]-->
      </td>
    </tr>
    <!-- end footer -->

        </table>
        <!-- end body -->

      </body>
      </html>';
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        'Receipt ' . $partnerName . ' UR',
        $html
      )) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }
  
  public function mailAddMaster($email, $name, $partnerName, $partnerID)
  {
    $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    
         
    $query = "SELECT template FROM `email_template` WHERE id=5";
    $templateQ = mysqli_query($db_conn, $query);
    
    if (mysqli_num_rows($templateQ) > 0) {
        $templates = mysqli_fetch_all($templateQ, MYSQLI_ASSOC);
        $template = $templates[0]['template'];
        
        $template = str_replace('$partnername',$partnerName,$template);
        $template = str_replace('$name',$name,$template);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $insertPendingEmail = mysqli_query($db_conn, "INSERT INTO `pending_email`(`email`, `partner_id`, `subject`, `body`, `created_at`) VALUES ('$email', '$partnerID', 'UR | Registration', '$template', NOW())");
            if($insertPendingEmail) {
                return "SUCCESS";
            } else {
                return "Email tidak terkirim";
            }
        } else {
            return "Email tidak valid";
        }
        
    } else {
        return "Template tidak ditemukan";
    }
  }

  public function mailBeliPartner($id)
  {
    $stmt = $this->conn->prepare("SELECT id,slot,duration_month,amount FROM subscribe_history WHERE phone = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
      $stmt->bind_result($id, $slot, $duration_month, $amount);
      $stmt->fetch();
      function rupiah($angka)
      {
        $hasil_rupiah = "Rp. " . number_format($angka, 0, ',', '.');
        return $hasil_rupiah;
      }
      $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
              <html xmlns="http://www.w3.org/1999/xhtml">

              <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title>A Simple Responsive HTML Email</title>
                <style type="text/css">
                body {margin: 0; padding: 0; min-width: 100%!important;}
                img {height: auto;}
                .content {width: 100%; max-width: 600px;}
                .header {padding: 40px 30px 20px 30px;}
                .innerpadding {padding: 30px 30px 30px 30px;}
                .borderbottom {border-bottom: 1px solid #f2eeed;}
                .subhead {font-size: 15px; color: #ffffff; font-family: sans-serif; letter-spacing: 10px;}
                .h1, .h2, .bodycopy {color: #153643; font-family: sans-serif;}
                .h1 {font-size: 33px; line-height: 38px; font-weight: bold;}
                .h2 {padding: 0 0 15px 0; font-size: 24px; line-height: 28px; font-weight: bold;}
                .bodycopy {font-size: 16px; line-height: 22px;}
                .button {text-align: center; font-size: 18px; font-family: sans-serif; font-weight: bold; padding: 0 30px 0 30px;}
                .button a {color: #ffffff; text-decoration: none;}
                .footer {padding: 20px 30px 15px 30px;}
                .footercopy {font-family: sans-serif; font-size: 14px; color: #ffffff;}
                .footercopy a {color: #ffffff; text-decoration: underline;}

                @media only screen and (max-width: 550px), screen and (max-device-width: 550px) {
                body[yahoo] .hide {display: none!important;}
                body[yahoo] .buttonwrapper {background-color: transparent!important;}
                body[yahoo] .button {padding: 0px!important;}
                body[yahoo] .button a {background-color: #e05443; padding: 15px 15px 13px!important;}
                body[yahoo] .unsubscribe {display: block; margin-top: 20px; padding: 10px 50px; background: #2f3942; border-radius: 5px; text-decoration: none!important; font-weight: bold;}
                }

                /*@media only screen and (min-device-width: 601px) {
                  .content {width: 600px !important;}
                  .col425 {width: 425px!important;}
                  .col380 {width: 380px!important;}
                  }*/

                </style>
              </head>

              <body yahoo bgcolor="#f6f8f1">
              <table width="100%" bgcolor="#f6f8f1" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td>
                  <!--[if (gte mso 9)|(IE)]>
                    <table width="600" align="center" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td>
                  <![endif]-->
                  <table bgcolor="#ffffff" class="content" align="center" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td bgcolor="#F48FB1" class="header">
                        <table width="70" align="left" border="0" cellpadding="0" cellspacing="0">
                          <tr>
                            <td height="70" style="padding: 0 20px 20px 0;">
                              <img class="fix" src="https://ur-hub.s3.us-west-2.amazonaws.com/assets/logo/logo.png" width="70" height="70" border="0" alt="" />
                            </td>
                          </tr>
                        </table>
                        <!--[if (gte mso 9)|(IE)]>
                          <table width="425" align="left" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                              <td>
                        <![endif]-->
                        <table class="col425" align="left" border="0" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 425px;">
                          <tr>
                            <td height="70">
                              <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                  <td class="subhead" style="padding: 0 0 0 3px;">
                                    UR Master
                                  </td>
                                </tr>
                                <tr>
                                  <td class="h1" style="padding: 5px 0 0 0;">
                                      Terima kasih sudah memudahkan usahamu dengan UR
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                        </table>
                        <!--[if (gte mso 9)|(IE)]>
                              </td>
                            </tr>
                        </table>
                        <![endif]-->
                      </td>
                    </tr>
                    <tr>
                      <td class="innerpadding borderbottom">
                        <table width="115" align="left" border="0" cellpadding="0" cellspacing="0">
                          <tr>
                            <td height="115" style="padding: 0 20px 20px 0;">
                              <img class="fix" src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/210284/article1.png" width="115" height="115" border="0" alt="" />
                            </td>
                          </tr>
                        </table>
                        <!--[if (gte mso 9)|(IE)]>
                          <table width="380" align="left" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                              <td>
                        <![endif]-->
                        <table class="col380" align="left" border="0" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 380px;">
                          <tr>
                            <td>
                              <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                  <td class="bodycopy">
                                  Selamat, Anda telah berhasil berlangganan di ur dengan detail:
                                  <table>
                                      <tr>
                                          <td width="75%">ID Transaksi</td>
                                          <td>:</td>
                                          <td><b>' . $id . '</b></td>
                                      </tr>
                                      <tr>
                                          <td>Banyak Partner</td>
                                          <td>:</td>
                                          <td>' . $slot . '</td>
                                      </tr>
                                      <tr>
                                          <td>Durasi</td>
                                          <td>:</td>
                                          <td>' . $duration_month . ' bulan</td>
                                      </tr>
                                      <tr>
                                          <td>Biaya</td>
                                          <td>:</td>
                                          <td>' . rupiah($amount) . '</td>
                                      </tr>
                                      </table>
                                  </td>
                                </tr>
                                <tr>
                                  <td style="padding: 20px 0 0 0;">
                                    <table class="buttonwrapper" bgcolor="#DE148C" border="0" cellspacing="0" cellpadding="0">
                                      <tr>
                                        <td class="button" height="45">
                                          <a href="https://master.ur-hub.com/signinMaster">Masuk sebagai Master</a>
                                        </td>
                                      </tr>
                                    </table>
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                        </table>
                        <!--[if (gte mso 9)|(IE)]>
                              </td>
                            </tr>
                        </table>
                        <![endif]-->
                      </td>
                    </tr>
                    <tr >
                      <td align="center" valign="top" style="font-size: 0;">
                        <!-- <p style="margin: 0;">To stop receiving these emails, you can <a href="https://sendgrid.com" target="_blank">unsubscribe</a> at any time.</p> -->
                        <div style="display: inline-block; width: 100%; max-width: 50%;  vertical-align: top;">
                          <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250;">
                            <tr>
                              <td align="left" border="1" valign="top" style="padding-bottom: 24px; padding-left: 0px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                                <!-- <p><strong></strong></p> -->
                                <p>Copyright 2020 © PT. Rahmat Tuhan Lestari</p>
                                <p>Hak Cipta Dilindungi Undang-undang</p>
                              </td>
                            </tr>
                          </table>
                        </div>
                        <!--[if (gte mso 9)|(IE)]>
                        </td>
                        <td align="left" valign="top" width="300">
                        <![endif]-->
                        <div style="display: inline-block; width: 100%; max-width: 50%; vertical-align: top;">
                          <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 250px;">
                            <tr>
                              <td align="left" valign="top" style="padding-bottom: 24px; padding-left: 24px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                                <p><strong>Ketahui Promo terbaru ur</strong></p>
                                <a href="https://www.facebook.com/UR-Easy-Quick-Order-101245531496672/" >
                                  <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/fb.png" alt="UR Facebook" border="0" width="20px" style="width: 20px; max-width: 20px; min-width: 20px; padding-right: 10px;">
                                </a>
                                <a href="https://www.instagram.com/ur.hub/" >
                                  <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/ig.png" alt="UR Instagram" border="0" width="20px" style="width: 20px; max-width: 20px; min-width: 20px; padding-right: 10px;">
                                </a>
                                <a href="https://id.linkedin.com/company/ur-hub?trk=public_profile_topcard_current_company" >
                                  <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/linkedin.png" alt="UR Linkedin" border="0" width="20px" style="width: 20px; max-width: 20px; min-width: 20px;">
                                </a>
                              </td>
                            </tr>
                          </table>
                        </div>
                      </td>
                    </tr>
                  </table>
                  <!--[if (gte mso 9)|(IE)]>
                        </td>
                      </tr>
                  </table>
                  <![endif]-->
                  </td>
                </tr>
              </table>
              </body>
              </html>';
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        'Selamat Bergabung',
        $html
      )) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }

  public function mailingPerDayPartner($email)
  {
    $stmt = $this->conn->prepare("SELECT id,name, phone,email,jam_buka,jam_tutup,tax,service FROM partner WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($id, $name, $phone, $email, $jamBuka, $jamTutup, $tax, $service);
      $stmt->fetch();

      $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
      function rupiah($angka)
      {
        $angka = ceil($angka);
        $hasil_rupiah = "Rp. " . number_format($angka, 0, ',', '.');
        return $hasil_rupiah;
      }
      $dateNow = date('d/M/Y');
      $dateNowDb = date('Y-m-d');
      // $first_day_last_month = date('01/M/Y', strtotime('-1 month'));
      // $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
      // $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
      // $dateLastDb = date('Y-m-t', strtotime('-1 month'));
      $menu = mysqli_query($db_conn, "SELECT * FROM menu WHERE id_partner='$id';");
      $transaksi = mysqli_query($db_conn, "SELECT total,promo,tax,service,status,tipe_bayar,charge_ewallet,charge_ur FROM transaksi  WHERE id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam)='$dateNowDb' AND TIME(jam) BETWEEN '$jamBuka' AND '$jamTutup'");
      $total = 0;
      $promo = 0;
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $charge_ewallet = 0;
      $taxtype = 0;
      $sumCharge_ur = 0;
      while ($row = mysqli_fetch_assoc($transaksi)) {
        // if($row['tipe_bayar']=='5'|| $row['tipe_bayar']=='7' || $row['tipe_bayar']==5 || $row['tipe_bayar']==7){
        //   $total += ($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)));
        //   $promo += $row['promo'];
        // }else{
        //   $total += ($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))-ceil(ceil(($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))*($row['charge_ewallet']/100))+(ceil(ceil(($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))*($row['charge_ewallet']/100))*($row['tax']/100))));

        // }
        // $promo += $row['promo'];
        $sumCharge_ur += $row['charge_ur'];
        $charge_ewallet = $row['charge_ewallet'];
        $taxtype = $row['tax'];
        $servicetype = $row['service'];
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $withTax = 0;
        if ($row['tipe_bayar'] == 1 || $row['tipe_bayar'] == '1') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $ovo += $withTax;
        } else if ($row['tipe_bayar'] == 2 || $row['tipe_bayar'] == '2') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $gopay += $withTax;
        } else if ($row['tipe_bayar'] == 3 || $row['tipe_bayar'] == '3') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $dana += $withTax;
        } else if ($row['tipe_bayar'] == 4 || $row['tipe_bayar'] == '4') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $linkaja += $withTax;
        } else if ($row['tipe_bayar'] == 5 || $row['tipe_bayar'] == '5') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($row['tipe_bayar'] == 6 || $row['tipe_bayar'] == '6') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $sakuku += $withTax;
        } else if ($row['tipe_bayar'] == 7 || $row['tipe_bayar'] == '7') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($row['tipe_bayar'] == 8 || $row['tipe_bayar'] == '8') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
      }
      $hargaPokok = 0;
      $menu2 = mysqli_query($db_conn, "SELECT * FROM menu WHERE id_partner='$id';");
      while ($rowcheck1 = mysqli_fetch_assoc($menu2)) {
        $idMenuCheck = $rowcheck1['id'];
        $hargaPokokAwal = $rowcheck1['hpp'];
        $detailcheck = mysqli_query($db_conn, "SELECT SUM(qty) AS qtytotal FROM detail_transaksi join transaksi ON detail_transaksi.id_transaksi = transaksi.id WHERE id_menu='$idMenuCheck' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam)='$dateNowDb' AND TIME(jam) BETWEEN '$jamBuka' AND '$jamTutup';");
        while ($rowpph = mysqli_fetch_assoc($detailcheck)) {
          $qtyJual = $rowpph['qtytotal'];
        }
        $hargaPokok += $hargaPokokAwal * $qtyJual;
      }

      // $subtotal = (($total + ($total * 0.1)) - ($hargaPokok + $promo)) - ($total * 0.1);
      $subtotal = (($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) - ceil($sumCharge_ur);
      // if($tax==1){
      //   $subtotal = ($total + ($total * 0.1));
      // }
      // if($service!=0){
      //   $subtotal = ($total + ($total * ($service/100)));
      // }
      $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
              <head>
                <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                <meta name="x-apple-disable-message-reformatting" />
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <title></title>
                <style type="text/css" rel="stylesheet" media="all">

                @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                body {
                  width: 100% !important;
                  height: 100%;
                  margin: 0;
                  -webkit-text-size-adjust: none;
                }

                a {
                  color: #3869D4;
                }

                a img {
                  border: none;
                }

                td {
                  word-break: break-word;
                }

                .preheader {
                  display: none !important;
                  visibility: hidden;
                  mso-hide: all;
                  font-size: 1px;
                  line-height: 1px;
                  max-height: 0;
                  max-width: 0;
                  opacity: 0;
                  overflow: hidden;
                }

                body,
                td,
                th {
                  font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                }

                h1 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 22px;
                  font-weight: bold;
                  text-align: left;
                }

                h2 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 16px;
                  font-weight: bold;
                  text-align: left;
                }

                h3 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 14px;
                  font-weight: bold;
                  text-align: left;
                }

                td,
                th {
                  font-size: 16px;
                }

                p,
                ul,
                ol,
                blockquote {
                  margin: .4em 0 1.1875em;
                  font-size: 16px;
                  line-height: 1.625;
                }

                p.sub {
                  font-size: 13px;
                }

                .align-right {
                  text-align: right;
                }

                .align-left {
                  text-align: left;
                }

                .align-center {
                  text-align: center;
                }
                .button {
                  background-color: #3869D4;
                  border-top: 10px solid #3869D4;
                  border-right: 18px solid #3869D4;
                  border-bottom: 10px solid #3869D4;
                  border-left: 18px solid #3869D4;
                  display: inline-block;
                  text-decoration: none;
                  border-radius: 3px;
                  box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                  -webkit-text-size-adjust: none;
                  box-sizing: border-box;
                }

                .button--green {
                  background-color: #22BC66;
                  border-top: 10px solid #22BC66;
                  border-right: 18px solid #22BC66;
                  border-bottom: 10px solid #22BC66;
                  border-left: 18px solid #22BC66;
                }

                .button--red {
                  background-color: #FF6136;
                  border-top: 10px solid #FF6136;
                  border-right: 18px solid #FF6136;
                  border-bottom: 10px solid #FF6136;
                  border-left: 18px solid #FF6136;
                }

                @media only screen and (max-width: 500px) {
                  .button {
                    width: 100% !important;
                    text-align: center !important;
                  }
                }

                .attributes {
                  margin: 0 0 21px;
                }

                .attributes_content {
                  background-color: #F4F4F7;
                  padding: 16px;
                }

                .attributes_item {
                  padding: 0;
                }

                .related {
                  width: 100%;
                  margin: 0;
                  padding: 25px 0 0 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .related_item {
                  padding: 10px 0;
                  color: #CBCCCF;
                  font-size: 15px;
                  line-height: 18px;
                }

                .related_item-title {
                  display: block;
                  margin: .5em 0 0;
                }

                .related_item-thumb {
                  display: block;
                  padding-bottom: 10px;
                }

                .related_heading {
                  border-top: 1px solid #CBCCCF;
                  text-align: center;
                  padding: 25px 0 10px;
                }

                .discount {
                  width: 100%;
                  margin: 0;
                  padding: 24px;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #F4F4F7;
                  border: 2px dashed #CBCCCF;
                }

                .discount_heading {
                  text-align: center;
                }

                .discount_body {
                  text-align: center;
                  font-size: 15px;
                }

                .social {
                  width: auto;
                }

                .social td {
                  padding: 0;
                  width: auto;
                }

                .social_icon {
                  height: 20px;
                  margin: 0 8px 10px 8px;
                  padding: 0;
                }

                .purchase {
                  width: 100%;
                  margin: 0;
                  padding: 35px 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .purchase_content {
                  width: 100%;
                  margin: 0;
                  padding: 25px 0 0 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .purchase_item {
                  padding: 10px 0;
                  color: #51545E;
                  font-size: 15px;
                  line-height: 18px;
                }

                .purchase_heading {
                  padding-bottom: 8px;
                  border-bottom: 1px solid #EAEAEC;
                }

                .purchase_heading p {
                  margin: 0;
                  color: #85878E;
                  font-size: 12px;
                }

                .purchase_footer {
                  padding-top: 15px;
                  border-top: 1px solid #EAEAEC;
                }

                .purchase_total {
                  margin: 0;
                  text-align: right;
                  font-weight: bold;
                  color: #333333;
                }

                .purchase_total--label {
                  padding: 0 15px 0 0;
                }

                body {
                  background-color: #F4F4F7;
                  color: #51545E;
                }

                p {
                  color: #51545E;
                }

                p.sub {
                  color: #6B6E76;
                }

                .email-wrapper {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #F4F4F7;
                }

                .email-content {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }


                .email-masthead {
                  padding: 25px 0;
                  text-align: center;
                }

                .email-masthead_logo {
                  width: 94px;
                }

                .email-masthead_name {
                  font-size: 16px;
                  font-weight: bold;
                  color: #A8AAAF;
                  text-decoration: none;
                  text-shadow: 0 1px 0 white;
                }
                .email-body {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #FFFFFF;
                }

                .email-body_inner {
                  width: 570px;
                  margin: 0 auto;
                  padding: 0;
                  -premailer-width: 570px;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #FFFFFF;
                }

                .email-footer {
                  width: 570px;
                  margin: 0 auto;
                  padding: 0;
                  -premailer-width: 570px;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  text-align: center;
                }

                .email-footer p {
                  color: #6B6E76;
                }

                .body-action {
                  width: 100%;
                  margin: 30px auto;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  text-align: center;
                }

                .body-sub {
                  margin-top: 25px;
                  padding-top: 25px;
                  border-top: 1px solid #EAEAEC;
                }

                .content-cell {
                  padding: 35px;
                }

                @media only screen and (max-width: 600px) {
                  .email-body_inner,
                  .email-footer {
                    width: 100% !important;
                  }
                }

                @media (prefers-color-scheme: dark) {
                  body,
                  .email-body,
                  .email-body_inner,
                  .email-content,
                  .email-wrapper,
                  .email-masthead,
                  .email-footer {
                    background-color: #333333 !important;
                    color: #FFF !important;
                  }
                  p,
                  ul,
                  ol,
                  blockquote,
                  h1,
                  h2,
                  h3 {
                    color: #FFF !important;
                  }
                  .attributes_content,
                  .discount {
                    background-color: #222 !important;
                  }
                  .email-masthead_name {
                    text-shadow: none !important;
                  }
                }
                </style>
              </head>
              <body>
                <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                  <tr>
                    <td align="center">
                      <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <!-- <tr>
                          <td class="email-masthead">
                            <a href="https://example.com" class="f-fallback email-masthead_name">
                            UR HUB
                          </a>
                          </td>
                        </tr> -->

                        <tr>
                          <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell">
                                <div class="align-center"><img  src="https://ur-hub.s3.us-west-2.amazonaws.com/assets/logo/logo.png"></div>
                                    <h3 class="align-right">Tanggal:' . $dateNow . '</h3>
                                  <div class="f-fallback">
                                    <h1>Hi ' . $name . ',</h1>

                                    <table class="discount" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                            <p>Pendapatan Hari Ini</p>
                                          <h1 class="f-fallback discount_heading">' . rupiah($subtotal) . '</h1>
                                        </td>
                                      </tr>
                                    </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                          <td>
                                            <h3>Rincian Pendapatan</h3></td>
                                          <td>
                                        </tr>
                                        <tr>
                                          <td colspan="2">

                                            <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                            ';
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $sumCharge_ur = 0;
      $tipe_bayar = mysqli_query($db_conn, "SELECT total, promo ,tax,service,status,tipe_bayar,charge_ewallet,charge_ur FROM transaksi WHERE id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam)='$dateNowDb' AND TIME(jam) BETWEEN '$jamBuka' AND '$jamTutup'");
      while ($rowtypeBayar = mysqli_fetch_assoc($tipe_bayar)) {
        // $totalType += $rowtypeBayar['total'];
        // $subtotal += $total;
        // if($tax==1){
        //   // $subtotal = ($total + ($total * 0.1));
        //   $totalType = ($rowtypeBayar['tottotal']+ ($rowtypeBayar['tottotal'] * 0.1));
        // }
        // if($service!=0){
        //   // $subtotal = ($total + ($total * ($service/100)));
        //   $totalType = ($rowtypeBayar['tottotal']+($rowtypeBayar['tottotal']*($service/100)));
        // }
        $sumCharge_ur += $rowtypeBayar['charge_ur'];
        $charge_ewallet = $rowtypeBayar['charge_ewallet'];
        $taxtype = $rowtypeBayar['tax'];
        $servicetype = $rowtypeBayar['service'];
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $withTax = 0;
        if ($rowtypeBayar['tipe_bayar'] == 1 || $rowtypeBayar['tipe_bayar'] == '1') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $ovo += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 2 || $rowtypeBayar['tipe_bayar'] == '2') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $gopay += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 3 || $rowtypeBayar['tipe_bayar'] == '3') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $dana += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 4 || $rowtypeBayar['tipe_bayar'] == '4') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $linkaja += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 5 || $rowtypeBayar['tipe_bayar'] == '5') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 6 || $rowtypeBayar['tipe_bayar'] == '6') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $sakuku += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 7 || $rowtypeBayar['tipe_bayar'] == '7') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 8 || $rowtypeBayar['tipe_bayar'] == '8') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
        // if($rowtypeBayar['type_bayar']=='5'|| $rowtypeBayar['type_bayar']=='7' || $rowtypeBayar['type_bayar']==5 || $rowtypeBayar['type_bayar']==7){
        //   $totalType += ($rowtypeBayar['total']+($rowtypeBayar['total']*($rowtypeBayar['tax']/100))+($rowtypeBayar['total']*($rowtypeBayar['service']/100)));
        //   $promoType += $rowtypeBayar['promo'];
        // }else{
        //   $totalType+= ($rowtypeBayar['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))+(($row['total']*($row['charge_ewallet']/100))*($row['tax']/100));
        //   $promoType += $rowtypeBayar['promo'];
        // }
        // $typeCode = $rowtypeBayar['tipe_bayar'];
        // switch ($typeCode) {
        //   case 1:
        //     $type = 'OVO';
        //     break;
        //   case 2:
        //     $type = 'GOPAY';
        //     break;
        //   case 3:
        //     $type = 'DANA';
        //     break;
        //   case 4:
        //     $type = 'T-CASH';
        //     break;
        //   case 5:
        //     $type = 'TUNAI/DEBIT';
        //     break;
        //   case 6:
        //     $type = 'SAKUKU';
        //     break;
        //   case 7:
        //     $type = 'CREDIT CARD';
        //     break;
        // }

      }

      //   <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">LINK AJA</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.rupiah($linkaja).'</span></td>
      // </tr>
      // <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">SAKUKU</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.rupiah($sakuku).'</span></td>
      // </tr>

      $html .= '<tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">E-wallet</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                </tr>
                <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">OVO</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($ovo) . '</span></td>
                </tr>
                <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">GOPAY</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($gopay) . '</span></td>
                </tr>
                  <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($dana) . '</span></td>
                </tr>
                  <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">LinkAja</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($linkaja) . '</span></td>
                </tr>
                  <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($sakuku) . '</span></td>
                </tr>
                <tr>
                <td width="80%" class="purchase_item"><span class="f-fallback">Charge E-Wallet (' . $charge_ewallet . '% + ' . $taxtype . '%)</span></td>
                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah(ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) . '</span></td>
              </tr>
              <tr>
              <td width="80%" class="purchase_item"><span class="f-fallback">Total E-Wallet</span></td>
              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah(($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100)))) . '</span></td>
            </tr>
                ';
      $html .= '
            <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                </tr>
            <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">Non E-wallet</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                </tr>
                <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">CASH</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($tunaiDebit) . '</span></td>
                </tr>
                <tr>
                <td width="80%" class="purchase_item"><span class="f-fallback">CREDIT CARD</span></td>
                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($creditCard) . '</span></td>
              </tr>
              <tr>
              <td width="80%" class="purchase_item"><span class="f-fallback">DEBIT CARD</span></td>
              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($debitCard) . '</span></td>
            </tr>
              <tr>
              <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
            </tr>
            <tr>
            <td width="80%" class="purchase_item"><span class="f-fallback">SUBTOTAL</span></td>
            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah(($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) . '</span></td>
          </tr>
          <tr>
          <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
          <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
        </tr>
        <tr>
        <td width="80%" class="purchase_item"><span class="f-fallback">Convenience Fee</span></td>
        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah($sumCharge_ur) . '</span></td>
      </tr>
      <tr>
      <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
    </tr>
    <tr>
        <td width="80%" class="purchase_item"><span class="f-fallback">TOTAL</span></td>
        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah((($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) - ceil($sumCharge_ur)) . '</span></td>
      </tr>
                ';


      $html .= '</table>
                                          </td>
                                        </tr>
                                      </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td>
                                          <h3>Menu Terlaris</h3></td>
                                        <td>
                                      </tr>
                                      <tr>
                                        <td colspan="2">

                                          <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                              <th class="purchase_heading" align="left">
                                                <p class="f-fallback">Menu</p>
                                              </th>
                                              <th class="purchase_heading" align="right">
                                                <p class="f-fallback">Amount</p>
                                              </th>
                                            </tr>';
      $fav = mysqli_query($db_conn, "SELECT menu.nama,SUM(detail_transaksi.qty) AS qty FROM menu join detail_transaksi on menu.id=detail_transaksi.id_menu join transaksi on detail_transaksi.id_transaksi = transaksi.id JOIN partner ON transaksi.id_partner = partner.id WHERE partner.id= '$id' AND transaksi.status<=2 AND transaksi.status>=1 AND DATE(jam)='$dateNowDb' AND TIME(transaksi.jam) BETWEEN '$jamBuka' AND '$jamTutup' GROUP BY menu.nama ORDER BY qty DESC LIMIT 5");
      while ($rowMenu = mysqli_fetch_assoc($fav)) {
        $namaMenu = $rowMenu['nama'];
        $qtyMenu = $rowMenu['qty'];
        $html .= '<tr>
                                              <td width="80%" class="purchase_item"><span class="f-fallback">' . $namaMenu . '</span></td>
                                              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">x ' . $qtyMenu . '</span></td>
                                            </tr>';
      }
      $html .= '</table>
                                        </td>
                                      </tr>
                                    </table>

                                    <p>Hormat Kami,
                                      <br>UR - Easy & Quick Order</p>

                                    <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                          <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                            <tr>
                                              <td align="center">
                                                <a href="https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdfPerDay.php?id=' . md5($id) . '" class="f-fallback button button--blue" target="_blank" style = "color:#fff">Download Full PDF</a>
                                              </td>
                                            </tr>
                                          </table>
                                        </td>
                                      </tr>
                                    </table>
                                    <!-- Sub copy -->
                                    <table class="body-sub" role="presentation">
                                      <tr>
                                        <td>
                                        <p class="f-fallback sub">Need a printable copy for your records?</strong> You can <a href="https://apis.ur-hub.com/qr/v2/csv/xlsPerDay.php?id=' . md5($id) . '">download a Xls version</a>.</p>
                                        </td>
                                      </tr>
                                    </table>
                                  </div>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell" align="center">
                                  <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                  <p class="f-fallback sub align-center">
                                    PT. Rahmat Tuhan Lestari
                                    <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir, Kota Bandung
                                    <br>Jawa Barat 40221
                                  </p>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
              </body>
            </html>';
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Laporan Keuangan",
        // "<div>Hai, " . $name . " </div>
        // // <>
        // <br>
        // <br>Laporan Keuangan
        // <br>
        // <br><a href='https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdf.php?id=" . md5(id) . "'>click here</a>
        // <br>
        // <br>
        // Jika anda merasa tidak melakukan request silahkan abaikan pesan ini.
        // <br>
        // <br>
        // Hormat Kami,
        // <br><br>
        // Ur Hub."
        $html
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }

  public function mailingPenagihanPartner($email)
  {
    $stmt = $this->conn->prepare("SELECT id,name,phone,tax,service,email,saldo FROM partner WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($id, $name, $phone, $tax, $service, $email, $saldo);
      $stmt->fetch();
      function tgl_indo($tanggal)
      {
        $bulan = array(
          1 =>   'Januari',
          'Februari',
          'Maret',
          'April',
          'Mei',
          'Juni',
          'Juli',
          'Agustus',
          'September',
          'Oktober',
          'November',
          'Desember'
        );
        return $bulan[(int)$tanggal];
      }
      $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
      function rupiah($angka)
      {

        $hasil_rupiah = "Rp. " . number_format($angka, 0, ',', '.');
        return $hasil_rupiah;
      }
      $dateNow = date('d/M/Y');
      $first_day_last_month = date('01/M/Y', strtotime('-1 month'));
      $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
      $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
      $dateLastDb = date('Y-m-t', strtotime('-1 month'));
      // $first_day_last_month = date('01/M/Y');
      // $last_day_last_month  = date('t/M/Y');
      // $dateFirstDb = date('Y-m-01');
      $menu = mysqli_query($db_conn, "SELECT * FROM menu WHERE id_partner='$id';");
      $transaksi = mysqli_query($db_conn, "SELECT charge_ur FROM transaksi WHERE id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'");
      $tagihan = mysqli_query($db_conn, "SELECT qr_string FROM tagihan_ur WHERE id_partner='$id' ORDER BY id DESC LIMIT 1");
      $sumCharge_ur = 0;
      while ($row = mysqli_fetch_assoc($transaksi)) {
        $sumCharge_ur += $row['charge_ur'];
      }
      while ($row2 = mysqli_fetch_assoc($tagihan)) {
        $qr_tagihan = $row2['qr_string'];
      }
      $total_penagihan = 0;
      $total_penagihan = $saldo - $sumCharge_ur;
      if ($total_penagihan >= 0) {
        $total_penagihan = $total_penagihan;
      } else {
        $total_penagihan = 0;
      }

      $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
      <html>
        <head>
          <meta name="viewport" content="width=device-width, initial-scale=1.0" />
          <meta name="x-apple-disable-message-reformatting" />
          <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
          <title></title>
          <style type="text/css" rel="stylesheet" media="all">

          @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
          body {
            width: 100% !important;
            height: 100%;
            margin: 0;
            -webkit-text-size-adjust: none;
          }

          a {
            color: #3869D4;
          }

          a img {
            border: none;
          }

          td {
            word-break: break-word;
          }

          .preheader {
            display: none !important;
            visibility: hidden;
            mso-hide: all;
            font-size: 1px;
            line-height: 1px;
            max-height: 0;
            max-width: 0;
            opacity: 0;
            overflow: hidden;
          }

          body,
          td,
          th {
            font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
          }

          h1 {
            margin-top: 0;
            color: #333333;
            font-size: 22px;
            font-weight: bold;
            text-align: left;
          }

          h2 {
            margin-top: 0;
            color: #333333;
            font-size: 16px;
            font-weight: bold;
            text-align: left;
          }

          h3 {
            margin-top: 0;
            color: #333333;
            font-size: 14px;
            font-weight: bold;
            text-align: left;
          }

          td,
          th {
            font-size: 16px;
          }

          p,
          ul,
          ol,
          blockquote {
            margin: .4em 0 1.1875em;
            font-size: 16px;
            line-height: 1.625;
          }

          p.sub {
            font-size: 13px;
          }

          .align-right {
            text-align: right;
          }

          .align-left {
            text-align: left;
          }

          .align-center {
            text-align: center;
          }
          .button {
            background-color: #3869D4;
            border-top: 10px solid #3869D4;
            border-right: 18px solid #3869D4;
            border-bottom: 10px solid #3869D4;
            border-left: 18px solid #3869D4;
            display: inline-block;
            text-decoration: none;
            border-radius: 3px;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
            -webkit-text-size-adjust: none;
            box-sizing: border-box;
          }

          .button--green {
            background-color: #22BC66;
            border-top: 10px solid #22BC66;
            border-right: 18px solid #22BC66;
            border-bottom: 10px solid #22BC66;
            border-left: 18px solid #22BC66;
          }

          .button--red {
            background-color: #FF6136;
            border-top: 10px solid #FF6136;
            border-right: 18px solid #FF6136;
            border-bottom: 10px solid #FF6136;
            border-left: 18px solid #FF6136;
          }

          @media only screen and (max-width: 500px) {
            .button {
              width: 100% !important;
              text-align: center !important;
            }
          }

          .attributes {
            margin: 0 0 21px;
          }

          .attributes_content {
            background-color: #F4F4F7;
            padding: 16px;
          }

          .attributes_item {
            padding: 0;
          }

          .related {
            width: 100%;
            margin: 0;
            padding: 25px 0 0 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
          }

          .related_item {
            padding: 10px 0;
            color: #CBCCCF;
            font-size: 15px;
            line-height: 18px;
          }

          .related_item-title {
            display: block;
            margin: .5em 0 0;
          }

          .related_item-thumb {
            display: block;
            padding-bottom: 10px;
          }

          .related_heading {
            border-top: 1px solid #CBCCCF;
            text-align: center;
            padding: 25px 0 10px;
          }

          .discount {
            width: 100%;
            margin: 0;
            padding: 24px;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #F4F4F7;
            border: 2px dashed #CBCCCF;
          }

          .discount_heading {
            text-align: center;
          }

          .discount_body {
            text-align: center;
            font-size: 15px;
          }

          .social {
            width: auto;
          }

          .social td {
            padding: 0;
            width: auto;
          }

          .social_icon {
            height: 20px;
            margin: 0 8px 10px 8px;
            padding: 0;
          }

          .purchase {
            width: 100%;
            margin: 0;
            padding: 35px 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
          }

          .purchase_content {
            width: 100%;
            margin: 0;
            padding: 25px 0 0 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
          }

          .purchase_item {
            padding: 10px 0;
            color: #51545E;
            font-size: 15px;
            line-height: 18px;
          }

          .purchase_heading {
            padding-bottom: 8px;
            border-bottom: 1px solid #EAEAEC;
          }

          .purchase_heading p {
            margin: 0;
            color: #85878E;
            font-size: 12px;
          }

          .purchase_footer {
            padding-top: 15px;
            border-top: 1px solid #EAEAEC;
          }

          .purchase_total {
            margin: 0;
            text-align: right;
            font-weight: bold;
            color: #333333;
          }

          .purchase_total--label {
            padding: 0 15px 0 0;
          }

          body {
            background-color: #F4F4F7;
            color: #51545E;
          }

          p {
            color: #51545E;
          }

          p.sub {
            color: #6B6E76;
          }

          .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #F4F4F7;
          }

          .email-content {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
          }


          .email-masthead {
            padding: 25px 0;
            text-align: center;
          }

          .email-masthead_logo {
            width: 94px;
          }

          .email-masthead_name {
            font-size: 16px;
            font-weight: bold;
            color: #A8AAAF;
            text-decoration: none;
            text-shadow: 0 1px 0 white;
          }
          .email-body {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #FFFFFF;
          }

          .email-body_inner {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            -premailer-width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #FFFFFF;
          }

          .email-footer {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            -premailer-width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            text-align: center;
          }

          .email-footer p {
            color: #6B6E76;
          }

          .body-action {
            width: 100%;
            margin: 30px auto;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            text-align: center;
          }

          .body-sub {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #EAEAEC;
          }

          .content-cell {
            padding: 35px;
          }

          @media only screen and (max-width: 600px) {
            .email-body_inner,
            .email-footer {
              width: 100% !important;
            }
          }

          @media (prefers-color-scheme: dark) {
            body,
            .email-body,
            .email-body_inner,
            .email-content,
            .email-wrapper,
            .email-masthead,
            .email-footer {
              background-color: #333333 !important;
              color: #FFF !important;
            }
            p,
            ul,
            ol,
            blockquote,
            h1,
            h2,
            h3 {
              color: #FFF !important;
            }
            .attributes_content,
            .discount {
              background-color: #222 !important;
            }
            .email-masthead_name {
              text-shadow: none !important;
            }
          }
          </style>
        </head>
        <body>
          <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
              <td align="center">
                <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                  <!-- <tr>
                    <td class="email-masthead">
                      <a href="https://example.com" class="f-fallback email-masthead_name">
                      UR HUB
                    </a>
                    </td>
                  </tr> -->

                  <tr>
                    <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                      <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                          <td class="content-cell">
                          <div class="align-center"><img  src="https://ur-hub.s3.us-west-2.amazonaws.com/assets/logo/logo.png"></div>
                              <h3 class="align-right">Tanggal:' . $dateNow . '</h3>
                            <div class="f-fallback">
                              <h1>Hi ' . $name . ',</h1>

                              <table class="discount" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                  <td align="center">
                                      <p>Penagihan Bulan ' . tgl_indo(date('m', strtotime('-1 month'))) . '</p>
                                    <h1 class="f-fallback discount_heading">' . rupiah($total_penagihan) . '</h1>
                                  </td>
                                </tr>
                              </table>

                              <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                  <tr>
                                    <td>
                                      <h3>Rincian Penagihan</h3></td>
                                    <td>
                                  </tr>
                                  <tr>
                                    <td colspan="2">

                                      <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">';
      $html .= ' <tr>
                        <td width="80%" class="purchase_item"><span class="f-fallback">Total Pendapatan E-wallet</span></td>
                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($saldo) . '</span></td>
                        </tr>
                        <tr>
                        <td width="80%" class="purchase_item"><span class="f-fallback">Charge</span></td>
                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($sumCharge_ur) . '</span></td>
                        </tr>
                              <tr>
                              <td width="80%" class="purchase_item"><span class="f-fallback">TOTAL</span></td>
                              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah($total_penagihan) . '</span></td>
                            </tr></table>
                                                            </td>
                                                          </tr>
                                                        </table>';
      if ($total_penagihan > 0) {
        //isi qrcode jika di scan
        $qrCode = new QrCode($qr_tagihan);
        header('Content-Type: ' . $qrCode->getContentType());
        $dataUri = $qrCode->writeDataUri();
        // $qr=$qrCode->writeString();
        $html .= '<table align="center" width="100%" cellpadding="0" cellspacing="0">
                                                      <tr>
                                                        <td align="center">
                                                        <img src="' . $dataUri . '">
                                                        <h3 class="align-center">Scan For Pay</h3>
                                                        </td>
                                                      </tr>
                                                    </table>';
      }
      $html .= '<p>Hormat Kami,
                                                        <br>UR - Easy & Quick Order</p>


                                                      <!-- Sub copy -->

                                                    </div>
                                                  </td>
                                                </tr>
                                              </table>
                                            </td>
                                          </tr>
                                          <tr>
                                            <td>
                                              <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                                <tr>
                                                  <td class="content-cell" align="center">
                                                    <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                                    <p class="f-fallback sub align-center">
                                                      PT. Rahmat Tuhan Lestari
                                                      <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir, Kota Bandung
                                                      <br>Jawa Barat 40221
                                                    </p>
                                                  </td>
                                                </tr>
                                              </table>
                                            </td>
                                          </tr>
                                        </table>
                                      </td>
                                    </tr>
                                  </table>
                                </body>
                              </html>';
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Penagihan UR",
        // "<div>Hai, " . $name . " </div>
        // // <>
        // <br>
        // <br>Laporan Keuangan
        // <br>
        // <br><a href='https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdf.php?id=" . md5(id) . "'>click here</a>
        // <br>
        // <br>
        // Jika anda merasa tidak melakukan request silahkan abaikan pesan ini.
        // <br>
        // <br>
        // Hormat Kami,
        // <br><br>
        // Ur Hub."
        $html
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }
  //Function to create a new user by phone
  public function createUserphone($phone)
  {
    if (!$this->isUserExistphone($phone)) {
      $stmt = $this->conn->prepare("INSERT INTO users (phone) VALUES (?)");
      $stmt->bind_param("s", $phone);
      if ($stmt->execute()) {
        return USER_CREATED;
      } else {
        return USER_NOT_CREATED;
      }
    } else {
      return USER_ALREADY_EXIST;
    }
  }
  public function checkEmail($email)
  {
    if (!$this->isUserExistemail($email)) {
      $stmt = $this->conn->prepare("select * from users where email = ?");
      $stmt->bind_param("s", $email);
      if ($stmt->execute()) {
        return USER_CREATED;
      } else {
        return USER_NOT_CREATED;
      }
    } else {
      return USER_ALREADY_EXIST;
    }
  }
  public function checkHandphone($phone)
  {
    if (!$this->isUserExistphone($phone)) {
      $stmt = $this->conn->prepare("select * from users where phone = ?");
      $stmt->bind_param("s", $phone);
      if ($stmt->execute()) {
        return USER_CREATED;
      } else {
        return USER_NOT_CREATED;
      }
    } else {
      return USER_ALREADY_EXIST;
    }
  }
  public function checkHandphonePartner($phone)
  {
    if (!$this->isUserExistphone($phone)) {
      $stmt = $this->conn->prepare("select * from partner where phone = ?");
      $stmt->bind_param("s", $phone);
      if ($stmt->execute()) {
        return USER_CREATED;
      } else {
        return USER_NOT_CREATED;
      }
    } else {
      return USER_ALREADY_EXIST;
    }
  }


  //ngecek username yang sama
  private function isUserExist($username, $email, $phone)
  {
    $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? OR phone = ?");
    $stmt->bind_param("sss", $username, $email, $phone);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
  }

  public function checkTable($partnerID, $table_no)
  {
    $stmt = $this->conn->prepare("SELECT is_queue FROM meja WHERE idpartner = ? AND idmeja = ?");
    $stmt->bind_param("ss", $partnerID, $table_no);
    $stmt->execute();
    $stmt->bind_result($is_queue);
    $stmt->fetch();
    $stmt->store_result();
    if ($is_queue >= 0) {
      return $is_queue;
    } else {
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }
  public function checkTableFoodcourt($id_foodcourt, $table_no)
  {
    $stmt = $this->conn->prepare("SELECT id FROM foodcourt_table WHERE id_foodcourt = ? AND idmeja = ?");
    $stmt->bind_param("ss", $id_foodcourt, $table_no);
    $stmt->execute();
    // $stmt->bind_result($is_queue);
    // $stmt->fetch();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return TRANSAKSI_CREATED;
    } else {
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }
  public function checkPartnerID($partnerID)
  {
    $stmt = $this->conn->prepare("SELECT id FROM partner WHERE id = ?");
    $stmt->bind_param("s", $partnerID);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      return TRANSAKSI_CREATED;
    } else {
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }

  //ngecek phone yang sama
  private function isUserExistphone($phone)
  {
    $stmt = $this->conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
  }

  //ngecek email yang sama
  private function isUserExistemail($email)
  {
    $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
  }
  public function userLoginbyphone($phone, $password)
  {
    $stmt = $this->conn->prepare("SELECT id FROM users WHERE phone = ? AND password = ?");
    $pass = md5($password);
    $stmt->bind_param("ss", $phone, $pass);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
  }

  //login user
  public function userLogin($username, $pass)
  {
    $password = md5($pass);
    $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
  }

  /*
     * After the successful login we will call this method
     * this method will return the user data in an array
     * */

  public function getAllUser()
  {
    $stmt = $this->conn->prepare("SELECT id, username, email, phone, name, gender, TglLahir FROM users");
    $stmt->execute();
    $stmt->bind_result($id, $uname, $email, $phone, $name, $gender, $TglLahir);
    $stmt->fetch();
    $user = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $user[$i]['id'] = $id;
      $user[$i]['username'] = $uname;
      $user[$i]['email'] = $email;
      $user[$i]['phone'] = $phone;
      $user[$i]['name'] = $name;
      $user[$i]['gender'] = $gender;
      $user[$i]['TglLahir'] = $TglLahir;
      $i++;
    }
    return $user;
  }
  public function getOngkir($id)
  {
    $stmt = $this->conn->prepare("SELECT delivery_fee FROM partner WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($ongkir);
    $stmt->fetch();
    return $ongkir;
  }

  public function getAllMenu()
  {
    $stmt = $this->conn->prepare("SELECT id,nama,harga,deskripsi,category, img_data, enabled FROM menu");
    $stmt->execute();
    $stmt->bind_result($id, $nama, $harga, $deskripsi, $category, $img, $enabled);
    $menu = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $menu[$i]['id'] = $id;
      $menu[$i]['nama'] = $nama;
      $menu[$i]['harga'] = $harga;
      $menu[$i]['deskripsi'] = $deskripsi;
      $menu[$i]['category'] = $category;
      $menu[$i]['img_data'] = $img;
      $menu[$i]['availability'] = $enabled;
      $i++;
    }
    // return $menu;

    return $menu;
  }

  public function getMenuByPartnerID($partnerID)
  {
    $stmt = $this->conn->prepare("SELECT id,nama,harga,deskripsi,category, img_data, enabled,stock, is_recommended, is_recipe FROM menu WHERE id_partner = ?");
    $stmt->bind_param("s", $partnerID);
    $stmt->execute();
    $stmt->bind_result($id, $nama, $harga, $deskripsi, $category, $img, $enabled, $stock, $is_recommended, $is_recipe);
    $menu = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $menu[$i]['id'] = $id;
      $menu[$i]['nama'] = $nama;
      $menu[$i]['harga'] = $harga;
      $menu[$i]['deskripsi'] = $deskripsi;
      $menu[$i]['category'] = $category;
      $menu[$i]['img_data'] = $img;
      $menu[$i]['availability'] = $enabled;
      $menu[$i]['stock'] = $stock;
      $menu[$i]['is_recommended'] = $is_recommended;
      $menu[$i]['is_recipe'] = $is_recipe;
      $i++;
    }
    // return $menu;

    return $menu;
  }

  public function getMenuByPartnerIDWithCategory($partnerID)
  {
    $stmt = $this->conn->prepare("SELECT menu.id, menu.nama, menu.harga, menu.deskripsi, menu.img_data, menu.enabled, menu.stock,categories.name AS category_name FROM menu JOIN categories ON menu.id_category = categories.id WHERE menu.id_partner=? ORDER BY id DESC");
    $stmt->bind_param("s", $partnerID);
    $stmt->execute();
    $stmt->bind_result($id, $nama, $harga, $deskripsi, $img, $enabled, $stock, $category_name);
    $menu = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $menu[$i]['id'] = $id;
      $menu[$i]['nama'] = $nama;
      $menu[$i]['harga'] = $harga;
      $menu[$i]['deskripsi'] = $deskripsi;
      $menu[$i]['category_name'] = $category_name;
      $menu[$i]['img_data'] = $img;
      $menu[$i]['availability'] = $enabled;
      $menu[$i]['stock'] = $stock;
      $i++;
    }
    // return $menu;

    return $menu;
  }

  public function getMenuSection($partnerID)
  {
    // ambil categorynya
    $stmts = $this->conn->prepare('SELECT distinct(category) from menu where id_partner = ?');
    $stmts->bind_param("s", $partnerID);
    $stmts->execute();
    $stmts->bind_result($section);
    $title = array();
    $o = 0;
    while ($datas = $stmts->fetch()) {
      $title[$o]['title'] = $section;
      $stmt = $this->conn2->prepare("SELECT id,nama,harga,deskripsi,category, img_data, enabled, id_category,stock FROM menu WHERE id_partner = ? AND category = ?");
      $stmt->bind_param("ss", $partnerID, $section);
      $stmt->execute();
      $stmt->bind_result($id, $nama, $harga, $deskripsi, $category, $img, $enabled, $id_category, $stock);
      $menu = array();
      $i = 0;
      while ($data = $stmt->fetch()) {
        $menu[$i]['id'] = $id;
        $menu[$i]['nama'] = $nama;
        $menu[$i]['harga'] = $harga;
        $menu[$i]['deskripsi'] = $deskripsi;
        $menu[$i]['id_category'] = $id_category;
        $menu[$i]['category_name'] = $category;
        $menu[$i]['img_data'] = $img;
        $menu[$i]['availability'] = $enabled;
        $menu[$i]['stock'] = $stock;
        $i++;
      }
      $title[$o]['data'] = $menu;
      $o++;
    }
    // return $menu;

    return $title;
  }

  public function getMenuSectionV2($partnerID)
  {
    // ambil categorynya
    $stmts = $this->conn->prepare("SELECT categories.id, categories.name, partner.name from categories JOIN partner ON categories.id_master = partner.id_master WHERE partner.id=?");
    $stmts->bind_param("s", $partnerID);
    $stmts->execute();
    $stmts->bind_result($section, $category_name, $partner_name);
    $title = array();
    $o = 0;
    while ($datas = $stmts->fetch()) {
      // $title[$o]['title'] = $section;
      $title[$o]['title'] = $category_name;
      $title[$o]['partner_name'] = $partner_name;

      $stmt = $this->conn2->prepare("SELECT menu.id, menu.nama, menu.harga, menu.deskripsi, menu.img_data, menu.enabled, menu.category,menu.stock, categories.name AS category_name, menu.is_variant FROM menu
            JOIN categories ON menu.id_category = categories.id
            WHERE menu.id_partner = ? AND categories.id = ?");
      $stmt->bind_param("ss", $partnerID, $section);
      $stmt->execute();
      $stmt->bind_result($id, $nama, $harga, $deskripsi, $img, $enabled, $category, $stock, $id_category, $is_variant);
      $menu = array();
      $i = 0;
      while ($data = $stmt->fetch()) {
        $menu[$i]['id'] = $id;
        $menu[$i]['nama'] = $nama;
        $menu[$i]['harga'] = $harga;
        $menu[$i]['deskripsi'] = $deskripsi;
        $menu[$i]['id_category'] = $id_category;
        $menu[$i]['category_name'] = $category;
        $menu[$i]['img_data'] = $img;
        $menu[$i]['availability'] = $enabled;
        $menu[$i]['stock'] = $stock;
        $menu[$i]['is_variant'] = $is_variant;
        $i++;
      }
      $title[$o]['data'] = $menu;
      $o++;
    }
    // return $menu;

    return $title;
  }

  public function getMenuCategoryByPartnerID($partnerID)
  {
    $stmt = $this->conn->prepare("SELECT DISTINCT category FROM menu WHERE id_partner = ?");
    $stmt->bind_param("s", $partnerID);
    $stmt->execute();
    $stmt->bind_result($category);
    $cat = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $cat[$i]['category'] = $category;
      $i++;
    }
    // return $menu;

    return $cat;
  }

  public function getTransaksibyphone($phone)
  {

    $stmt = $this->conn->prepare("SELECT a.id, a.jam, a.phone, a.id_partner, a.no_meja,a.no_meja_foodcourt,a.status, a.total ,a.tipe_bayar, a.promo, b.name, a.id_voucher,a.id_voucher_redeemable, a.queue, a.tax, a.service, a.charge_ewallet, a.charge_xendit,a.charge_ur
                                        FROM transaksi a, partner b
                                        WHERE a.phone = ? AND a.id_partner = b.id
                                        ORDER BY jam DESC");

    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $stmt->bind_result($id, $jam, $phone, $id_partner, $no_meja, $no_meja_foodcourt, $status, $total, $tipe_bayar, $promo, $nama_outlet, $id_voucher, $id_voucher_redeemable, $queue, $tax, $service, $charge_ewallet, $charge_xendit, $charge_ur);
    $trx = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $trx[$i]['id'] = $id;
      $trx[$i]['jam'] = $jam;
      $trx[$i]['phone'] = $phone;
      $trx[$i]['id_partner'] = $id_partner;
      $trx[$i]['no_meja'] = $no_meja;
      $trx[$i]['no_meja_foodcourt'] = $no_meja_foodcourt;
      $trx[$i]['status'] = $status;
      $trx[$i]['total'] = $total;
      $trx[$i]['id_voucher'] = $id_voucher;
      $trx[$i]['id_voucher_redeemable'] = $id_voucher_redeemable;
      $trx[$i]['tipe_bayar'] = $tipe_bayar;
      $trx[$i]['nama_outlet'] = $nama_outlet;
      $trx[$i]['promo'] = $promo;
      $trx[$i]['queue'] = $queue;
      $trx[$i]['tax'] = $tax;
      $trx[$i]['service'] = $service;
      $trx[$i]['charge_ewallet'] = $charge_ewallet;
      $trx[$i]['charge_xendit'] = $charge_xendit;
      $trx[$i]['charge_ur'] = $charge_ur;
      $i++;
    }
    return $trx;
    //return $menu;
  }

  public function getAllTransaksi()
  {

    $stmt = $this->conn->prepare("SELECT a.id, a.jam, a.phone, a.id_partner, a.no_meja, a.status, a.total ,a.tipe_bayar, a.promo, b.name,   a.id_voucher,a.id_voucher_redeemable, a.tax, a.service , a.charge_ewallet, a.charge_xendit, a.charge_ur
                                        FROM transaksi a, partner b
                                        WHERE a.id_partner = b.id
                                        ORDER BY jam DESC");
    $stmt->execute();
    $stmt->bind_result($id, $jam, $phone, $id_partner, $no_meja, $status, $total, $tipe_bayar, $promo, $nama_outlet, $id_voucher, $id_voucher_redeemable, $tax, $service);
    $trx = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $trx[$i]['id'] = $id;
      $trx[$i]['jam'] = $jam;
      $trx[$i]['phone'] = $phone;
      $trx[$i]['id_partner'] = $id_partner;
      $trx[$i]['no_meja'] = $no_meja;
      $trx[$i]['status'] = $status;
      $trx[$i]['total'] = $total;
      $trx[$i]['tipe_bayar'] = $tipe_bayar;
      $trx[$i]['promo'] = $promo;
      $trx[$i]['id_voucher'] = $id_voucher;
      $trx[$i]['id_voucher_redeemable'] = $id_voucher_redeemable;
      $trx[$i]['nama_outlet'] = $nama_outlet;
      $trx[$i]['tax'] = $tax;
      $trx[$i]['service'] = $service;

      $i++;
    }
    return $trx;
    //return $menu;
  }

  public function getAllMeja()
  {
    $stmt = $this->conn->prepare("SELECT * FROM meja");
    $stmt->execute();
    $stmt->bind_result($idpartner, $idmeja);
    $meja = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $meja[$i]['idpartner'] = $idpartner;
      $meja[$i]['idmeja'] = $idmeja;
      $i++;
    }
    // return $menu;

    return $meja;
  }

  public function getAllPartner()
  {
    $stmt = $this->conn->prepare("SELECT * FROM partner");
    $stmt->execute();
    $stmt->bind_result($id, $name, $address, $phone, $status, $email);
    $partner = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $partner[$i]['id'] = $id;
      $partner[$i]['name'] = $name;
      $partner[$i]['address'] = $address;
      $partner[$i]['phone'] = $phone;
      $partner[$i]['status'] = $status;
      $partner[$i]['email'] = $email;
      $i++;
    }
    // return $menu;

    return $partner;
  }

  public function getAllUserVoucher()
  {
    $stmt = $this->conn->prepare("SELECT * FROM user_voucher_ownership ");
    $stmt->execute();
    $stmt->bind_result($userid, $voucherid, $transaksi_id);
    $userVoucher = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $userVoucher[$i]['userid'] = $userid;
      $userVoucher[$i]['voucherid'] = $voucherid;
      $userVoucher[$i]['transaksi_id'] = $transaksi_id;
      $i++;
    }
    // return $menu;

    return $userVoucher;
  }

  public function getAllVoucher()
  {
    $stmt = $this->conn->prepare("SELECT * FROM voucher");
    $stmt->execute();
    $stmt->bind_result($id, $code, $title, $description, $discount, $category, $enabled, $valid_from, $valid_until, $total_usage, $prerequisite, $partnerID);
    $voucher = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $voucher[$i]['id'] = $id;
      $voucher[$i]['code'] = $code;
      $voucher[$i]['title'] = $title;
      $voucher[$i]['description'] = $description;
      $voucher[$i]['discount'] = $discount;
      $voucher[$i]['category'] = $category;
      $voucher[$i]['enabled'] = $enabled;
      $voucher[$i]['valid_from'] = $valid_from;
      $voucher[$i]['valid_until'] = $valid_until;
      $voucher[$i]['total_usage'] = $total_usage;
      $voucher[$i]['prerequisite'] = $prerequisite;
      $voucher[$i]['partnerID'] = $partnerID;


      $i++;
    }
    // return $menu;

    return $voucher;
  }


  public function updateInfoUser($name, $email, $TglLahir, $gender, $phone, $alamat)
  {
    $stmt = $this->conn->prepare("UPDATE users SET name = ?,email = ?, TglLahir = ?, gender = ?, alamat = ? WHERE phone = ?");
    $stmt->bind_param("ssssss", $name, $email, $TglLahir, $gender, $alamat, $phone);
    if ($stmt->execute()) {
      return USER_UPDATED;
    } else {
      return USER_NOT_UPDATED;
    }
  }

  public function updateInfoUserbyphone($name, $email, $password, $TglLahir, $gender, $phone)
  {
    $stmt = $this->conn->prepare("UPDATE users SET name = ?,email = ?, password = ?, TglLahir = ?, gender = ?  WHERE phone = ?");
    $pass = md5($password);
    $stmt->bind_param("ssssss", $name, $email, $pass, $TglLahir, $gender, $phone);
    if ($stmt->execute()) {
      return USER_UPDATED;
    } else {
      return USER_NOT_UPDATED;
    }
  }

  public function updatePassword($password, $token)
  {
    $stmt = $this->conn->prepare("UPDATE users
    INNER JOIN reset_password
    ON users.email=reset_password.email
    SET users.password= ?
    WHERE md5(reset_password.token)=?");

    $pass = md5($password);
    $stmt->bind_param("ss", $pass, $token);
    if ($stmt->execute()) {
      return USER_UPDATED;
    } else {
      return USER_NOT_UPDATED;
    }
  }

  public function updatePasswordProfile($password, $phone)
  {

    $stmt = $this->conn->prepare("UPDATE `users` SET `password`=? WHERE phone=?");
    $pass = md5($password);
    $stmt->bind_param("ss", $pass, $phone);
    if ($stmt->execute()) {
      return USER_UPDATED;
    } else {
      return USER_NOT_UPDATED;
    }
  }

  public function updatePasswordPartner($password, $token)
  {
    $stmt = $this->conn->prepare("UPDATE partner
        INNER JOIN reset_password
        ON partner.email=reset_password.email
        SET partner.password= ?
        WHERE md5(reset_password.token)=?");
    $stmt->bind_param("ss", $password, $token);
    if ($stmt->execute()) {
      return USER_UPDATED;
    } else {
      return USER_NOT_UPDATED;
    }
  }
  public function updatePasswordEmployee($password, $token)
  {
    $stmt = $this->conn->prepare("UPDATE employees
        INNER JOIN reset_password
        ON employees.email=reset_password.email
        SET employees.pin= ?
        WHERE reset_password.token=?");
    $stmt->bind_param("ss", $password, $token);
    if ($stmt->execute()) {
      return USER_UPDATED;
    } else {
      return USER_NOT_UPDATED;
    }
  }

  public function updatePasswordFoodcourt($password, $token)
  {
    $stmt = $this->conn->prepare("UPDATE partner
        INNER JOIN reset_password
        ON foodcourt.email=reset_password.email
        SET foodcourt.password= ?
        WHERE md5(reset_password.token)=?");
    $stmt->bind_param("ss", $password, $token);
    if ($stmt->execute()) {
      return USER_UPDATED;
    } else {
      return USER_NOT_UPDATED;
    }
  }
  public function checkOldPassword($token)
  {
    $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    $stmt = mysqli_query($db_conn, "SELECT partner.password AS pass
    FROM partner
    JOIN reset_password
    ON partner.email=reset_password.email
    WHERE md5(reset_password.token)='$token'");
    while ($row = mysqli_fetch_assoc($stmt)) {
      $password = $row['pass'];
    }


    return $password;
  }

  public function updatePasswordMaster($password, $token)
  {
    $stmt = $this->conn->prepare("UPDATE master
        INNER JOIN reset_password
        ON master.email=reset_password.email
        SET master.password= ?
        WHERE md5(reset_password.token)=?");
    $stmt->bind_param("ss", $password, $token);
    if ($stmt->execute()) {
      return USER_UPDATED;
    } else {
      return USER_NOT_UPDATED;
    }
  }

  public function updatePasswordMD5($password, $phone)
  {
    $stmt = $this->conn->prepare("UPDATE users
    INNER JOIN reset_password
    ON users.phone=reset_password.email
    SET users.password= ?
    WHERE md5(reset_password.token)=?");

    $pass = md5($password);
    $stmt->bind_param("ss", $pass, $phone);
    if ($stmt->execute()) {
      return USER_UPDATED;
    } else {
      return USER_NOT_UPDATED;
    }
  }

  public function updatePINMD5($pin, $phone)
  {
    $stmt = $this->conn->prepare("UPDATE users
    INNER JOIN reset_password
    ON users.phone=reset_password.email
    SET users.pin= ?
    WHERE md5(reset_password.token)=?");

    $pass = md5($pin);
    $stmt->bind_param("ss", $pass, $phone);
    if ($stmt->execute()) {
      return USER_UPDATED;
    } else {
      return USER_NOT_UPDATED;
    }
  }

  public function insertInfoUserbyphone($name, $email, $password, $TglLahir, $gender, $phone, $alamat)
  {
    $stmt = $this->conn->prepare("INSERT into users (password,email,name,phone,TglLahir,Gender,alamat) values (?,?,?,?,?,?,?)");
    $pass = md5($password);
    $stmt->bind_param("sssssss", $pass, $email, $name, $phone, $TglLahir, $gender, $alamat);
    if ($stmt->execute()) {
      return USER_UPDATED;
    } else {
      return USER_NOT_UPDATED;
    }
  }

  public function getUserByPhone($phone)
  {
    $stmt = $this->conn->prepare("SELECT id, email, name, gender, TglLahir, alamat FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->bind_result($id, $email, $name, $gender, $TglLahir, $alamat);
    $stmt->fetch();
    $user = array();
    $user['id'] = $id;
    $user['email'] = $email;
    $user['name'] = $name;
    $user['gender'] = $gender;
    $user['TglLahir'] = $TglLahir;
    $user['alamat'] = $alamat;
    return $user;
  }

  // createTransaksi.php
  public function createTransaksi($id, $phone, $idpartner, $nomeja, $status, $total, $tipebayar, $promo, $point, $queue, $takeaway, $notes, $id_foodcourt, $tax, $service, $charge_ewallet, $charge_xendit, $charge_ur)
  {
    // echo ($status);
    // echo ($charge_ur);
    // id    datetime    phone    id_partner    no_meja    status    total    id_voucher    tipe_bayar
    date_default_timezone_set('Asia/Jakarta');
    $dates = date('Y-m-d H:i:s', time());

    $sql = "INSERT INTO transaksi(id, jam, phone, id_partner, no_meja, status, total, tipe_bayar, promo, point, queue, takeaway, notes, id_foodcourt, tax, service,charge_ewallet,charge_xendit,charge_ur) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?,?,?,?,?)";
    if ($query = $this->conn->prepare($sql)) {
      $query->bind_param('sssssiiiiiiisisssss', $id, $dates, $phone, $idpartner, $nomeja, $status, $total, $tipebayar, $promo, $point, $queue, $takeaway, $notes, $id_foodcourt, $tax, $service, $charge_ewallet, $charge_xendit, $charge_ur);
      if ($exe = $query->execute()) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
      //rest of code here
    } else {
      //error !! don't go further
    }
  }
  // createTransaksi.php
  public function createTransaksiFC($id_transaksi, $id_foodcourt, $phone, $nomeja, $total, $id_voucher, $id_voucher_redeemable, $tipebayar, $promo, $tax, $service, $charge_ewallet, $charge_xendit, $charge_ur, $status)
  {
    // id    datetime    phone    id_partner    no_meja    status    total    id_voucher    tipe_bayar
    date_default_timezone_set('Asia/Jakarta');
    $dates = date('Y-m-d H:i:s', time());

    $sql = "INSERT INTO transaksi_foodcourt(id, id_foodcourt, phone, no_meja, total, id_voucher, id_voucher_redeemable, tipe_bayar, promo, tax, service, charge_ewallet, charge_xendit, charge_ur, created_at,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    if ($query = $this->conn->prepare($sql)) {
      $query->bind_param('sississiiiissssi', $id_transaksi, $id_foodcourt, $phone, $nomeja, $total, $id_voucher, $id_voucher_redeemable, $tipebayar, $promo, $tax, $service, $charge_ewallet, $charge_xendit, $charge_ur, $dates, $status);
      if ($exe = $query->execute()) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
      //rest of code here
    } else {
      //error !! don't go further
    }
  }

  public function createTransaksiFoodcourt($id, $phone, $idpartner, $nomeja, $status, $total, $tipebayar, $promo, $queue, $takeaway, $notes, $id_foodcourt, $tax, $service, $charge_ewallet, $charge_xendit, $charge_ur)
  {
    // id    datetime    phone    id_partner    no_meja    status    total    id_voucher    tipe_bayar
    date_default_timezone_set('Asia/Jakarta');
    $dates = date('Y-m-d H:i:s', time());

    $sql = "INSERT INTO transaksi(id, jam, phone, id_partner, no_meja_foodcourt, status, total, tipe_bayar, promo, queue, takeaway, notes, id_foodcourt, tax, service,charge_ewallet,charge_xendit,charge_ur) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?,?,?,?)";
    if ($query = $this->conn->prepare($sql)) {
      $query->bind_param('sssssiiiiiisisssss', $id, $dates, $phone, $idpartner, $nomeja, $status, $total, $tipebayar, $promo, $queue, $takeaway, $notes, $id_foodcourt, $tax, $service, $charge_ewallet, $charge_xendit, $charge_ur);
      if ($exe = $query->execute()) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
      //rest of code here
    } else {
      //error !! don't go further
    }
  }

  public function createDelivery($transaksi_id, $alamat, $longitude, $latitude, $notes, $ongkir)
  {
    $sql = "INSERT INTO `delivery`(`transaksi_id`,`alamat`,`longitude`,`latitude`,`notes` ,`ongkir`) VALUES (?, ?, ?, ?, ?,?)";
    if ($query = $this->conn->prepare($sql)) {
      $query->bind_param('ssssss', $transaksi_id, $alamat, $longitude, $latitude, $notes, $ongkir);
      if ($exe = $query->execute()) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
      //rest of code here
    } else {
      //error !! don't go further
    }
  }

  public function getQueue($id_partner, $is_queue, $to)
  {
    date_default_timezone_set('Asia/Jakarta');
    $dates1 = date('Y-m-d', time());
    if ($to == 1) {
      $sql_queue = $this->conn->prepare("SELECT COUNT(*) FROM transaksi WHERE id_partner = ? AND DATE(jam) = ?");
      $sql_queue->bind_param("ss", $id_partner, $dates1);
      $sql_queue->execute();
      $sql_queue->bind_result($queue);
      $sql_queue->fetch();

      if ($is_queue == 0) {
        return $queue = 0;
      } else {
        return $queue += 1;
      }
    }
  }

  public function redeemVoucher($id, $phone, $id_voucher)
  {
    $sql = "UPDATE transaksi SET id_voucher = ? WHERE id=? AND phone = ?";
    if ($query = $this->conn->prepare($sql)) {
      $query->bind_param('sss', $id_voucher, $id, $phone);
      if ($exe = $query->execute()) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
      //rest of code here
    } else {
      //error !! don't go further
    }
  }

  public function redeemVoucherRedeemable($id, $phone, $id_voucher_redeemable)
  {
    $sql = "UPDATE transaksi SET id_voucher_redeemable = ? WHERE id=? AND phone = ?";
    if ($query = $this->conn->prepare($sql)) {
      $query->bind_param('sss', $id_voucher_redeemable, $id, $phone);
      if ($exe = $query->execute()) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
      //rest of code here
    } else {
      //error !! don't go further
    }
  }

  public function insertDetailTransaksi($id_transaksi, $carts)
  {
    try {
      foreach ($carts as $cart) {
        $json = "['variant':[";
        $variant = $cart['variant'];
        $i = 0;
        if ($variant == "null") {
          $json = "";
        } else {
          foreach ($variant as $vars) {
            if ($i == 0) {
              $json .= "{";
            } else {
              $json .= ",{";
            }
            $json .= "'name':'" . $vars['name'] . "',";
            $json .= "'id':'" . $vars['id_variant'] . "',";
            $json .= "'tipe':'" . $vars['type'] . "',";
            $json .= "'detail':[";
            $dvariant = $vars['data_variant'];
            $i += 1;
            $j = 0;
            foreach ($dvariant as $detail) {
              if ($j == 0) {
                $json .= "{";
              } else {
                $json .= ",{";
              }

              $json .= "'id':'" . $detail['id'] . "',";
              $json .= "'qty':'" . $detail['qty'] . "',";
              $json .= "'name':'" . $detail['name'] . "'}";
              $j += 1;
            }
            $json .= "]}";
          }
          $json .= "]]";
          # code...
        }
        $sql = "INSERT INTO detail_transaksi(id_transaksi, id_menu, harga_satuan, qty, notes, harga, variant) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($query = $this->conn->prepare($sql)) {
          $query->bind_param('siiisis', $id_transaksi, $cart['id_menu'], $cart['harga_satuan'], $cart['qty'], $cart['notes'], $cart['harga'], $json);
          $query->execute();

          //rest of code here
        } else {
          //error !! don't go further
          return FAILED_TO_CREATE_TRANSAKSI;
        }
        $query->close();
      }
      return TRANSAKSI_CREATED;
    } catch (Exception $e) {
      echo ($e);
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }
  public function insertDetailTransaksiFC($id_transaksi, $carts)
  {
    try {


      foreach ($carts as $cart) {
        $json = "['variant':[";
        $variant = $cart['variant'];
        $i = 0;
        if ($variant == "null") {
          $json = "";
        } else {
          foreach ($variant as $vars) {
            if ($i == 0) {
              $json .= "{";
            } else {
              $json .= ",{";
            }
            $json .= "'name':'" . $vars['name'] . "',";
            $json .= "'id':'" . $vars['id_variant'] . "',";
            $json .= "'tipe':'" . $vars['type'] . "',";
            $json .= "'detail':[";
            $dvariant = $vars['data_variant'];
            $i += 1;
            $j = 0;
            foreach ($dvariant as $detail) {
              if ($j == 0) {
                $json .= "{";
              } else {
                $json .= ",{";
              }

              $json .= "'id':'" . $detail['id'] . "',";
              $json .= "'qty':'" . $detail['qty'] . "',";
              $json .= "'name':'" . $detail['name'] . "'}";
              $j += 1;
            }
            $json .= "]}";
          }
          $json .= "]]";
          # code...
        }
        $id_trans_tenant = $id_transaksi . '-' . $cart['id_partner'];
        $sql = "INSERT INTO `transaksi_detail_tenant`(`id_transaksi_tenant`, `id_menu`, `harga_satuan`, `qty`, `notes`,`variant`) VALUES (?,?,?,?,?,?)";
        if ($query = $this->conn->prepare($sql)) {
          $query->bind_param('siiiss', $id_trans_tenant, $cart['id_menu'], $cart['harga_satuan'], $cart['qty'], $cart['notes'], $json);
          $query->execute();
          //rest of code here
        } else {
          //error !! don't go further
          return FAILED_TO_CREATE_TRANSAKSI;
        }
        $query->close();
      }
      return TRANSAKSI_CREATED;
    } catch (Exception $e) {
      echo ($e);
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }
  public function insertTransaksiTenant($id_transaksi, $promo, $carts)
  {
    try {
      //get promo per tenant
      $promo = (int) $promo;
      $length = 0;
      foreach ($carts as $cart) {
        $length += 1;
      }
      $countPartner = 0;
      $promoPerTenant = 0;
      $arr = array();
      $temp = true;
      $k = 0;
      for ($counter = 0; $counter < $length; $counter++) {

        for ($i = 0; $i < $counter; $i++) {

          if ($carts[$counter]['id_partner'] == $carts[$i]['id_partner']) {

            $temp = false;
          }
        }

        $a = 0;
        while ($a <= $counter  && $temp == true) {
          if ($carts[$a]['id_partner'] == $carts[$counter]['id_partner']) {
            $k += 1;
            $arr[$k] = $carts[$a]['id_partner'];
          }

          $a += 1;
        }
        $temp = true;
      }

      foreach ($arr as $a) {
        $countPartner += 1;
      }
      $promoPerTenant = ceil($promo / $countPartner);

      //get total per tenant
      $length = count($carts);
      $countTotal = 0;

      //if just 1 partner and 1 menu
      if ($length == 1) {
        $id_partner = $carts[0]['id_partner'];
        $id_transaksi_fc = $id_transaksi;
        $status = 0;
        $id = $id_transaksi . '-' . $id_partner;
        foreach ($carts as $cart) {
          $harga_satuan = $cart['harga_satuan'];
          echo $harga_satuan;
          $qty = $cart['qty'];
          echo $qty;
          $countTotal += $harga_satuan * $qty;
          echo $countTotal;
        }


        $sql = "INSERT INTO transaksi_tenant(id, id_partner, id_transaksi_fc, total, promo, status) VALUES (?,?,?,?,?,?)";
        if ($query = $this->conn->prepare($sql)) {
          $query->bind_param('sssiii', $id, $id_partner, $id_transaksi_fc, $countTotal, $promoPerTenant, $status);
          $query->execute();
        } else {
          //error !! don't go further
          return FAILED_TO_CREATE_TRANSAKSI;
        }
        $query->close();

        $countTotal = 0;

        //if more than 1 partner or 1 menu
      } else {

        for ($counter = 0; $counter < $length; $counter++) {

          if ($counter == 0) {

            $countTotal += $carts[$counter]['qty'] * $carts[$counter]['harga_satuan'];

            if ($carts[$counter]['id_partner'] != $carts[$counter + 1]['id_partner']) {
              $id_partner = $carts[$counter]['id_partner'];
              $id_transaksi_fc = $id_transaksi;
              $status = 0;
              $id = $id_transaksi . "-" . $id_partner;

              $sql = "INSERT INTO transaksi_tenant(id, id_partner, id_transaksi_fc, total, promo, status) VALUES (?,?,?,?,?,?)";
              if ($query = $this->conn->prepare($sql)) {
                $query->bind_param('sssiii', $id, $id_partner, $id_transaksi_fc, $countTotal, $promoPerTenant, $status);
                $query->execute();
                $countTotal = 0;
              } else {
                //error !! don't go further
                return FAILED_TO_CREATE_TRANSAKSI;
              }
            }
          } else {

            if ($carts[$counter]['id_partner'] == $carts[$counter - 1]['id_partner']) {

              $countTotal += $carts[$counter]['qty'] * $carts[$counter]['harga_satuan'];
              $id_partner = $carts[$counter]['id_partner'];
              $id_transaksi_fc = $id_transaksi;
              $status = 0;
              $id = $id_transaksi . "-" . $id_partner;

              if ($counter == $length - 1) {

                $sql = "INSERT INTO transaksi_tenant(id, id_partner, id_transaksi_fc, total, promo, status) VALUES (?,?,?,?,?,?)";
                if ($query = $this->conn->prepare($sql)) {
                  $query->bind_param('sssiii', $id, $id_partner, $id_transaksi_fc, $countTotal, $promoPerTenant, $status);
                  $query->execute();
                  $countTotal = 0;
                } else {
                  //error !! don't go further
                  return FAILED_TO_CREATE_TRANSAKSI;
                }
              } else {

                if ($id_partner != $carts[$counter + 1]['id_partner']) {

                  $sql = "INSERT INTO transaksi_tenant(id, id_partner, id_transaksi_fc, total, promo, status) VALUES (?,?,?,?,?,?)";
                  if ($query = $this->conn->prepare($sql)) {
                    $query->bind_param('sssiii', $id, $id_partner, $id_transaksi_fc, $countTotal, $promoPerTenant, $status);
                    $query->execute();
                    $countTotal = 0;
                  } else {
                    //error !! don't go further
                    return FAILED_TO_CREATE_TRANSAKSI;
                  }
                }
              }

              $query->close();
            } else {

              $countTotal = $carts[$counter]['qty'] * $carts[$counter]['harga_satuan'];
              $id_partner = $carts[$counter]['id_partner'];
              $id_transaksi_fc = $id_transaksi;
              $status = 0;
              $id = $id_transaksi . "-" . $id_partner;

              if ($counter == $length - 1) {

                $sql = "INSERT INTO transaksi_tenant(id, id_partner, id_transaksi_fc, total, promo, status) VALUES (?,?,?,?,?,?)";
                if ($query = $this->conn->prepare($sql)) {
                  $query->bind_param('sssiii', $id, $id_partner, $id_transaksi_fc, $countTotal, $promoPerTenant, $status);
                  $query->execute();
                  $countTotal = 0;
                } else {
                  //error !! don't go further
                  return FAILED_TO_CREATE_TRANSAKSI;
                }
              } else {

                if ($id_partner != $carts[$counter + 1]['id_partner']) {

                  $sql = "INSERT INTO transaksi_tenant(id, id_partner, id_transaksi_fc, total, promo, status) VALUES (?,?,?,?,?,?)";
                  if ($query = $this->conn->prepare($sql)) {
                    $query->bind_param('sssiii', $id, $id_partner, $id_transaksi_fc, $countTotal, $promoPerTenant, $status);
                    $query->execute();
                    $countTotal = 0;
                  } else {
                    //error !! don't go further
                    return FAILED_TO_CREATE_TRANSAKSI;
                  }
                }
              }


              $query->close();
            }
          }
        }
      }

      return TRANSAKSI_CREATED;
    } catch (Exception $e) {
      echo ($e);
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }

  public function insertTransaksiTenantAndroid($id_transaksi, $promo, $carts)
  {
    try {
      //get promo per tenant
      $promo = (int) $promo;
      $length = 0;
      foreach ($carts as $cart) {
        $length += 1;
      }
      $countPartner = 0;
      $promoPerTenant = 0;

      $arr = array();
      $temp = true;
      $k = 0;
      for ($counter = 0; $counter < $length; $counter++) {

        for ($i = 0; $i < $counter; $i++) {

          if ($carts[$counter]->id_partner == $carts[$i]->id_partner) {

            $temp = false;
          }
        }

        $a = 0;
        while ($a <= $counter  && $temp == true) {
          if ($carts[$a]->id_partner == $carts[$counter]->id_partner) {
            $k += 1;
            $arr[$k] = $carts[$a]->id_partner;
          }

          $a += 1;
        }
        $temp = true;
      }

      foreach ($arr as $a) {
        $countPartner += 1;
      }
      $promoPerTenant = ceil($promo / $countPartner);

      //get total per tenant
      $length = count($carts);
      $countTotal = 0;

      //if just 1 partner and 1 menu
      if ($length == 1) {
        $id_partner = $carts[0]->id_partner;
        $id_transaksi_fc = $id_transaksi;
        $status = 0;
        $id = $id_transaksi . '-' . $id_partner;
        foreach ($carts as $cart) {
          $harga_satuan = $cart->harga_satuan;
          $qty = $cart->qty;
          $countTotal += $harga_satuan * $qty;
        }


        $sql = "INSERT INTO transaksi_tenant(id, id_partner, id_transaksi_fc, total, promo, status) VALUES (?,?,?,?,?,?)";
        if ($query = $this->conn->prepare($sql)) {
          $query->bind_param('sssiii', $id, $id_partner, $id_transaksi_fc, $countTotal, $promoPerTenant, $status);
          $query->execute();
        } else {
          //error !! don't go further
          return FAILED_TO_CREATE_TRANSAKSI;
        }
        $query->close();

        $countTotal = 0;

        //if more than 1 partner or 1 menu
      } else {

        for ($counter = 0; $counter < $length; $counter++) {

          if ($counter == 0) {

            $countTotal += $carts[$counter]->qty * $carts[$counter]->harga_satuan;

            if ($carts[$counter]->id_partner != $carts[$counter + 1]->id_partner) {
              $id_partner = $carts[$counter]->id_partner;
              $id_transaksi_fc = $id_transaksi;
              $status = 0;
              $id = $id_transaksi . "-" . $id_partner;

              $sql = "INSERT INTO transaksi_tenant(id, id_partner, id_transaksi_fc, total, promo, status) VALUES (?,?,?,?,?,?)";
              if ($query = $this->conn->prepare($sql)) {
                $query->bind_param('sssiii', $id, $id_partner, $id_transaksi_fc, $countTotal, $promoPerTenant, $status);
                $query->execute();
                $countTotal = 0;
              } else {
                //error !! don't go further
                return FAILED_TO_CREATE_TRANSAKSI;
              }
            }
          } else {

            if ($carts[$counter]->id_partner == $carts[$counter - 1]->id_partner) {

              $countTotal += $carts[$counter]->qty * $carts[$counter]->harga_satuan;
              $id_partner = $carts[$counter]->id_partner;
              $id_transaksi_fc = $id_transaksi;
              $status = 0;
              $id = $id_transaksi . "-" . $id_partner;

              if ($counter == $length - 1) {

                $sql = "INSERT INTO transaksi_tenant(id, id_partner, id_transaksi_fc, total, promo, status) VALUES (?,?,?,?,?,?)";
                if ($query = $this->conn->prepare($sql)) {
                  $query->bind_param('sssiii', $id, $id_partner, $id_transaksi_fc, $countTotal, $promoPerTenant, $status);
                  $query->execute();
                  $countTotal = 0;
                } else {
                  //error !! don't go further
                  return FAILED_TO_CREATE_TRANSAKSI;
                }
              } else {

                if ($id_partner != $carts[$counter + 1]->id_partner) {

                  $sql = "INSERT INTO transaksi_tenant(id, id_partner, id_transaksi_fc, total, promo, status) VALUES (?,?,?,?,?,?)";
                  if ($query = $this->conn->prepare($sql)) {
                    $query->bind_param('sssiii', $id, $id_partner, $id_transaksi_fc, $countTotal, $promoPerTenant, $status);
                    $query->execute();
                    $countTotal = 0;
                  } else {
                    //error !! don't go further
                    return FAILED_TO_CREATE_TRANSAKSI;
                  }
                }
              }
            } else {

              $countTotal = $carts[$counter]->qty * $carts[$counter]->harga_satuan;
              $id_partner = $carts[$counter]->id_partner;
              $id_transaksi_fc = $id_transaksi;
              $status = 0;
              $id = $id_transaksi . "-" . $id_partner;

              if ($counter == $length - 1) {

                $sql = "INSERT INTO transaksi_tenant(id, id_partner, id_transaksi_fc, total, promo, status) VALUES (?,?,?,?,?,?)";
                if ($query = $this->conn->prepare($sql)) {
                  $query->bind_param('sssiii', $id, $id_partner, $id_transaksi_fc, $countTotal, $promoPerTenant, $status);
                  $query->execute();
                  $countTotal = 0;
                } else {
                  //error !! don't go further
                  return FAILED_TO_CREATE_TRANSAKSI;
                }
              } else {

                if ($id_partner != $carts[$counter + 1]->id_partner) {

                  $sql = "INSERT INTO transaksi_tenant(id, id_partner, id_transaksi_fc, total, promo, status) VALUES (?,?,?,?,?,?)";
                  if ($query = $this->conn->prepare($sql)) {
                    $query->bind_param('sssiii', $id, $id_partner, $id_transaksi_fc, $countTotal, $promoPerTenant, $status);
                    $query->execute();
                    $countTotal = 0;
                  } else {
                    //error !! don't go further
                    return FAILED_TO_CREATE_TRANSAKSI;
                  }
                }
              }


              $query->close();
            }
          }
        }
      }

      return TRANSAKSI_CREATED;
    } catch (Exception $e) {
      echo ($e);
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }


  public function insertDetailTransaksiFCAndro($id_transaksi, $carts)
  {
    try {
      foreach ($carts as $cart) {
        $json = "['variant':[";
        $i = 0;
        if (empty($cart->variant)) {
          $json = "";
        } else {
          $variant = $cart->variant;
          foreach ($variant as $vars) {
            if ($i == 0) {
              $json .= "{";
            } else {
              $json .= ",{";
            }
            $json .= "'name':'" . $vars->name . "',";
            $json .= "'id':'" . $vars->id_variant . "',";
            $json .= "'tipe':'" . $vars->type . "',";
            $json .= "'detail':[";
            $dvariant = $vars->data_variant;
            $i += 1;
            $j = 0;
            foreach ($dvariant as $detail) {
              if ($j == 0) {
                $json .= "{";
              } else {
                $json .= ",{";
              }

              $json .= "'id':'" . $detail->id . "',";
              $json .= "'qty':'" . $detail->qty . "',";
              $json .= "'name':'" . $detail->name . "'}";
              $j += 1;
            }
            $json .= "]}";
          }
          $json .= "]]";
          # code...
        }
        $id_trans_tenant = $id_transaksi . '-' . $cart->id_partner;
        $sql = "INSERT INTO `transaksi_detail_tenant`(`id_transaksi_tenant`, `id_menu`, `harga_satuan`, `qty`, `notes`,`variant`) VALUES (?,?,?,?,?,?)";
        if ($query = $this->conn->prepare($sql)) {
          $query->bind_param('siiiss', $id_trans_tenant, $cart->id_menu, $cart->harga_satuan, $cart->qty, $cart->notes, $json);
          $query->execute();
          //rest of code here
        } else {
          //error !! don't go further
          return FAILED_TO_CREATE_TRANSAKSI;
        }
        $query->close();
      }
      return TRANSAKSI_CREATED;
    } catch (Exception $e) {
      echo ($e);
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }


  public function insertDetailTransaksiAndroid($id_transaksi, $carts)
  {
    try {
      foreach ($carts as $cart) {
        $json = "['variant':[";
        $i = 0;
        if (empty($cart->variant)) {
          $json = "";
        } else {
          $variant = $cart->variant;
          foreach ($variant as $vars) {
            if ($i == 0) {
              $json .= "{";
            } else {
              $json .= ",{";
            }
            $json .= "'name':'" . $vars->name . "',";
            $json .= "'id':'" . $vars->id_variant . "',";
            $json .= "'tipe':'" . $vars->type . "',";
            $json .= "'detail':[";
            $dvariant = $vars->data_variant;
            $i += 1;
            $j = 0;
            foreach ($dvariant as $detail) {
              if ($j == 0) {
                $json .= "{";
              } else {
                $json .= ",{";
              }

              $json .= "'id':'" . $detail->id . "',";
              $json .= "'qty':'" . $detail->qty . "',";
              $json .= "'name':'" . $detail->name . "'}";
              $j += 1;
            }
            $json .= "]}";
          }
          $json .= "]]";
          # code...
        }
        if (!isset($cart->isConsignment)) {
          $cart->isConsignment = 0;
        }
        if (!isset($cart->serverID)) {
          $cart->serverID = 0;
        }
        $cart->bundle_id = (int)$cart->bundle_id;
        if (!isset($cart->bundle_id)) {
          $cart->bundle_id = 0;
        }
        $cart->bundle_qty = (int)$cart->bundle_qty;
        if (!isset($cart->bundle_qty)) {
          $cart->bundle_qty = 0;
        }
        $sql = "INSERT INTO detail_transaksi(id_transaksi, id_menu, harga_satuan, qty, notes, harga,variant,is_consignment, server_id, bundle_id, bundle_qty) VALUES (?, ?, ?, ?, ?, ?,?, ?,? ,?,?)";
        if ($query = $this->conn->prepare($sql)) {
          try {
            $query->bind_param('siiisisiiii', $id_transaksi, $cart->id_menu, $cart->harga_satuan, $cart->qty, $cart->notes, $cart->harga, $json, $cart->isConsignment, $cart->serverID, $cart->bundle_id, $cart->bundle_qty);
            $query->execute();
          } catch (Exception $e) {
            echo ($e);
            return FAILED_TO_CREATE_TRANSAKSI;
          }
          //rest of code here
        } else {
          //error !! don't go further
          return FAILED_TO_CREATE_TRANSAKSI;
        }
        $query->close();
      }
      return TRANSAKSI_CREATED;
    } catch (Exception $e) {
      echo ($e);
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }

  public function insertDetailTransaksiAndroidStatus($id_transaksi, $carts)
  {
    try {
      foreach ($carts as $cart) {
        $json = "['variant':[";
        $i = 0;
        if (empty($cart->variant)) {
          $json = "";
        } else {
          $variant = $cart->variant;
          foreach ($variant as $vars) {
            if ($i == 0) {
              $json .= "{";
            } else {
              $json .= ",{";
            }
            $json .= "'name':'" . $vars->name . "',";
            $json .= "'id':'" . $vars->id_variant . "',";
            $json .= "'tipe':'" . $vars->type . "',";
            $json .= "'detail':[";
            $dvariant = $vars->data_variant;
            $i += 1;
            $j = 0;
            foreach ($dvariant as $detail) {
              if ($j == 0) {
                $json .= "{";
              } else {
                $json .= ",{";
              }

              $json .= "'id':'" . $detail->id . "',";
              $json .= "'qty':'" . $detail->qty . "',";
              $json .= "'name':'" . $detail->name . "'}";
              $j += 1;
            }
            $json .= "]}";
          }
          $json .= "]]";
          # code...
        }
        $sql = "INSERT INTO detail_transaksi(id_transaksi, id_menu, harga_satuan, qty, qty_delivered, notes, harga,variant, status) VALUES (?, ?, ?, ?, ?, ?,?, ?,?)";
        if ($query = $this->conn->prepare($sql)) {
          try {
            $query->bind_param('siiiisisi', $id_transaksi, $cart->id_menu, $cart->harga_satuan, $cart->qty, $cart->qty_delivered, $cart->notes, $cart->harga, $json, $cart->status);
            $query->execute();
          } catch (Exception $e) {
            echo ($e);
            return FAILED_TO_CREATE_TRANSAKSI;
          }
          //rest of code here
        } else {
          //error !! don't go further
          return FAILED_TO_CREATE_TRANSAKSI;
        }
        $query->close();
      }
      return TRANSAKSI_CREATED;
    } catch (Exception $e) {
      echo ($e);
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }

  public function getIDTransaksi()
  {
    $permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $ix = substr(str_shuffle($permitted_chars), 0, 8);
    $truth = false;

    while (!$truth) {
      if (!$this->checkIDTransaksi($ix)) {
        $truth = true;
        return $ix;
      } else {
        $truth = false;
        $ix = substr(str_shuffle($permitted_chars), 0, 10);
      }
    }
    return USER_ALREADY_EXIST;
  }

  public function checkTakeaway($id)
  {
    $sql = "SELECT takeaway FROM transaksi WHERE id = ? AND takeaway > 0";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return TRANSAKSI_CREATED;
    } else {
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }

  public function checkDelivery($id)
  {
    $sql = "SELECT id FROM delivery WHERE transaksi_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return TRANSAKSI_CREATED;
    } else {
      return FAILED_TO_CREATE_TRANSAKSI;
    }
  }

  public function checkIDTransaksi($id)
  {
    // id    id_transaksi    id_menu    qty    note
    $sql = "SELECT id FROM transaksi WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->store_result();
    return ($stmt->num_rows > 0);
  }

  public function getTransaksiByID($id)
  {

    // $stmt = $this->conn->prepare("SELECT a.id, a.jam, a.phone, a.id_partner, a.no_meja, a.status, a.total ,a.tipe_bayar, a.promo, b.name, a.id_voucher, a.promo, c.is_queue, a.takeaway
    //                                     FROM transaksi a, partner b, meja c
    //                                     WHERE a.id = ? AND a.id_partner = b.id AND a.no_meja = c.idmeja AND a.id_partner = c.idpartner
    //                                     ORDER BY jam DESC");
    // $stmt = $this->conn->prepare("SELECT a.id, a.jam, a.phone, a.id_partner, a.no_meja, a.status, a.total ,a.tipe_bayar, a.promo, b.name, a.id_voucher, a.promo, c.is_queue, a.takeaway
    //                                      FROM transaksi a, partner b, meja c
    //                                      WHERE a.id = ? AND a.id_partner = b.id AND a.id_partner = c.idpartner
    //                                      ORDER BY jam DESC LIMIT 1");

    $stmt = $this->conn->prepare("SELECT a.id, a.jam, a.phone, a.id_partner, a.no_meja, a.status, a.total ,a.tipe_bayar, a.promo, b.name, a.id_voucher,a.id_voucher_redeemable,a.promo, c.is_queue, a.queue, a.takeaway, a.tax, a.service, a.charge_ewallet, a.charge_xendit, a.charge_ur, a.point
                                         FROM transaksi AS a JOIN partner AS b ON a.id_partner=b.id JOIN meja AS c ON a.id_partner = c.idpartner
                                         WHERE a.id = ?
                                         ORDER BY a.jam DESC LIMIT 1");

    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($id, $jam, $phone, $id_partner, $no_meja, $status, $total, $tipe_bayar, $promo, $nama_outlet, $id_voucher, $id_voucher_redeemable, $promo, $is_queue, $queue, $takeaway, $tax, $service, $charge_ewallet, $charge_xendit, $charge_ur, $point);
    $trx = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $trx[$i]['id'] = $id;
      $trx[$i]['jam'] = $jam;
      $trx[$i]['phone'] = $phone;
      $trx[$i]['id_partner'] = $id_partner;
      $trx[$i]['no_meja'] = $no_meja;
      $trx[$i]['status'] = $status;
      $trx[$i]['total'] = $total;
      $trx[$i]['id_voucher'] = $id_voucher;
      $trx[$i]['id_voucher_redeemable'] = $id_voucher_redeemable;
      $trx[$i]['tipe_bayar'] = $tipe_bayar;
      $trx[$i]['promo'] = $promo;
      $trx[$i]['nama_outlet'] = $nama_outlet;
      $trx[$i]['is_queue'] = $is_queue;
      $trx[$i]['queue'] = $queue;
      $trx[$i]['takeaway'] = $takeaway;
      $trx[$i]['tax'] = $tax;
      $trx[$i]['service'] = $service;
      $trx[$i]['charge_ewallet'] = $charge_ewallet;
      $trx[$i]['charge_xendit'] = $charge_xendit;
      $trx[$i]['charge_ur'] = $charge_ur;
      $trx[$i]['point'] = $point;
      $i++;
    }
    return $trx;
  }

  public function getTransaksiFoodcourtByID($id)
  {
    $stmt = $this->conn->prepare("SELECT a.id, a.jam, a.phone, a.id_partner, a.no_meja_foodcourt, a.status, a.total ,a.tipe_bayar, a.promo, b.name, a.id_voucher,a.id_voucher_redeemable,a.promo,a.takeaway,b.id_foodcourt, a.tax, a.service, a.charge_ewallet, a.charge_xendit, a.charge_ur
                                         FROM transaksi AS a JOIN partner AS b ON a.id_partner=b.id
                                         WHERE a.id = ? AND b.id_foodcourt IS NOT NULL
                                         ORDER BY a.jam DESC LIMIT 1");

    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($id, $jam, $phone, $id_partner, $no_meja_foodcourt, $status, $total, $tipe_bayar, $promo, $nama_outlet, $id_voucher, $id_voucher_redeemable, $promo, $takeaway, $id_foodcourt, $tax, $service, $charge_ewallet, $charge_xendit, $charge_ur);
    $trx = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $trx[$i]['id'] = $id;
      $trx[$i]['jam'] = $jam;
      $trx[$i]['phone'] = $phone;
      $trx[$i]['id_partner'] = $id_partner;
      $trx[$i]['no_meja_foodcourt'] = $no_meja_foodcourt;
      $trx[$i]['status'] = $status;
      $trx[$i]['total'] = $total;
      $trx[$i]['id_voucher'] = $id_voucher;
      $trx[$i]['id_voucher_redeemable'] = $id_voucher_redeemable;
      $trx[$i]['tipe_bayar'] = $tipe_bayar;
      $trx[$i]['promo'] = $promo;
      $trx[$i]['nama_outlet'] = $nama_outlet;
      $trx[$i]['takeaway'] = $takeaway;
      $trx[$i]['id_foodcourt'] = $id_foodcourt;
      $trx[$i]['tax'] = $tax;
      $trx[$i]['service'] = $service;
      $trx[$i]['charge_ewallet'] = $charge_ewallet;
      $trx[$i]['charge_xendit'] = $charge_xendit;
      $trx[$i]['charge_ur'] = $charge_ur;
      $i++;
    }
    return $trx;
  }

  public function getTransaksiQueueFoodcourtByID($id)
  {
    $stmt = $this->conn->prepare("SELECT a.id, a.jam, a.phone, a.id_partner, a.no_meja, a.status, a.total ,a.tipe_bayar, a.promo, b.name, a.id_voucher,a.id_voucher_redeemable,a.promo, c.is_queue, a.takeaway, b.id_foodcourt, a.tax, a.service, a.charge_ewallet, a.charge_xendit, a.charge_ur
                                         FROM transaksi AS a JOIN partner AS b ON a.id_partner=b.id JOIN meja AS c ON a.id_partner = c.idpartner
                                         WHERE a.id = ? AND b.id_foodcourt IS NOT NULL
                                         ORDER BY a.jam DESC LIMIT 1");

    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($id, $jam, $phone, $id_partner, $no_meja, $status, $total, $tipe_bayar, $promo, $nama_outlet, $id_voucher, $id_voucher_redeemable, $promo, $is_queue, $takeaway, $id_foodcourt, $tax, $service, $charge_ewallet, $charge_xendit, $charge_ur);
    $trx = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $trx[$i]['id'] = $id;
      $trx[$i]['jam'] = $jam;
      $trx[$i]['phone'] = $phone;
      $trx[$i]['id_partner'] = $id_partner;
      $trx[$i]['no_meja'] = $no_meja;
      $trx[$i]['status'] = $status;
      $trx[$i]['total'] = $total;
      $trx[$i]['id_voucher'] = $id_voucher;
      $trx[$i]['id_voucher_redeemable'] = $id_voucher_redeemable;
      $trx[$i]['tipe_bayar'] = $tipe_bayar;
      $trx[$i]['promo'] = $promo;
      $trx[$i]['nama_outlet'] = $nama_outlet;
      $trx[$i]['is_queue'] = $is_queue;
      $trx[$i]['takeaway'] = $takeaway;
      $trx[$i]['id_foodcourt'] = $id_foodcourt;
      $trx[$i]['tax'] = $tax;
      $trx[$i]['service'] = $service;
      $i++;
    }
    return $trx;
  }

  public function getDetailTrxByIDTrx($id)
  {
    $stmt = $this->conn->prepare("SELECT a.id, a.id_transaksi, a.id_menu, a.harga_satuan, a.qty, a.notes, a.harga, a.variant, a.status, b.nama, c.queue, c.takeaway FROM detail_transaksi a, menu b, transaksi c WHERE a.id_transaksi = ? AND a.id_menu = b.id AND a.id_transaksi = c.id");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($id, $id_transaksi, $id_menu, $harga_satuan, $qty, $notes, $harga, $variant, $status, $menu_nama, $queue, $takeaway);
    $dtrx = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $dtrx[$i]['id'] = $id;
      $dtrx[$i]['id_transaksi'] = $id_transaksi;
      $dtrx[$i]['id_menu'] = $id_menu;
      $dtrx[$i]['harga_satuan'] = $harga_satuan;
      $dtrx[$i]['qty'] = $qty;
      $dtrx[$i]['notes'] = $notes;
      $dtrx[$i]['harga'] = $harga;
      $dtrx[$i]['status'] = $status;
      $dtrx[$i]['menu_nama'] = $menu_nama;
      $dtrx[$i]['queue'] = $queue;
      $dtrx[$i]['takeaway'] = $takeaway;
      $dtrx[$i]['variant'] = $variant;
      $i++;
    }
    return $dtrx;
  }

  public function getVoucherById($code)
  {
    $stmt = $this->conn->prepare("SELECT * FROM voucher WHERE code = ? AND deleted=0");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $stmt->bind_result($id, $code, $title, $description, $discount, $category, $enabled, $valid_from, $valid_until, $total_usage, $prerequisite, $partnerid, $img, $deleted);
    $voucher = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $voucher[$i]['id'] = $id;
      $voucher[$i]['code'] = $code;
      $voucher[$i]['title'] = $title;
      $voucher[$i]['keterangan'] = $description;
      $voucher[$i]['discount'] = $discount;
      $voucher[$i]['category'] = $category;
      $voucher[$i]['enabled'] = $enabled;
      $voucher[$i]['tgl_mulai'] = $valid_from;
      $voucher[$i]['expired_date'] = $valid_until;
      $voucher[$i]['total_usage'] = $total_usage;
      $voucher[$i]['prerequisite'] = $prerequisite;
      $voucher[$i]['partnerID'] = $partnerid;
      $voucher[$i]['img'] = $img;
      $i++;
    }
    return $voucher;
  }

  public function getVoucherRedeemableById($code)
  {
    $stmt = $this->conn->prepare("SELECT * FROM membership_voucher WHERE code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $stmt->bind_result($id, $code, $title, $description, $discount, $category, $enabled, $valid_from, $valid_until, $total_usage, $prerequisite, $master_id, $img);
    $voucher = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $voucher[$i]['id'] = $id;
      $voucher[$i]['code'] = $code;
      $voucher[$i]['title'] = $title;
      $voucher[$i]['keterangan'] = $description;
      $voucher[$i]['discount'] = $discount;
      $voucher[$i]['category'] = $category;
      $voucher[$i]['enabled'] = $enabled;
      $voucher[$i]['tgl_mulai'] = $valid_from;
      $voucher[$i]['expired_date'] = $valid_until;
      $voucher[$i]['total_usage'] = $total_usage;
      $voucher[$i]['prerequisite'] = $prerequisite;
      $voucher[$i]['master_id'] = $master_id;
      $voucher[$i]['img'] = $img;
      $i++;
    }
    return $voucher;
  }

  public function getVoucher()
  {
    $stmt = $this->conn->prepare("SELECT * FROM voucher WHERE deleted=0");
    $stmt->execute();
    $stmt->bind_result($id, $code, $title, $description, $discount, $category, $enabled, $valid_from, $valid_until, $total_usage, $prerequisite, $partnerid, $img, $deleted);
    $voucher = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $voucher[$i]['id'] = $id;
      $voucher[$i]['code'] = $code;
      $voucher[$i]['title'] = $title;
      $voucher[$i]['keterangan'] = $description;
      $voucher[$i]['discount'] = $discount;
      $voucher[$i]['category'] = $category;
      $voucher[$i]['enabled'] = $enabled;
      $voucher[$i]['tgl_mulai'] = $valid_from;
      $voucher[$i]['expired_date'] = $valid_until;
      $voucher[$i]['total_usage'] = $total_usage;
      $voucher[$i]['prerequisite'] = $prerequisite;
      $voucher[$i]['partnerID'] = $partnerid;
      $voucher[$i]['img'] = $img;
      $i++;
    }
    return $voucher;
  }

  public function getVoucherRedeemable()
  {
    $stmt = $this->conn->prepare("SELECT * FROM membership_voucher");
    $stmt->execute();
    $stmt->bind_result($id, $code, $title, $description, $discount, $category, $enabled, $valid_from, $valid_until, $total_usage, $prerequisite, $master_id, $img);
    $voucher = array();
    $i = 0;
    while ($data = $stmt->fetch()) {
      $voucher[$i]['id'] = $id;
      $voucher[$i]['code'] = $code;
      $voucher[$i]['title'] = $title;
      $voucher[$i]['keterangan'] = $description;
      $voucher[$i]['discount'] = $discount;
      $voucher[$i]['category'] = $category;
      $voucher[$i]['enabled'] = $enabled;
      $voucher[$i]['tgl_mulai'] = $valid_from;
      $voucher[$i]['expired_date'] = $valid_until;
      $voucher[$i]['total_usage'] = $total_usage;
      $voucher[$i]['prerequisite'] = $prerequisite;
      $voucher[$i]['master_id'] = $master_id;
      $voucher[$i]['img'] = $img;
      $i++;
    }
    return $voucher;
  }

  public function getVoucherRedemption($phone, $code)
  {
    $stmt = $this->conn->prepare("SELECT COUNT(id) as total FROM transaksi WHERE phone=? AND id_voucher=?");
    $stmt->bind_param("ss", $phone, $code);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();

    return $total;
  }

  public function cekQtyDb($id_menu)
  {

    $stmt = $this->conn->prepare("SELECT nama, stock FROM menu WHERE id=? ");
    $stmt->bind_param("i", $id_menu);
    $stmt->execute();
    $stmt->bind_result($nama, $stock);
    $stmt->fetch();

    $a["nama"] = $nama;
    $a["stock"] = $stock;

    return $a;
  }



  public function checkOrders($phone, $voucher_id)
  {
    $stmt = $this->conn->prepare("SELECT COUNT(id) as total FROM transaksi WHERE phone=? AND id_voucher=? AND status<=2 AND status>=1");
    $stmt->bind_param("ss", $phone, $voucher_id);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();

    return $total;
  }

  public function checkUsage($voucher_id)
  {
    $stmt = $this->conn->prepare("SELECT COUNT(id) as total FROM transaksi WHERE id_voucher=? AND status<=2 AND status>=1");
    $stmt->bind_param("s", $voucher_id);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();

    return $total;
  }

  public function getVoucherMaxUsage($voucher_id)
  {
    $stmt = $this->conn->prepare("SELECT total_usage FROM `voucher` WHERE code = ? ");
    $stmt->bind_param("s", $voucher_id);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();

    return $total;
  }

  public function checkOrdersLoyalty($phone, $id_voucher_redeemable)
  {
    $stmt = $this->conn->prepare("SELECT COUNT(id) as total FROM transaksi WHERE phone=? AND id_voucher_redeemable=?");
    $stmt->bind_param("ss", $phone, $id_voucher_redeemable);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();

    return $total;
  }
  public function sendOTP($phone)
  {
    // $credentials = new Aws\Credentials\Credentials('AKIAJHNL4FI2EWEHCKSA', 'nszCCIfz4N1OThGtaK1Hxik1oXmqpCG3T7L+DrYZ');
    // $credentials = new Aws\Credentials\Credentials('AKIAI2XCN446J7DEJMNA', 'tYype/JmGkz3CvfuMm5zDflT1+QofnBsva9BYEFf');

    $SnSclient = new SnsClient([
      'profile' => 'default',
      'region' => 'us-west-2',
      'version' => '2010-03-31'
    ]);

    $otp = rand(1000, 9999);
    $message = "Your passcode is " . $otp . ".";
    $phoneNumber = $phone;
    try {
      $result_set = $SnSclient->SetSMSAttributes([
        'attributes' => [
          'DefaultSMSType' => 'Transactional',
        ],
      ]);
      $result = $SnSclient->publish([
        'Message' => $message,
        'PhoneNumber' => $phoneNumber
      ]);
    } catch (AwsException $e) {
      // output error message if fails
      error_log($e->getMessage());
    }

    return $otp;
  }
  public function getEmailByPhone($phone)
  {
    $stmt = $this->conn->prepare("SELECT email FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->bind_result($email);
    $stmt->fetch();

    return $email;
  }
  public function getEmailPartnerByPhone($phone)
  {
    $stmt = $this->conn->prepare("SELECT email FROM partner WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->bind_result($email);
    $stmt->fetch();

    return $email;
  }
  public function updateStatus($id, $enabled)
  {
    $stmt = $this->conn->prepare("UPDATE menu SET enabled = ?  WHERE id = ?");
    $stmt->bind_param("ii", $enabled, $id);
    if ($stmt->execute()) {
      return USER_UPDATED;
    } else {
      return USER_NOT_UPDATED;
    }
  }

  public function deleteMenu($id)
  {
    $stmt = $this->conn->prepare("DELETE FROM menu WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
      return REMOVE_MENU;
    } else {
      return FAILED_TO_REMOVE_MENU;
    }
  }

  public function getAllPaymentIdPartner($id)
  {
    $stmt = $this->conn->prepare("SELECT id, id_ovo, id_dana, id_linkaja FROM partner WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($partnerId, $ovo, $dana, $linkaja);
    $payment = array();
    if ($data = $stmt->fetch()) {
      $payment['id'] = $partnerId;
      $payment['id_ovo'] = $ovo;
      $payment['id_dana'] = $dana;
      $payment['id_linkaja'] = $linkaja;

      return $payment;
    } else {
      return null;
    }
  }

  public function getTaxEnabled($id)
  {
    $stmt = $this->conn->prepare("SELECT tax FROM partner WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($tax);
    $stmt->fetch();
    $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
    if ($tax == 1 || $tax == '1') {
      $ppn = mysqli_query($db_conn, "SELECT value FROM settings WHERE name='ppn';");
      while ($row = mysqli_fetch_assoc($ppn)) {
        $stax = $row['value'];
      }
    } else {
      $stax = 0;
    }
    return $stax;
  }
  public function getTax($id)
  {
    $stmt = $this->conn->prepare("SELECT tax FROM partner WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($tax);
    $stmt->fetch();

    return $tax;
  }
  public function getService($id)
  {
    $stmt = $this->conn->prepare("SELECT service FROM partner WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($service);
    $stmt->fetch();

    return $service;
  }

  public function getChargeEwallet()
  {
    $stmt = $this->conn->prepare("SELECT value FROM settings WHERE name = 'charge_ewallet'");
    $stmt->execute();
    $stmt->bind_result($value);
    $stmt->fetch();

    return $value;
  }

  public function getChargeXendit()
  {
    $stmt = $this->conn->prepare("SELECT value FROM settings WHERE name = 'charge_xendit'");
    $stmt->execute();
    $stmt->bind_result($value);
    $stmt->fetch();

    return $value;
  }

  public function getChargeUr($status, $hide)
  {
    // echo($hide);
    if ($status == "FULL") {
      $stmt = $this->conn->prepare("SELECT value FROM settings WHERE name = 'charge_ur'");
      $stmt->execute();
      // $stmt->bind_result($value);

      if ($hide == 1) {
        // echo ("masuk if");
        $value = 0;
      } else {
        // echo("masuk else");
        $stmt->bind_result($value);
      }
      $stmt->fetch();
    } else {
      $value = 0;
    }
    // echo ($value);
    return $value;
  }
  public function getStatus($id)
  {
    $stmt = $this->conn->prepare("SELECT master.status FROM `master`
    JOIN partner ON master.id = partner.id_master
    WHERE partner.id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();

    return $status;
  }


  public function createTagihan($email)
  {
    $stmt = $this->conn->prepare("SELECT id,name,phone,tax,service,email,saldo_ewallet FROM partner WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($id, $name, $phone, $tax, $service, $email, $saldo_ewallet);
      $stmt->fetch();
      $dateNow = date('Y-m-d H:i:s');
      $first_day_last_month = date('01/M/Y', strtotime('-1 month'));
      $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
      $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
      $dateLastDb = date('Y-m-t', strtotime('-1 month'));
      $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
      $transaksi = mysqli_query($db_conn, "SELECT charge_ur FROM transaksi WHERE id_partner='$id' AND transaksi.status<=2 and transaksi.status>=1 AND DATE(jam) BETWEEN '$dateFirstDb' AND '$dateLastDb'");
      $sumCharge_ur = 0;
      while ($row = mysqli_fetch_assoc($transaksi)) {
        $sumCharge_ur += $row['charge_ur'];
      }
      $total_penagihan = 0;
      $total_penagihan = $saldo_ewallet - $sumCharge_ur;
      if ($total_penagihan >= 0) {
        $total_penagihan = $total_penagihan;
        $id_external = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 1, 10);
        $amount = (int)$total_penagihan;
        // $amount = $amount;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://' . $_ENV['XENDIT_URL'] . '/qr_codes');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "external_id=" . $id_external . "&type=DYNAMIC&callback_url=https://apis.ur-hub.com/xendit/qris/Callback.php&nominal=" . $amount . "&amount=" . $amount);
        curl_setopt($ch, CURLOPT_USERPWD, 'xnd_production_6Le73KckkVkYENtIBukFMOnJphL0beuV4egkDNVziNIyHhmqfvQG1ZBiqNTlJ' . ':' . '');

        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
          echo 'Error:' . curl_error($ch);
        }

        curl_close($ch);
        $data = json_decode($result);
        $qr_string = mysqli_real_escape_string($db_conn, trim($data->qr_string));
        $tagihan = mysqli_query($db_conn, "INSERT INTO tagihan_ur (id_external,id_partner,saldo_ewallet,charge_ur,amount,status,qr_string,created_at) VALUE  ('$id_external','$id',$saldo_ewallet,$sumCharge_ur,$total_penagihan,'PENDING','$qr_string','$dateNow');");
      } else {
        $total_penagihan = 0;
        $id_external = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 1, 10);
        $tagihan = mysqli_query($db_conn, "INSERT INTO tagihan_ur (id_external,id_partner,saldo_ewallet,charge_ur,amount,status,created_at) VALUE  ('$id_external','$id',$saldo_ewallet,$sumCharge_ur,$total_penagihan,'COMPLETED','$dateNow')");
      }
      if ($tagihan) {
        return TRANSAKSI_CREATED;
      } else {
        return USER_NOT_CREATED;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }

  public function mailingAccountingFoodcourt($email)
  {
    $stmt = $this->conn->prepare("SELECT id,name,phone,tax,service,email FROM foodcourt WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($id, $name, $phone, $tax, $service, $email);
      $stmt->fetch();
      function tgl_indo($tanggal)
      {
        $bulan = array(
          1 =>   'Januari',
          'Februari',
          'Maret',
          'April',
          'Mei',
          'Juni',
          'Juli',
          'Agustus',
          'September',
          'Oktober',
          'November',
          'Desember'
        );
        return $bulan[(int)$tanggal];
      }
      $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
      function rupiah($angka)
      {

        $hasil_rupiah = "Rp. " . number_format($angka, 0, ',', '.');
        return $hasil_rupiah;
      }
      $dateNow = date('d/M/Y');
      $first_day_last_month = date('01/M/Y', strtotime('-1 month'));
      $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
      $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
      $dateLastDb = date('Y-m-t', strtotime('-1 month'));
      // $first_day_last_month = date('01/M/Y');
      // $last_day_last_month  = date('t/M/Y');
      // $dateFirstDb = date('Y-m-01');
      // $dateLastDb = date('Y-m-t');
      $menu = mysqli_query($db_conn, "SELECT * FROM menu JOIN partner ON menu.id_partner=partner.id WHERE partner.id_foodcourt='$id';");
      $transaksi = mysqli_query($db_conn, "SELECT total,promo,tax,service,status,tipe_bayar,charge_ewallet,charge_ur FROM transaksi_foodcourt WHERE id_foodcourt='$id' AND transaksi_foodcourt.status <= 2 AND transaksi_foodcourt.status>=1 AND DATE(created_at) BETWEEN '$dateFirstDb' AND '$dateLastDb'");

      $total = 0;
      $promo = 0;
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $charge_ewallet = 0;
      $taxtype = 0;
      $sumCharge_ur = 0;
      while ($row = mysqli_fetch_assoc($transaksi)) {
        // if($row['tipe_bayar']=='5'|| $row['tipe_bayar']=='7' || $row['tipe_bayar']==5 || $row['tipe_bayar']==7){
        //   $total += ($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)));
        //   $promo += $row['promo'];
        // }else{
        //   $total += ($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))-ceil(ceil(($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))*($row['charge_ewallet']/100))+(ceil(ceil(($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))*($row['charge_ewallet']/100))*($row['tax']/100))));

        // }
        // $promo += $row['promo'];
        $sumCharge_ur += $row['charge_ur'];
        $charge_ewallet = $row['charge_ewallet'];
        $taxtype = $row['tax'];
        $servicetype = $row['service'];
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $withTax = 0;
        if ($row['tipe_bayar'] == 1 || $row['tipe_bayar'] == '1') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $ovo += $withTax;
        } else if ($row['tipe_bayar'] == 2 || $row['tipe_bayar'] == '2') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $gopay += $withTax;
        } else if ($row['tipe_bayar'] == 3 || $row['tipe_bayar'] == '3') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $dana += $withTax;
        } else if ($row['tipe_bayar'] == 4 || $row['tipe_bayar'] == '4') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $linkaja += $withTax;
        } else if ($row['tipe_bayar'] == 5 || $row['tipe_bayar'] == '5') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($row['tipe_bayar'] == 6 || $row['tipe_bayar'] == '6') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $sakuku += $withTax;
        } else if ($row['tipe_bayar'] == 7 || $row['tipe_bayar'] == '7') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($row['tipe_bayar'] == 8 || $row['tipe_bayar'] == '8') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
      }
      $hargaPokok = 0;
      $menu2 = mysqli_query($db_conn, "SELECT * FROM menu JOIN partner ON menu.id_partner=partner.id WHERE partner.id_foodcourt='$id';");
      while ($rowcheck1 = mysqli_fetch_assoc($menu2)) {
        $idMenuCheck = $rowcheck1['id'];
        $hargaPokokAwal = $rowcheck1['hpp'];
        $detailcheck = mysqli_query($db_conn, "SELECT SUM(qty) AS qtytotal FROM transaksi_detail_tenant join transaksi_tenant ON transaksi_detail_tenant.id_transaksi_tenant = transaksi_tenant.id JOIN transaksi_foodcourt ON transaksi_tenant.id_transaksi_fc=transaksi_foodcourt.id WHERE id_menu='$idMenuCheck' AND transaksi_foodcourt.status <= 2 AND transaksi_foodcourt.status>=1 AND DATE(created_at) BETWEEN '$dateFirstDb' AND '$dateLastDb';");
        while ($rowpph = mysqli_fetch_assoc($detailcheck)) {
          $qtyJual = $rowpph['qtytotal'];
        }
        $hargaPokok += $hargaPokokAwal * $qtyJual;
      }

      // $subtotal = (($total + ($total * 0.1)) - ($hargaPokok + $promo)) - ($total * 0.1);
      $subtotal = (($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) - ceil($sumCharge_ur);
      // if($tax==1){
      //   $subtotal = ($total + ($total * 0.1));
      // }
      // if($service!=0){
      //   $subtotal = ($total + ($total * ($service/100)));
      // }
      $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
              <head>
                <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                <meta name="x-apple-disable-message-reformatting" />
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <title></title>
                <style type="text/css" rel="stylesheet" media="all">

                @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                body {
                  width: 100% !important;
                  height: 100%;
                  margin: 0;
                  -webkit-text-size-adjust: none;
                }

                a {
                  color: #3869D4;
                }

                a img {
                  border: none;
                }

                td {
                  word-break: break-word;
                }

                .preheader {
                  display: none !important;
                  visibility: hidden;
                  mso-hide: all;
                  font-size: 1px;
                  line-height: 1px;
                  max-height: 0;
                  max-width: 0;
                  opacity: 0;
                  overflow: hidden;
                }

                body,
                td,
                th {
                  font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                }

                h1 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 22px;
                  font-weight: bold;
                  text-align: left;
                }

                h2 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 16px;
                  font-weight: bold;
                  text-align: left;
                }

                h3 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 14px;
                  font-weight: bold;
                  text-align: left;
                }

                td,
                th {
                  font-size: 16px;
                }

                p,
                ul,
                ol,
                blockquote {
                  margin: .4em 0 1.1875em;
                  font-size: 16px;
                  line-height: 1.625;
                }

                p.sub {
                  font-size: 13px;
                }

                .align-right {
                  text-align: right;
                }

                .align-left {
                  text-align: left;
                }

                .align-center {
                  text-align: center;
                }
                .button {
                  background-color: #3869D4;
                  border-top: 10px solid #3869D4;
                  border-right: 18px solid #3869D4;
                  border-bottom: 10px solid #3869D4;
                  border-left: 18px solid #3869D4;
                  display: inline-block;
                  text-decoration: none;
                  border-radius: 3px;
                  box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                  -webkit-text-size-adjust: none;
                  box-sizing: border-box;
                }

                .button--green {
                  background-color: #22BC66;
                  border-top: 10px solid #22BC66;
                  border-right: 18px solid #22BC66;
                  border-bottom: 10px solid #22BC66;
                  border-left: 18px solid #22BC66;
                }

                .button--red {
                  background-color: #FF6136;
                  border-top: 10px solid #FF6136;
                  border-right: 18px solid #FF6136;
                  border-bottom: 10px solid #FF6136;
                  border-left: 18px solid #FF6136;
                }

                @media only screen and (max-width: 500px) {
                  .button {
                    width: 100% !important;
                    text-align: center !important;
                  }
                }

                .attributes {
                  margin: 0 0 21px;
                }

                .attributes_content {
                  background-color: #F4F4F7;
                  padding: 16px;
                }

                .attributes_item {
                  padding: 0;
                }

                .related {
                  width: 100%;
                  margin: 0;
                  padding: 25px 0 0 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .related_item {
                  padding: 10px 0;
                  color: #CBCCCF;
                  font-size: 15px;
                  line-height: 18px;
                }

                .related_item-title {
                  display: block;
                  margin: .5em 0 0;
                }

                .related_item-thumb {
                  display: block;
                  padding-bottom: 10px;
                }

                .related_heading {
                  border-top: 1px solid #CBCCCF;
                  text-align: center;
                  padding: 25px 0 10px;
                }

                .discount {
                  width: 100%;
                  margin: 0;
                  padding: 24px;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #F4F4F7;
                  border: 2px dashed #CBCCCF;
                }

                .discount_heading {
                  text-align: center;
                }

                .discount_body {
                  text-align: center;
                  font-size: 15px;
                }

                .social {
                  width: auto;
                }

                .social td {
                  padding: 0;
                  width: auto;
                }

                .social_icon {
                  height: 20px;
                  margin: 0 8px 10px 8px;
                  padding: 0;
                }

                .purchase {
                  width: 100%;
                  margin: 0;
                  padding: 35px 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .purchase_content {
                  width: 100%;
                  margin: 0;
                  padding: 25px 0 0 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .purchase_item {
                  padding: 10px 0;
                  color: #51545E;
                  font-size: 15px;
                  line-height: 18px;
                }

                .purchase_heading {
                  padding-bottom: 8px;
                  border-bottom: 1px solid #EAEAEC;
                }

                .purchase_heading p {
                  margin: 0;
                  color: #85878E;
                  font-size: 12px;
                }

                .purchase_footer {
                  padding-top: 15px;
                  border-top: 1px solid #EAEAEC;
                }

                .purchase_total {
                  margin: 0;
                  text-align: right;
                  font-weight: bold;
                  color: #333333;
                }

                .purchase_total--label {
                  padding: 0 15px 0 0;
                }

                body {
                  background-color: #F4F4F7;
                  color: #51545E;
                }

                p {
                  color: #51545E;
                }

                p.sub {
                  color: #6B6E76;
                }

                .email-wrapper {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #F4F4F7;
                }

                .email-content {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }


                .email-masthead {
                  padding: 25px 0;
                  text-align: center;
                }

                .email-masthead_logo {
                  width: 94px;
                }

                .email-masthead_name {
                  font-size: 16px;
                  font-weight: bold;
                  color: #A8AAAF;
                  text-decoration: none;
                  text-shadow: 0 1px 0 white;
                }
                .email-body {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #FFFFFF;
                }

                .email-body_inner {
                  width: 570px;
                  margin: 0 auto;
                  padding: 0;
                  -premailer-width: 570px;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #FFFFFF;
                }

                .email-footer {
                  width: 570px;
                  margin: 0 auto;
                  padding: 0;
                  -premailer-width: 570px;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  text-align: center;
                }

                .email-footer p {
                  color: #6B6E76;
                }

                .body-action {
                  width: 100%;
                  margin: 30px auto;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  text-align: center;
                }

                .body-sub {
                  margin-top: 25px;
                  padding-top: 25px;
                  border-top: 1px solid #EAEAEC;
                }

                .content-cell {
                  padding: 35px;
                }

                @media only screen and (max-width: 600px) {
                  .email-body_inner,
                  .email-footer {
                    width: 100% !important;
                  }
                }

                @media (prefers-color-scheme: dark) {
                  body,
                  .email-body,
                  .email-body_inner,
                  .email-content,
                  .email-wrapper,
                  .email-masthead,
                  .email-footer {
                    background-color: #333333 !important;
                    color: #FFF !important;
                  }
                  p,
                  ul,
                  ol,
                  blockquote,
                  h1,
                  h2,
                  h3 {
                    color: #FFF !important;
                  }
                  .attributes_content,
                  .discount {
                    background-color: #222 !important;
                  }
                  .email-masthead_name {
                    text-shadow: none !important;
                  }
                }
                </style>
              </head>
              <body>
                <span class="preheader">Laporan Keuangan Bulanan Periode ' . $first_day_last_month . ' Sampai Dengan ' . $last_day_last_month . '</span>
                <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                  <tr>
                    <td align="center">
                      <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <!-- <tr>
                          <td class="email-masthead">
                            <a href="https://example.com" class="f-fallback email-masthead_name">
                            UR HUB
                          </a>
                          </td>
                        </tr> -->

                        <tr>
                          <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell">
                                <div class="align-center"><img  src="https://ur-hub.s3.us-west-2.amazonaws.com/assets/logo/logo.png"></div>
                                    <h3 class="align-right">Tanggal:' . $dateNow . '</h3>
                                  <div class="f-fallback">
                                    <h1>Hi ' . $name . ',</h1>

                                    <table class="discount" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                            <p>Pendapatan Bulan ' . tgl_indo(date('m', strtotime('-1 month'))) . '</p>
                                          <h1 class="f-fallback discount_heading">' . rupiah($subtotal) . '</h1>
                                          <p>Periode ' . $first_day_last_month . ' Sampai Dengan ' . $last_day_last_month . '</p>
                                        </td>
                                      </tr>
                                    </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                          <td>
                                            <h3>Rincian Pendapatan</h3></td>
                                          <td>
                                        </tr>
                                        <tr>
                                          <td colspan="2">

                                            <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                            ';
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $charge_ewallet = 0;
      $taxtype = 0;
      $sumCharge_ur = 0;
      $tipe_bayar = mysqli_query($db_conn, "SELECT total, promo ,tax,service,status,tipe_bayar,charge_ewallet,charge_ur FROM transaksi_foodcourt WHERE id_foodcourt='$id' AND transaksi_foodcourt.status <= 2 AND transaksi_foodcourt.status >=1 AND DATE(created_at) BETWEEN '$dateFirstDb' AND '$dateLastDb'");
      while ($rowtypeBayar = mysqli_fetch_assoc($tipe_bayar)) {
        // $totalType += $rowtypeBayar['total'];
        // $subtotal += $total;
        // if($tax==1){
        //   // $subtotal = ($total + ($total * 0.1));
        //   $totalType = ($rowtypeBayar['tottotal']+ ($rowtypeBayar['tottotal'] * 0.1));
        // }
        // if($service!=0){
        //   // $subtotal = ($total + ($total * ($service/100)));
        //   $totalType = ($rowtypeBayar['tottotal']+($rowtypeBayar['tottotal']*($service/100)));
        // }
        $sumCharge_ur = $rowtypeBayar['charge_ur'];
        $charge_ewallet = $rowtypeBayar['charge_ewallet'];
        $taxtype = $rowtypeBayar['tax'];
        $servicetype = $rowtypeBayar['service'];
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $withTax = 0;
        if ($rowtypeBayar['tipe_bayar'] == 1 || $rowtypeBayar['tipe_bayar'] == '1') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $ovo += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 2 || $rowtypeBayar['tipe_bayar'] == '2') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $gopay += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 3 || $rowtypeBayar['tipe_bayar'] == '3') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $dana += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 4 || $rowtypeBayar['tipe_bayar'] == '4') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $linkaja += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 5 || $rowtypeBayar['tipe_bayar'] == '5') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 6 || $rowtypeBayar['tipe_bayar'] == '6') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $sakuku += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 7 || $rowtypeBayar['tipe_bayar'] == '7') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 8 || $rowtypeBayar['tipe_bayar'] == '8') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
        // if($rowtypeBayar['type_bayar']=='5'|| $rowtypeBayar['type_bayar']=='7' || $rowtypeBayar['type_bayar']==5 || $rowtypeBayar['type_bayar']==7){
        //   $totalType += ($rowtypeBayar['total']+($rowtypeBayar['total']*($rowtypeBayar['tax']/100))+($rowtypeBayar['total']*($rowtypeBayar['service']/100)));
        //   $promoType += $rowtypeBayar['promo'];
        // }else{
        //   $totalType+= ($rowtypeBayar['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))+(($row['total']*($row['charge_ewallet']/100))*($row['tax']/100));
        //   $promoType += $rowtypeBayar['promo'];
        // }
        // $typeCode = $rowtypeBayar['tipe_bayar'];
        // switch ($typeCode) {
        //   case 1:
        //     $type = 'OVO';
        //     break;
        //   case 2:
        //     $type = 'GOPAY';
        //     break;
        //   case 3:
        //     $type = 'DANA';
        //     break;
        //   case 4:
        //     $type = 'T-CASH';
        //     break;
        //   case 5:
        //     $type = 'TUNAI/DEBIT';
        //     break;
        //   case 6:
        //     $type = 'SAKUKU';
        //     break;
        //   case 7:
        //     $type = 'CREDIT CARD';
        //     break;
        // }

      }

      //   <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">LINK AJA</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.rupiah($linkaja).'</span></td>
      // </tr>
      // <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">SAKUKU</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.rupiah($sakuku).'</span></td>
      // </tr>

      $html .= '<tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">E-wallet</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                      </tr>
                                                      <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">OVO</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($ovo) . '</span></td>
                                                      </tr>
                                                      <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">GOPAY</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($gopay) . '</span></td>
                                                      </tr>
                                                        <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($dana) . '</span></td>
                                                      </tr>
                                                        <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">LinkAja</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($linkaja) . '</span></td>
                                                      </tr>
                                                        <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($sakuku) . '</span></td>
                                                      </tr>
                                                      <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">Charge E-Wallet (' . $charge_ewallet . '% + ' . $taxtype . '%)</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah(ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $taxtype / 100))) . '</span></td>
                                                    </tr>
                                                    <tr>
                                                    <td width="80%" class="purchase_item"><span class="f-fallback">Total E-Wallet</span></td>
                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah(($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $taxtype / 100)))) . '</span></td>
                                                  </tr>
                                                      ';
      $html .= '
                                                  <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                      </tr>
                                                  <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">Non E-wallet</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                      </tr>
                                                      <tr>
                                                        <td width="80%" class="purchase_item"><span class="f-fallback">CASH</span></td>
                                                        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($tunaiDebit) . '</span></td>
                                                      </tr>
                                                      <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">CREDIT CARD</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($creditCard) . '</span></td>
                                                    </tr>
                                                    <tr>
                                                      <td width="80%" class="purchase_item"><span class="f-fallback">DEBIT CARD</span></td>
                                                      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($debitCard) . '</span></td>
                                                    </tr>
                                                    <tr>
                                                    <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                  </tr>
                                                  <tr>
                                                  <td width="80%" class="purchase_item"><span class="f-fallback">SUBTOTAL</span></td>
                                                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah(($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $taxtype / 100))) + $tunaiDebit + $creditCard + $debitCard) . '</span></td>
                                                </tr>
                                                <tr>
                                                  <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                                </tr>
                                                <tr>
                                                <td width="80%" class="purchase_item"><span class="f-fallback">Convenience Fee</span></td>
                                                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah($sumCharge_ur) . '</span></td>
                                              </tr>
                                              <tr>
                                              <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                                              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                                            </tr>
                                            <tr>
                                                <td width="80%" class="purchase_item"><span class="f-fallback">TOTAL</span></td>
                                                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah((($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) - ceil($sumCharge_ur)) . '</span></td>
                                              </tr>
                                                      ';


      $html .= '</table>
                                                                                </td>
                                                                              </tr>
                                                                            </table>

                                                                          <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                                                            <tr>
                                                                              <td>
                                                                                <h3>Menu Terlaris</h3></td>
                                                                              <td>
                                                                            </tr>
                                                                            <tr>
                                                                              <td colspan="2">

                                                                                <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                                                                  <tr>
                                                                                    <th class="purchase_heading" align="left">
                                                                                      <p class="f-fallback">Menu</p>
                                                                                    </th>
                                                                                    <th class="purchase_heading" align="right">
                                                                                      <p class="f-fallback">Amount</p>
                                                                                    </th>
                                                                                  </tr>';
      $fav = mysqli_query($db_conn, "SELECT menu.nama,SUM(transaksi_detail_tenant.qty) AS qty FROM menu join transaksi_detail_tenant on menu.id=transaksi_detail_tenant.id_menu join transaksi_tenant on transaksi_detail_tenant.id_transaksi_tenant = transaksi_tenant.id JOIN transaksi_foodcourt ON transaksi_tenant.id_transaksi_fc=transaksi_foodcourt.id JOIN partner ON transaksi_tenant.id_partner = partner.id WHERE partner.id_foodcourt= '$id' AND transaksi_foodcourt.status<=2 AND transaksi_foodcourt.status>=1  AND DATE(transaksi_foodcourt.created_at) BETWEEN '$dateFirstDb' AND '$dateLastDb' GROUP BY menu.nama ORDER BY qty DESC LIMIT 5");
      while ($rowMenu = mysqli_fetch_assoc($fav)) {
        $namaMenu = $rowMenu['nama'];
        $qtyMenu = $rowMenu['qty'];
        $html .= '<tr>
                                                                                    <td width="80%" class="purchase_item"><span class="f-fallback">' . $namaMenu . '</span></td>
                                                                                    <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">x ' . $qtyMenu . '</span></td>
                                                                                  </tr>';
      }
      $html .= '</table>
                                                                              </td>
                                                                            </tr>
                                                                          </table>


                                    <p>Hormat Kami,
                                      <br>UR - Easy & Quick Order</p>

                                    <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                          <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                            <tr>
                                              <td align="center">
                                                <a href="https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdfFoodcourt.php?id=' . md5($id) . '" class="f-fallback button button--blue" target="_blank" style = "color:#fff">Download Full PDF</a>
                                              </td>
                                            </tr>
                                          </table>
                                        </td>
                                      </tr>
                                    </table>
                                    <!-- Sub copy -->
                                    <table class="body-sub" role="presentation">
                                      <tr>
                                        <td>
                                        <p class="f-fallback sub">Need a printable copy for your records?</strong> You can <a href="https://apis.ur-hub.com/qr/v2/csv/xlsFoodcourt.php?id=' . md5($id) . '">download a Xls version</a>.</p>
                                        </td>
                                      </tr>
                                    </table>
                                  </div>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell" align="center">
                                  <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                  <p class="f-fallback sub align-center">
                                    PT. Rahmat Tuhan Lestari
                                    <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir, Kota Bandung
                                    <br>Jawa Barat 40221
                                  </p>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
              </body>
            </html>';
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Laporan Keuangan",
        // "<div>Hai, " . $name . " </div>
        // // <>
        // <br>
        // <br>Laporan Keuangan
        // <br>
        // <br><a href='https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdf.php?id=" . md5(id) . "'>click here</a>
        // <br>
        // <br>
        // Jika anda merasa tidak melakukan request silahkan abaikan pesan ini.
        // <br>
        // <br>
        // Hormat Kami,
        // <br><br>
        // Ur Hub."
        $html
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }

  public function mailingPerDayFoodcourt($email)
  {
    $stmt = $this->conn->prepare("SELECT id,name, phone,email,jam_buka,jam_tutup,tax,service FROM foodcourt WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
      $stmt->bind_result($id, $name, $phone, $email, $jamBuka, $jamTutup, $tax, $service);
      $stmt->fetch();

      $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
      function rupiah($angka)
      {
        $angka = ceil($angka);
        $hasil_rupiah = "Rp. " . number_format($angka, 0, ',', '.');
        return $hasil_rupiah;
      }
      $dateNow = date('d/M/Y');
      $dateNowDb = date('Y-m-d');
      // $first_day_last_month = date('01/M/Y', strtotime('-1 month'));
      // $last_day_last_month  = date('t/M/Y', strtotime('-1 month'));
      // $dateFirstDb = date('Y-m-01', strtotime('-1 month'));
      // $dateLastDb = date('Y-m-t', strtotime('-1 month'));
      $menu = mysqli_query($db_conn, "SELECT * FROM menu JOIN partner ON menu.id_partner=partner.id WHERE partner.id_foodcourt='$id';");
      $transaksi = mysqli_query($db_conn, "SELECT total,promo,tax,service,status,tipe_bayar,charge_ewallet,charge_ur FROM transaksi_foodcourt WHERE id_foodcourt='$id' AND transaksi_foodcourt.status <= 2 AND transaksi_foodcourt.status >=1 AND DATE(created_at)='$dateNowDb' AND TIME(created_at) BETWEEN '$jamBuka' AND '$jamTutup'");
      $total = 0;
      $promo = 0;
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $charge_ewallet = 0;
      $taxtype = 0;
      $sumCharge_ur = 0;
      while ($row = mysqli_fetch_assoc($transaksi)) {
        // if($row['tipe_bayar']=='5'|| $row['tipe_bayar']=='7' || $row['tipe_bayar']==5 || $row['tipe_bayar']==7){
        //   $total += ($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)));
        //   $promo += $row['promo'];
        // }else{
        //   $total += ($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))-ceil(ceil(($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))*($row['charge_ewallet']/100))+(ceil(ceil(($row['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))*($row['charge_ewallet']/100))*($row['tax']/100))));
        // }
        // $promo += $row['promo'];
        $sumCharge_ur += $row['charge_ur'];
        $charge_ewallet = $row['charge_ewallet'];
        $taxtype = $row['tax'];
        $servicetype = $row['service'];
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $withTax = 0;
        if ($row['tipe_bayar'] == 1 || $row['tipe_bayar'] == '1') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $ovo += $withTax;
        } else if ($row['tipe_bayar'] == 2 || $row['tipe_bayar'] == '2') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $gopay += $withTax;
        } else if ($row['tipe_bayar'] == 3 || $row['tipe_bayar'] == '3') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $dana += $withTax;
        } else if ($row['tipe_bayar'] == 4 || $row['tipe_bayar'] == '4') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $linkaja += $withTax;
        } else if ($row['tipe_bayar'] == 5 || $row['tipe_bayar'] == '5') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($row['tipe_bayar'] == 6 || $row['tipe_bayar'] == '6') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $sakuku += $withTax;
        } else if ($row['tipe_bayar'] == 7 || $row['tipe_bayar'] == '7') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($row['tipe_bayar'] == 8 || $row['tipe_bayar'] == '8') {
          $countService = ceil(($row['total'] - $row['promo']) * ($row['service'] / 100));
          $withService = ($row['total'] - $row['promo']) + $countService + $row['charge_ur'];
          $countTax = ceil($withService * ($row['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
      }
      $hargaPokok = 0;
      $menu2 = mysqli_query($db_conn, "SELECT * FROM menu JOIN partner ON menu.id_partner=partner.id WHERE partner.id_foodcourt='$id'");
      while ($rowcheck1 = mysqli_fetch_assoc($menu2)) {
        $idMenuCheck = $rowcheck1['id'];
        $hargaPokokAwal = $rowcheck1['hpp'];
        $detailcheck = mysqli_query($db_conn, "SELECT SUM(qty) AS qtytotal FROM transaksi_detail_tenant join transaksi_tenant ON transaksi_detail_tenant.id_transaksi_tenant = transaksi_tenant.id JOIN transaksi_foodcourt ON transaksi_tenant.id_transaksi_fc=transaksi_foodcourt.id WHERE id_menu='$idMenuCheck' AND transaksi_foodcourt.status <= 2 AND transaksi_foodcourt.status >=1 AND DATE(created_at)='$dateNowDb' AND TIME(created_at) BETWEEN '$jamBuka' AND '$jamTutup';");
        while ($rowpph = mysqli_fetch_assoc($detailcheck)) {
          $qtyJual = $rowpph['qtytotal'];
        }
        $hargaPokok += $hargaPokokAwal * $qtyJual;
      }

      // $subtotal = (($total + ($total * 0.1)) - ($hargaPokok + $promo)) - ($total * 0.1);
      $subtotal = (($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) - ceil($sumCharge_ur);
      // if($tax==1){
      //   $subtotal = ($total + ($total * 0.1));
      // }
      // if($service!=0){
      //   $subtotal = ($total + ($total * ($service/100)));
      // }
      $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
              <head>
                <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                <meta name="x-apple-disable-message-reformatting" />
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <title></title>
                <style type="text/css" rel="stylesheet" media="all">

                @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");
                body {
                  width: 100% !important;
                  height: 100%;
                  margin: 0;
                  -webkit-text-size-adjust: none;
                }

                a {
                  color: #3869D4;
                }

                a img {
                  border: none;
                }

                td {
                  word-break: break-word;
                }

                .preheader {
                  display: none !important;
                  visibility: hidden;
                  mso-hide: all;
                  font-size: 1px;
                  line-height: 1px;
                  max-height: 0;
                  max-width: 0;
                  opacity: 0;
                  overflow: hidden;
                }

                body,
                td,
                th {
                  font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
                }

                h1 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 22px;
                  font-weight: bold;
                  text-align: left;
                }

                h2 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 16px;
                  font-weight: bold;
                  text-align: left;
                }

                h3 {
                  margin-top: 0;
                  color: #333333;
                  font-size: 14px;
                  font-weight: bold;
                  text-align: left;
                }

                td,
                th {
                  font-size: 16px;
                }

                p,
                ul,
                ol,
                blockquote {
                  margin: .4em 0 1.1875em;
                  font-size: 16px;
                  line-height: 1.625;
                }

                p.sub {
                  font-size: 13px;
                }

                .align-right {
                  text-align: right;
                }

                .align-left {
                  text-align: left;
                }

                .align-center {
                  text-align: center;
                }
                .button {
                  background-color: #3869D4;
                  border-top: 10px solid #3869D4;
                  border-right: 18px solid #3869D4;
                  border-bottom: 10px solid #3869D4;
                  border-left: 18px solid #3869D4;
                  display: inline-block;
                  text-decoration: none;
                  border-radius: 3px;
                  box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                  -webkit-text-size-adjust: none;
                  box-sizing: border-box;
                }

                .button--green {
                  background-color: #22BC66;
                  border-top: 10px solid #22BC66;
                  border-right: 18px solid #22BC66;
                  border-bottom: 10px solid #22BC66;
                  border-left: 18px solid #22BC66;
                }

                .button--red {
                  background-color: #FF6136;
                  border-top: 10px solid #FF6136;
                  border-right: 18px solid #FF6136;
                  border-bottom: 10px solid #FF6136;
                  border-left: 18px solid #FF6136;
                }

                @media only screen and (max-width: 500px) {
                  .button {
                    width: 100% !important;
                    text-align: center !important;
                  }
                }

                .attributes {
                  margin: 0 0 21px;
                }

                .attributes_content {
                  background-color: #F4F4F7;
                  padding: 16px;
                }

                .attributes_item {
                  padding: 0;
                }

                .related {
                  width: 100%;
                  margin: 0;
                  padding: 25px 0 0 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .related_item {
                  padding: 10px 0;
                  color: #CBCCCF;
                  font-size: 15px;
                  line-height: 18px;
                }

                .related_item-title {
                  display: block;
                  margin: .5em 0 0;
                }

                .related_item-thumb {
                  display: block;
                  padding-bottom: 10px;
                }

                .related_heading {
                  border-top: 1px solid #CBCCCF;
                  text-align: center;
                  padding: 25px 0 10px;
                }

                .discount {
                  width: 100%;
                  margin: 0;
                  padding: 24px;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #F4F4F7;
                  border: 2px dashed #CBCCCF;
                }

                .discount_heading {
                  text-align: center;
                }

                .discount_body {
                  text-align: center;
                  font-size: 15px;
                }

                .social {
                  width: auto;
                }

                .social td {
                  padding: 0;
                  width: auto;
                }

                .social_icon {
                  height: 20px;
                  margin: 0 8px 10px 8px;
                  padding: 0;
                }

                .purchase {
                  width: 100%;
                  margin: 0;
                  padding: 35px 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .purchase_content {
                  width: 100%;
                  margin: 0;
                  padding: 25px 0 0 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }

                .purchase_item {
                  padding: 10px 0;
                  color: #51545E;
                  font-size: 15px;
                  line-height: 18px;
                }

                .purchase_heading {
                  padding-bottom: 8px;
                  border-bottom: 1px solid #EAEAEC;
                }

                .purchase_heading p {
                  margin: 0;
                  color: #85878E;
                  font-size: 12px;
                }

                .purchase_footer {
                  padding-top: 15px;
                  border-top: 1px solid #EAEAEC;
                }

                .purchase_total {
                  margin: 0;
                  text-align: right;
                  font-weight: bold;
                  color: #333333;
                }

                .purchase_total--label {
                  padding: 0 15px 0 0;
                }

                body {
                  background-color: #F4F4F7;
                  color: #51545E;
                }

                p {
                  color: #51545E;
                }

                p.sub {
                  color: #6B6E76;
                }

                .email-wrapper {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #F4F4F7;
                }

                .email-content {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                }


                .email-masthead {
                  padding: 25px 0;
                  text-align: center;
                }

                .email-masthead_logo {
                  width: 94px;
                }

                .email-masthead_name {
                  font-size: 16px;
                  font-weight: bold;
                  color: #A8AAAF;
                  text-decoration: none;
                  text-shadow: 0 1px 0 white;
                }
                .email-body {
                  width: 100%;
                  margin: 0;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #FFFFFF;
                }

                .email-body_inner {
                  width: 570px;
                  margin: 0 auto;
                  padding: 0;
                  -premailer-width: 570px;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  background-color: #FFFFFF;
                }

                .email-footer {
                  width: 570px;
                  margin: 0 auto;
                  padding: 0;
                  -premailer-width: 570px;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  text-align: center;
                }

                .email-footer p {
                  color: #6B6E76;
                }

                .body-action {
                  width: 100%;
                  margin: 30px auto;
                  padding: 0;
                  -premailer-width: 100%;
                  -premailer-cellpadding: 0;
                  -premailer-cellspacing: 0;
                  text-align: center;
                }

                .body-sub {
                  margin-top: 25px;
                  padding-top: 25px;
                  border-top: 1px solid #EAEAEC;
                }

                .content-cell {
                  padding: 35px;
                }

                @media only screen and (max-width: 600px) {
                  .email-body_inner,
                  .email-footer {
                    width: 100% !important;
                  }
                }

                @media (prefers-color-scheme: dark) {
                  body,
                  .email-body,
                  .email-body_inner,
                  .email-content,
                  .email-wrapper,
                  .email-masthead,
                  .email-footer {
                    background-color: #333333 !important;
                    color: #FFF !important;
                  }
                  p,
                  ul,
                  ol,
                  blockquote,
                  h1,
                  h2,
                  h3 {
                    color: #FFF !important;
                  }
                  .attributes_content,
                  .discount {
                    background-color: #222 !important;
                  }
                  .email-masthead_name {
                    text-shadow: none !important;
                  }
                }
                </style>
              </head>
              <body>
                <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                  <tr>
                    <td align="center">
                      <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <!-- <tr>
                          <td class="email-masthead">
                            <a href="https://example.com" class="f-fallback email-masthead_name">
                            UR HUB
                          </a>
                          </td>
                        </tr> -->

                        <tr>
                          <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell">
                                <div class="align-center"><img  src="https://ur-hub.s3.us-west-2.amazonaws.com/assets/logo/logo.png"></div>
                                    <h3 class="align-right">Tanggal:' . $dateNow . '</h3>
                                  <div class="f-fallback">
                                    <h1>Hi ' . $name . ',</h1>

                                    <table class="discount" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                            <p>Pendapatan Hari Ini</p>
                                          <h1 class="f-fallback discount_heading">' . rupiah($subtotal) . '</h1>
                                        </td>
                                      </tr>
                                    </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                          <td>
                                            <h3>Rincian Pendapatan</h3></td>
                                          <td>
                                        </tr>
                                        <tr>
                                          <td colspan="2">

                                            <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                            ';
      $ovo = 0;
      $gopay = 0;
      $dana = 0;
      $linkaja = 0;
      $tunaiDebit = 0;
      $sakuku = 0;
      $creditCard = 0;
      $debitCard = 0;
      $sumCharge_ur = 0;
      $tipe_bayar = mysqli_query($db_conn, "SELECT total, promo ,tax,service,status,tipe_bayar,charge_ewallet,charge_ur FROM transaksi_foodcourt WHERE id_foodcourt='$id' AND transaksi_foodcourt.status<=2 AND transaksi_foodcourt.status>=1 AND DATE(created_at)='$dateNowDb' AND TIME(created_at) BETWEEN '$jamBuka' AND '$jamTutup'");
      while ($rowtypeBayar = mysqli_fetch_assoc($tipe_bayar)) {
        // $totalType += $rowtypeBayar['total'];
        // $subtotal += $total;
        // if($tax==1){
        //   // $subtotal = ($total + ($total * 0.1));
        //   $totalType = ($rowtypeBayar['tottotal']+ ($rowtypeBayar['tottotal'] * 0.1));
        // }
        // if($service!=0){
        //   // $subtotal = ($total + ($total * ($service/100)));
        //   $totalType = ($rowtypeBayar['tottotal']+($rowtypeBayar['tottotal']*($service/100)));
        // }
        $sumCharge_ur += $rowtypeBayar['charge_ur'];
        $charge_ewallet = $rowtypeBayar['charge_ewallet'];
        $taxtype = $rowtypeBayar['tax'];
        $servicetype = $rowtypeBayar['service'];
        $countService = 0;
        $withService = 0;
        $countTax = 0;
        $withTax = 0;
        if ($rowtypeBayar['tipe_bayar'] == 1 || $rowtypeBayar['tipe_bayar'] == '1') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $ovo += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 2 || $rowtypeBayar['tipe_bayar'] == '2') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $gopay += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 3 || $rowtypeBayar['tipe_bayar'] == '3') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $dana += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 4 || $rowtypeBayar['tipe_bayar'] == '4') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $linkaja += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 5 || $rowtypeBayar['tipe_bayar'] == '5') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $tunaiDebit += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 6 || $rowtypeBayar['tipe_bayar'] == '6') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $sakuku += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 7 || $rowtypeBayar['tipe_bayar'] == '7') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $creditCard += $withTax;
        } else if ($rowtypeBayar['tipe_bayar'] == 8 || $rowtypeBayar['tipe_bayar'] == '8') {
          $countService = ceil(($rowtypeBayar['total'] - $rowtypeBayar['promo']) * ($rowtypeBayar['service'] / 100));
          $withService = ($rowtypeBayar['total'] - $rowtypeBayar['promo']) + $countService + $rowtypeBayar['charge_ur'];
          $countTax = ceil($withService * ($rowtypeBayar['tax'] / 100));
          $withTax = $withService + $countTax;
          $debitCard += $withTax;
        }
        // if($rowtypeBayar['type_bayar']=='5'|| $rowtypeBayar['type_bayar']=='7' || $rowtypeBayar['type_bayar']==5 || $rowtypeBayar['type_bayar']==7){
        //   $totalType += ($rowtypeBayar['total']+($rowtypeBayar['total']*($rowtypeBayar['tax']/100))+($rowtypeBayar['total']*($rowtypeBayar['service']/100)));
        //   $promoType += $rowtypeBayar['promo'];
        // }else{
        //   $totalType+= ($rowtypeBayar['total']+($row['total']*($row['tax']/100))+($row['total']*($row['service']/100)))+(($row['total']*($row['charge_ewallet']/100))*($row['tax']/100));
        //   $promoType += $rowtypeBayar['promo'];
        // }
        // $typeCode = $rowtypeBayar['tipe_bayar'];
        // switch ($typeCode) {
        //   case 1:
        //     $type = 'OVO';
        //     break;
        //   case 2:
        //     $type = 'GOPAY';
        //     break;
        //   case 3:
        //     $type = 'DANA';
        //     break;
        //   case 4:
        //     $type = 'T-CASH';
        //     break;
        //   case 5:
        //     $type = 'TUNAI/DEBIT';
        //     break;
        //   case 6:
        //     $type = 'SAKUKU';
        //     break;
        //   case 7:
        //     $type = 'CREDIT CARD';
        //     break;
        // }

      }

      //   <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">LINK AJA</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.rupiah($linkaja).'</span></td>
      // </tr>
      // <tr>
      //   <td width="80%" class="purchase_item"><span class="f-fallback">SAKUKU</span></td>
      //   <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">'.rupiah($sakuku).'</span></td>
      // </tr>

      $html .= '<tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">E-wallet</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                </tr>
                <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">OVO</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($ovo) . '</span></td>
                </tr>
                <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">GOPAY</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($gopay) . '</span></td>
                </tr>
                  <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($dana) . '</span></td>
                </tr>
                  <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">LinkAja</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($linkaja) . '</span></td>
                </tr>
                  <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">DANA</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($sakuku) . '</span></td>
                </tr>
                <tr>
                <td width="80%" class="purchase_item"><span class="f-fallback">Charge E-Wallet (' . $charge_ewallet . '% + ' . $taxtype . '%)</span></td>
                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah(ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) . '</span></td>
              </tr>
              <tr>
              <td width="80%" class="purchase_item"><span class="f-fallback">Total E-Wallet</span></td>
              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah(($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100)))) . '</span></td>
            </tr>
                ';
      $html .= '
            <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                </tr>
            <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">Non E-wallet</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
                </tr>
                <tr>
                  <td width="80%" class="purchase_item"><span class="f-fallback">CASH</span></td>
                  <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($tunaiDebit) . '</span></td>
                </tr>
                <tr>
                <td width="80%" class="purchase_item"><span class="f-fallback">CREDIT CARD</span></td>
                <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($creditCard) . '</span></td>
              </tr>
              <tr>
              <td width="80%" class="purchase_item"><span class="f-fallback">DEBIT CARD</span></td>
              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' . rupiah($debitCard) . '</span></td>
            </tr>
              <tr>
              <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
            </tr>
            <tr>
            <td width="80%" class="purchase_item"><span class="f-fallback">SUBTOTAL</span></td>
            <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah(($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) . '</span></td>
          </tr>
          <tr>
          <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
          <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
        </tr>
        <tr>
        <td width="80%" class="purchase_item"><span class="f-fallback">Convenience Fee</span></td>
        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah($sumCharge_ur) . '</span></td>
      </tr>
      <tr>
      <td width="80%" class="purchase_item"><span class="f-fallback"></span></td>
      <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback"></span></td>
    </tr>
    <tr>
        <td width="80%" class="purchase_item"><span class="f-fallback">TOTAL</span></td>
        <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">' .  rupiah((($ovo + $gopay + $dana + $linkaja + $sakuku) - (ceil(($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) + (ceil((($ovo + $gopay + $dana + $linkaja + $sakuku) * $charge_ewallet / 100) * $tax / 100))) + $tunaiDebit + $creditCard + $debitCard) - ceil($sumCharge_ur)) . '</span></td>
      </tr>
                ';


      $html .= '</table>
                                          </td>
                                        </tr>
                                      </table>

                                    <table class="purchase" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td>
                                          <h3>Menu Terlaris</h3></td>
                                        <td>
                                      </tr>
                                      <tr>
                                        <td colspan="2">

                                          <table class="purchase_content" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                              <th class="purchase_heading" align="left">
                                                <p class="f-fallback">Menu</p>
                                              </th>
                                              <th class="purchase_heading" align="right">
                                                <p class="f-fallback">Amount</p>
                                              </th>
                                            </tr>';
      $fav = mysqli_query($db_conn, "SELECT menu.nama,SUM(transaksi_detail_tenant.qty) AS qty FROM menu join transaksi_detail_tenant on menu.id=transaksi_detail_tenant.id_menu join transaksi_tenant on transaksi_detail_tenant.id_transaksi_tenant = transaksi_tenant.id JOIN transaksi_foodcourt ON transaksi_tenant.id_transaksi_fc=transaksi_foodcourt.id JOIN partner ON transaksi_tenant.id_partner = partner.id WHERE partner.id_foodcourt= '$id' AND transaksi_foodcourt.status<=2  AND transaksi_foodcourt.status>=1 AND DATE(created_at)='$dateNowDb' AND TIME(transaksi_foodcourt.created_at) BETWEEN '$jamBuka' AND '$jamTutup' GROUP BY menu.nama ORDER BY qty DESC LIMIT 5");
      while ($rowMenu = mysqli_fetch_assoc($fav)) {
        $namaMenu = $rowMenu['nama'];
        $qtyMenu = $rowMenu['qty'];
        $html .= '<tr>
                                              <td width="80%" class="purchase_item"><span class="f-fallback">' . $namaMenu . '</span></td>
                                              <td class="align-right" width="20%" class="purchase_item"><span class="f-fallback">x ' . $qtyMenu . '</span></td>
                                            </tr>';
      }
      $html .= '</table>
                                        </td>
                                      </tr>
                                    </table>

                                    <p>Hormat Kami,
                                      <br>UR - Easy & Quick Order</p>

                                    <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                      <tr>
                                        <td align="center">
                                          <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                            <tr>
                                              <td align="center">
                                                <a href="https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdfPerDayFoodcourt.php?id=' . md5($id) . '" class="f-fallback button button--blue" target="_blank" style = "color:#fff">Download Full PDF</a>
                                              </td>
                                            </tr>
                                          </table>
                                        </td>
                                      </tr>
                                    </table>
                                    <!-- Sub copy -->
                                    <table class="body-sub" role="presentation">
                                      <tr>
                                        <td>
                                        <p class="f-fallback sub">Need a printable copy for your records?</strong> You can <a href="https://apis.ur-hub.com/qr/v2/csv/xlsPerDayFoodcourt.php?id=' . md5($id) . '">download a Xls version</a>.</p>
                                        </td>
                                      </tr>
                                    </table>
                                  </div>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                              <tr>
                                <td class="content-cell" align="center">
                                  <p class="f-fallback sub align-center">&copy; 2020 UR. All rights reserved.</p>
                                  <p class="f-fallback sub align-center">
                                    PT. Rahmat Tuhan Lestari
                                    <br>Jl. Jend. Sudirman No.496, Ciroyom, Kec. Andir, Kota Bandung
                                    <br>Jawa Barat 40221
                                  </p>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
              </body>
            </html>';
      $mailer = new Mailer();
      if ($mailer->sendMessage(
        $email,
        "Laporan Keuangan",
        // "<div>Hai, " . $name . " </div>
        // // <>
        // <br>
        // <br>Laporan Keuangan
        // <br>
        // <br><a href='https://apis.ur-hub.com/qr/v2/tcpdf/examples/pdf.php?id=" . md5(id) . "'>click here</a>
        // <br>
        // <br>
        // Jika anda merasa tidak melakukan request silahkan abaikan pesan ini.
        // <br>
        // <br>
        // Hormat Kami,
        // <br><br>
        // Ur Hub."
        $html
      ) == TRANSAKSI_CREATED) {
        return TRANSAKSI_CREATED;
      } else {
        return FAILED_TO_CREATE_TRANSAKSI;
      }
    } else {
      return USER_NOT_CREATED;
    }
  }

  public function getHideCharge($idPartner)
  {
    $stmt = $this->conn->prepare("SELECT partner.hide_charge FROM `master`
    JOIN partner ON master.id = partner.id_master
    WHERE partner.id = ?");
    $stmt->bind_param("s", $idPartner);
    $stmt->execute();
    $stmt->bind_result($hide);
    $stmt->fetch();

    return $hide;
  }
}
