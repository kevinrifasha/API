<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
session_start();
require '../v2/db_connection.php';

$id = $_GET['id'];
$dateFirstDb = date('Y-m-d', strtotime('-1 week'));
$dateLastDb = date('Y-m-d');
$today = date('Y-m-d');
$res = array();

$indexRecom = 0;
$i = 0;
$limit_recom = 0;

$allCategories = mysqli_query($db_conn, "SELECT categories.* FROM categories JOIN partner ON partner.id_master = categories.id_master  WHERE partner.id='$id'  
ORDER BY `categories`.`sequence`  ASC");
while($rowC=mysqli_fetch_assoc($allCategories)){
    $id_c = $rowC['id'];
    $allMenuCategory = mysqli_query($db_conn, "SELECT menu.*,
        partner.name, 
        categories.name as cname
        FROM menu 
        JOIN partner ON menu.id_partner = partner.id
        JOIN categories ON categories.id = menu.id_category
        WHERE partner.id = '$id' 
        AND menu.id_category = '$id_c'
        GROUP BY menu.id
        ORDER BY categories.sequence ASC
        ");
    $arr[$i]["category"] = $rowC['name'];
    $indexMenu = 0;
    while($rowMC=mysqli_fetch_assoc($allMenuCategory)){    


        if($rowMC['is_recipe']=='1'){
            $id_m = $rowMC['id'];
            $recipe = mysqli_query($db_conn, "SELECT *FROM recipe WHERE recipe.id_menu = $id_m");
            $all_recipe = mysqli_fetch_all($recipe, MYSQLI_ASSOC);
            $j = 0;
            $stockRaw = mysqli_query($db_conn, "SELECT `raw_material_stock`.* FROM `raw_material_stock` JOIN recipe ON recipe.id_raw =raw_material_stock.id_raw_material WHERE recipe.id_menu='$id_m' AND DATE(raw_material_stock.exp_date)>'$today' ORDER BY raw_material_stock.id_raw_material ASC");
            $stock_raw = mysqli_fetch_all($stockRaw, MYSQLI_ASSOC);

        $j+=0;
        $first = true;
        $stocks = array();
        foreach($stock_raw as $value) {
            if($first==true){
                $stocks[$j]['id_raw']=$value['id_raw_material'];
                $stocks[$j]['stock']=(int)$value['stock'];
                $stocks[$j]['id_metric']=$value['id_metric'];
                $first = false;
            }else{
                if($stocks[$j]['id_raw']==$value['id_raw_material']){
                    if($stocks[$j]['id_metric']==$value['id_metric']){$stocks[$j]['stock']+=(int)$value['stock'];
                        $stocks[$j]['id_metric']=$value['id_metric'];
                    }else{
                        $val_convert = 1;
                        $mainMetric = 0;
                        $metric1 = $stocks[$j]['id_metric'];
                        $metric2 = $value['id_metric'];
                        $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                        if(mysqli_num_rows($metric)>0){
                            $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                            $mainMetric = $metric2;
                            $val_convert = ($stocks[$j]['stock']*$metrics[0]['value'])+$value['stock'];
                        }else{
                            $metric2 = $stocks[$j]['id_metric'];
                            $metric1 = $value['id_metric'];
                            $metric_1 = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                            if(mysqli_num_rows($metric_1)>0){
                                $metrics1 = mysqli_fetch_all($metric_1, MYSQLI_ASSOC);
                                $mainMetric = $metric2;
                                $val_convert = ($value['stock']*$metrics1[0]['value'])+$stocks[$j]['stock'];
                            }
                        }
                        $stocks[$j]['id_metric'] = $mainMetric;
                        $stocks[$j]['stock'] = $val_convert;
                    }

                }else{
                    $j+=1;
                    $stocks[$j]['id_raw']=$value['id_raw_material'];
                    $stocks[$j]['stock']=(int)$value['stock'];
                    $stocks[$j]['id_metric']=$value['id_metric'];
                }
            }
        }
        $res_stock = 999999999999999999999999;
        foreach ($all_recipe as $recipe_arr) {
            foreach ($stocks as $stock_arr) {
                if($stock_arr['id_raw']==$recipe_arr['id_raw']){
                    if($stock_arr['id_metric']==$recipe_arr['id_metric']){
                        if((int) ($stock_arr['stock']/$recipe_arr['qty'])>0){
                            if((int) ($stock_arr['stock']/$recipe_arr['qty'])<$res_stock){
                                $res_stock = (int) ($stock_arr['stock']/$recipe_arr['qty']);
                            }
                        }
                    }else{
                        $mainMetric = 0;
                        $metric1 = $stock_arr['id_metric'];
                        $metric2 = $recipe_arr['id_metric'];
                        $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                        if(mysqli_num_rows($metric)>0){
                            $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                            if((int) ($stock_arr['stock']*$metrics[0]['value'])/$recipe_arr['qty']>0){
                                if((int) ($stock_arr['stock']*$metrics[0]['value'])/$recipe_arr['qty']<$res_stock){
                                    $res_stock = (int) ($stock_arr['stock']*$metrics[0]['value'])/$recipe_arr['qty'];
                                }
                           }
                        }
                    }
                    // $i+=1;
                }
            }
        }
        if($res_stock<0 || $res_stock == 999999999999999999999999){
            $res_stock=0;
        }
        $rowMC['stock']=$res_stock;
    }


        $arr[$i]["data"][$indexMenu] = $rowMC;
        $indexMenu+=1;
    }

    $i +=1;
}

if (mysqli_num_rows($allCategories) > 0) {
    echo json_encode(["success" => 1, "Data" => $arr]);
} else {
    echo json_encode(["success" => 0]);
}
