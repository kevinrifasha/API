<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../db_connection.php';

$data = json_decode(file_get_contents("php://input"));
if(gettype($data)=="NULL"){
  $data = json_decode(json_encode($_POST));
}

if(isset($data->serviceID) && isset($data->phone)){
    $idL = (int) mysqli_real_escape_string($db_conn, trim($data->serviceID));
    $userPhone = mysqli_real_escape_string($db_conn, trim($data->phone));

    $todayDT = date("Y-m-d H:i:s");
    $todayD = date("Y-m-d");

    $queue = 0;
    $getQueueNum = mysqli_query($db_conn,"SELECT `queue_number` FROM `antrian` WHERE DATE(date)='$todayD' AND id_layanan='$idL' ORDER BY id DESC LIMIT 1");
    
    if (mysqli_num_rows($getQueueNum) > 0) {
        $row = mysqli_fetch_array($getQueueNum);
        $queue = $row['queue_number'];
    }
    $queue +=1;

  $insertAntrian = mysqli_query($db_conn,"INSERT INTO `antrian`(`id_layanan`, `user_phone`, `queue_number`, `date`) VALUES ('$idL', '$userPhone', '$queue', '$todayDT')");
  if($insertAntrian){
    http_response_code(200);
    echo json_encode(["success" => 1, "msg" => "Queue Inserted.", "queue_number"=>$queue]);
  } else {
    http_response_code(204);
    echo json_encode(["success" => 0, "msg" => "Queue Not Inserted!"]);
  }
  
}else{
  http_response_code(400);
  echo json_encode(["success" => 0, "msg" => "Please fill all the required fields!!!"]);
}

?>