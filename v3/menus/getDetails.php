<?php    
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../menuModels/menuManager.php");
require_once("./../categoryModels/categoryManager.php");
require_once("./../menusVariantGroupsModels/menusVariantGroupsManager.php");
require_once("./../variantGroupModels/variantGroupManager.php");
require_once("./../recipeModels/recipeManager.php");
require_once("./../rawMaterialModels/rawMaterialManager.php"); 
require_once("./../metricModels/metricManager.php"); 
require_once("./../metricConvertModels/metricConvertManager.php");
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
    
    $success = 0;
    $signupMsg = "Failed";
    $db = connectBase();
    $tokenizer = new TokenManager($db);
    $tokens = $tokenizer->validate($token);
    $data = array();

    $menuId = $_GET['menuId'];
    $partnerId = $_GET['partnerId'];

    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
        $signupMsg = $tokens['msg']; 
        $success = 0; 
    }else{
        $Tmanager = new MenuManager($db);
        if(isset($menuId) && !empty($menuId)){

            $menu = $Tmanager->getById($menuId);
            if($menu!=false){

                $res = array();
                $data = $menu->getDetails();
                $Cmanager = new CategoryManager($db);
                $category = $Cmanager->getById($data['id_category']);
                if($category==false){
                    $data['category_name'] = "Wrong Category";
                }else{
                    $data['category_name'] = $category->getName();
                }
                $mvgManager = new MenusVariantGroupsManager($db);

                $mvgs = $mvgManager->getByMenuId($menuId);
                if($mvgs !=false){
                    $vgs = array();
                    foreach ($mvgs as $value) {
                        $mvgsVal = $value->getDetails();
                        $vg_manager = new variantGroupManager($db);
                        $vg = $vg_manager->getById($mvgsVal['variant_group_id']);
                        if($vg!=false){
                            $vg = $vg->getDetails();
                            $vg['menu_variant_groups_id'] = $mvgsVal['id'];
                            array_push($vgs,$vg);
                        }
                    }
                }
                
                $recipes = false;
                if($data['is_recipe']=='1'){
                    $recipeManage = new RecipeManager($db);
                    $recipes = $recipeManage->getByMenuId($menuId);
                }
                if($recipes!=false){
                    $rmManager = new RawMaterialManager($db);
                    $meManager = new MetricManager($db);
                    $i=0;
                    foreach ($recipes as $value) {
                        $data['recipes'][$i]=$value->getDetails();
                        $raws = $rmManager->getByID($value->getId_raw());
                        $metrics = $meManager->getByID($value->getId_metric());
                        if($raws!=false){
                            $data['recipes'][$i]['raw_name']=$raws->getName();
                        }else{
                            $data['recipes'][$i]['raw_name']="wrong";
                        }
                        if($metrics!=false){
                            $data['recipes'][$i]['metric_name']=$metrics->getName();
                        }else{
                            $data['recipes'][$i]['metric_name']="wrong";
                        }

                        
    $metricConvertManager = new MetricConvertManager($db);
                        
                        $metrics1 = $metricConvertManager->getByMetricsConvert($value->getId_metric());
                        $ids = array();
                        if($metrics1!=false){
                            
                            foreach($metrics1 as $metric){
                                $add1 = true;
                                $add2 = true;
                                $data1 = $metric->getDetails();
                                foreach($ids as $id){
                                    // var_dump($id);
                                    // var_dump($data1['id_metric1']);
                                    // var_dump($data1['id_metric2']);
                                    if($id===$data1['id_metric1']){
                                        $add1 = false;
                                    }
                                    if($id===$data1['id_metric2']){
                                        $add2 = false;
                                    }
                                }
                                if($add1==true){
                                    array_push($ids, $data1['id_metric1']);
                                }
                                if($add2==true){
                                    array_push($ids, $data1['id_metric2']);
                                }
                            }
                            $resM = array();
                            foreach($ids as $id){
                                $metrics1 = $meManager->getById($id);
                                if($metrics1!=false){
                                    $x = $metrics1->getDetails();
                                    $x['label'] = $x['name'];
                                    $x['value'] = $x['id'];
                                    $x['name']="id_metric";
                                    $x['target']['id']=$x['id'];
                                    $x['target']['label']=$x['label'];
                                    $x['target']['name']="id_metric";
                                    array_push($resM,$x);
                                }
                            }
                            
                        }
                        $data['recipes'][$i]['metricRelated']=$resM;
                        $i++;
                    }
                }else{
                    $data['recipes']=array();
                }
                
                // get menu surcharges
                $surcharge_types = array();
                
                // ambil surcharge id dari menuid dan partnerid
                $query = "SELECT mst.id, mst.surcharge_id, mst.surcharge_id, mst.partner_id, mst.price, surcharges.name AS surcharge_name FROM menu_surcharge_types mst JOIN surcharges ON mst.surcharge_id=surcharges.id WHERE mst.partner_id = '$partnerId' AND menu_id ='$menuId' AND mst.deleted_at IS NULL ORDER BY `id` DESC";
                $sql = mysqli_query($db_conn, $query);

                if(mysqli_num_rows($sql) > 0) {
                    $dataGet = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $surcharge_types = $dataGet;
                }
                $data['surcharges'] = $surcharge_types;
                // get menu surcharges end
                
                $data['variant_groups']=$vgs;
                $success = 1;
                $signupMsg = "Success";
                $status=200;
            }else{
                $success=0;
                $msg="Data Not Registered";
                $status=400;
            }
        }else{
            $success=0;
            $msg="Missing require field's";
            $status=400;
        }
    }

    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$signupMsg, "menus"=>$data]);

    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;
 ?>
 