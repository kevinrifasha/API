<?php


require_once '../includes/DbOperation.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['partnerID'])) {

        $db = new DbOperation();

        if ($db->getMenuCategoryByPartnerID($_POST['partnerID'])) {
            $response['error'] = false;
            $response['cat'] = $db->getMenuCategoryByPartnerID($_POST['partnerID']);
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
