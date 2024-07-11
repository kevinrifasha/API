<?php

require_once '../includes/DbOperation.php';
require_once '../includes/APNS.php';
require '../vendor/autoload.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyRequiredParams(array('phone'))) {
        //getting values
        $phone = $_POST['phone'];
        
        //creating db operation object
        $db = new DbOperation();
        $APNS = new APNS();

        //adding user to database
        $result = $db->sendOTP($phone);
        
        //making the response accordingly
        
        if ($result != null) {
            $response['OTP'] = $result;
            $response['error'] = false;
            $response['message'] = 'OTP has been send';
        } else {
            $response['error'] = true;
            $response['message'] = 'Some error occured';
        }

    } else {
        $response['error'] = true;
        $response['message'] = 'Required parameters are missing';
    }
} else {
    $response['error'] = true;
    $response['message'] = 'Invalid request';
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

echo json_encode($response);
?>
