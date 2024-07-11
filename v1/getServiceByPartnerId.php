<?php

require_once '../includes/DbOperation.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['partnerID'])) {

        $db = new DbOperation();
        $service = $db->getService($_POST['partnerID']);
        if (isset($service)) {
            $response['error'] = false;
            $response['service'] = $service;
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid Partner ID';
        }

    } else {
        $response['error'] = true;
        $response['message'] = 'Parameters are missing';
    }

} else {
    $response['error'] = true;
    $response['message'] = "Request not allowed";
}

echo json_encode($response);
?>
