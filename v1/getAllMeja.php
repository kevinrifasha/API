<?php


require_once '../includes/DbOperation.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {    
        $db = new DbOperation();
        $response['menu'] = $db->getAllMeja();
} else {
    $response['error'] = true;
    $response['message'] = "Connection Error";
}

echo json_encode($response);
?>
