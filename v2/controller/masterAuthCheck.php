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

// Populate Password from JSON $obj array and hash it (is stored on db hashed with sha1) store into $password.
//$password = sha1($obj['password']);

//open connexion to DB
$db = connectBase();

//  // $manager allow us to manage users (add user, get user, ...)
$manager = new masterManager($db);
$partner = $manager->getPartner1($email);

// $password = md5($password);
$sql = "Select * from master where email='$email'";

$result = mysqli_query($db_conn, $sql);
// $check = mysqli_query($db_conn,);

if (mysqli_num_rows($result) == 1) {
   $_SESSION['email_user'] = $partner->email();
   $_SESSION['id'] = $partner->id();
   $_SESSION['name'] = $partner->name();
   $_SESSION['role'] = 'master';
   $LoginMsg = [
      'status' => 200,
      'name' => $partner->name(),
      'email' => $partner->email(),
      'id' => $partner->id(),
      'role' => 'master',
      'is_foodcourt' => $partner->is_foodcourt(),
      'master_status' => $partner->status(),
      'trial_untill' => $partner->trial_untill(),
      'hold_untill' => $partner->hold_untill(),
      'created_at' => $partner->created_at(),
      'expired' => $exp,
      'isLogged' => true
   ];
} else {
   $LoginMsg = 'Email tidak ada';
}

$LoginJson = '';

// Converting the message into JSON format. 
$LoginJson = json_encode($LoginMsg);

// Echo the message.
echo $LoginJson;
