<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';

date_default_timezone_set('Asia/Jakarta');
$todayExact = date("Y-m-d H:i:s");
$today = date("Y-m-d");
$period = date("Y-m-d H:i:s", strtotime("-3 days"));
$partnerID = "000035";
$q="SELECT id, nama, thumbnail, img_data FROM menu WHERE id_partner='$partnerID' AND LENGTH(thumbnail)>1";
// echo $q;
$getThumbnails = mysqli_query($db_conn, $q);

while($row=mysqli_fetch_assoc($getThumbnails)){
    // echo $row['thumbnail']."\n";
    // if(substr($row['thumbnail'],0,42)=="https://ur-hub.s3.us-west-2.amazonaws.com/"){
    //     // echo "masih aws\n";
    //     // $sisanya = substr($row['thumbnail'],42);
    //     // $newURL = "https://ik.imagekit.io/urhub/".$sisanya;
    //     // echo $newURL."\n";
    // }else{
    //     $id = $row['id'];
    //     $updateThumbnail = mysqli_query($db_conn, "UPDATE menu SET thumbnail=img_data WHERE id='$id'");
    //     if($updateThumbnail){
    //         echo "berhasil update: ".$id;
    //     }
    // }
    $id = $row['id'];
    $newThumb = $row['img_data']."?tr=w-100,h-100";
    $updateThumbnail = mysqli_query($db_conn, "UPDATE menu SET thumbnail='$newThumb' WHERE id='$id'");
    if($updateThumbnail){
        echo "berhasil update: ".$id;
    }
}

?>
