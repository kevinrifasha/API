<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
include '../../ImageKitConfig.php';
$url = "";
function generateRandomString($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
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
        $target_dir .= $_POST['partnerID']."/";   
    }
    if(isset($_POST['menuID']) && !empty($_POST['menuID'])){
        $replace_name .= $_POST['menuID']."-";   
    }
    $replace_name .= $file_name1."-".generateRandomString()."."."webp";   
    $uploadOk = 1;
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
     && $imageFileType != "gif" && $imageFileType != "jfif" && $imageFileType != "webp") {
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