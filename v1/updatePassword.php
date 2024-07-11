<?php

require_once '../includes/DbOperation.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['password']) && isset($_POST['phone'])) {

      $password = $_POST["password"];
      $phone = $_POST["phone"];

        $db = new DbOperation();

        if ($db->updatePasswordProfile($password, $phone) == USER_UPDATED) {
            $response['error'] = false;
            $response['menu'] = "Update Password Successfully";
        } else {
            $response['error'] = true;
            $response['message'] = 'Some error occured';
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
