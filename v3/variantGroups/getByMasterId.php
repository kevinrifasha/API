<?php    
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../metricModels/metricManager.php"); 
require_once("./../metricConvertModels/metricConvertManager.php");

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
$res = array();

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}       
    $db = connectBase();
    $tokenizer = new TokenManager($db);
    $tokens = $tokenizer->validate($token);
    $master_id = $_GET['masterId'];

    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
        $msg = $tokens['msg']; 
        $success = 0; 
    }else{
        $res = array();
        $sqlGetVarGroup = mysqli_query($db_conn, "SELECT * FROM `variant_group` WHERE id_master = '$master_id'");
    
        if(mysqli_num_rows($sqlGetVarGroup) > 0) {
            $dataVarGroup = mysqli_fetch_all($sqlGetVarGroup, MYSQLI_ASSOC);
            $variant = [];
            
            // get variantsnya dari masing-masing variantGroup by varGroupID
            foreach($dataVarGroup as $group) {
                $varGroupID = $group['id'];
                $sqlGetVariants = mysqli_query($db_conn, "SELECT * FROM `variant` WHERE id_variant_group = '$varGroupID'");
                $dataVariants = mysqli_fetch_all($sqlGetVariants, MYSQLI_ASSOC);
                $variantList = [];
                
                // check apakah pakai resep
                if(count($dataVariants) > 0) {
                    foreach($dataVariants as $var) {
                        $variant_id = $var['id'];
                        $is_recipe = $var['is_recipe'];
                        
                        // jika pakai resep
                        if($is_recipe === "1") {
                            $sqlGetRecipes = mysqli_query($db_conn, "SELECT r.id, r.id_raw, r.id_menu, r.qty, r.id_metric, r.id_variant, rm.name AS raw_name, m.name AS metric_name FROM `recipe` r JOIN `raw_material` rm ON rm.id=r.id_raw AND rm.deleted_at IS NULL JOIN `metric` m ON m.id=rm.id_metric WHERE id_variant = '$variant_id' AND r.deleted_at IS NULL");
                            $dataRecipes = mysqli_fetch_all($sqlGetRecipes, MYSQLI_ASSOC);
                            $recipeList = [];
                            
                            // cari metric relatednya dari masing-masing idmetric recipe
                            foreach($dataRecipes as $recipe) {
                                $meManager = new MetricManager($db);
                                $metricConvertManager = new MetricConvertManager($db);
                                $metrics1 = $metricConvertManager->getByMetricsConvert($recipe['id_metric']);
                                $ids = array();
                                
                                if($metrics1!=false){
                                    foreach($metrics1 as $metric){
                                        $add1 = true;
                                        $add2 = true;
                                        $data1 = $metric->getDetails();
                                        foreach($ids as $id){
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
                                $dataRecipe = $recipe;
                                $dataRecipe['metricRelated'] = $resM;
                                array_push($recipeList, $dataRecipe);
                            }
                            $vars = $var;
                            $vars['recipes'] = $recipeList;
                            array_push($variantList, $vars);
                        }
                        // jika tidak pakai resep
                        else {
                            $vars = $var;
                            $vars['recipes'] = [];
                            array_push($variantList, $vars);
                        }
                    }
                }
                $data = $group;
                $data['variant'] = $variantList;
                array_push($res, $data);
            }
        }
        $success = 1;
        $msg = "Success";
        $status = 200;
    }
    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "variantGroups"=>$res]);
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;
 ?>
 