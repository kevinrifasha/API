
<?php
	
    require_once '../../includes/DbOperation.php';
    require_once '../../includes/APNS.php';
    require '../../vendor/autoload.php';
    require '../db_connection.php';    
    $json = file_get_contents('php://input');
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: access");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    // $email = $_GET['email'];
    // decoding the received JSON and store into $obj variable.
    // $obj = json_decode($json, true);
 
 
    // // Populate User email from JSON $obj array and store into $email.
    // $email = $obj['email'];
    $allMaster = mysqli_query($db_conn, "SELECT email FROM partner WHERE status = 'FULL'");
    if(date("d")==1){
        while($row=mysqli_fetch_assoc($allMaster)){
        $email = $row['email'];         
        // $email = "nyonathan21@gmail.com";
            
            if(isset($email)){
                //creating db operation object
                $db = new DbOperation();

                //adding user to database
                $result = $db->createTagihan($email); 
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

            }
        }
    }
    // $LoginJson = '';

    // // Converting the message into JSON format. 
    // $LoginJson = json_encode($LoginMsg);
 
    // // Echo the message.
    // echo $LoginJson;
?>
