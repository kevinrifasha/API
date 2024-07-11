<?php


require_once '../includes/DbOperation.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['id'])) {

        $db = new DbOperation();
        $payment = $db->getAllPaymentIdPartner($_POST['id']);
        if ($payment != null) {
            $response['error'] = false;
            $response['partner'] = $payment;
        } else {
            $response['error'] = true;
            $response['message'] = 'ID not found';
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
