<?php    
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../transactionModels/transactionManager.php");
require_once("./../transactionDetailModels/transactionDetailManager.php");
require_once("./../partnerTableModels/partnerTableManager.php");
require_once("./../menuModels/menuManager.php");
require_once("./../variantModels/variantManager.php");
require_once("./../recipeModels/recipeManager.php");
require_once("./../rawMaterialStockModels/rawMaterialStockManager.php");
require_once("./../metricConvertModels/metricConvertManager.php");
require_once("./../notification/notificationManager.php");


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
date_default_timezone_set('Asia/Jakarta');
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$transaction = array();

$data = json_decode(json_encode($_POST));
    if(
        isset($data->id)
        && isset($data->status)
        && !empty($data->id)
        && !empty($data->status)
    ){
        $id = $data->id;
        $status = $data->status;
        
        if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];

            $msg = $tokens['msg']; 
            $success = 0;

        }else{
    
            //transaction
            $Tmanager = new TransactionManager($db);
            $transaction = $Tmanager->getTransaction($id);
            $transaction->setStatus($status);
            
            //cek meja antrian
            $Mmanager = new PartnerTableManager($db);
            $meja = $Mmanager->getByPartnerIdAndMejaId($transaction->getId_partner(),$transaction->getNo_meja());
            if($meja!=false){

                $is_queue = $meja->getIs_queue();
                if($is_queue=="1" && $status=='1'){
                    $transaction->setQueue($Tmanager->getQueue($transaction->getId_partner()));
                }
                
            }
            //pesanan selesai
            if($status=='2'){
                $today = date('Y-m-d H:i:s', time());
                $transaction->setConfirm_at($today);
            }
            
            //kurangi stock
            if($status=='1'){
                //Detail Transaction
                $TDmanager = new TransactionDetailManager($db);
                $transactionDetails = $TDmanager->getDetail($transaction->getId());
                $MenuOrder = array();
                $variantOrder = array();
                $imo = 0;
                $iv = 0;
                foreach($transactionDetails as $value){
                    $MenuOrder[$imo]['id_menu'] = $value->getId_menu();
                    $MenuOrder[$imo]['qty'] = $value->getQty();
                    $MenuOrder[$imo]['variant'] = json_decode($value->getVariant());
                    foreach ($MenuOrder[$imo]['variant'] as $value1) {
                        foreach ($value1 as $value2) {
                            foreach($value2 as $value3){
                                $variantOrder[$iv]['id']=$value3->id;
                                $variantOrder[$iv]['qty']=$MenuOrder[$imo]['qty'];
                                $iv+=1;
                            }
                        }
                    }
                    $imo+=1;
                }

                    //Menu
                    $MnManager = new MenuManager($db);
                    foreach ($MenuOrder as $value) {
                        $qtyOrder = $value["qty"];
                        $menu = $MnManager->getById($value['id_menu']);
                        if($menu->getIs_recipe()=='0'){
                            $stock = $menu->getStock();
                            $menu->setStock($stock-$value['qty']);
                            $MnManager->update($menu);
                        }else{
                            //Receipe
                            $Rmanager = new RecipeManager($db);
                            $recipe = $Rmanager->getByMenuId($menu->getId());

                            //Raw Material Stock
                            $rawMaterialStocks = array();
                            $irms=0;
                            foreach ($recipe as $valueR) {
                                $RMSmanager = new RawMaterialStockManager($db);
                                $rawMaterialStocks[$irms] = $RMSmanager->getByRawId($valueR->getId_raw());
                                $irms+=1;
                            }

                            //update stock
                            //cek Resep
                            foreach ($recipe as $valueR) {
                                $minStock =($valueR->getQty()*$qtyOrder);

                                foreach ($rawMaterialStocks as $valueLRMS) {
                                    foreach ($valueLRMS as $valueRMS) {
                                        if($minStock>0){
                                            if($valueR->getId_raw()==$valueRMS->getId_raw_material()){

                                                if($valueR->getId_metric()==$valueRMS->getId_metric()){
                                                    $stockMC = $valueRMS->getStock()-$minStock;
                                                    if($stockMC>=0){
                                                        $minMCStock = $minStock;
                                                    }else{
                                                        $minMCStock = $valueRMS->getStock();
                                                    }
                                                    $minStock = $minStock-$minMCStock;
                                                    $valueRMS->setStock( $valueRMS->getStock()- $minMCStock );

                                                }else{
                                                    $MCmanager = new MetricConvertManager($db);
                                                    $mcVal = $MCmanager->getByMetricsId($valueR->getId_metric(),$valueRMS->getId_metric());
                                                    if($mcVal==false){
                                                        $mcVal = $MCmanager->getByMetricsId($valueRMS->getId_metric(),$valueR->getId_metric());
                                                        $stockMC = ($valueRMS->getStock()*$mcVal->getValue()) - $minStock ;
                                                        if($stockMC>=0){
                                                            $minMCStock = $minStock;
                                                        }else{
                                                            $minMCStock = $valueRMS->getStock();
                                                        }
                                                        $minStock = $minStock-$minMCStock;
                                                        $valueRMS->setId_metric($valueR->getId_metric());
                                                        $valueRMS->setStock( ($valueRMS->getStock()*$mcVal->getValue()) - $minMCStock );
                                                    }else{
                                                        $valueR->setId_metric($valueRMS->getId_metric());
                                                        $minStock= $minStock*$mcVal->getValue();
                                                        $stockMC = ( $minStock-$valueRMS->getStock() ) ;
                                                        if($stockMC>=0){
                                                            $minMCStock = $minStock;
                                                        }else{
                                                            $minMCStock = $valueRMS->getStock();
                                                        }
                                                        $minStock = $minStock-$minMCStock;
                                                        $valueRMS->setId_metric($valueR->getId_metric());
                                                        $valueRMS->setStock( ($valueRMS->getStock()*$mcVal->getValue()) - $minMCStock );
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                            }
                        }

                    }

                    //variant
                    $Vmanager = new VariantManager($db);
                    $iv = 0;
                    foreach ($variantOrder as $value) {
                        $variant = $Vmanager->getById($value['id']);
                        if($variant->getIs_recipe()=='0'){
                            $stock = $variant->getStock();
                            $variant->setStock($stock-$value['qty']);
                            $Vmanager->update($variant);
                        }else{

                            //Receipe
                            $Rmanager = new RecipeManager($db);
                            $recipe = $Rmanager->getByVariantId($menu->getId());

                            //Raw Material Stock
                            $rawMaterialStocks = array();
                            $irms=0;
                            foreach ($recipe as $valueR) {
                                $RMSmanager = new RawMaterialStockManager($db);
                                $rawMaterialStocks[$irms] = $RMSmanager->getByRawId($valueR->getId_raw());
                                $irms+=1;
                            }

                            //update stock
                            //cek Resep
                            foreach ($recipe as $valueR) {
                                $minStock =($valueR->getQty()*$qtyOrder);

                                foreach ($rawMaterialStocks as $valueLRMS) {
                                    foreach ($valueLRMS as $valueRMS) {
                                        if($minStock>0){
                                            if($valueR->getId_raw()==$valueRMS->getId_raw_material()){

                                                if($valueR->getId_metric()==$valueRMS->getId_metric()){
                                                    $stockMC = $valueRMS->getStock()-$minStock;
                                                    if($stockMC>=0){
                                                        $minMCStock = $minStock;
                                                    }else{
                                                        $minMCStock = $valueRMS->getStock();
                                                    }
                                                    $minStock = $minStock-$minMCStock;
                                                    $valueRMS->setStock( $valueRMS->getStock()- $minMCStock );

                                                }else{
                                                    $MCmanager = new MetricConvertManager($db);
                                                    $mcVal = $MCmanager->getByMetricsId($valueR->getId_metric(),$valueRMS->getId_metric());
                                                    if($mcVal==false){
                                                        $mcVal = $MCmanager->getByMetricsId($valueRMS->getId_metric(),$valueR->getId_metric());
                                                        $stockMC = ($valueRMS->getStock()*$mcVal->getValue()) - $minStock ;
                                                        if($stockMC>=0){
                                                            $minMCStock = $minStock;
                                                        }else{
                                                            $minMCStock = $valueRMS->getStock();
                                                        }
                                                        $minStock = $minStock-$minMCStock;
                                                        $valueRMS->setId_metric($valueR->getId_metric());
                                                        $valueRMS->setStock( ($valueRMS->getStock()*$mcVal->getValue()) - $minMCStock );
                                                    }else{
                                                        $valueR->setId_metric($valueRMS->getId_metric());
                                                        $minStock= $minStock*$mcVal->getValue();
                                                        $stockMC = ( $minStock-$valueRMS->getStock() ) ;
                                                        if($stockMC>=0){
                                                            $minMCStock = $minStock;
                                                        }else{
                                                            $minMCStock = $valueRMS->getStock();
                                                        }
                                                        $minStock = $minStock-$minMCStock;
                                                        $valueRMS->setId_metric($valueR->getId_metric());
                                                        $valueRMS->setStock( ($valueRMS->getStock()*$mcVal->getValue()) - $minMCStock );
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                            }

                        }
                    }

                }

                // $notif = new NotificationManager($db);
                // $pushNotif = $notif->pushPaymentNotification()

                //update
                $update  = $Tmanager->update($transaction);
                if($update){
                    $msg = "Success";
                    $success = 1;
                    $status=200;
                }else{
                    $msg = "Failed";
                    $success = 0;
                    $status=204;
                }
        }

    }else{

        $success = 0;
        $msg = "Missing Mandatory Field";
        $status= 400;

    }

    $json = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);  
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $json;

 ?>
 