<?php
require '../../db_connection.php';
 //JANGAN LUPA UPDATE YANG INI!!!!! KALO GA KACAU

$getData = mysqli_query($db_conn,"SELECT id, image AS urlx FROM sa_visitations WHERE length(image)>1 AND deleted_at IS NULL");
if(mysqli_num_rows($getData)>0){
    while($row = mysqli_fetch_assoc($getData)){
        $id = $row['id'];
        $url = $row['urlx'];
        if(substr($url,0,49)=="https://ur-hub.s3.us-west-2.amazonaws.com/assets/" || substr($url,0,49)=="https://ur-hub.s3-us-west-2.amazonaws.com/assets/"){
            $url = substr($url, 49);

        $url = "https://ik.imagekit.io/urhub/assets/".$url;
        $url = str_replace("%20", "_",$url);
        // echo $url."<br/>";
        //JANGAN LUPA UPDATE YANG INI!!!!! KALO GA KACAU
        $updateQ = mysqli_query($db_conn, "UPDATE sa_visitations SET image='$url' WHERE id='$id'");
        if($updateQ){
            echo "Berhasil update image ".$id."<br/>";
        }
        }

    }
}

// $getData = mysqli_query($db_conn,"SELECT id, img_data AS urlx FROM menu WHERE length(img_data)>1 AND deleted_at IS NULL");
// if(mysqli_num_rows($getData)>0){
//     while($row = mysqli_fetch_assoc($getData)){
//         $id = $row['id'];
//         $url = $row['urlx'];
//         if(substr($url,0,49)=="https://ur-hub.s3.us-west-2.amazonaws.com/assets/" || substr($url,0,49)=="https://ur-hub.s3-us-west-2.amazonaws.com/assets/"){
//             $url = substr($url, 49);

//         $url = "https://ik.imagekit.io/urhub/assets/".$url;

//         // echo $url."<br/>";
//         //JANGAN LUPA UPDATE YANG INI!!!!! KALO GA KACAU
//         $updateQ = mysqli_query($db_conn, "UPDATE menu SET img_data='$url' WHERE id='$id'");
//         if($updateQ){
//             echo "Berhasil update ".$id."<br/>";
//         }
//         }

//     }
// }
?>