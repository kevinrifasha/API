<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';
require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$headers = apache_request_headers();
$token = '';

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
$partnerID=$_GET['partnerID'];
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $obj = json_decode(file_get_contents('php://input'));
    if(isset($obj->reviewID) && isset($obj->content)){
        $sql = mysqli_query($db_conn, "INSERT INTO review_replies SET review_id='$obj->reviewID', employee_id='$tokenDecoded->id', content='$obj->content'");
        if($sql) {
            $success = 1;
            $status = 200;
            $msg = "Berhasil tambah balasan";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Gagal tambah balasan, mohon coba lagi";
        }
    }else{
        $success = 0;
        $status = 400;
        $msg = "Data tidak lengkap";
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);

?>