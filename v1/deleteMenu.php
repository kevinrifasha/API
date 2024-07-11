<?php
require_once '../includes/DbOperation.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyRequiredParams(array('id'))) {
        //getting values
        $id = $_POST['id'];

        //creating db operation object
        $db = new DbOperation();

        //adding user to database
        $result = $db->deleteMenu($id);

        //making the response accordingly
        if ($result == REMOVE_MENU) {
            $response['error'] = false;
            $response['message'] = 'Menu deleted successfully';
        } elseif ($result == FAILED_TO_REMOVE_MENU) {
            $response['error'] = true;
            $response['message'] = 'Menu not Delete';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Required parameters are missing';
    }
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
    
echo json_encode($response);

?>