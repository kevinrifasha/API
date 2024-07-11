<?php    
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require_once("./../transactionModels/transactionManager.php");
require_once("./../transactionDetailModels/transactionDetailManager.php");
require_once("./../transactionDeliveryModels/transactionDeliveryManager.php");
require_once("./../menuModels/menuManager.php");
require_once("./../ipCategoryModels/ipCategoryManager.php");
require_once("./../partnerModels/partnerManager.php");

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

    $id = $_GET['id'];

    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
        $signupMsg = $tokens['msg']; 
        $success = 0; 
    }else{
        $res = array();

        //transaction
        $Tmanager = new TransactionManager($db);
        $transaction = $Tmanager->getTransaction($id);
        $transaction = $transaction->getDetails();
        $res = $transaction;
        $transaction['delivery_detail'] = array();
        if($transaction['takeaway']==1 || $transaction['takeaway']=='1'){
            $res['tipe_pesanan'] = "Takeaway";
        }else{
            //cek delivery atau dine in
            $TDelivmanager = new TransactionDeliveryManager($db);
            $delivery = $TDelivmanager->getDelivery($id);
            if($delivery==false){
                $res['tipe_pesanan'] = "Dine In";
            }else{
                $res['tipe_pesanan'] = "Delivery";
                $res['delivery_detail'] = $delivery;
            }
        }

        //partner
        $Pmanager = new PartnerManager($db);
        $partner = $Pmanager->getPartnerDetails($transaction['id_partner']);
        $ip_receipt = $partner->getIp_receipt();

        //transaction detail
        $res1 = array();
        $TDmanager = new TransactionDetailManager($db);
        $transactionDetails = $TDmanager->getDetailSortByIdCategory($id);
        $itd = 0;
        foreach($transactionDetails as $value){
            $res1[$itd] = $value->getDetails();
            $itd +=1;
        }
        
        //menu
        $i = 0;
        foreach ($transactionDetails as $value) {
            $Mmanager = new MenuManager($db);
            $menu = $Mmanager->getById($value->getId_menu());
            $res1[$i]['nama'] = $menu->getNama();
            $res1[$i]['id_category'] = $menu->getId_category();
            $ICmanager = new IpCategoryManager($db);
            $category = $ICmanager->getByCategoryId($menu->getId_category());
            $res1[$i]['ip_address'] = $category->getIp();
            $i+=1;
        }

        //grouping by ip_address
        $j = 0;
        $checker=$res;
        $checker['ip_address']=substr($ip_receipt,2);
        $checker['ip_address'] = substr($checker['ip_address'],0,-2);
        
        foreach ($res1 as $value) {

            $checker['menu'][$j]['nama']=$value['nama'];
            $checker['menu'][$j]['qty']=$value['qty'];
            $checker['menu'][$j]['variant']=$value['variant'];
            $checker['menu'][$j]['notes']=$value['notes'];
                
            $j+=1;
        }
    }
    $success=0;
    $signupMsg="Failed";
    $status = 204;
    if($checker){
        $success=1;
        $signupMsg="Success";
        $status = 200;
    }

        
    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$signupMsg, "transaction"=>$checker]);  
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;

 ?>
 