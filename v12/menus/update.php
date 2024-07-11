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
$success=0;
$msg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];

}else{

    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);
    $data = json_decode(file_get_contents('php://input'));
    $partnerID = $tokenDecoded->partnerID;
    $addedQuery = '';
    if($data->id_partner == $partnerID){
        $partnerID = $data->id_partner;
        $addedQuery = "AND id_partner='$partnerID'";
    }
    $nama = mysqli_real_escape_string($db_conn, $data->nama);
    $desc = mysqli_real_escape_string($db_conn, $data->deskripsi);
    $MenuManager = new MenuManager($db);
    if(isset($obj['id']) && !empty($obj['id'])){
        $menuID = $obj['id'];
        $checkMasterID = "SELECT ma.id as masterID FROM menu m LEFT JOIN partner p ON p.id = m.id_partner LEFT JOIN master ma ON ma.id = p.id_master WHERE m.id='$menuID'";
        $mqCheckMasterID = mysqli_query($db_conn, $checkMasterID);
        $fetchCheckMasterID = mysqli_fetch_all($mqCheckMasterID, MYSQLI_ASSOC);
        $masterID = $fetchCheckMasterID[0]['masterID'];

        $update = false;
        $data->harga = str_replace(".","", $data->harga);
        if($masterID == $tokenDecoded->masterID){
            $update = mysqli_query($db_conn, "UPDATE menu SET sku='$data->sku', nama='$nama', harga='$data->harga', Deskripsi='$desc', id_category='$data->id_category', img_data='$data->img_data', thumbnail='$data->thumbnail', enabled='$data->enabled', stock='$data->stock', is_variant='$data->is_variant', hpp='$data->hpp', harga_diskon='$data->harga_diskon', is_recommended='$data->is_recommended', is_recipe='$data->is_recipe', is_auto_cogs='$data->is_auto_cogs',show_in_sf='$data->show_in_sf', show_in_waiter='$data->show_in_waiter' WHERE id='$data->id' " . $addedQuery . " AND deleted_at IS NULL");
    
                if($obj['is_variant']=='1' && $update!=false || $obj['is_variant']==1 && $update!=false){
                    $update = mysqli_query($db_conn, "UPDATE menus_variantgroups SET deleted_at=NOW() WHERE menu_id='$menuID'");
                    foreach($obj['variants'] as $data){
                        $idVG = $data['id_variant_group'];
                        $insert = mysqli_query($db_conn, "INSERT INTO menus_variantgroups SET menu_id='$menuID', variant_group_id='$idVG'");
                    }
                    // $menuVariantGroupsManager = new MenusVariantGroupsManager($db);
    
                    // //delete menu variant groups
                    // $menuVariantGroups = $menuVariantGroupsManager->getByMenuId($obj['id']);
                    // foreach ($menuVariantGroups as $value) {
                    //     $update = mysqli_query($db_conn, "UPDATE menus_variantgroups SET deleted_at=NOW() WHERE id='$value->id'");
                    // }
    
                    // //add menu variants groups
                    // $menuVariantGroups = $menuVariantGroupsManager->getByMenuId($obj['id']);
                    //     foreach ($obj['variants'] as $data) {
                    //         $add = true;
                    //         $vgId = 0;
                    //         foreach ($menuVariantGroups as $value) {
                    //             if($data['id_variant_group']==$value->getVariant_group_id()){
                    //                 $add=false;
                    //             }
                    //         }
                    //         if($add == true){
                    //             $mvg = new MenusVariantGroups(array("menu_id"=>$obj['id'],"variant_group_id"=>$data['id_variant_group'],"created_at"=>date('Y-m-d'), "updated_at"=>date('Y-m-d'),"deleted"=>NULL));
                    //             $add = $menuVariantGroupsManager->add($mvg);
                    //         }
                    //     }
    
                }
                if($obj['is_variant']=='0' && $update!=false || $obj['is_variant']==0 && $update!=false){
                    $update = mysqli_query($db_conn, "UPDATE menus_variantgroups SET deleted_at=NOW() WHERE menu_id='$menuID'");
                }
                if($obj['is_recipe']=='1' && $update!=false || $obj['is_recipe']==1 && $update!=false){
                        $recipeManager = new RecipeManager($db);
                        $rawIDs = $obj['recipes'];
                        $registered = $recipeManager->getByMenuId($obj['id']);
                        foreach($registered as $rgstrd){
                            $details = $rgstrd->getDetails();
                            $delete = true;
                            foreach ($rawIDs as $rawID) {
                                $ID = $details['id'];
                                $rID = $rawID['id_raw'];
                                $mID = $rawID['id_metric'];
                                $qty = $rawID['qty'];
                                if($rID==$details['id_raw'] && $mID==$details['id_metric']){
                                    $delete=false;
                                }
                            }
                            if($delete==true){
                                $deleted = $recipeManager->delete($details['id']);
                            }
                        }
                        $rawIDs = $obj['recipes'];
                        $registered = $recipeManager->getByMenuId($obj['id']);
                        foreach ($rawIDs as $rawID) {
                            $rID = $rawID['id_raw'];
                            $mID = $rawID['id_metric'];
                            $qty = $rawID['qty'];
                            $uID = 0;
                            foreach($registered as $rgstrd){
                                $details = $rgstrd->getDetails();
                                if($rID==$details['id_raw']){
                                    $uID=$details['id'];
                                }
                            }
                            if($uID==0){
                                $recipe = new Recipe(array("id"=>0,"id_menu"=>$obj['id'],"id_raw"=>$rID,"qty"=>$qty, "id_metric"=>$mID,"id_variant"=>0,"id_partner"=>$partnerID));
                                $insert = $recipeManager->add($recipe);
                            }else{
                                $recipe = new Recipe(array("id"=>$uID,"id_menu"=>$obj['id'],"id_raw"=>$rID,"qty"=>$qty, "id_metric"=>$mID,"id_variant"=>0,"id_partner"=>$partnerID));
                                $insert = $recipeManager->update($recipe);
                            }
                        }
    
                        $rawIDs = $obj['recipes'];
                        $id_partner = $obj['id_partner'];
                        // $getIAC = mysqli_query($db_conn, "SELECT is_average_cogs FROM `partner` WHERE id='$id_partner'");
                        // $IAC = mysqli_fetch_all($getIAC, MYSQLI_ASSOC);
                        // $is_average_cogs = (int) $IAC[0]['is_average_cogs'];
    
                        if($obj['is_auto_cogs']=="1"){
                            foreach ($rawIDs as $rawID1) {
                                $mnID = $obj['id'];
                                $rawID = $rawID1['id_raw'];
                                $getMenu = mysqli_query($db_conn, "SELECT id_menu FROM recipe WHERE id_raw='$rawID' AND id_menu='$mnID'");
                                $menus = mysqli_fetch_all($getMenu, MYSQLI_ASSOC);
                                foreach ($menus as $menu) {
                                    $cogs = 0;
                                    $rawPrices = 0;
                                    $menuID = $menu['id_menu'];
                                    $getRecipe = mysqli_query($db_conn, "SELECT id_raw, qty,id_metric FROM recipe WHERE id_menu='$menuID'");
                                    $recipe = mysqli_fetch_all($getRecipe, MYSQLI_ASSOC);
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
                                            // $getPO = mysqli_query($db_conn, "SELECT qty, metric_id, price, price/qty unit_price FROM `purchase_orders_details` WHERE raw_id='$recipeRawID' AND deleted_at IS NULL ORDER BY unit_price DESC LIMIT 1");
                                            // $po = mysqli_fetch_all($getPO, MYSQLI_ASSOC);
                                            $i = 0;
    
                                            $poMetricID = 0;
                                            $poQty = 0;
                                            $poPrice = 0;
                                            // foreach ($po as $item) {
                                            //     $poMetricID = $item['metric_id'];
                                            //     $poQty =(int) $item['qty'];
                                            //     $poPrice =(int) $item['price'];
                                            //     $rawPrice =(int) $item['unit_price'];
                                            // }
    
                                            // $rawMetric = $poMetricID;
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
    
            $idPartner = $obj['id_partner'];
            $menuPrice = $obj['harga'];
            $surchargeData = $obj['surchargeData'];
            if(count($surchargeData) > 0 && $obj['is_multiple_price'] > 0) {
                $getSurchargeMenu = mysqli_query($db_conn, "SELECT id, surcharge_id, price, menu_id FROM `menu_surcharge_types` WHERE partner_id='$idPartner' AND menu_id='$menuID' AND deleted_at IS NULL ORDER BY `id` DESC");
                $dataMenuSurcharge = mysqli_fetch_all($getSurchargeMenu, MYSQLI_ASSOC);
                $surcharges = $dataMenuSurcharge;
                $processedSurcharge = [];
                $getSurcharge = [];
                $comparisonSurchargeProc = [];
                $comparisonSurchargeGet = [];
                $i = 0;
                foreach($surchargeData as $val) {
                    $processedSurcharge[$i]["surcharge_id"] = $val["id"]; 
                    $processedSurcharge[$i]["price"] = $val["price"]; 
                    $processedSurcharge[$i]["menu_id"] = $menuID; 
                    $comparisonSurchargeProc[$i] = json_encode($processedSurcharge[$i]);
                    $i++;
                }
                
                $j = 0;
                foreach($surcharges as $sur) {
                    $getSurcharge[$j]["surcharge_id"] = $sur["surcharge_id"]; 
                    $getSurcharge[$j]["price"] = $sur["price"]; 
                    $getSurcharge[$j]["menu_id"] = $menuID; 
                    $comparisonSurchargeGet[$j] = json_encode($getSurcharge[$j]);
                    $j++;
                }
                
                $arrTest = [];
                $k = 0;
                foreach($comparisonSurchargeProc as $ps){
                    $test = in_array($ps, $comparisonSurchargeGet);
                    $arrTest[$k] = $test;
                    if(in_array($ps, $comparisonSurchargeGet)){
                        $k++;
                        continue;
                    } else {
                        //Get data of surcharges with same menu id, same surcharge, same partner_id
                        $surID = $processedSurcharge[$k]["surcharge_id"];
                        $price = $processedSurcharge[$k]["price"];
                        $getSurchargeMenuLoop = mysqli_query($db_conn, "SELECT id  FROM `menu_surcharge_types` WHERE partner_id='$idPartner' AND menu_id='$menuID' AND surcharge_id='$surID'  AND deleted_at IS NULL ORDER BY `id` DESC LIMIT 1");
                        $fetchIdMenuLoop = mysqli_fetch_all($getSurchargeMenuLoop);
                        $idMenuLoop = $fetchIdMenuLoop[0]["id"];
                        
                        //Update deleted at surcharge like that
                        $updateMenu = mysqli_query($db_conn, "UPDATE menu_surcharge_types SET deleted_at=NOW() WHERE id='$idMenuLoop' AND deleted_at IS NULL");
                        
                        // insert new surcharge
                        $qInsert = mysqli_query($db_conn, "INSERT INTO menu_surcharge_types SET menu_id='$menuID', surcharge_id='$surID', partner_id='$idPartner', price='$price'");
                        $k++;
                    }
                }
                
                $l = 0;
                foreach($comparisonSurchargeGet as $gs){
                    $test = in_array($gs, $comparisonSurchargeProc);
                    if(in_array($gs, $comparisonSurchargeProc)){
                        $l++;
                        continue;
                    } else {
                        $gSurId = $surcharges[$l]["id"];
                        //Update deleted at surcharge like that
                        $updateMenu = mysqli_query($db_conn, "UPDATE menu_surcharge_types SET deleted_at=NOW() WHERE partner_id='$idPartner' AND menu_id='$menuID' AND id='$gSurId' AND deleted_at IS NULL");
                        $l++;
                    }
                }
            } else {
                $updateMenu = mysqli_query($db_conn, "UPDATE menu_surcharge_types SET deleted_at=NOW() WHERE partner_id='$idPartner' AND menu_id='$menuID' AND deleted_at IS NULL");
            }
        } else {
            $update = false;
        }

        if($update!=false){
            $success=1;
            $msg="Success";
            $status=200;
        }else{
            $success=0;
            $msg="Failed";
            $status=503;
        }

    }else{

        $success = 0;
        $msg = "Missing Mandatory Field";
        $status = 400;

    }

}

$signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);
echo $signupJson;
