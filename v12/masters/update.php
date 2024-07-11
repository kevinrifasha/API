<?php    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../masterModels/masterManager.php"); 
require_once("./../tokenModels/tokenManager.php"); 


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
$success=0;
$msg = 'Failed'; 

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];

    $msg = $tokens['msg']; 

}else{

    $data = json_decode(json_encode($_POST));
    
    if(isset($data->id) && !empty($data->id)){

        $Pmanager = new MasterManager($db);
        $master = $Pmanager->getMasterById($data->id);
        
        if(isset($data->email) && !empty($data->email)){
            $master->setEmail($data->email);
        }
        if(isset($data->password) && !empty($data->password)){
            if($master->getPassword()==md5($data->password)){
                $master->setPassword(md5($data->newPassword));
            }else{
                $msg="Password Lama Salah";
            }
        }
        if(isset($data->name) && !empty($data->name)){
            $master->setName(str_replace("'","''",$data->name));
        }
        if(isset($data->phone) && !empty($data->phone)){
            $master->setPhone($data->phone);
        }
        if(isset($data->office_number) && !empty($data->office_number)){
            $master->setOffice_number($data->office_number);
        }
        if(isset($data->address) && !empty($data->address)){
            $master->setAddress($data->address);
        }
        if(isset($data->harga_point) ){
            $master->setHarga_point($data->harga_point);
        }
        if(isset($data->point_pay)){
            $master->setPoint_pay($data->point_pay);
        }
        if(isset($data->transaction_point_max) ){
            $master->setTransaction_point_max($data->transaction_point_max);
        }
        if(isset($data->img) && !empty($data->img)){
            $master->setImg($data->img);
        }
        if(isset($data->is_foodcourt) && !empty($data->is_foodcourt)){
            $master->setIs_foodcourt($data->is_foodcourt);
        }
        if(isset($data->no_rekening) && !empty($data->no_rekening)){
            $master->setNo_rekening($data->no_rekening);
        }
        if(isset($data->status) && !empty($data->status)){
            $master->setStatus($data->status);
        }
        if(isset($data->created_at) && !empty($data->created_at)){
            $master->setCreated_at($data->created_at);
        }
        if(isset($data->trial_untill) && !empty($data->trial_untill)){
            $master->setTrial_untill($data->trial_untill);
        }
        if(isset($data->hold_untill) && !empty($data->hold_untill)){
            $master->setHold_untill($data->hold_untill);
        }
        if(isset($data->referer) && !empty($data->referer)){
            $master->setReferer($data->referer);
        }
        if(isset($data->deposit_balance) && !empty($data->deposit_balance)){
            $master->setDeposit_balance($data->deposit_balance);
        }
        
        $update = $Pmanager->update($master);
        if($update==true && $msg!="Password Lama Salah"){
            $success=1;
            $msg="Success";
            $status = 200;
        }else{
            $success=0;
            $msg="Failed";
            $status = 503;    
        }
    }else{
        $status = 400;
        $success=0;
        $msg="Missing require field's"; 
    }
    
}


    $signupJson = json_encode(["msg"=>$msg, "success"=>$success,"status"=>$status]);  
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;

 ?>
 