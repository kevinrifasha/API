<?php


require_once '../includes/DbOperation.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['phone'])) {

        $db = new DbOperation();

        if ($db->getTransaksibyphone($_POST['phone'])) {
            $response['error'] = false;
            $response['transaksi'] = $db->getTransaksibyphone($_POST['phone']);
        } else {
            $response['error'] = true;
            $response['message'] = 'There is no Transactions';
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
