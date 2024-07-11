<?php
	$http_origin = $_SERVER['HTTP_ORIGIN'];

    if ($http_origin == "http://localhost:3000" || $http_origin == "https://partner.ur-hub.com") {
       header("Access-Control-Allow-Origin: $http_origin");
    }
    
    header("Access-Control-Allow-Credentials:true");
    header('Content-type: application/json');
    session_start();
    require_once '../../includes/DbOperation.php';
    require_once '../../includes/APNS.php';
    require '../../vendor/autoload.php';
        
    $json = file_get_contents('php://input');

    // // decoding the received JSON and store into $obj variable.
    $obj = json_decode($json, true);
 
 
    // // // Populate User email from JSON $obj array and store into $email.
    $email = $obj['email'];
         
    // $email = "ellasavia33@gmail.com";
    if(isset($email)){
        //creating db operation object
        $db = new DbOperation();

        //addtoken
        // $token = $db->addtoken($email);
        //adding user to database
        $result = $db->changePasswordPartner($email); 
        if ($result == TRANSAKSI_CREATED) {
            $LoginMsg = [
                'status' => 200
            ];
        } else {
            $LoginMsg = 'Periksa Email Anda';
        }
    }else{
        $LoginMsg = 'Email kosong';
    }
    $LoginJson = '';

    // Converting the message into JSON format. 
    $LoginJson = json_encode($LoginMsg);
 
    // Echo the message.
    echo $LoginJson;
?>
