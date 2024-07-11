<?php



date_default_timezone_set('Asia/Jakarta');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
class functions
{
    private $conn;
    private $conn2;

    public function rupiah($angka)
    {
        $hasil_rupiah = "Rp" . number_format((int) $angka, 0, ',', '.');
        return $hasil_rupiah;
    }

    public function tgl_indo($tanggal)
    {
        $bulan = array(
            1 =>   'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );
        return $bulan[(int)$tanggal];
    }

    public function variant_stock_reduce($variantID, $qty, $offline = 0)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $qtyOrder = $qty;
        $updateStockMenu = mysqli_query($db_conn, "UPDATE `variant` SET `stock`=`stock`-$qty WHERE id='$variantID'");
        $qMn = mysqli_query($db_conn, "SELECT is_recipe FROM `variant` WHERE id='$variantID'");
        if (mysqli_num_rows($qMn) > 0) {
            $menus = mysqli_fetch_all($qMn, MYSQLI_ASSOC);
            $menu = $menus[0];
            if ($menu['is_recipe'] != '0') {
                //Recipe
                $qRc = mysqli_query($db_conn, "SELECT id_raw, qty, id_metric FROM `recipe` WHERE id_variant='$variantID' AND deleted_at IS NULL");
                if (mysqli_num_rows($qRc) > 0) {
                    $recipes = mysqli_fetch_all($qRc, MYSQLI_ASSOC);
                    // if($offline == 1){
                    //     $this->reduce_recipe_until_minus($db_conn,$recipes,$qtyOrder);
                    // } else {
                    //     $this->reduce_recipe($db_conn,$recipes,$qtyOrder);
                    // }
                    $this->reduce_recipe_until_minus($db_conn, $recipes, $qtyOrder);
                }
            }
        }
        return true;
    }
    // public function stock_reduce($menuID, $qty, $offline = 0)
    public function stock_reduce($menuID, $qty, $offline = 0, $masterID, $partnerID, $isRecipe)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $remaining = 0;
        $qtyOrder = $qty;
        $updateStockMenu = mysqli_query($db_conn, "UPDATE `menu` SET `stock`=`stock`-$qty WHERE id='$menuID'");
        // $qMn = mysqli_query($db_conn, "SELECT id, id_partner, is_recipe FROM `menu` WHERE id='$menuID'");
        // if (mysqli_num_rows($qMn) > 0) {
        // $menus = mysqli_fetch_all($qMn, MYSQLI_ASSOC);
        // $menu = $menus[0];
        // if ($menu['is_recipe'] != '0' || $menu['is_recipe'] != 0) {

        if ($isRecipe != '0' || $isRecipe != 0) {
            //Recipe
            $qRc = mysqli_query($db_conn, "SELECT * FROM `recipe` WHERE id_menu='$menuID' AND deleted_at IS NULL");
            if (mysqli_num_rows($qRc) > 0) {
                $recipes = mysqli_fetch_all($qRc, MYSQLI_ASSOC);
                // if($offline == 1){
                //     $this->reduce_recipe_until_minus($db_conn,$recipes,$qtyOrder);
                // } else {
                //     $this->reduce_recipe($db_conn,$recipes,$qtyOrder);
                // }
                $this->reduce_recipe_until_minus($db_conn, $recipes, $qtyOrder);
            }
        } else {
            $remainingStock = mysqli_query($db_conn, "SELECT remaining FROM stock_movements WHERE menu_id='$menuID' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
            if (mysqli_num_rows($remainingStock) > 0) {
                $resRS =  mysqli_fetch_all($remainingStock, MYSQLI_ASSOC);
                $remaining = (float)$resRS[0]['remaining'];
            } else {
                $remaining = 0;
            }
            $remaining = $remaining - $qty;
            // $partnerID = $menu['id_partner'];
            $track = mysqli_query($db_conn, "INSERT INTO stock_movements SET menu_id='$menuID', metric_id='6', qty='$qty', remaining='$remaining', partner_id='$partnerID', master_id='$masterID'");
        }
        // }
        return true;
    }

    private function reduce_recipe($db_conn, $recipes, $qtyOrder)
    {
        //Raw Material Stock
        $rawMaterialStocks = array();
        $irms = 0;
        foreach ($recipes as $valueR) {
            $rawID = $valueR['id_raw'];
            $qTemp = mysqli_query($db_conn, "SELECT * FROM `raw_material_stock` WHERE id_raw_material='$rawID' AND DATE(exp_date)>NOW() AND deleted_at IS NULL");
            $rawMaterialStocks[$irms] = mysqli_fetch_all($qTemp, MYSQLI_ASSOC);
            $irms += 1;
        }
        //update stock
        //cek Resep
        $remaining = 0;
        foreach ($recipes as $valueR) {
            $minStock = ($valueR['qty'] * $qtyOrder);
            $rawID = $valueR['id_raw'];
            $metricID = $valueR['id_metric'];
            $remainingStock = mysqli_query($db_conn, "SELECT remaining FROM stock_movements WHERE raw_id='$rawID' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
            if (mysqli_num_rows($remainingStock) > 0) {
                $resRS =  mysqli_fetch_all($remainingStock, MYSQLI_ASSOC);
                $remaining = (float)$resRS[0]['remaining'];
            } else {
                $remaining = 0;
            }
            $remaining = $remaining - $minStock;
            $track = mysqli_query($db_conn, "INSERT INTO stock_movements SET raw_id='$rawID', metric_id='$metricID', qty='$minStock', remaining='$remaining'");
            foreach ($rawMaterialStocks as $valueLRMS) {
                foreach ($valueLRMS as $valueRMS) {
                    if ($minStock > 0) {
                        if ($valueR['id_raw'] == $valueRMS['id_raw_material']) {
                            if ($valueR['id_metric'] == $valueRMS['id_metric']) {
                                $stockMC = $valueRMS['stock'] - $minStock;
                                if ($stockMC >= 0) {
                                    $minMCStock = $minStock;
                                } else {
                                    $minMCStock = $valueRMS['stock'];
                                }
                                $minStock = $minStock - $minMCStock;
                                $rmsID = $valueRMS['id'];
                                $rmsStock = $valueRMS['stock'] - $minMCStock;
                                $updateStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`='$rmsStock', updated_at=NOW() WHERE id='$rmsID'");
                            } else {
                                $idm = $valueR['id_metric'];
                                $findMC = $valueRMS['id_metric'];
                                $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$idm}' AND `id_metric2`='{$findMC}' ");
                                if (mysqli_num_rows($qMC) == 0) {
                                    $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$findMC}' AND `id_metric2`='{$idm}' ");
                                    $mcVal  = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                                    $mcVal = $mcVal[0];
                                    $stockMC = $valueRMS['stock'] * $mcVal['value'] - $minStock;
                                    if ($stockMC >= 0) {
                                        $minMCStock = $minStock;
                                    } else {
                                        $minMCStock = $valueRMS['stock'];
                                    }
                                    $valueRMS['id_metric'] = $valueR['id_metric'];
                                    $valueRMS['stock'] = ($valueRMS['stock'] * $mcVal['value']) - $minMCStock;
                                    $rmsID = $valueRMS['id'];
                                    $rmsMetricID = $valueRMS['id_metric'];
                                    $rmsStock = $valueRMS['stock'];
                                    $updateStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`='$rmsStock', `id_metric`='$rmsMetricID', updated_at=NOW() WHERE id='$rmsID'");
                                } else {
                                    $mcVal  = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                                    $mcVal = $mcVal[0];
                                    $valueR['id_metric'] = $valueRMS['id_metric'];
                                    $minStock = $minStock * $mcVal['value'];
                                    $stockMC = ($minStock - $valueRMS['stock']);
                                    if ($stockMC >= 0) {
                                        $minMCStock = $minStock;
                                    } else {
                                        $minMCStock = $valueRMS['stock'];
                                    }
                                    $valueRMS['id_metric'] = $valueR['id_metric'];
                                    $valueRMS['stock'] = ($valueRMS['stock']) - $minStock;
                                    $minStock =  $stockMC;
                                    $rmsID = $valueRMS['id'];
                                    $rmsMetricID = $valueRMS['id_metric'];
                                    $rmsStock = $valueRMS['stock'];
                                    $updateStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`='$rmsStock', `id_metric`='$findMC', updated_at=NOW() WHERE id='$rmsID'");
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    private function reduce_recipe_until_minus($db_conn, $recipes, $qtyOrder)
    {
        //Raw Material Stock
        $irms = 0;

        foreach ($recipes as $valueR) {
            $minStock = ($valueR['qty'] * $qtyOrder);
            $rawID = $valueR['id_raw'];
            $metricID = $valueR['id_metric'];
            $remainingStock = mysqli_query($db_conn, "SELECT remaining FROM stock_movements WHERE raw_id='$rawID' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
            if (mysqli_num_rows($remainingStock) > 0) {
                $resRS =  mysqli_fetch_all($remainingStock, MYSQLI_ASSOC);
                $remaining = (float)$resRS[0]['remaining'];
            } else {
                $remaining = 0;
            }
            $remaining = $remaining - $minStock;
            $track = mysqli_query($db_conn, "INSERT INTO stock_movements SET raw_id='$rawID', metric_id='$metricID', qty='$minStock', remaining='$remaining'");
            $qTemp = mysqli_query($db_conn, "SELECT * FROM `raw_material_stock` WHERE id_raw_material='$rawID' AND DATE(exp_date)>NOW() AND deleted_at IS NULL");
            if (mysqli_num_rows($qTemp) > 0) {
                $resTemp = mysqli_fetch_all($qTemp, MYSQLI_ASSOC);
                $idRMS = $resTemp[0]['id'];
                if ($valueR['id_metric'] == $resTemp[0]['id_metric']) {
                    $addStock = $qtyOrder * $valueR['qty'];
                    $qRc = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`=`stock`-'$addStock', updated_at=NOW() WHERE `id`='$idRMS'");
                } else {
                    $idm = $valueR['id_metric'];
                    $findMC = $resTemp[0]['id_metric'];
                    $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$idm}' AND `id_metric2`='{$findMC}' ");
                    if (mysqli_num_rows($qMC) == 0) {
                        $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$findMC}' AND `id_metric2`='{$idm}'");
                        $mcVal  = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                        $mcVal = $mcVal[0];
                        $stockMC = ($resTemp[0]['stock'] * $mcVal['value']) - ($qtyOrder * $valueR['$qty']);
                        $rmsMetricID = $valueR['id_metric'];
                        $updateStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`='$stockMC', `id_metric`='$rmsMetricID', updated_at=NOW() WHERE id='$idRMS'");
                    } else {
                        $mcVal  = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                        $mcVal = $mcVal[0];
                        $valueR['id_metric'] = $resTemp[0]['id_metric'];
                        $minStock = $qtyOrder * $mcVal['value'];
                        $resTemp[0]['stock'] = ($resTemp[0]['stock']) - $minStock;
                        $rmsStock = $resTemp[0]['stock'];
                        $updateStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`='$rmsStock', `id_metric`='$findMC', updated_at=NOW() WHERE id='$idRMS'");
                    }
                }
            }
        }
    }


    public function variant_stock_return($variantID, $qty)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $qtyOrder = $qty;
        $updateStockMenu = mysqli_query(
            $db_conn,
            "UPDATE `variant` SET `stock`=`stock`+$qty, updated_at=NOW() WHERE id='$variantID'"
        );
        $qMn = mysqli_query($db_conn, "SELECT is_recipe FROM `variant` WHERE id='$variantID'");
        if (mysqli_num_rows($qMn) > 0) {
            $menus = mysqli_fetch_all($qMn, MYSQLI_ASSOC);
            $menu = $menus[0];
            if ($menu['is_recipe'] != '0') {
                //Recipe
                $qRc = mysqli_query($db_conn, "SELECT id_raw,id_metric, qty  FROM `recipe` WHERE id_variant='$variantID' AND deleted_at IS NULL");
                if (mysqli_num_rows($qRc) > 0) {
                    $recipes = mysqli_fetch_all($qRc, MYSQLI_ASSOC);
                    $this->return_recipe($db_conn, $recipes, $qtyOrder);
                }
            }
        }
        return true;
    }


    public function stock_return($menuID, $qty)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $qtyOrder = $qty;
        $updateStockMenu = mysqli_query($db_conn, "UPDATE `menu` SET `stock`=`stock`+$qty, updated_at=NOW() WHERE id='$menuID'");
        $today = date("Y-m-d H:i:s");
        $qMn = mysqli_query($db_conn, "SELECT id_partner, is_recipe FROM `menu` WHERE id='$menuID' ");
        if (mysqli_num_rows($qMn) > 0) {
            $menus = mysqli_fetch_all($qMn, MYSQLI_ASSOC);
            $menu = $menus[0];
            $partnerID = $menu['id_partner'];
            if ($menu['is_recipe'] != '0' || $menu['is_recipe'] != 0) {
                //Recipe
                $qRc = mysqli_query($db_conn, "SELECT id_raw, id_metric, qty FROM `recipe` WHERE id_menu='$menuID' AND deleted_at IS NULL");
                if (mysqli_num_rows($qRc) > 0) {
                    $recipes = mysqli_fetch_all($qRc, MYSQLI_ASSOC);
                    $this->return_recipe($db_conn, $recipes, $qtyOrder);
                }
            } else {
                $remainingStock = mysqli_query($db_conn, "SELECT remaining FROM stock_movements WHERE menu_id='$menuID' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
                if (mysqli_num_rows($remainingStock) > 0) {
                    $resRS =  mysqli_fetch_all($remainingStock, MYSQLI_ASSOC);
                    $remaining = (float)$resRS[0]['remaining'];
                } else {
                    $remaining = 0;
                }
                $remaining = $remaining + $qty;
                $track = mysqli_query($db_conn, "INSERT INTO stock_movements SET partner_id='$partnerID', menu_id='$menuID', metric_id='6', returned='$qty', remaining='$remaining'");
            }
        }
        return true;
    }
    private function return_recipe($db_conn, $recipes, $qtyOrder)
    {
        //Raw Material Stock
        $rawMaterialStocks = array();
        $irms = 0;
        foreach ($recipes as $valueR) {
            $minStock = ($valueR['qty'] * $qtyOrder);
            $rawID = $valueR['id_raw'];
            $metricID = $valueR['id_metric'];
            $remainingStock = mysqli_query($db_conn, "SELECT remaining FROM stock_movements WHERE raw_id='$rawID' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
            if (mysqli_num_rows($remainingStock) > 0) {
                $resRS =  mysqli_fetch_all($remainingStock, MYSQLI_ASSOC);
                $remaining = (float)$resRS[0]['remaining'];
            } else {
                $remaining = 0;
            }
            $remaining = $remaining + $minStock;
            $track = mysqli_query($db_conn, "INSERT INTO stock_movements SET raw_id='$rawID', metric_id='$metricID', returned='$minStock', remaining='$remaining'");
            $qTemp = mysqli_query($db_conn, "SELECT * FROM `raw_material_stock` WHERE id_raw_material='$rawID' AND deleted_at IS NULL ORDER BY exp_date DESC LIMIT 1");
            if (mysqli_num_rows($qTemp) > 0) {
                $resTemp = mysqli_fetch_all($qTemp, MYSQLI_ASSOC);
                $idRMS = $resTemp[0]['id'];
                if ($valueR['id_metric'] == $resTemp[0]['id_metric']) {
                    $addStock = $qtyOrder * $valueR['qty'];
                    $qRc = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`=`stock`+'$addStock', updated_at=NOW() WHERE `id`='$idRMS'");
                } else {
                    $idm = $valueR['id_metric'];
                    $findMC = $resTemp[0]['id_metric'];
                    $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$idm}' AND `id_metric2`='{$findMC}' ");
                    if (mysqli_num_rows($qMC) == 0) {
                        $qMC = mysqli_query($db_conn, "SELECT * FROM `metric_convert` WHERE id_metric1='{$findMC}' AND `id_metric2`='{$idm}'");
                        $mcVal  = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                        $mcVal = $mcVal[0];
                        $stockMC = ($resTemp[0]['stock'] * $mcVal['value']) + ($qtyOrder * $valueR['$qty']);
                        $rmsMetricID = $valueR['id_metric'];
                        $updateStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`='$stockMC', `id_metric`='$rmsMetricID', updated_at=NOW() WHERE id='$idRMS'");
                    } else {
                        $mcVal  = mysqli_fetch_all($qMC, MYSQLI_ASSOC);
                        $mcVal = $mcVal[0];
                        $valueR['id_metric'] = $resTemp[0]['id_metric'];
                        $minStock = $qtyOrder * $mcVal['value'];
                        $resTemp[0]['stock'] = ($resTemp[0]['stock']) + $minStock;
                        $rmsStock = $resTemp[0]['stock'];
                        $updateStock = mysqli_query($db_conn, "UPDATE `raw_material_stock` SET `stock`='$rmsStock', `id_metric`='$findMC', updated_at=NOW() WHERE id='$idRMS'");
                    }
                }
            }
        }
    }

    public function trigger_update_stock(){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $_ENV['BASEURL']."crons/updateStock.php");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($curl);
        curl_close($curl);
    }

    public function stock_menu($rowR1)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $indexRecom = 0;
        $i = 0;
        $arr = array();
        $today = date('Y-m-d');
        foreach ($rowR1 as $rowR) {
            $id_m = $rowR['id'];
            if ($rowR['is_recipe'] == '1') {
                $recipe = mysqli_query($db_conn, "SELECT * FROM recipe WHERE recipe.id_menu = $id_m AND deleted_at IS NULL");
                if (mysqli_num_rows($recipe) == 0){
                    $rowR['is_recipe'] = '0';
                }
                $all_recipe = mysqli_fetch_all($recipe, MYSQLI_ASSOC);
                $j = 0;
                $stockRaw = mysqli_query($db_conn, "SELECT `raw_material_stock`.* FROM `raw_material_stock` JOIN recipe ON recipe.id_raw =raw_material_stock.id_raw_material WHERE recipe.id_menu='$id_m' AND DATE(raw_material_stock.exp_date)>'$today' ORDER BY raw_material_stock.id_raw_material ASC");
                $stock_raw = mysqli_fetch_all($stockRaw, MYSQLI_ASSOC);
                $first = true;
                $stocks = array();
                foreach ($stock_raw as $value) {
                    if ($first == true) {
                        $stocks[$j]['id_raw'] = $value['id_raw_material'];
                        $stocks[$j]['stock'] = (int)$value['stock'];
                        $stocks[$j]['id_metric'] = $value['id_metric'];
                        $first = false;
                    } else {
                        if ($stocks[$j]['id_raw'] == $value['id_raw_material']) {
                            if ($stocks[$j]['id_metric'] == $value['id_metric']) {
                                $stocks[$j]['stock'] += (int)$value['stock'];
                                $stocks[$j]['id_metric'] = $value['id_metric'];
                            } else {
                                $val_convert = 1;
                                $mainMetric = 0;
                                $metric1 = $stocks[$j]['id_metric'];
                                $metric2 = $value['id_metric'];
                                $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                                if (mysqli_num_rows($metric) > 0) {
                                    $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                                    $mainMetric = $metric2;
                                    $val_convert = ($stocks[$i]['stock'] * $metrics[0]['value']) + $value['stock'];
                                } else {
                                    $metric2 = $stocks[$i]['id_metric'];
                                    $metric1 = $value['id_metric'];
                                    $metric_1 = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                                    if (mysqli_num_rows($metric_1) > 0) {
                                        $metrics1 = mysqli_fetch_all($metric_1, MYSQLI_ASSOC);
                                        $mainMetric = $metric2;
                                        $val_convert = ($value['stock'] * $metrics1[0]['value']) + $stocks[$i]['stock'];
                                    }
                                }
                                $stocks[$j]['id_metric'] = $mainMetric;
                                $stocks[$j]['stock'] = $val_convert;
                            }
                        } else {
                            $j += 1;
                            $stocks[$j]['id_raw'] = $value['id_raw_material'];
                            $stocks[$j]['stock'] = (int)$value['stock'];
                            $stocks[$j]['id_metric'] = $value['id_metric'];
                        }
                    }
                }
                $res_stock = 999999999999999999999999;
                $boolEmpty = false;
                foreach ($all_recipe as $recipe_arr) {
                    $tempBool = true;
                    foreach ($stocks as $stock_arr) {



                        if ($stock_arr['id_raw'] == $recipe_arr['id_raw']) {
                            $tempBool = false;
                            if ($stock_arr['id_metric'] == $recipe_arr['id_metric']) {
                                // if((int) ($stock_arr['stock']/$recipe_arr['qty'])>0){
                                if ((int) ($stock_arr['stock'] / $recipe_arr['qty']) < $res_stock) {
                                    $res_stock = (int) ($stock_arr['stock'] / $recipe_arr['qty']);
                                }
                                // }
                            } else {
                                $mainMetric = 0;
                                $metric1 = $stock_arr['id_metric'];
                                $metric2 = $recipe_arr['id_metric'];
                                $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                                if (mysqli_num_rows($metric) > 0) {
                                    $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                                    // if((int) ($stock_arr['stock']*$metrics[0]['value'])/$recipe_arr['qty']>0){
                                    if ((int) ($stock_arr['stock'] * $metrics[0]['value']) / $recipe_arr['qty'] < $res_stock) {
                                        $res_stock = (int) ($stock_arr['stock'] * $metrics[0]['value']) / $recipe_arr['qty'];
                                    }
                                    //   }
                                } else {
                                    $metric1 = $stock_arr['id_metric'];
                                    $metric2 = $recipe_arr['id_metric'];
                                    $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric2  AND id_metric2=$metric1");
                                    if (mysqli_num_rows($metric) > 0) {
                                        $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                                        // if((int) ($stock_arr['stock'])/($recipe_arr['qty']*$metrics[0]['value'])>0){
                                        if ((int) ($stock_arr['stock']) / ($recipe_arr['qty'] * $metrics[0]['value']) < $res_stock) {
                                            $res_stock = (int) ($stock_arr['stock']) / ($recipe_arr['qty'] * $metrics[0]['value']);
                                        }
                                        //   }
                                    }
                                }
                            }
                        }
                    }
                    if ($tempBool == true) {
                        $boolEmpty = true;
                    }
                }

                if ($res_stock < 0 || $res_stock == 999999999999999999999999 || $boolEmpty == true) {
                    $res_stock = 0;
                }
                $rowR['stock'] = $res_stock;
                array_push($arr, $rowR);
            }
        }
        return $arr;
    }

    function update_menu_stocks($menuIds){
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $indexRecom = 0;
        $i = 0;
        $arr = array();
        $today = date('Y-m-d');
        $result = mysqli_query($db_conn, "SELECT * FROM menu WHERE id IN (".implode(',', $menuIds).") AND deleted_at IS NULL");
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        foreach ($rows as $rowR) {
            $id_m = $rowR['id'];
            if ($rowR['is_recipe'] == '1') {
                $recipe = mysqli_query($db_conn, "SELECT * FROM recipe WHERE recipe.id_menu = $id_m AND deleted_at IS NULL");
                if (mysqli_num_rows($recipe) == 0){
                    $rowR['is_recipe'] = '0';
                }
                $all_recipe = mysqli_fetch_all($recipe, MYSQLI_ASSOC);
                $j = 0;
                $stockRaw = mysqli_query($db_conn, "SELECT `raw_material_stock`.* FROM `raw_material_stock` JOIN recipe ON recipe.id_raw =raw_material_stock.id_raw_material WHERE recipe.id_menu='$id_m' AND DATE(raw_material_stock.exp_date)>'$today' ORDER BY raw_material_stock.id_raw_material ASC");
                $stock_raw = mysqli_fetch_all($stockRaw, MYSQLI_ASSOC);
                $first = true;
                $stocks = array();
                foreach ($stock_raw as $value) {
                    if ($first == true) {
                        $stocks[$j]['id_raw'] = $value['id_raw_material'];
                        $stocks[$j]['stock'] = (int)$value['stock'];
                        $stocks[$j]['id_metric'] = $value['id_metric'];
                        $first = false;
                    } else {
                        if ($stocks[$j]['id_raw'] == $value['id_raw_material']) {
                            if ($stocks[$j]['id_metric'] == $value['id_metric']) {
                                $stocks[$j]['stock'] += (int)$value['stock'];
                                $stocks[$j]['id_metric'] = $value['id_metric'];
                            } else {
                                $val_convert = 1;
                                $mainMetric = 0;
                                $metric1 = $stocks[$j]['id_metric'];
                                $metric2 = $value['id_metric'];
                                $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                                if (mysqli_num_rows($metric) > 0) {
                                    $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                                    $mainMetric = $metric2;
                                    $val_convert = ($stocks[$i]['stock'] * $metrics[0]['value']) + $value['stock'];
                                } else {
                                    $metric2 = $stocks[$i]['id_metric'];
                                    $metric1 = $value['id_metric'];
                                    $metric_1 = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                                    if (mysqli_num_rows($metric_1) > 0) {
                                        $metrics1 = mysqli_fetch_all($metric_1, MYSQLI_ASSOC);
                                        $mainMetric = $metric2;
                                        $val_convert = ($value['stock'] * $metrics1[0]['value']) + $stocks[$i]['stock'];
                                    }
                                }
                                $stocks[$j]['id_metric'] = $mainMetric;
                                $stocks[$j]['stock'] = $val_convert;
                            }
                        } else {
                            $j += 1;
                            $stocks[$j]['id_raw'] = $value['id_raw_material'];
                            $stocks[$j]['stock'] = (int)$value['stock'];
                            $stocks[$j]['id_metric'] = $value['id_metric'];
                        }
                    }
                }
                $res_stock = 999999999999999999999999;
                $boolEmpty = false;
                foreach ($all_recipe as $recipe_arr) {
                    $tempBool = true;
                    foreach ($stocks as $stock_arr) {



                        if ($stock_arr['id_raw'] == $recipe_arr['id_raw']) {
                            $tempBool = false;
                            if ($stock_arr['id_metric'] == $recipe_arr['id_metric']) {
                                // if((int) ($stock_arr['stock']/$recipe_arr['qty'])>0){
                                if ((int) ($stock_arr['stock'] / $recipe_arr['qty']) < $res_stock) {
                                    $res_stock = (int) ($stock_arr['stock'] / $recipe_arr['qty']);
                                }
                                // }
                            } else {
                                $mainMetric = 0;
                                $metric1 = $stock_arr['id_metric'];
                                $metric2 = $recipe_arr['id_metric'];
                                $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                                if (mysqli_num_rows($metric) > 0) {
                                    $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                                    // if((int) ($stock_arr['stock']*$metrics[0]['value'])/$recipe_arr['qty']>0){
                                    if ((int) ($stock_arr['stock'] * $metrics[0]['value']) / $recipe_arr['qty'] < $res_stock) {
                                        $res_stock = (int) ($stock_arr['stock'] * $metrics[0]['value']) / $recipe_arr['qty'];
                                    }
                                    //   }
                                } else {
                                    $metric1 = $stock_arr['id_metric'];
                                    $metric2 = $recipe_arr['id_metric'];
                                    $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric2  AND id_metric2=$metric1");
                                    if (mysqli_num_rows($metric) > 0) {
                                        $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                                        // if((int) ($stock_arr['stock'])/($recipe_arr['qty']*$metrics[0]['value'])>0){
                                        if ((int) ($stock_arr['stock']) / ($recipe_arr['qty'] * $metrics[0]['value']) < $res_stock) {
                                            $res_stock = (int) ($stock_arr['stock']) / ($recipe_arr['qty'] * $metrics[0]['value']);
                                        }
                                        //   }
                                    }
                                }
                            }
                        }
                    }
                    if ($tempBool == true) {
                        $boolEmpty = true;
                    }
                }

                if ($res_stock < 0 || $res_stock == 999999999999999999999999 || $boolEmpty == true) {
                    $res_stock = 0;
                }
                $rowR['stock'] = $res_stock;
                $update = mysqli_query($db_conn, "UPDATE `menu` SET stock='$res_stock', updated_at=NOW() WHERE id='$id_m'");
            }
        }
    }

    public function stock_variant($rowR1)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $indexRecom = 0;
        $i = 0;
        $arr = array();
        $today = date('Y-m-d');
        foreach ($rowR1 as $rowR) {
            $id_m = $rowR['id'];
            if ($rowR['is_recipe'] == '1') {
                $recipe = mysqli_query($db_conn, "SELECT * FROM recipe WHERE recipe.id_variant = $id_m AND deleted_at IS NULL");
                if (mysqli_num_rows($recipe) == 0){
                    $rowR['is_recipe'] = '0';
                }
                $all_recipe = mysqli_fetch_all($recipe, MYSQLI_ASSOC);
                $j = 0;
                $stockRaw = mysqli_query($db_conn, "SELECT `raw_material_stock`.* FROM `raw_material_stock` JOIN recipe ON recipe.id_raw =raw_material_stock.id_raw_material WHERE recipe.id_variant='$id_m' AND DATE(raw_material_stock.exp_date)>'$today' AND raw_material_stock.deleted_at IS NULL AND recipe.deleted_at IS NULL ORDER BY raw_material_stock.id_raw_material ASC");
                $stock_raw = mysqli_fetch_all($stockRaw, MYSQLI_ASSOC);
                $first = true;
                $stocks = array();
                foreach ($stock_raw as $value) {
                    if ($first == true) {
                        $stocks[$j]['id_raw'] = $value['id_raw_material'];
                        $stocks[$j]['stock'] = (int)$value['stock'];
                        $stocks[$j]['id_metric'] = $value['id_metric'];
                        $first = false;
                    } else {
                        if ($stocks[$j]['id_raw'] == $value['id_raw_material']) {

                            if ($stocks[$j]['id_metric'] == $value['id_metric']) {
                                $stocks[$j]['stock'] += (int)$value['stock'];
                                $stocks[$j]['id_metric'] = $value['id_metric'];
                            } else {
                                $val_convert = 1;
                                $mainMetric = 0;
                                $metric1 = $stocks[$j]['id_metric'];
                                $metric2 = $value['id_metric'];
                                $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                                if (mysqli_num_rows($metric) > 0) {
                                    $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                                    $mainMetric = $metric2;
                                    $val_convert = ($stocks[$i]['stock'] * $metrics[0]['value']) + $value['stock'];
                                } else {
                                    $metric2 = $stocks[$i]['id_metric'];
                                    $metric1 = $value['id_metric'];
                                    $metric_1 = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                                    if (mysqli_num_rows($metric_1) > 0) {
                                        $metrics1 = mysqli_fetch_all($metric_1, MYSQLI_ASSOC);
                                        $mainMetric = $metric2;
                                        $val_convert = ($value['stock'] * $metrics1[0]['value']) + $stocks[$i]['stock'];
                                    }
                                }
                                $stocks[$j]['id_metric'] = $mainMetric;
                                $stocks[$j]['stock'] = $val_convert;
                            }
                        } else {
                            $j += 1;
                            $stocks[$j]['id_raw'] = $value['id_raw_material'];
                            $stocks[$j]['stock'] = (int)$value['stock'];
                            $stocks[$j]['id_metric'] = $value['id_metric'];
                        }
                    }
                }
                $res_stock = 999999999999999999999999;
                $boolEmpty = false;
                foreach ($all_recipe as $recipe_arr) {
                    $tempBool = true;
                    foreach ($stocks as $stock_arr) {
                        if ($stock_arr['id_raw'] == $recipe_arr['id_raw']) {
                            $tempBool = false;
                            if ($stock_arr['id_metric'] == $recipe_arr['id_metric']) {
                                // if((int) ($stock_arr['stock']/$recipe_arr['qty'])>0){
                                if ((int) ($stock_arr['stock'] / $recipe_arr['qty']) < $res_stock) {
                                    $res_stock = (int) ($stock_arr['stock'] / $recipe_arr['qty']);
                                }
                                // }
                            } else {

                                $mainMetric = 0;
                                $metric1 = $stock_arr['id_metric'];
                                $metric2 = $recipe_arr['id_metric'];
                                $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                                if (mysqli_num_rows($metric) > 0) {
                                    $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                                    // if((int) ($stock_arr['stock']*$metrics[0]['value'])/$recipe_arr['qty']>0){
                                    if ((int) ($stock_arr['stock'] * $metrics[0]['value']) / $recipe_arr['qty'] < $res_stock) {
                                        $res_stock = (int) ($stock_arr['stock'] * $metrics[0]['value']) / $recipe_arr['qty'];
                                    }
                                    //   }
                                } else {
                                    $metric1 = $stock_arr['id_metric'];
                                    $metric2 = $recipe_arr['id_metric'];
                                    $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric2  AND id_metric2=$metric1");
                                    if (mysqli_num_rows($metric) > 0) {
                                        $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                                        // if((int) ($stock_arr['stock'])/($recipe_arr['qty']*$metrics[0]['value'])>0){
                                        if ((int) ($stock_arr['stock']) / ($recipe_arr['qty'] * $metrics[0]['value']) < $res_stock) {
                                            $res_stock = (int) ($stock_arr['stock']) / ($recipe_arr['qty'] * $metrics[0]['value']);
                                        }
                                        //   }
                                    }
                                }
                            }
                            // $i+=1;
                        }
                    }
                    if ($tempBool == true) {
                        $boolEmpty = true;
                    }
                }

                if ($res_stock < 0 || $res_stock == 999999999999999999999999 || $boolEmpty == true) {
                    $res_stock = 0;
                }
                $rowR['stock'] = $res_stock;
                array_push($arr, $rowR);
            }
        }
        return $arr;
    }
    
    function update_variant_stocks($variantIds)
    {
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        $indexRecom = 0;
        $i = 0;
        $arr = array();
        $today = date('Y-m-d');
        $result = mysqli_query($db_conn, "SELECT * FROM variant WHERE id IN (".implode(',', $variantIds).") AND deleted_at IS NULL");
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        foreach ($rows as $rowR) {
            $id_m = $rowR['id'];
            if ($rowR['is_recipe'] == '1') {
                $recipe = mysqli_query($db_conn, "SELECT * FROM recipe WHERE recipe.id_variant = $id_m AND deleted_at IS NULL");
                if (mysqli_num_rows($recipe) == 0){
                    $rowR['is_recipe'] = '0';
                }
                $all_recipe = mysqli_fetch_all($recipe, MYSQLI_ASSOC);
                $j = 0;
                $stockRaw = mysqli_query($db_conn, "SELECT `raw_material_stock`.* FROM `raw_material_stock` JOIN recipe ON recipe.id_raw =raw_material_stock.id_raw_material WHERE recipe.id_variant='$id_m' AND DATE(raw_material_stock.exp_date)>'$today' AND raw_material_stock.deleted_at IS NULL AND recipe.deleted_at IS NULL ORDER BY raw_material_stock.id_raw_material ASC");
                $stock_raw = mysqli_fetch_all($stockRaw, MYSQLI_ASSOC);
                $first = true;
                $stocks = array();
                foreach ($stock_raw as $value) {
                    if ($first == true) {
                        $stocks[$j]['id_raw'] = $value['id_raw_material'];
                        $stocks[$j]['stock'] = (int)$value['stock'];
                        $stocks[$j]['id_metric'] = $value['id_metric'];
                        $first = false;
                    } else {
                        if ($stocks[$j]['id_raw'] == $value['id_raw_material']) {

                            if ($stocks[$j]['id_metric'] == $value['id_metric']) {
                                $stocks[$j]['stock'] += (int)$value['stock'];
                                $stocks[$j]['id_metric'] = $value['id_metric'];
                            } else {
                                $val_convert = 1;
                                $mainMetric = 0;
                                $metric1 = $stocks[$j]['id_metric'];
                                $metric2 = $value['id_metric'];
                                $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                                if (mysqli_num_rows($metric) > 0) {
                                    $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                                    $mainMetric = $metric2;
                                    $val_convert = ($stocks[$i]['stock'] * $metrics[0]['value']) + $value['stock'];
                                } else {
                                    $metric2 = $stocks[$i]['id_metric'];
                                    $metric1 = $value['id_metric'];
                                    $metric_1 = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                                    if (mysqli_num_rows($metric_1) > 0) {
                                        $metrics1 = mysqli_fetch_all($metric_1, MYSQLI_ASSOC);
                                        $mainMetric = $metric2;
                                        $val_convert = ($value['stock'] * $metrics1[0]['value']) + $stocks[$i]['stock'];
                                    }
                                }
                                $stocks[$j]['id_metric'] = $mainMetric;
                                $stocks[$j]['stock'] = $val_convert;
                            }
                        } else {
                            $j += 1;
                            $stocks[$j]['id_raw'] = $value['id_raw_material'];
                            $stocks[$j]['stock'] = (int)$value['stock'];
                            $stocks[$j]['id_metric'] = $value['id_metric'];
                        }
                    }
                }
                $res_stock = 999999999999999999999999;
                $boolEmpty = false;
                foreach ($all_recipe as $recipe_arr) {
                    $tempBool = true;
                    foreach ($stocks as $stock_arr) {
                        if ($stock_arr['id_raw'] == $recipe_arr['id_raw']) {
                            $tempBool = false;
                            if ($stock_arr['id_metric'] == $recipe_arr['id_metric']) {
                                // if((int) ($stock_arr['stock']/$recipe_arr['qty'])>0){
                                if ((int) ($stock_arr['stock'] / $recipe_arr['qty']) < $res_stock) {
                                    $res_stock = (int) ($stock_arr['stock'] / $recipe_arr['qty']);
                                }
                                // }
                            } else {

                                $mainMetric = 0;
                                $metric1 = $stock_arr['id_metric'];
                                $metric2 = $recipe_arr['id_metric'];
                                $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric1  AND id_metric2=$metric2");
                                if (mysqli_num_rows($metric) > 0) {
                                    $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                                    // if((int) ($stock_arr['stock']*$metrics[0]['value'])/$recipe_arr['qty']>0){
                                    if ((int) ($stock_arr['stock'] * $metrics[0]['value']) / $recipe_arr['qty'] < $res_stock) {
                                        $res_stock = (int) ($stock_arr['stock'] * $metrics[0]['value']) / $recipe_arr['qty'];
                                    }
                                    //   }
                                } else {
                                    $metric1 = $stock_arr['id_metric'];
                                    $metric2 = $recipe_arr['id_metric'];
                                    $metric = mysqli_query($db_conn, "SELECT value FROM `metric_convert` WHERE id_metric1=$metric2  AND id_metric2=$metric1");
                                    if (mysqli_num_rows($metric) > 0) {
                                        $metrics = mysqli_fetch_all($metric, MYSQLI_ASSOC);
                                        // if((int) ($stock_arr['stock'])/($recipe_arr['qty']*$metrics[0]['value'])>0){
                                        if ((int) ($stock_arr['stock']) / ($recipe_arr['qty'] * $metrics[0]['value']) < $res_stock) {
                                            $res_stock = (int) ($stock_arr['stock']) / ($recipe_arr['qty'] * $metrics[0]['value']);
                                        }
                                        //   }
                                    }
                                }
                            }
                            // $i+=1;
                        }
                    }
                    if ($tempBool == true) {
                        $boolEmpty = true;
                    }
                }

                if ($res_stock < 0 || $res_stock == 999999999999999999999999 || $boolEmpty == true) {
                    $res_stock = 0;
                }
                $update = mysqli_query($db_conn, "UPDATE `variant` SET stock='$res_stock' WHERE id='$id_m'");
            }
        }
    }
    
    public function update_raw_material_stock($rawIds){
        $db_conn = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        
        $result = mysqli_query($db_conn, 
            "SELECT 
            	r.id_menu,
                r.id_variant
            FROM recipe r
            WHERE r.id_raw IN (".implode(',', $rawIds).")
            AND r.deleted_at IS NULL"
        );
        $result = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $menuIds = array();
        $variantIds = array();
        foreach($result as $row){
            if ($row['id_menu'] != '0'){
                array_push($menuIds, $row['id_menu']);
            }
            if ($row['id_variant'] != '0'){
                array_push($variantIds, $row['id_variant']);
            }
        }
        $this->update_menu_stocks($menuIds);
        $this->update_variant_stocks($variantIds);
    }
}
