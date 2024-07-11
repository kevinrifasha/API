<?php
date_default_timezone_set('Asia/Jakarta');
//Get time and ISO8601 formar
$timestamp = new DateTime();
// $time =  $timestamp->format(DateTime::ISO8601);
$time = $timestamp->format('Y-m-d\TG:i:s.vP');
$timeNow = $timestamp->format('Y-m-d');
// $tranId = "00000001";
$tranId = mt_rand(10000000,99999999);
//get toket 0Auth2.0
require_once 'auth.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyRequiredParams(array('SourceAccountNumber','Amount','BeneficiaryAccountNumber'))) {
        //getting values
        $SourceAccountNumber = $_POST['SourceAccountNumber'];
        $Amount = $_POST['Amount'];
        $BeneficiaryAccountNumber = $_POST['BeneficiaryAccountNumber'];

        $auth = new auth();
        $token = $auth->getAuth();
        $token = json_decode(json_encode(json_decode($token)));
        // echo gettype($token);
        foreach ($token as $key => $object) {
            if ($key=="access_token") {
                $access_token = $object;
            }
        }
        //req body
        $td['CorporateID'] = "KBBRAHMATT";
        $td['SourceAccountNumber'] = $SourceAccountNumber;
        $td['TransactionID'] = "$tranId";
        $td['TransactionDate'] = "$timeNow";
        $td['ReferenceID'] = "00000002";
        $td['CurrencyCode'] = "IDR";
        $td['Amount'] = $Amount;
        $td['BeneficiaryAccountNumber'] = $BeneficiaryAccountNumber;
        $td['Remark1'] = "UR";
        $td['Remark2'] = " ";

        //merge set array diatas agar dapat di execute oleh API midtran
        $reqBody = json_encode($td);
        $reqBody1 = json_encode($td);
        $reqBody = strtolower(hash('sha256', $reqBody));

        //set API Secret
        $apiScrt = "3e397b87-128c-473d-a83b-aecde6194925";

        //url api
        $url = 'https://api.klikbca.com:443/banking/corporates/transfers';
        $relativeUrl = '/banking/corporates/transfers';

        //http method
        $httpMethod = "POST";

        $stringToSign = $httpMethod.":".$relativeUrl.":".$access_token.":".$reqBody.":".$time;
        $sig = hash_hmac('sha256', $stringToSign, $apiScrt);

        //curl
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $reqBody1);

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

        echo $result;
        curl_close($ch);
    }
}

//function to validate the required parameter in request
function verifyRequiredParams($required_fields)
{

    //Getting the request parameters
    $request_params = $_REQUEST;

    //Looping through all the parameters
    foreach ($required_fields as $field) {
        //if any requred parameter is missing
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {

            //returning true;
            return true;
        }
    }
    return false;
}

?>
