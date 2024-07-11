<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../db_connection.php';

$data = json_decode(file_get_contents("php://input"));

if (
    isset($data->newPass)
    && isset($data->oldPass)
    && isset($data->id)
	&& !empty(trim($data->newPass))
    && !empty(trim($data->oldPass))
    && !empty(trim($data->id))
) {
    $newPass = mysqli_real_escape_string($db_conn, trim($data->newPass));
    $oldPass = mysqli_real_escape_string($db_conn, trim($data->oldPass));
    $id = mysqli_real_escape_string($db_conn, trim($data->id));
    // $newPass = '12345678';
    // $oldPass = 'password';
    // $id = 64;

    //get old password
    $master = mysqli_query(
        $db_conn,
        "SELECT password FROM foodcourt WHERE id=$id"
    );

    if (mysqli_num_rows($master) == 1) {
        $one_master = mysqli_fetch_all($master, MYSQLI_ASSOC);
        $oldCheck = $one_master[0]['password'];
    }

    
    $newPass = md5($newPass);
    $oldPass = md5($oldPass);

    // echo $oldCheck;
    // echo '<br>';
    // echo $oldPass;
    // echo '<br>';
    // echo $newPass;
    // echo '<br>';

    if($oldCheck != $oldPass){
        echo json_encode(["success" => 0, "msg" => "Pasword Lama Salah!"]);
    }else{
        $updateUser = mysqli_query($db_conn, "UPDATE `foodcourt` SET `password`='$newPass' WHERE id=$id");
        if ($updateUser){
            echo json_encode(["success" => 1, "msg" => "Master Berhasil Diubah"]);
        }else{
            echo json_encode(["success" => 0, "msg" => "Ganti Password Gagal!"]);
        }
    }
    
    
} else {
    echo json_encode(["success" => 0, "msg" => "Isi Semua Field Wajib!"]);
}
