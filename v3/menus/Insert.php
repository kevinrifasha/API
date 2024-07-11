<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../menuModels/menuManager.php");
require_once("./../tokenModels/tokenManager.php");
require_once("./../recipeModels/recipeManager.php");
require_once("./../menusVariantGroupsModels/menusVariantGroupsManager.php");
require '../../db_connection.php';


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
$surchargeData;
$menu_id;

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;
}else{
    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    if(isset($obj['nama']) && !empty($obj['nama'])){
        if(isset($obj['Deskripsi']) && !empty($obj['Deskripsi'])){
        }else{
            $obj['Deskripsi']="-";
        }
        $menu = new Menu(array("nama"=>str_replace("'","''",$obj['nama']),"sku"=>$obj['sku'],"id_partner"=>$obj['id_partner'],"harga"=>$obj['harga'],"Deskripsi"=>str_replace("'","''",$obj['Deskripsi']),"id_category"=>$obj['id_category'],"category"=>"","img_data"=>$obj['img_data'],"enabled"=>$obj['enabled'],"stock"=>$obj['stock'],"is_variant"=>$obj['is_variant'],"hpp"=>$obj['hpp'],"harga_diskon"=>$obj['harga_diskon'],"is_recommended"=>0,"is_recipe"=>$obj['is_recipe'],"thumbnail"=>$obj['thumbnail'],"is_auto_cogs"=>$obj['is_auto_cogs'],"is_multiple_price"=>$obj['is_multiple_price'],"show_in_sf"=>$obj['show_in_sf']));

        $MenuManager = new MenuManager($db);
        $insert = $MenuManager->add($menu);
        $partnerID = $obj['id_partner'];
        $initialStock = $obj['stock'];
        if($obj['is_recipe']=='0'||$obj['is_recipe']==0){
            $qMovement="INSERT INTO stock_movements SET master_id='$tokenDecoded->masterID', partner_id='$partnerID', menu_id='$insert', metric_id='6', type=0, initial='$initialStock', remaining='$initialStock'";
        $movement = mysqli_query($db_conn, $qMovement);
        }

        if($obj['is_variant']=='1' && $insert!=false || $obj['is_variant']==1 && $insert!=false){

            foreach ($obj['variants'] as $value) {
                // var_dump($value);
                $MenusVariantGroupsManager = new MenusVariantGroupsManager($db);
                $MenusVariantGroups = new MenusVariantGroups(array("menu_id"=>$insert,"variant_group_id"=>$value['id_variant_group'],"created_at"=>date('Y-m-d H:i:s'), "updated_at"=>date('Y-m-d H:i:s'),"deleted_at"=>null));
                // var_dump($MenusVariantGroups);
                $add = $MenusVariantGroupsManager->add($MenusVariantGroups);
            }
        }
        
        if($obj['is_recipe']=='1' && $insert!=false || $obj['is_recipe']==1 && $insert!=false){
            foreach ($obj['recipes'] as $value) {
                $recipeManager = new RecipeManager($db);
                $recipes = new Recipe(array("id_menu"=>$insert,"id_raw"=>$value['id_raw'],"qty"=>$value['qty'],"id_metric"=>$value['id_metric'],"id_variant"=>0));
                $add = $recipeManager->add($recipes);


                $rawID = $value['id_raw'];
                $getMenu = mysqli_query($db_conn, "SELECT id_menu FROM recipe WHERE id_raw='$rawID' AND id_menu='$insert'");
                $menus = mysqli_fetch_all($getMenu, MYSQLI_ASSOC);
                foreach ($menus as $menu) {
                    $cogs = 0;
                    $rawPrices = 0;
                    $menuID = $menu['id_menu'];
                    $getRecipe = mysqli_query($db_conn, "SELECT id_raw, qty,id_metric FROM recipe WHERE id_menu='$menuID'");
                    $recipe = mysqli_fetch_all($getRecipe, MYSQLI_ASSOC);
                    if($obj['is_auto_cogs']=="1"){

                        $id_partner = $obj['id_partner'];
                        $getIAC = mysqli_query($db_conn, "SELECT is_average_cogs FROM `partner` WHERE id='$id_partner'");
                        $IAC = mysqli_fetch_all($getIAC, MYSQLI_ASSOC);
                        $is_average_cogs = (int) $IAC[0]['is_average_cogs'];

                        if($is_average_cogs==0){
                            foreach ($recipe as $raw) {
                                $rawPrice = 0;
                                $recipeRawID = $raw['id_raw'];
                                $getRaw = mysqli_query($db_conn, "SELECT unit_price, id_metric_price FROM `raw_material` WHERE id='$recipeRawID'");
                                $raws = mysqli_fetch_all($getRaw, MYSQLI_ASSOC);
                                $price = (int) $raws[0]['unit_price'];
                                $id_metric = $raws[0]['id_metric_price'];
                                $getPO = mysqli_query($db_conn, "SELECT qty, metric_id, price, price/qty unit_price FROM `purchase_orders_details` WHERE raw_id='$recipeRawID' AND deleted_at IS NULL ORDER BY unit_price DESC LIMIT 1");
                                $po = mysqli_fetch_all($getPO, MYSQLI_ASSOC);
                                $i = 0;

                                $poMetricID = 0;
                                $poQty = 0;
                                $poPrice = 0;
                                foreach ($po as $item) {
                                    $poMetricID = $item['metric_id'];
                                    $poQty =(int) $item['qty'];
                                    $poPrice =(int) $item['price'];
                                    $rawPrice =(int) $item['unit_price'];
                                }

                                $rawMetric = $poMetricID;
                                if($price>$rawPrice){
                                    $rawPrice = $price;
                                    $rawMetric = $id_metric;
                                }

                                if($rawMetric==$raw['id_metric']){
                                    $rawPrices += $rawPrice*$raw['qty'];
                                }else{
                                    $id_metric=$raw['id_metric'];
                                    $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$rawMetric' AND `id_metric2`='$id_metric'");
                                    if (mysqli_num_rows($getMC) > 0) {
                                        $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                        $conVal = (int) $mc[0]['value'];
                                        $rawPrices +=$rawPrice/$conVal*$raw['qty'];
                                    }else{
                                        $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$id_metric' AND `id_metric2`='$rawMetric'");
                                        $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                        $conVal = (int) $mc[0]['value'];
                                        $rawPrices +=$rawPrice*$conVal*$raw['qty'];
                                    }
                                }
                                $cogs=ceil($rawPrices);
                            }
                        }else{
                            foreach ($recipe as $raw) {
                                $rawPrice = 0;
                                $recipeRawID = $raw['id_raw'];
                                $getRaw = mysqli_query($db_conn, "SELECT unit_price, id_metric_price FROM `raw_material` WHERE id='$recipeRawID'");
                                $raws = mysqli_fetch_all($getRaw, MYSQLI_ASSOC);
                                $price = (int) $raws[0]['unit_price'];
                                $id_metric = $raws[0]['id_metric_price'];
                                $getPO = mysqli_query($db_conn, "SELECT qty, metric_id, price FROM `purchase_orders_details` WHERE raw_id='$recipeRawID' AND deleted_at IS NULL");
                                $po = mysqli_fetch_all($getPO, MYSQLI_ASSOC);
                                $i = 0;

                                $poMetricID = 0;
                                $poQty = 0;
                                $poPrice = 0;
                                foreach ($po as $item) {
                                    if($i==0){
                                        $poMetricID = $item['metric_id'];
                                        $poQty =(int) $item['qty'];
                                        $poPrice =(int) $item['price'];
                                    }else{
                                        $poPrice +=(int) $item['price'];
                                        if($poMetricID==$item['metric_id']){
                                            $poQty +=(int) $item['qty'];
                                        }else{
                                            $findMetricID = $item['metric_id'];
                                            $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$poMetricID' AND `id_metric2`='$findMetricID'");
                                            if (mysqli_num_rows($getMC) > 0) {
                                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                                $poMetricID = $findMetricID;
                                                $conVal = (int) $mc[0]['value'];
                                                $poQty =$poQty*$conVal;
                                                $poQty +=(int) $item['qty'];
                                            }else{
                                                $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$findMetricID' AND `id_metric2`='$poMetricID'");
                                                $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                                $conVal = (int) $mc[0]['value'];
                                                $item['qty'] =$item['qty']*$conVal;
                                                $poQty +=(int) $item['qty'];
                                            }
                                        }
                                    }
                                    $i+=1;
                                }
                                if($poQty>0 && $poPrice>0){
                                    $rawPrice = $poPrice/$poQty;
                                }
                                $rawMetric = $poMetricID;
                                if($rawMetric==$id_metric || $poMetricID==0){
                                    $rawPrice += $price;
                                    if($poMetricID!=0){
                                        $rawPrice = $rawPrice/2;
                                    }else{
                                        $rawMetric = $id_metric;
                                    }
                                }else{
                                    $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$rawMetric' AND `id_metric2`='$id_metric'");
                                    if (mysqli_num_rows($getMC) > 0) {
                                        $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                        $rawMetric = $id_metric;
                                        $conVal = (int) $mc[0]['value'];
                                        $rawPrice =$rawPrice/$conVal;
                                        $rawPrice+=$price;
                                        $rawPrice = $rawPrice/2;
                                    }else{
                                        $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$id_metric' AND `id_metric2`='$rawMetric'");
                                        $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                        $conVal = (int) $mc[0]['value'];
                                        $price =$price/$conVal;
                                        $rawPrice+=$price;
                                        $rawPrice = $rawPrice/2;
                                    }
                                }
                                if($rawMetric==$raw['id_metric']){
                                    $rawPrices += $rawPrice*$raw['qty'];
                                }else{
                                    $id_metric=$raw['id_metric'];
                                    $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$rawMetric' AND `id_metric2`='$id_metric'");
                                    if (mysqli_num_rows($getMC) > 0) {
                                        $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                        $conVal = (int) $mc[0]['value'];
                                        $rawPrices +=$rawPrice/$conVal*$raw['qty'];
                                    }else{
                                        $getMC = mysqli_query($db_conn, "SELECT `value` FROM `metric_convert` WHERE `id_metric1`='$id_metric' AND `id_metric2`='$rawMetric'");
                                        $mc = mysqli_fetch_all($getMC, MYSQLI_ASSOC);
                                        $conVal = (int) $mc[0]['value'];
                                        $rawPrices +=$rawPrice*$conVal*$raw['qty'];
                                    }
                                }
                                $cogs=ceil($rawPrices);
                            }
                        }
                        $updateMenu = mysqli_query($db_conn, "UPDATE `menu` SET `hpp`='$cogs' WHERE id='$menuID'");
                    }
                }
                
                


            }
        }
        
        // masukan surchargenya
        // get id menu terakhir
        $getLastMenuID = mysqli_query($db_conn, "SELECT MAX(m.id) AS menu_id FROM `menu` m ORDER BY `id` DESC");
        $dataMenuID = mysqli_fetch_all($getLastMenuID, MYSQLI_ASSOC);
        $menu_id = $dataMenuID[0]['menu_id'];
        
        $menuPrice = $obj['harga'];
        $surchargeData = $obj['surchargeData'];
        $idPartner = $obj['id_partner'];
        if(count($surchargeData) > 0 && $obj['is_multiple_price'] > 0) {
            foreach($surchargeData as $val) {
                $surcharge_id=$val['surchargeId'];
                $price=$val['price'];
                // if($price == 0) { 
                //     $price = $menuPrice;
                // }
                if($surcharge_id) {
                    $queryAdd = "INSERT INTO menu_surcharge_types SET menu_id='$menu_id', surcharge_id='$surcharge_id', partner_id='$idPartner', price='$price', created_at=NOW()";
                    $insertSurcharges = mysqli_query($db_conn, $queryAdd);
                }
                                
            }
        }
        // masukan surchargenya end
        
        if($insert==true){
            $success=1;
            $signupMsg="Success";
            $status=200;
        }
        
        else{
            $success=0;
            $signupMsg="Failed";
            $status=503;
        }
    }
    else{
        $success=0;
        $signupMsg="Missing require filed's";
        $status=400;
    }
}

    $signupJson = json_encode(["msg"=>$signupMsg, "status"=>$status,"success"=>$success, "test"=>$queryAdd]);
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;

 ?>
