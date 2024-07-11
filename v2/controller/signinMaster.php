<?php
$http_origin = $_SERVER['HTTP_ORIGIN'];

if ($http_origin == "http://localhost:3000" || $http_origin == "https://master.ur-hub.com" || $http_origin == "http://localhost:3001") {
   header("Access-Control-Allow-Origin: $http_origin");
}

header("Access-Control-Allow-Credentials:true");
header('Content-type: application/json');
session_start();
// require_once("../includes/fonctions.php");
// require_once("../modele/masterManager.php");
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
$password = $obj['password'];
$password = md5($password);

$sql = "Select * from master where email='$email' and password='$password'";

$result = mysqli_query($db_conn, $sql);

if (mysqli_num_rows($result) == 1) {
   while ($row = mysqli_fetch_assoc($result)) {
      $_SESSION['email_user'] = $row['email'];
      $_SESSION['id'] = $row['id'];
      $_SESSION['name'] = $row['name'];
      $_SESSION['role'] = 'master';
      $LoginMsg = [
         'status' => 200,
         'name' => $row['name'],
         'email' => $row['email'],
         'id' => $row['id'],
         'role' => 'master',
         'is_foodcourt' => $row['is_foodcourt'],
         'master_status' => $row['status'],
         'trial_untill' => $row['trial_untill'],
         'hold_untill' => $row['hold_untill'],
         'created_at' => $row['created_at'],
         'referer' => $row['referer'],
         'expired' => $exp,
         'isLogged' => true
      ];
   }
} else {
   $LoginMsg = 'Email dan Password tidak Valid! Silahkan coba lagi';
}

$LoginJson = '';

// Converting the message into JSON format. 
$LoginJson = json_encode($LoginMsg);

// Echo the message.
echo $LoginJson;
