<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';

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

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    // POST DATA
    $data = json_decode(file_get_contents('php://input'));
    if(
        isset($data->source) && !empty($data->source)
    ){
        $extraQuery=" AND id_partner='$tokenDecoded->partnerID' AND deleted_at IS NULL";
        $categories = mysqli_fetch_all($getCategories, MYSQLI_ASSOC);
        foreach($data->source as $x){
            $id = $x['id'];
            $sku = $x['sku'];
            $name= $x['name'];
            $description = $x['description'];
            $stock = $x['stock'];
            $cogs = str_replace('.','',$x['cogs']);
            $cogs = str_replace(',','',$cogs);
            $price = str_replace('.','',$x['price']);
            $price = str_replace(',','',$price);
            $categoryName = trim($x['categoryName']);
            $categoryName = strtolower($categoryName);
            $idList = $x['id'].",";
            $validateCategory = mysqli_query($db_conn, "SELECT c.id FROM categories c WHERE id_master='$tokenDecoded->masterID' AND deleted_at IS NULL AND lower(name)='$categoryName'");
        }
        substr_replace($idList ,"", -1);
        $delete = mysqli_query($db_conn, "UPDATE menu SET deleted_at=NOW() WHERE id NOT IN(".$idList.")".$extraQuery);
        
        // $validateMenu = mysqli_query($db_conn, "SELECT id FROM menu WHERE id IN(".$idList.")".$extraQuery);
        // if(mysqli_num_rows($validateMenu)>0){
        //     while($row=mysqli_fetch_assoc($validateMenu)){

        //     }
        // }else{
        //     $msg="Data menu tidak ditemukan";
        //     $success=0;
        //     $status=400;
        // }
        // $insert = mysqli_query($db_conn,"INSERT INTO `operational_expenses` SET `name`='$data->name', amount='$data->amount', created_by='$tokenDecoded->id', category_id='$data->categoryID'");
        // if($insert){
        //     $msg = "Berhasil tambah data";
        //     $success = 1;
        //     $status=200;
        // }else{
        //     $msg = "Gagal tambah data";
        //     $success = 0;
        //     $status=204;
        // }
    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;
    }

}
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg]);

?>
