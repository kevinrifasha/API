<?php
$http_origin = $_SERVER['HTTP_ORIGIN'];

if ($http_origin == "http://localhost:3000" || $http_origin == "https://admin.ur-hub.com") {
   header("Access-Control-Allow-Origin: $http_origin");
}

header("Access-Control-Allow-Credentials:true");
header('Content-type: application/json');
session_start();
require_once("../includes/fonctions.php");
require_once("../modele/adminManager.php");
require '../db_connection.php';
$now = date('Y-m-d');
$start_date = strtotime($now);
$end_date = strtotime("+7 day", $start_date);
$exp=date('Y-m-d', $end_date);

$json = file_get_contents('php://input');

// Decoding the received JSON and store into $obj variable.
$obj = json_decode($json, true);

// Populate User email from JSON $obj array and store into $email.
$email = $obj['email'];

// Populate Password from JSON $obj array and hash it (is stored on db hashed with sha1) store into $password.
$password = $obj['password'];

// Open connexion to DB
// $db = connectBase();
$password = md5($password);
// $manager allows us to manage users (add user, get user, ...)
// $admin = new adminManager($db);
// $admin = $admin->getUser($email, $password);

// $password = md5($password);
$sql = "select * from admin where email='$email' and password='$password'";


$result = mysqli_query($db_conn, $sql);

if (mysqli_num_rows($result) == 1) {
    while($row =mysqli_fetch_assoc($result)){
        $_SESSION['email_user'] = $row['email'];
        $_SESSION['id'] = $row['id'];
        $_SESSION['role'] = 'admin';
    
        $LoginMsg = [
            'status' => 200,
            'email' => $row['email'],
            'id' => $row['id'],
            'role' => 'admin',
            'expired'=> $exp,
            'isLogged' => true
        ];        
    }
    // $_SESSION['email_user'] = $admin->email();
    // $_SESSION['id'] = $admin->id();
    // $_SESSION['role'] = 'admin';

    // $LoginMsg = [
    //     'status' => 200,
    //     'email' => $admin->email(),
    //     'id' => $admin->id(),
    //     'role' => 'admin',
    //     'expired'=> $exp,
    //     'isLogged' => true
    // ];
} else {
    $LoginMsg = 'Email dan Password tidak Valid! Silahkan coba lagi';
}

// Converting the message into JSON format. 
$LoginJson = '';
$LoginJson = json_encode($LoginMsg);

// Echo the message.
echo $LoginJson;
