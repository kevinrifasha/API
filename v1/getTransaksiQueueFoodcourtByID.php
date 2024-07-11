<?php


require_once '../includes/DbOperation.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id'])) {
        $db = new DbOperation();

        if ($db->getTransaksiQueueFoodcourtByID($_POST['id'])) {
            $response['error'] = false;
            $response['transaksi'] = $db->getTransaksiQueueFoodcourtByID($_POST['id']);
        } else {
            $response['error'] = true;
            $response['message'] = 'no ID';
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
