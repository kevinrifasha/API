<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('../auth/Token.php');

//init var
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
$token = '';
$res = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    $phone = $token->phone;
    $id = $_GET['id'];
    $category = $_GET['category'];
    $load = $_GET['load'];
    $offset = $_GET['offset'];
    $dateFirstDb = date('Y-m-d', strtotime('-1 week'));
    $dateLastDb = date('Y-m-d');
    $today = date('Y-m-d');
    $res = array();

    $indexRecom = 0;
    $indexFav = 0;
    $i = 0;
    $limit_recom = 0;

    if($category=="all"){

        $query = "SELECT  menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at, partner.name, categories.name as cname, categories.is_consignment, CASE WHEN stock=0 THEN 0 ELSE 1 END AS tempFlag FROM menu JOIN partner ON menu.id_partner = partner.id JOIN categories ON categories.id = menu.id_category WHERE partner.id = '$id' AND menu.deleted_at IS NULL AND menu.enabled='1' AND menu.show_in_waiter='1' GROUP BY menu.id ";

        if(isset($_GET['order']) && !empty($_GET['order'])){
            $order = $_GET['order'];
            if($order=="name_asc"){
                $query .= "ORDER BY tempFlag DESC, menu.nama ASC";
            }else if($order=="name_desc"){
                $query .= "ORDER BY tempFlag DESC, menu.nama DESC";
            }else if($order=="price_asc"){
                $query .= "ORDER BY tempFlag DESC, menu.harga ASC";
            }else if($order=="price_desc"){
                $query .= "ORDER BY tempFlag DESC, menu.harga DESC";
            }else if($order=="category_asc"){
                $query .= "ORDER BY tempFlag DESC, categories.name ASC";
            }else if($order=="category_desc"){
                $query .= "ORDER BY tempFlag DESC, categories.name DESC";
            }
        }

        $query .= " LIMIT $offset, $load ";

        $allRecom = mysqli_query($db_conn, $query);

        while($rowR=mysqli_fetch_assoc($allRecom)){
            $id_m = $rowR['id'];
            if($indexFav>0){
                $rowR['is_favorite']=false;
                foreach ($arr[0]['data'] as $fav) {
                    if($fav['id']===$id_m){
                        $rowR['is_favorite']=true;
                    }
                }
            }else{
                $rowR['is_favorite']=false;
            }

            $arr[$i]["category"] = "all";
            $arr[$i]["data"][$indexRecom] = $rowR;
            $indexRecom+=1;
        }

        if($indexRecom!=0){
            $i+=1;
        }
    }
    if($category=="recomended"){
        $lim = mysqli_query($db_conn, "SELECT value FROM `settings` WHERE name = 'limit_recommended_menu'");

        while($rowL=mysqli_fetch_assoc($lim)){
            $limit_recom = $rowL['value'];
        }
        $query = "SELECT  menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at, partner.name, categories.name as cname, categories.is_consignment, CASE WHEN stock=0 THEN 0 ELSE 1 END AS tempFlag FROM menu JOIN partner ON menu.id_partner = partner.id JOIN categories ON categories.id = menu.id_category WHERE partner.id = '$id' AND menu.is_recommended=1  AND menu.deleted_at IS NULL AND menu.enabled='1' AND menu.show_in_waiter='1' GROUP BY menu.id ";

        if(isset($_GET['order']) && !empty($_GET['order'])){
            $order = $_GET['order'];
            if($order=="name_asc"){
                $query .= "ORDER BY tempFlag DESC, menu.nama ASC";
            }else if($order=="name_desc"){
                $query .= "ORDER BY tempFlag DESC, menu.nama DESC";
            }else if($order=="price_asc"){
                $query .= "ORDER BY tempFlag DESC, menu.harga ASC";
            }else if($order=="price_desc"){
                $query .= "ORDER BY tempFlag DESC, menu.harga DESC";
            }else if($order=="category_asc"){
                $query .= "ORDER BY tempFlag DESC, categories.name ASC";
            }else if($order=="category_desc"){
                $query .= "ORDER BY tempFlag DESC, categories.name DESC";
            }
        }

        $query .= " LIMIT $offset, $load ";

        $allRecom = mysqli_query($db_conn, $query);


        while($rowR=mysqli_fetch_assoc($allRecom)){
            $id_m = $rowR['id'];
            if($indexFav>0){
                $rowR['is_favorite']=false;
                foreach ($arr[0]['data'] as $fav) {
                    if($fav['id']===$id_m){
                        $rowR['is_favorite']=true;
                    }
                }
            }else{
                $rowR['is_favorite']=false;
            }

            $arr[$i]["category"] = "Recommended";
            $arr[$i]["data"][$indexRecom] = $rowR;

            $indexRecom+=1;
        }

        if($indexRecom!=0){
            $i+=1;
        }
    }

    if($category=="best_seller"){

        $indexBS = 0;

        $allBest = mysqli_query($db_conn, "SELECT  menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at, partner.name, categories.name as cname, categories.is_consignment, sum(detail_transaksi.qty) as qty, CASE WHEN stock=0 THEN 0 ELSE 1 END AS tempFlag FROM menu  JOIN partner ON menu.id_partner = partner.id JOIN categories ON categories.id = menu.id_category JOIN detail_transaksi ON detail_transaksi.id_menu=menu.id JOIN transaksi ON transaksi.id = detail_transaksi.id_transaksi WHERE partner.id = '$id' AND menu.deleted_at IS NULL AND transaksi.deleted_at IS NULL AND DATE(transaksi.jam) BETWEEN '$dateFirstDb' AND '$dateLastDb' AND transaksi.status<=2 and transaksi.status>=1 AND menu.enabled='1' AND menu.show_in_waiter='1' GROUP BY menu.id ORDER BY tempFlag DESC, qty DESC LIMIT 6");

        while($rowB=mysqli_fetch_assoc($allBest)){
            $id_b = $rowB['id'];
            if($indexFav>0){
                $rowB['is_favorite']=false;
                foreach ($arr[0]['data'] as $fav) {
                    if($fav['id']===$id_b){
                        $rowB['is_favorite']=true;
                    }
                }
            }else{
                $rowB['is_favorite']=false;
            }
            $arr[$i]["category"] = "Best Seller";
            $arr[$i]["data"][$indexBS] = $rowB;

            $indexBS+=1;
        }

        if($indexBS!=0){
            $i+=1;
        }

    }

    if($category=="category"){
        $category_id = $_GET['category_id'];
        $allCategories = mysqli_query($db_conn, "SELECT categories.id, categories.id_master, categories.name, categories.sequence FROM categories JOIN partner ON partner.id_master = categories.id_master  WHERE categories.id='$category_id' ORDER BY `categories`.`sequence` ASC ");
        while($rowC=mysqli_fetch_assoc($allCategories)){
            $id_c = $rowC['id'];


        $query = "SELECT  menu.id, menu.id_partner, menu.nama, menu.harga, menu.Deskripsi, menu.category, menu.id_category, menu.img_data, menu.enabled, menu.stock, menu.hpp, menu.harga_diskon, menu.is_variant, menu.is_recommended, menu.is_recipe, menu.is_auto_cogs, menu.thumbnail, menu.created_at,
        partner.name, categories.is_consignment,
        categories.name as cname, CASE WHEN stock=0 THEN 0 ELSE 1 END AS tempFlag
        FROM menu
        JOIN partner ON menu.id_partner = partner.id
        JOIN categories ON categories.id = menu.id_category
        WHERE partner.id = '$id'
        AND menu.id_category = '$id_c' AND menu.deleted_at IS NULL AND menu.enabled='1' AND menu.show_in_waiter='1'
        GROUP BY menu.id ";

        if(isset($_GET['order']) && !empty($_GET['order'])){
            $order = $_GET['order'];
            if($order=="name_asc"){
                $query .= "ORDER BY tempFlag DESC, menu.nama ASC";
            }else if($order=="name_desc"){
                $query .= "ORDER BY tempFlag DESC, menu.nama DESC";
            }else if($order=="price_asc"){
                $query .= "ORDER BY tempFlag DESC, menu.harga ASC";
            }else if($order=="price_desc"){
                $query .= "ORDER BY tempFlag DESC, menu.harga DESC";
            }else if($order=="category_asc"){
                $query .= "ORDER BY tempFlag DESC, categories.name ASC";
            }else if($order=="category_desc"){
                $query .= "ORDER BY tempFlag DESC, categories.name DESC";
            }
        }

        $query .= " LIMIT $offset, $load ";

            $allMenuCategory = mysqli_query($db_conn, $query);

            $arr[$i]["category"] = $rowC['name'];
            $indexMenu = 0;
            while($rowMC=mysqli_fetch_assoc($allMenuCategory)){


            if($indexFav>0){
                $rowMC['is_favorite']=false;
                foreach ($arr[0]['data'] as $fav) {
                    $id_m = $rowMC['id'];
                    if($fav['id']===$id_m){
                        $rowMC['is_favorite']=true;
                    }
                }
            }else{
                $rowMC['is_favorite']=false;
            }


                $arr[$i]["data"][$indexMenu] = $rowMC;
                $indexMenu+=1;
            }

            $i +=1;
        }
    }
if (count($arr)>0) {
    $success = 1;
    $status = 200;
    $msg = "success";

} else {
    $success = 0;
    $status = 204;
    $msg = "Data Tidak Ada";
}
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "menus"=>$arr]);
