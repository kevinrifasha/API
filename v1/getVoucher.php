<?php


require_once '../includes/DbOperation.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $db = new DbOperation();

        if ($db->getVoucher()) {
            $response['error'] = false;
            $response['transaksi'] = $db->getVoucher();
        } else {
            $response['error'] = true;
            $response['message'] = 'no ID';
        }

} else {
    $response['error'] = true;
    $response['message'] = "Request not allowed";
}

echo json_encode($response);
?>

