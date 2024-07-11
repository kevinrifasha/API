<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../db_connection.php';
require_once('./Token.php');

$token = "";
$today = date("Y-m-d H:i:s");
$tokenizer = new Token();

$obj = json_decode(file_get_contents("php://input"));
if(gettype($obj)=="NULL"){
    $obj = json_decode(json_encode($_POST));
}
$data = array();
if(
    isset($obj->phone)&&!empty($obj->phone)
    && isset($obj->password)&&!empty($obj->password)
){
    $email = $obj->phone;
    $password = $obj->password;
    $newPassword = md5($password);
    $x = "SELECT u.id, u.role_id, u.org_id, u.phone, u.email, u.name, u.profile_picture, r.name AS roleName, r.mobile FROM sa_users u JOIN sa_roles r ON r.id=u.role_id WHERE u.phone='$email' AND u.password='$newPassword' AND u.deleted_at IS NULL";
    $q = mysqli_query($db_conn, $x);
    if (mysqli_num_rows($q) > 0) {
        $fetched = mysqli_fetch_assoc($q);
        $roleID = $fetched['role_id'];
        $jsonToken = json_encode(['id'=>$fetched['id'], 'roleID'=>$roleID, 'created_at'=>$today, 'expired'=>3000]);
        $token = $tokenizer->stringEncryption('encrypt', $jsonToken);
        $data = $fetched;
        $getRoles = mysqli_query($db_conn, "SELECT * FROM sa_roles WHERE id='$roleID'");
        $roles = mysqli_fetch_assoc($getRoles);
        $data['roles']=$roles;
        if($fetched['mobile']==1){
            $success = 1;
            $status = 200;
            $msg = "Logged";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Tidak ada akses aplikasi";
        }
    } else {
        $success = 0;
        $status = 204;
        $msg = "Nomor telepon atau password salah";
    }

}else{
    $success = 0;
    $status = 400;
    $msg = "Missing Required Field";
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "detail"=>$data,"token"=>$token]);
?>