<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("./../menuModels/menuManager.php");
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
$MenuManager = new MenuManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
$i = 0;

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $data = json_decode(file_get_contents('php://input'));
    if(
        isset($data) && !empty($data)
    ){
        // validasi nama menu sama yang dari excel
        $vals = array_count_values(array_column($data, 'name'));
        if(count($data) != count($vals)){
            $validateDuplicateName = 1;
        }
        
        $isValidated=0;
        if($validateDuplicateName == 0) {
            foreach($data as $item) {
                if(is_int($item->price) != 1 || (floatval($item->cogs) == 0 && $item->cogs != "0,00" && $item->cogs != "0.00")) {
                    $isValidated = 1;
                     // jika ada salah satu item yang error validasi maka langsung hentikan looping dengan continue dan langsung eksekusi code dibawahnya
                    continue;
                }
            }
            
            if($isValidated == 0) {
                // Karena ini replaceAll maka hapus dulu semua menu dari id_partner tertentu
                $extraQuery=" id_partner='$tokenDecoded->partnerID' AND deleted_at IS NULL;";
                $query = "UPDATE menu SET deleted_at=NOW() WHERE".$extraQuery;
                $delete = mysqli_query($db_conn, $query);
                
                foreach($data as $x){
                    $id = $x->id;
                    $sku = $x->sku;
                    $name= $x->name;
                    $description = $x->description;
                    $stock = $x->stock;
                    $statusEnable = $x->status;
                    $is_recommended = $x->is_recommended;
                    $cogs = $x->cogs;
                    $price = str_replace('.','',$x->price);
                    $price = str_replace(',','',$price);
                    $categoryName = trim($x->categoryName);
                    $categoryName = strtolower($categoryName);
                    $categoryNameForQuery = mysqli_real_escape_string($db_conn, $categoryName);
                    // $categoryName = mysqli_escape_string($db_conn, $categoryName);
                    $sequence = 0;
                    $category_id= "";
                    $departmen_id= "";
                    
                    // cek category apakah sudah ada di db atau belum
                    $getCategories= mysqli_query($db_conn, "SELECT c.id FROM categories c WHERE id_master='$tokenDecoded->masterID' AND lower(name)='$categoryNameForQuery' AND deleted_at IS NULL");
                    // Jika ada maka ambil category idnya
                    if(mysqli_num_rows($getCategories) > 0) {
                        $categories = mysqli_fetch_all($getCategories, MYSQLI_ASSOC);
                        $category_id = $categories[0]['id'];
                    } else {
                        // add category baru
                        // untuk department id nya masukin ke kitchen aja dulu
                        
                        // berarti harus get id_department kitchenn nya menggunakan min
                        $getDepID=mysqli_query($db_conn, "SELECT MIN(departments.id) as dep_id FROM `departments` WHERE partner_id = '$tokenDecoded->partnerID'");
                        $resDepID=mysqli_fetch_all($getDepID, MYSQLI_ASSOC);
                        $departmen_id= $resDepID[0]['dep_id'];
                        
                        // $categoryName = trim($x->categoryName);
                        $categoryName=ucwords($categoryName);
                        $categoryInsertName = mysqli_real_escape_string($db_conn,$categoryName);

                        $insert = mysqli_query($db_conn, "INSERT INTO categories SET id=0, name='$categoryInsertName', id_master='$tokenDecoded->masterID', sequence='$sequence', department_id='$departmen_id'");
                        $category_id = mysqli_insert_id($db_conn);
                    }
                    
                    // Tambahkan semua menu
                    $partner_id = $tokenDecoded->partnerID;
                    $menu = new Menu(array("nama"=>str_replace("'","''",$name),"sku"=>$sku,"id_partner"=>$partner_id,"harga"=>$price,"Deskripsi"=>str_replace("'","''",$description),"id_category"=>$category_id,"category"=>"","img_data"=>"","enabled"=>$statusEnable,"stock"=>$stock,"is_variant"=>0,"hpp"=>$cogs,"harga_diskon"=>0,"is_recommended"=>$is_recommended,"is_recipe"=>0,"thumbnail"=>"","is_auto_cogs"=>0, "show_in_waiter"=>1, "show_in_sf"=>1));
                        
                    $insertMenu = $MenuManager->add($menu);
                    $test = $insertMenu;
                    
                    $i += 1;
                        
                    if($insertMenu=false){
                        $success=0;
                        $msg="Failed";
                        $status=503;
                    }
                }
                
                $msg = "Berhasil Import '$i' Data dari Excel";
                $success = 1;
                $status=200;
            } else {
                $success = 0;
                $msg = "Mohon cek penulisan pada bagian Harga dan HPP. Harga merupakan bilangan bulat, dan HPP bisa desimal";
                $status = 204;
            }
        } else {
            $success = 0;
            $msg = "Nama menu tidak boleh ada yang sama";
            $status = 204;
        }
    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;
    }

}
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg, "insertData"=>$data]);

?>
