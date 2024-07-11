<?php
$http_origin = $_SERVER['HTTP_ORIGIN'];

if ($http_origin == "http://localhost:3000" || $http_origin == "https://partner.ur-hub.com") {
   header("Access-Control-Allow-Origin: $http_origin");
}

header("Access-Control-Allow-Credentials:true");
header('Content-type: application/json');
session_start();
require_once("../includes/fonctions.php");
require_once("../modele/partnerManager.php");
require '../db_connection.php';
$now = date('Y-m-d');
$start_date = strtotime($now);
$end_date = strtotime("+7 day", $start_date);
$exp = date('Y-m-d', $end_date);

$json = file_get_contents('php://input');

// decoding the received JSON and store into $obj variable.
$obj = json_decode($json, true);


// Populate User email from JSON $obj array and store into $email.
$email = $obj['email'];

// Populate Password from JSON $obj array and hash it (is stored on db hashed with sha1) store into $password.
//$password = sha1($obj['password']);
$password = $obj['password'];

//open connexion to DB
// $db = connectBase();
$password = md5($password);

$sql = "Select * from partner where email='$email' and password='$password'";

$sql1 = "Select * from travel_partners where email='$email' and password='$password'";


$result = mysqli_query($db_conn, $sql);
$result1 = mysqli_query($db_conn, $sql1);
// $check = mysqli_query($db_conn,);

if (mysqli_num_rows($result) == 1) {
   while ($row = mysqli_fetch_assoc($result)) {
      $_SESSION['email_user'] = $row['email'];
      $_SESSION['id'] = $row['id'];
      $_SESSION['id'] = $row['id'];
      $_SESSION['name'] = $row['name'];
      if($row['id_foodcourt']==0 || $row['id_foodcourt']=='null'){
      $_SESSION['role'] = 'partner';
      }else{
      $_SESSION['role'] = 'tenant';
      }
      $LoginMsg = [
         'status' => 200,
         'phone' => $row['phone'],
         'email' => $row['email'],
         'id' => $row['id'],
         'name' => $row['name'],
         'tax' => $row['tax'],
         'service' => $row['service'],
         // 'id_ovo' => $row['id_ovo'],
         // 'id_linkaja' => $row['id_linkaja'],
         // 'id_dana' => $row['id_dana'],
         'id_master' => $row['id_master'],
         'id_foodcourt' => $row['id_foodcourt'],
         'saldo_ewallet' => $row['saldo_ewallet'],
         'role' => 'partner',
         'expired' => $exp,
         'isLogged' => true
      ];
   }
} else {
   if (mysqli_num_rows($result1) == 1) {
      while ($row = mysqli_fetch_assoc($result1)) {
         $_SESSION['email_user'] = $row['email'];
         $_SESSION['id'] = $row['id'];
         $_SESSION['id_partner'] = $row['id_partner'];
         $_SESSION['name'] = $row['name'];
         $LoginMsg = [
            'status' => 200,
            'phone' => $row['phone'],
            'email' => $row['email'],
            'id' => $row['id'],
            'id_partner' => $row['id_partner'],
            'name' => $row['name'],
            'tax' => $row['tax'],
            'service' => $row['service'],
            // 'id_ovo' => $row['id_ovo'],
            // 'id_linkaja' => $row['id_linkaja'],
            // 'id_dana' => $row['id_dana'],
            'id_master' => $row['id_master'],
            'id_foodcourt' => $row['id_foodcourt'],
            'saldo_ewallet' => $row['saldo_ewallet'],
            'role' => 'travel',
            'expired' => $exp,
            'isLogged' => true
         ];
      }
   } else {
      $LoginMsg = 'Email dan Password tidak Valid! Silahkan coba lagi';
   }
}

$LoginJson = '';

// Converting the message into JSON format. 
$LoginJson = json_encode($LoginMsg);

// Echo the message.
echo $LoginJson;
