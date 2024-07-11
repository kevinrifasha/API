<?php

/**
 * Created by PhpStorm.
 * User: Belal
 * Date: 04/02/17
 * Time: 7:51 PM
 */

//importing required script
require_once '../includes/DbOperation.php';
require_once '../includes/APNS.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyRequiredParams(array(
        'name', 'email', 'password',
        // 'TglLahir',
        // 'gender'
    ))) {
        // Getting values
        $name = $_POST['name'];
        $email = $_POST['email'];
        $TglLahir = $_POST['TglLahir'];
        $gender = $_POST['gender'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $dev_token = $_POST['dev_token'];
        $alamat = $_POST['alamat'];

        // Creating db operation object
        $db = new DbOperation();
        $APNS = new APNS();

        // Adding user to database
        $result = $db->insertInfoUserbyphone($name, $email, $password, $TglLahir, $gender, $phone, $alamat);

        // Making the response accordingly
        if ($result == USER_UPDATED) {
            $response['error'] = false;
            $response['message'] = 'User updated successfully';
            $response['user'] = $db->getUserByPhone($phone);
            $hasil = $APNS->updateDeviceToken($phone, $dev_token);
        } elseif ($result == USER_NOT_UPDATED) {
            $response['error'] = true;
            $response['message'] = 'User not updated';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Required parameters are missing';
    }
} else {
    $response['error'] = true;
    $response['message'] = 'Invalid request';
}


// Function to validate the required parameter in request
function verifyRequiredParams($required_fields)
{
    // Getting the request parameters
    $request_params = $_REQUEST;

    // Looping through all the parameters
    foreach ($required_fields as $field) {

        // If any requred parameter is missing
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {

            // Returning true;
            return true;
        }
    }
    return false;
}

echo json_encode($response);
