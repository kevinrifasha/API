<?php


require_once '../includes/DbOperation.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['partnerID'])) {

        $db = new DbOperation();

        if ($db->getMenuByPartnerIDWithCategory($_POST['partnerID'])) {
            $response['error'] = false;
            $response['menu'] = $db->getMenuSectionV2($_POST['partnerID']);
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
