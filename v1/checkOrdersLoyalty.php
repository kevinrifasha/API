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

if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
    if(!verifyRequiredParams(array('phone')))
    {
        $phone = $_POST['phone'];
        $id_voucher_redeemable = $_POST['id_voucher_redeemable'];
        $db = new DbOperation();
            //making the response accordingly
       
            $response['error'] = false;
            $response['hasil'] = $db->checkOrdersLoyalty($phone, $id_voucher_redeemable);
    }
    else
    {
        $response['error'] = true;
        $response['message'] = 'Missing parameter(s)';
    }
}else{
    $response['error'] = true;
    $response['message'] = 'REQUEST_METHOD Not Found';
}

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

echo json_encode($response)
?>