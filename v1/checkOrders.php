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
        $voucher_id = $_POST['voucher_id'];
        $db = new DbOperation();

        $used = $db->checkUsage($voucher_id);
        $maxUse = $db->getVoucherMaxUsage($voucher_id);

        if ($used<$maxUse) {
            $response['error'] = false;
            $response['hasil'] = $db->checkOrders($phone, $voucher_id);
        } else {
            $response['error'] = true;
            $response['hasil'] = $maxUse;
        }
        
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