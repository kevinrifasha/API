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
    if (!verifyRequiredParams(array('phone', 'voucherId', 'id' ))) {
        //getting values
        $phone = $_POST['phone'];
        $id = $_POST['id'];
        $idvoucher = $_POST['voucherId'];

        //creating db operation object
        $db = new DbOperation();

        //adding user to database
        $result = $db->redeemVoucherRedeemable($id, $phone, $idvoucher);

        //making the response accordingly
        if ($result == TRANSAKSI_CREATED) {
            $response['error'] = false;
            $response['message'] = 'Redeem Voucher Successfully';
        } elseif ($result == FAILED_TO_CREATE_TRANSAKSI) {
            $response['error'] = true;
            $response['message'] = 'Failed To Redeem Voucher';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Required parameter(s) are missing';
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

?>