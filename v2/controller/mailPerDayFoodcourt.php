
<?php
	date_default_timezone_set('Asia/Jakarta');
    require_once '../../includes/DbOperation.php';
    require_once '../../includes/APNS.php';
    require '../../vendor/autoload.php';
    require '../db_connection.php';
    $json = file_get_contents('php://input');

    // decoding the received JSON and store into $obj variable.
    // $obj = json_decode($json, true);
 
    $date=date('H');
    // // Populate User email from JSON $obj array and store into $email.
    // $email = $obj['email'];
    $allPartner = mysqli_query($db_conn, "SELECT email FROM foodcourt WHERE foodcourt.status='FULL' AND HOUR(foodcourt.jam_tutup) =$date ");
    if(date('H')){
        while($row=mysqli_fetch_assoc($allPartner)){
        $email = $row['email'];         
    // $email = "pwbbatoarung@gmail.com";
            if(isset($email)){
                // creating db operation object
                $db = new DbOperation();

                // adding user to database
                $result = $db->mailingPerDayFoodcourt($email); 
                if ($result == TRANSAKSI_CREATED) {
                    echo json_encode(["success"=>$result]);
                    // $_SESSION['role'] = 'partner';

                    // $LoginMsg = [
                    //     'status' => 200,
                    //     'role' => 'partner',
                    //     'isLogged' => false
                    // ];
                } else {
                    echo json_encode(["fail"=>$result]);
                }
            }else{

            }
        }
    }
    // $LoginJson = '';

    // // Converting the message into JSON format. 
    // $LoginJson = json_encode($LoginMsg);
 
    // // Echo the message.
    // echo $LoginJson;
?>
