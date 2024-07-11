<?php
/**
 * Created by PhpStorm.
 * User: Belal
 * Date: 04/02/17
 * Time: 7:51 PM
 */

//importing required script
require_once '../includes/APNS.php';


$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyRequiredParams(array('from','to','id_transaksi','is_queue','idpartner'))) {
        //getting values
        $from = $_POST['from'];
        $to = $_POST['to'];
        $transaksi_id = $_POST['id_transaksi'];
        $is_queue = $_POST['is_queue'];
        $partner_id = $_POST['idpartner'];
        //creating db operation object
        $APNS = new APNS();
        //adding user to database
        $getQueue = $APNS->getQueue($partner_id, $is_queue, $to);
        $result = $APNS->changeStatus($transaksi_id, $from, $to, $getQueue);
        
        //making the response accordingly
        if ($result == TRANSAKSI_CREATED) {
            $response['error'] = false;
            $response['message'] = 'Message Sent';
        } elseif ($result == FAILED_TO_CREATE_TRANSAKSI) {
            $response['error'] = true;
            $response['message'] = 'Message Sent';
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

?>
