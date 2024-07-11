<?php
//Get time and ISO8601 formar
$timestamp = new DateTime();
$time = $timestamp->format('Y-m-d\TG:i:s.vP');

//Get File
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $accounts = $_GET['accounts'];
    $accounts = str_replace(",","%2C",$accounts);
    $timestamp = new DateTime();
$time1 = $timestamp->format('Y-m-d G:i:s');
//get toket 0Auth2.0
require_once 'auth.php';
$auth = new auth();
$token = $auth->getAuth();
$token = json_decode(json_encode(json_decode($token)));

$timestamp = new DateTime();
$time1 = $timestamp->format('Y-m-d G:i:s');
foreach ($token as $key => $object) {
    if ($key=="access_token") {
        $access_token = $object;
    }
}

//req body
$reqBody = "";
$reqBody = strtolower(hash('sha256', $reqBody));

//set API Secret
$apiScrt = "3e397b87-128c-473d-a83b-aecde6194925";

//url api
$url = 'https://api.klikbca.com:443/banking/v3/corporates/KBBRAHMATT/accounts/'.$accounts;
$relativeUrl = '/banking/v3/corporates/KBBRAHMATT/accounts/'.$accounts;

//http method
$httpMethod = "GET";

$stringToSign = $httpMethod.":".$relativeUrl.":".$access_token.":".$reqBody.":".$time;
$sig = hash_hmac('sha256', $stringToSign, $apiScrt);

$timestamp = new DateTime();
$time1 = $timestamp->format('Y-m-d G:i:s');

//curl
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);


$headers = array();
$headers[] = 'Authorization: Bearer '. $access_token;
$headers[] = 'Content-Type: application/json';
$headers[] = 'Origin: ur-hub.com';
$headers[] = 'X-Bca-Key: f8f02dfc-b7cb-4c54-b4b3-64a7d70b1042';
$headers[] = 'X-Bca-Timestamp: '.$time;
$headers[] = 'X-Bca-Signature: '.$sig;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}

$timestamp = new DateTime();
$time1 = $timestamp->format('Y-m-d G:i:s');
echo $result;
curl_close($ch);
}else{
    echo "Request Method Salah";
}


?>