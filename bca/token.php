<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../v2/db_connection.php';
// $headers = apache_request_headers();
    $headers = array();
    $rx_http = '/\AHTTP_/';
    foreach($_SERVER as $key => $val) {
      if( preg_match($rx_http, $key) ) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
          foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
          $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
      }
    }
$authorization = "";
$randomString = '';
$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
$today = date("Y/m/d H:i:s");


foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $authorization=$value;
    }
}

$boolValidate = false;
$getData = mysqli_query($db_conn, "SELECT * FROM `client`");
while ($row = mysqli_fetch_assoc($getData)) {
    $id = $row['id'];
    $secret = $row['secret'];
    $idscrt = $id.':'.$secret;
    $checker = "Basic ".base64_encode($id.':'.$secret);

    if($checker == $authorization){
        $boolValidate=true;
    }
}

//get params
if($boolValidate==true){
    parse_str(file_get_contents("php://input"), $data);
    if($data['grant_type']=="client_credentials"){
        for ($i = 0; $i < 36; $i++) {
            $randomString .= $characters[rand(0, 35)];
        }
        $createToken = mysqli_query($db_conn, "INSERT INTO `token`(`token`, `client_id`, `types`, `scope`, `expires_in`, `created_at`) VALUES ('$randomString', '$id', 'Bearer', 'resource.WRITE resource.READ',3600, '$today')");
        
        if ($createToken) {
            echo json_encode(["access_token"=>$randomString,"token_type"=>"Bearer","expires_in"=>3600, "scope"=>"resource.WRITE resource.READ"]);
        }else{
            echo json_encode(["success"=>0,"msg"=>"Failed to get Token"]);
        }
    }else{
        echo json_encode(["success"=>0,"msg"=>"Wrong grant_type"]);
    }
}else{
    echo json_encode(["success"=>0,"msg"=>"Wrong Authorization"]);
}
?>