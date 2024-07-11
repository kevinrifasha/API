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
        $vals = array_count_values(array_column($data, 'nama'));
        if(count($data) != count($vals)){
            $validateDuplicateName = 1;
        }
        $error_in ="metric";
        $isValidated=0;
        if($validateDuplicateName == 0) {
            foreach($data as $item) {
                // $menuName = strtolower($item->name);
                $getStockMetric = mysqli_query($db_conn, "SELECT m.id FROM metric m WHERE lower(name)='$item->stockMetricName' AND deleted_at IS NULL");    
                $getMetric = mysqli_query($db_conn, "SELECT m.id FROM metric m WHERE lower(name)='$item->metricName' AND deleted_at IS NULL");
                if(mysqli_num_rows($getMetric) < 1 || mysqli_num_rows($getStockMetric) < 1) {
                    $isValidated = 1;
                    // jika ada salah satu item yang error validasi maka langsung hentikan looping dengan continue dan langsung eksekusi code dibawahnya
                }
                if(is_float(floatval($item->unit_price)) == false && is_int($item->unit_price) == false){
                    $isValidated = 1;
                    $error_in = "unit price";
                }
                if(!is_int($item->stock) && $item->stock != ""){
                    $error_in = "stock";
                    $isValidated = 1;
                }
                if(!is_int((int)$item->reminderAlert) && ($item->reminderAlert != 0 || $item->reminderAlert != "0")){
                    $error_in = "reminder alert";
                    $isValidated = 1;
                }
                if($isValidated == 1){
                    break;
                }    
            }
            
            if($isValidated == 0) {
                // Karena ini replaceAll maka hapus dulu semua menu dari id_partner tertentu
                $extraQuery=" id_partner='$tokenDecoded->partnerID' AND deleted_at IS NULL;";
                $query = "UPDATE raw_material SET deleted_at=NOW() WHERE".$extraQuery;
                $delete = mysqli_query($db_conn, $query);
                
                foreach($data as $x){
                    $id = $x->id;
                    if($x->id) {
                        $idList .= $x->id.",";
                    }
                    $reminderAlert = $x->reminderAlert;
                    $name= $x->nama;
                    $metricName = $x->metricName;
                    $stock = $x->stock;
                    $stockMetricName = $x->stockMetricName;
                    $unit_price = $x->unit_price;
                    $yield = $x->yield ?? 100;
                    $categoryName = trim($x->categoryName);
                    $categoryName = strtolower($categoryName);
                    $extraQuery=" AND id_partner='$tokenDecoded->partnerID' AND deleted_at IS NULL";
                    
                    // cek category apakah sudah ada di db atau belum
                    $getCategories= mysqli_query($db_conn, "SELECT c.id FROM rm_categories c WHERE master_id='$tokenDecoded->masterID' AND partner_id='$tokenDecoded->partnerID' AND lower(name)='$categoryName' AND deleted_at IS NULL");
                    
                    $getStockMetric = mysqli_query($db_conn, "SELECT m.id FROM metric m WHERE lower(name)='$stockMetricName' AND deleted_at IS NULL");    
                    
                    $getMetric = mysqli_query($db_conn, "SELECT m.id FROM metric m WHERE lower(name)='$metricName' AND deleted_at IS NULL");
                    
                    $metric = 0;
                    if(mysqli_num_rows($getMetric) > 0) {
                        $metric = mysqli_fetch_all($getMetric, MYSQLI_ASSOC);
                        $metric_id = $metric[0]['id'];
                    }
                    
                    $stock_metric_id =0;
                    if(mysqli_num_rows($getStockMetric) > 0) {
                        $stockMetric = mysqli_fetch_all($getStockMetric, MYSQLI_ASSOC);
                        $stock_metric_id = $stockMetric[0]['id'];
                    }
                    
                    // Jika ada maka ambil category idnya
                    if(mysqli_num_rows($getCategories) > 0) {
                        $categories = mysqli_fetch_all($getCategories, MYSQLI_ASSOC);
                        $category_id = $categories[0]['id'];
                    } else {
                        // add category baru
                        
                        $categoryName=ucwords($categoryName);
                        
                        $insert = mysqli_query($db_conn, "INSERT INTO rm_categories SET name='$categoryName', master_id='$tokenDecoded->masterID', partner_id='$tokenDecoded->partnerID', sequence='$sequence', created_at=NOW()");
                        $category_id = mysqli_insert_id($db_conn);
                        }
                        $insert = mysqli_query($db_conn, "INSERT INTO raw_material SET name='$name', category_id='$category_id',reminder_allert='$reminderAlert', id_metric='$metric_id', id_metric_price='$metric_id' , unit_price= '$unit_price',updated_at=NOW(), id_partner='$tokenDecoded->partnerID',id_master='$tokenDecoded->masterID', yield='$yield' ");
                        $newRawID = mysqli_insert_id($db_conn);
                    
                        $qMovement="INSERT INTO stock_movements SET master_id='$tokenDecoded->masterID', partner_id='$tokenDecoded->partnerID', raw_id='$newRawID', metric_id='$stock_metric_id', type=0, initial='$stock', remaining='$stock'";
                        $sqlMovement = mysqli_query($db_conn, $qMovement);
                    
                        
                        $qStock = "INSERT INTO raw_material_stock SET stock='$stock', id_metric='$stock_metric_id',  id_raw_material='$newRawID'";
                        
                        $sqlStock = mysqli_query($db_conn, $qStock);

                        $i += 1;
                }
                
                
                $msg = "Berhasil Import '$i' Data dari Excel";
                $success = 1;
                $status=200;
            } else {
                $success = 0;
                if($error_in == ""){
                    $msg = "Penulisan satuan pada bagian Satuan Stok Peringatan dan Satuan Stok tidak tepat. Pilihan: miligram, gram, kilogram, mililiter, liter, pcs, lusin dan butir";
                } else if ($error_in == "reminder alert"){
                    $msg = "Stok Peringatan bukan angka bulat, mohon ubah menjadi angka bulat";
                } else if ($error_in == "unit price"){
                    $msg = "Harga Per Satuan Stok tidak tepat, mohon isi dengan angka desimal atau angka bulat";
                } else if ($error_in == "stock"){
                    $msg = "Terdapat Input Stok tidak tepat, mohon ubah menjadi angka bulat";
                }
                $status = 204;
            }
        } else {
            $success = 0;
            $msg = "Nama bahan baku tidak boleh ada yang sama";
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
