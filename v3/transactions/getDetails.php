<?php    
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php"); 
require_once("./../transactionModels/transactionManager.php"); 
require_once("./../transactionDetailModels/transactionDetailManager.php"); 
require_once("./../userModels/userManager.php"); 
require_once("./../membershipModels/membershipManager.php"); 
require_once("./../partnerModels/partnerManager.php"); 
require_once("./../specialMemberModels/specialMemberManager.php"); 
require_once("./../transactionDeliveryModels/transactionDeliveryManager.php"); 
require_once("./../menuModels/menuManager.php"); 
require_once("./../paymentModels/paymentManager.php"); 
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

    $id = $_GET['id'];

    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
        $msg = $tokens['msg']; 
        $success = 0; 
    }else{
        $res = array();

        //transaction
        $Tmanager = new TransactionManager($db);
        $transaction = $Tmanager->getTransaction($id);
        $transaction = $transaction->getDetails();
        $transaction['delivery_detail'] = array();
        $res['surcharge_percent']=$transaction['surcharge_percent'];;
        $idS = $transaction['surcharge_id'];
        $sql = mysqli_query($db_conn, "SELECT name FROM `surcharges` WHERE `id`= '$idS'");
        if(mysqli_num_rows($sql) > 0) {    
            $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
             $res['surcharge_name'] = $data[0]['name'];
        }else{
             $res['surcharge_name'] = "";

        }
        if($transaction['takeaway']==1 || $transaction['takeaway']=='1'){
            $res['tipe_pesanan'] = "Takeaway";
        }else{
            //cek delivery atau dine in
            $TDelivmanager = new TransactionDeliveryManager($db);
            $delivery = $TDelivmanager->getDelivery($id);
            if($delivery==false){
                $res['tipe_pesanan'] = "Dine In";
                $res['delivery_detail']['id'] = "";
                $res['delivery_detail']['transaksi_id'] = "";
                $res['delivery_detail']['alamat'] = "";
                $res['delivery_detail']['longitude'] = "";
                $res['delivery_detail']['latitude'] = "";
                $res['delivery_detail']['notes'] = "";
                $res['delivery_detail']['ongkir'] = "";
            }else{
                $res['tipe_pesanan'] = "Delivery";
                $res['delivery_detail'] = $delivery;
            }
        }

        // payment method
        $PaymentManager = new PaymentManager($db);
        $payment = $PaymentManager->getById($transaction['tipe_bayar']);
        if($payment!=false){
            $res['paymentName'] = $payment->getNama();
        }else{
            $res['paymentName'] = "Wrong";
        }


        //transaction detail
        $TDmanager = new TransactionDetailManager($db);
        $transactionDetails = $TDmanager->getDetail($id);
        $itd = 0;
        if($transactionDetails!=false){
            foreach($transactionDetails as $value){
                $res['details'][$itd] = $value->getDetails();
                $itd +=1;
            }
        }else{
            $res['details']=array();
        }
        
        //menu
        $i = 0;
        foreach ($transactionDetails as $value) {
            $Mmanager = new MenuManager($db);
            $menu = $Mmanager->getById($value->getId_menu());
            if($menu!=false){

                $res['details'][$i]['nama_menu'] = $menu->getNama();
                $res['details'][$i]['img_data'] = $menu->getImg_data();
            }else{
                $res['details'][$i]['nama_menu'] = "Wrong";
                $res['details'][$i]['img_data'] = "";

            }
            $i+=1;
        }

        //customer
        $Umanager = new UserManager($db);
        $user = $Umanager->getUser($transaction['phone']);
        if($user!=false){

            $user = $user->getDetails();
            $res['customer_name']=$user['name'];
            $res['customer_phone']=$user['phone'];
        }else{
            if(isset($transaction['phone']) && !empty($transaction['phone'])){
                $res['customer_name']=$transaction['phone'];
                $res['customer_phone']=$transaction['phone'];
            }else{
                $res['customer_name']="Wrong";
                $res['customer_phone']="Wrong";
            }

        }

        if(count($transaction)>0){
            $success = 1;
            $msg = "Success";
            $status = 200;
        }else{
            $success = 0;
            $msg = "Failed";
            $status = 204;
        }

        //partner
        $Pmanager = new PartnerManager($db);
        $partner = $Pmanager->getPartnerDetails($transaction['id_partner']);
        $partner = $partner->getDetails();

        //membership
        $Mmanager = new MembershipManager($db);
        $membership = $Mmanager->getMembership($transaction['phone'], $partner['id_master']);
        if($membership==false){
            $res['point_user']=0;
        }else{
            $membership = $membership->getPoint();
            $res['point_user']=$membership;
        }

        //special member
        $SMmanager = new SpecialMemberManager($db);
        $special_member = $SMmanager->getSpecialMember($transaction['phone'],$partner['id_master']);
        if($special_member==false){
            $res['is_special_member']=false;
            $res['special_member']["id"]="";
            $res['special_member']["id_master"]="";
            $res['special_member']["phone"]="";
            $res['special_member']["max_disc"]="";
        }else{
            $special_member = $special_member->getDetails();
            $res['is_special_member']=true;
            $res['special_member']=$special_member;
        }
    }

        
    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg, "transaction"=>$res]);  
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;

 ?>
 