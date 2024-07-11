<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once('../auth/Token.php');
include '../../ImageKitConfig.php';
function generateRandomString($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

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
$tokenizer = new Token();

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$values = array();
$tokens = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($_FILES['myFile'])){
    $temp_file_location = $_FILES['myFile']['tmp_name']; 
    $imagedata = file_get_contents($temp_file_location);
    $encoded = base64_encode($imagedata);
    $file_name = $_FILES['myFile']['name']; 
    $target_dir = $_POST['targetDir'];
    $imageFileType = strtolower(pathinfo($file_name,PATHINFO_EXTENSION));
    $file_name1 = str_replace( $imageFileType, "",strtolower($file_name));
    $file_name1 = str_replace( ".", "",strtolower($file_name1));
    $replace_name = "";   
    if(isset($_POST['partnerID']) && !empty($_POST['partnerID'])){
        $target_dir .= "/".$_POST['partnerID']."/";   
    }
    // $replace_name .= $token->id_partner."/";   
    if(isset($_POST['menuID']) && !empty($_POST['menuID'])){
        $replace_name .= $_POST['menuID']."-";   
    }
    $replace_name .= $file_name1."-".generateRandomString()."."."webp";     
    $uploadOk = 1;
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
     && $imageFileType != "gif" && $imageFileType != "jfif") {
        $uploadOk = 0;
    }

    if($uploadOk==1){
        $result = $imageKit->uploadFile([
            'file' => $encoded, 
            'fileName' => $replace_name,
            "folder" => "/assets/".$target_dir
        ]);
        $url = $result->result->url;
        if(!empty($url)){
            $status = 200;
            $msg = "upload success";
        }else{
            $status = 204;
            $msg = "upload failed";
        }
        // $status = 200;
    }else{ 
        $status = 400;
        $msg = "system error";
    }

}else{
    $uploadOk=0;
    $status=400;
    $msg = "system error";
}
echo json_encode(["url"=>$url,"status"=>$status,"uploadOk"=>$uploadOk, "msg"=>$msg]);
?>    