<?php

class APNS{
    private $conn;
    private $db;
    //Constructor
    function __construct()
    {
        require_once dirname(__FILE__) . '/Constants.php';
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    private function status($id)
    {
        switch($id)
        {
            case 0 : 
                return "Belum Bayar";
            break;
            case 1 : 
                return "Pembayaran Selesai";
            break;
            case 2 : 
                return "Order Selesai";
            break;
            case 3 : 
                return "Pesanan dibatalkan";
            break;
        }
    }
        public function changeStatus($tid, $from, $to, $getQueue)
        {
            $sql2 = "SELECT a.id, a.phone, a.status, b.dev_token, b.name "
                    ."FROM transaksi a, users b "
                    ."WHERE a.phone = b.phone AND a.id = ? ";
            if($query2 = $this->conn->prepare($sql2)){
                $query2->bind_param('s', $tid);
                if ($exe = $query2->execute())
                {   
                    $query2->bind_result($id, $phone, $status, $token, $name);
                    $query2->fetch();
                    $query2->store_result();
                    $query2->close();
                    
                    $sql = "UPDATE transaksi SET status = ? , queue = ? WHERE id=?";
                    if($query = $this->conn->prepare($sql))
                    {
                        $query->bind_param('iis',  $to, $getQueue, $tid);
                        $query->execute();
                        switch($to)
                        {
                            case 1:
                                $this->sendPushMessage($token, "Sans Resto","Hai, ".$name.". Pembayaran kamu sudah diverifikasi", $id);
                                break;
                            case 2:
                                $this->sendPushMessage($token, "Sans Resto","Hai, ".$name.". Order kamu sudah selesai.", $id);
                                break;
                            case 3:
                                $this->sendPushMessage($token, "Sans Resto","Hai, ".$name.". Order kamu dibatalkan", $id);
                                break;
                                
                        }
                        return TRANSAKSI_CREATED;
                    }
                    else{
                       var_dump($this->conn->error_list);
                    } 
                }
                else{
                    return FAILED_TO_CREATE_TRANSAKSI;
                }
                //rest of code here
            }else{
            //error !! don't go further
                var_dump($this->conn->error);
            }
        }

        public function getQueue($id_partner,$is_queue,$to)
        {
            date_default_timezone_set('Asia/Jakarta');
            $dates1 = date('Y-m-d', time());
            if($to == 1){
              $sql_queue = $this->conn->prepare ("SELECT MAX(queue) FROM transaksi WHERE id_partner = ? AND DATE(jam) = ?");
              $sql_queue->bind_param("ss", $id_partner, $dates1);
              $sql_queue->execute();
              $sql_queue->bind_result($queue);
              $sql_queue->fetch(); 
              
              if($is_queue==0){
                return $queue=0;
              }else{
                return $queue+=1;
              }
              
            }
          }

        public function updateDeviceToken($phone, $dev_token)
        {
            $sql = "UPDATE users SET dev_token = ? WHERE phone = ?";
            if($query = $this->conn->prepare($sql))
            {
                $query->bind_param('ss', $dev_token, $phone);
                if($exec = $query->execute())
                {
                    return TRANSAKSI_CREATED;
                }
                else{
                    return FAILED_TO_CREATE_TRANSAKSI;
                }
            } 
            else{
                return FAILED_TO_CREATE_TRANSAKSI;
            }

        }
        public function updateDeviceTokenPartner($phone, $dev_token)
        {
            $sql = "UPDATE partner SET device_token = ? WHERE phone = ?";
            if($query = $this->conn->prepare($sql))
            {
                $query->bind_param('ss', $dev_token, $phone);
                if($exec = $query->execute())
                {
                    return TRANSAKSI_CREATED;
                }
                else{
                    return FAILED_TO_CREATE_TRANSAKSI;
                }
            } 
            else{
                return FAILED_TO_CREATE_TRANSAKSI;
            }

        }

        public function sendPushMessage($dev_token, $title, $message, $id)
        {
            /* We are using the sandbox version of the APNS for development. For production
                environments, change this to ssl://gateway.push.apple.com:2195 */
                $apnsServer = 'ssl://gateway.sandbox.push.apple.com:2195';
                /* Make sure this is set to the password that you set for your private key
                when you exported it to the .pem file using openssl on your OS X */
                $privateKeyPassword = '';
                /* Put your own message here if you want to */
                $message = $message;
                /* Pur your device token here */
                $deviceToken = $dev_token;
                /* Replace this with the name of the file that you have placed by your PHP
                script file, containing your private key and certificate that you generated
                earlier */
                $pushCertAndKeyPemFile = 'cert.pem';
                $stream = stream_context_create();
                stream_context_set_option($stream,
                'ssl',
                'passphrase',
                $privateKeyPassword);
                stream_context_set_option($stream,
                'ssl',
                'local_cert',
                $pushCertAndKeyPemFile);

                $connectionTimeout = 20;
                $connectionType = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;
                $connection = stream_socket_client($apnsServer,
                $errorNumber,
                $errorString,
                $connectionTimeout,
                $connectionType,
                $stream);
                if (!$connection){
                // echo "Failed to connect to the APNS server. Error no = $errorNumber<br/>";
                exit;
                } else {
                // echo "Successfully connected to the APNS. Processing...</br>";
                }
                $alert_body = array(
                    'title' => $title,
                    'body' => $message,
                );

                $messageBody['transaksi_id'] = $id;
                $messageBody['aps'] = array(
                'alert' => $alert_body,
                'sound' => 'default',
                'badge' => 0,
                );
            
                $payload = json_encode($messageBody);
                $notification = chr(0) .
                pack('n', 32) .
                pack('H*', $deviceToken) .
                pack('n', strlen($payload)) .
                $payload;
                $wroteSuccessfully = fwrite($connection, $notification, strlen($notification));
                if (!$wroteSuccessfully){
                // echo "Could not send the message<br/>";
                }
                else {
                // echo "Successfully sent the message<br/>";
                }
                fclose($connection);
        }
    }
  ?>