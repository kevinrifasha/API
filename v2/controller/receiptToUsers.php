
<?php
	header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: access");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    
    require_once '../../includes/DbOperation.php';
    require_once '../../includes/APNS.php';
    require '../../vendor/autoload.php';
    require '../db_connection.php';    
    $json = file_get_contents('php://input');

    // decoding the received JSON and store into $obj variable.
    $obj = json_decode($json, true);
    $transId = $_GET['id_transaksi'];
    // // Populate User email from JSON $obj array and store into $email.
    // $transId = $obj['id_transaksi'];
    $allPartner = mysqli_query($db_conn, "SELECT phone FROM transaksi WHERE id='$transId' LIMIT 1");        
    while ($row = mysqli_fetch_assoc($allPartner)) {
        $phone =$row['phone'];
    }
        // $email = "pratama14wijaya@gmail.com";
            
            if(isset($phone)&&isset($transId)){
                //creating db operation object
                $db = new DbOperation();
                //adding user to database
                $result = $db->receipt($phone,$transId); 
                echo json_encode(["success"=>$result]);
                // if ($result == TRANSAKSI_CREATED) {
                //     echo($result);
                //     echo json_encode(["success"=>$result]);
                //     $_SESSION['role'] = 'partner';

                //     $LoginMsg = [
                //         'status' => 200,
                //         'role' => 'partner',
                //         'isLogged' => false
                //     ];
                // } else {
                //     echo json_encode(["fail"=>$result]);
                // }
            }else{
                echo json_encode(["success"=>"0"]);
            }
     
    // $LoginJson = '';

    // // Converting the message into JSON format. 
    // $LoginJson = json_encode($LoginMsg);
 
    // // Echo the message.
    // echo $LoginJson;
?>
