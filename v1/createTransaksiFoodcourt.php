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
    if (!verifyRequiredParams(array('phone', 'idpartner', 'status', 'total', 'tipebayar','promo', 'is_queue'))) {
        //getting values
        $phone = $_POST['phone'];
        $idpartner = $_POST['idpartner'];
        $nomeja = $_POST['no_meja_foodcourt'];
        $status = $_POST['status'];
        $total = $_POST['total'];
        $tipebayar = $_POST['tipebayar'];
        $id_transaksi = $_POST['id_transaksi'];
        $promo = $_POST['promo'];
        $is_queue = $_POST['is_queue'];
        $takeaway = $_POST['takeaway'];
        $notes = $_POST['notes'];
        $id_foodcourt = $_POST['id_foodcourt'];
        //creating db operation object
        $db = new DbOperation();

        //adding user to database
        // public function createTransaksi($id, $phone, $idpartner, $nomeja, $status, $total, $tipebayar){
        // $queue = $db->getQueue($idpartner,$is_queue, $takeaway);
        $service = $db->getService($idpartner);
        $tax = $db->getTaxEnabled($idpartner);
        $charge_ewallet = $db->getChargeEwallet();    
        $charge_xendit = $db->getChargeXendit();
        $charge_ur = $db->getChargeUr();
        $result = $db->createTransaksiFoodcourt($id_transaksi, $phone, $idpartner, $nomeja, $status, $total ,$tipebayar, $promo, $is_queue, $takeaway, $notes, $id_foodcourt, $tax, $service,$charge_ewallet,$charge_xendit,$charge_ur);

        //making the response accordingly
        if ($result == TRANSAKSI_CREATED) {
            $response['error'] = false;
            $response['message'] = 'Transaksi Created Succesfully';
        } elseif ($result == FAILED_TO_CREATE_TRANSAKSI) {
            $response['error'] = true;
            $response['message'] = 'Failed To Create Transaksi';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Required parameters are missing';
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $db = new DbOperation();
    $result = $db->getIDTransaksi();

        //making the response accordingly
    if ($result != USER_ALREADY_EXIST) {
        $response['error'] = false;
        $response['hasil'] = $result;
    }
    else {
        $response['error'] = true;
        $response['message'] = 'Failed To Insert Transaksi';
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
