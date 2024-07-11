<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyRequiredParams(array('id_external', 'amount'))) {
        $id_external = $_POST['id_external'];
        $amount = (int)$_POST['amount'];
        // $amount = $amount;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.xendit.co/qr_codes');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "external_id=" . $id_external . "&type=DYNAMIC&callback_url=https://apis.ur-hub.com/xendit/qris/callback.php&nominal=" . $amount . "&amount=" . $amount);
        curl_setopt($ch, CURLOPT_USERPWD, 'xnd_production_6Le73KckkVkYENtIBukFMOnJphL0beuV4egkDNVziNIyHhmqfvQG1ZBiqNTlJ' . ':' . '');

        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        echo $result;
        curl_close($ch);
    }
}

function verifyRequiredParams($required_fields)
{
    //Getting the request parameters
    $request_params = $_REQUEST;

    //Looping through all the parameters
    foreach ($required_fields as $field) {
        //if any requred parameter is missing
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {

            //returning true;
            return true;
        }
    }
    return false;
}
