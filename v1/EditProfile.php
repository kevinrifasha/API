<?php

/**
 * Created by PhpStorm.
 * User: Belal
 * Date: 04/02/17
 * Time: 7:51 PM
 */

//importing required script
require_once '../includes/DbOperation.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyRequiredParams(array('name', 'email', 'TglLahir', 'gender', 'phone'))) {
        //getting values
        $name = $_POST['name'];
        $email = $_POST['email'];
        $TglLahir = $_POST['TglLahir'];
        $gender = $_POST['gender'];
        $phone = $_POST['phone'];
        $alamat = $_POST['alamat'];
        //        $username = $_POST['username'];

        //creating db operation object
        $db = new DbOperation();

        //adding user to database
        $result = $db->updateInfoUser($name, $email, $TglLahir, $gender, $phone, $alamat);

        //making the response accordingly
        if ($result == USER_UPDATED) {
            $response['error'] = false;
            $response['message'] = 'User updated successfully';
        } elseif ($result == USER_NOT_UPDATED) {
            $response['error'] = true;
            $response['message'] = 'User not updated';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Required parameters are missing';
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

echo json_encode($response);
