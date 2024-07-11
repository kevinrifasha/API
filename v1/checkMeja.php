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
    if (!verifyRequiredParams(array('partnerID','nomeja'))) {
        //getting values
        $partnerID = $_POST['partnerID'];
        $nomeja = $_POST['nomeja'];
        
        //creating db operation object
        $db = new DbOperation();

        //adding user to database
        $result = (string)$db->checkTable($partnerID, $nomeja);

        //making the response accordingly
        if ($result == '1' || $result == '0') {
            $response['error'] = false;
            $response['message'] = 'Meja Found';
            $response['is_queue'] = (int)$result;
        }else if ($result == null) {
            $response['error'] = true;
            $response['message'] = 'Meja Not Found';
        }elseif ($result == FAILED_TO_CREATE_TRANSAKSI) {
            $response['error'] = true;
            $response['message'] = 'Meja Not Found';
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
