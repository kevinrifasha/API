<?php


require_once '../includes/DbOperation.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['phone']) && isset($_POST['password'])) {

        $db = new DbOperation();

        if ($db->userLoginbyphone($_POST['phone'], $_POST['password'])) {
            $response['error'] = false;
            $response['user'] = $db->getUserByPhone($_POST['phone']);
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid password';
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
