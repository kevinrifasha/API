<?php
    // Enabling error reporting
        error_reporting(-1);
        ini_set('display_errors', 'On');
 
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        
        $fields = NULL;
        
        if($type == "single") {
            $token = isset($_GET['token']) ? $_GET['token'] : '';
            $message = isset($_GET['message']) ? $_GET['message'] : '';
            
            $res = array();
            $res['title'] = "jahat";
            $res['body'] = $message;
            $res['type'] = "biasalah";
            $res['click_action'] = "FLUTTER_NOTIFICATION_CLICK";
            $fields = array(
                'to' => $token,
                'notification' => $res,
                'data' => $res,
            );
            echo json_encode($fields);
        }else if($type == "topics") {
            $topics = isset($_GET['topics']) ? $_GET['topics'] : '';
            $message = isset($_GET['message']) ? $_GET['message'] : '';
            
            $res = array();
             $res['title'] = "jahat";
            $res['body'] = $message;
            
            $fields = array(
                'to' => '/topics/' . $topics,
                'notification' => $res,
            );
            
            echo json_encode($fields);
            echo 'Topics : '. $topics . '<br/>Message : ' . $message;
        }
        
        // Set POST variables
        $url = 'https://fcm.googleapis.com/fcm/send';
        $server_key = "AAAAAK5b3HA:APA91bEjzlH9js4T1HqESl7ub57D5ownF0kaisjp67cPoWIfXDJVLPuq0ojIQUWepaX-iqG2rPaW6bO_g77ZSGAT3alHRSEGnFA22bpSYYoAPTK1lfO84C5hMYZKy2M_dDrR_UWLB1Yo";
        
        $headers = array(
            'Authorization: key=' . $server_key,
            'Content-Type: application/json'
        );
        // Open connection
        $ch = curl_init();
 
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
 
        // Execute post
        $result = curl_exec($ch);
        if ($result === FALSE) {
            echo 'Curl failed: ' . curl_error($ch);
        }
 
        // Close connection
        curl_close($ch);
               
    
?>