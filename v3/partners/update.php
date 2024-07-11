<?php    

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../partnerModels/partnerManager.php"); 
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
$signupMsg = 'Failed';

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $signupMsg = $tokens['msg']; 
}else{
    $data = json_decode(json_encode($_POST));
    
    $Pmanager = new PartnerManager($db);
    if(isset($data->id) && !empty($data->id)){

        $partner = $Pmanager->getPartnerDetails($data->id);
    
        if($partner!=false){

            if(isset($data->name) && !empty($data->name)){
                $partner->setName($data->name);
            }
            if(isset($data->address) && !empty($data->address)){
                $partner->setAddress($data->address);
            }
            if(isset($data->phone) && !empty($data->phone)){
                $partner->setPhone($data->phone);
            }
            if(isset($data->status) && !empty($data->status)){
                $partner->setStatus($data->status);
            }
            // if(isset($data->saldo_ewallet) && !empty($data->saldo_ewallet)){
            //     $partner->setSaldo_ewallet($data->saldo_ewallet);
            // }
            if(isset($data->tax) && $data->tax >= 0){
                $partner->setTax($data->tax);
            }
            if((isset($data->service) && !empty($data->service) && $data->service >= 0) || $data->service == 0 || $data->service == "0"){
                $partner->setService($data->service);
            }
            if(isset($data->email) && !empty($data->email)){
                $partner->setEmail($data->email);
            }
            if(isset($data->password) && !empty($data->password)){
                if($partner->getPassword()==md5($data->password)){
                    $partner->setPassword(md5($data->newPassword));
                }else{
                    $signupMsg="Password Lama Salah";
                }
            }
            if(isset($data->phone) && !empty($data->phone)){
                $partner->setPhone($data->phone);
            }
            // if(isset($data->delivery_fee) && !empty($data->delivery_fee)){
            //     $partner->setDelivery_fee($data->delivery_fee);
            // }
            // var_dump("a");
            if(isset($data->id_ovo) && !empty($data->id_ovo)){
                $partner->setId_ovo($data->id_ovo);
            }
            if(isset($data->id_dana) && !empty($data->id_dana)){
                $partner->setId_dana($data->id_dana);
            }
            if(isset($data->id_linkaja) && !empty($data->id_linkaja)){
                $partner->setId_linkaja($data->id_linkaja);
            }
            if(isset($data->id_master) && !empty($data->id_master)){
                $partner->setId_master($data->id_master);
            }
            if(isset($data->is_foodcourt) && !empty($data->is_foodcourt)){
                $partner->setis_foodcourt($data->is_foodcourt);
            }
            if(isset($data->device_token) && !empty($data->device_token)){
                $partner->setDevice_token($data->device_token);
            }
            if(isset($data->is_centralized)){
                $partner->setIsCentralized($data->is_centralized);
            }
            if(isset($data->longitude)){
                $partner->setLongitude($data->longitude);
            }
            if(isset($data->latitude)){
                $partner->setLatitude($data->latitude);
            }
            if(isset($data->img_map) && !empty($data->img_map)){
                $partner->setImg_map($data->img_map);
            }
            if(isset($data->desc_map) && !empty($data->desc_map)){
                $partner->setDesc_map($data->desc_map);
            }
            if(isset($data->is_delivery) ){
                $partner->setIs_delivery($data->is_delivery);
            }
            if(isset($data->is_takeaway)){
                $partner->setIs_takeaway($data->is_takeaway);
            }
            if(isset($data->is_open) && !empty($data->is_open)){
                $partner->setIs_open($data->is_open);
            }
            if(isset($data->jam_buka) && !empty($data->jam_buka)){
                $partner->setJam_buka($data->jam_buka);
            }
            if(isset($data->jam_tutup) && !empty($data->jam_tutup)){
                $partner->setJam_tutup($data->jam_tutup);
            }
            if(isset($data->wifi_ssid) && !empty($data->wifi_ssid)){
                $partner->setWifi_ssid($data->wifi_ssid);
            }
            if(isset($data->wifi_password) && !empty($data->wifi_password)){
                $partner->setWifi_password($data->wifi_password);
            }
            if(isset($data->is_booked) && !empty($data->is_booked)){
                $partner->setIs_booked($data->is_booked);
            }
            if(isset($data->booked_before) && !empty($data->booked_before)){
                $partner->setBooked_before($data->booked_before);
            }
            if(isset($data->created_at) && !empty($data->created_at)){
                $partner->setCreated_at($data->created_at);
            }
            if(isset($data->hide_charge)){
                $partner->setHide_charge($data->hide_charge);
            }
            if(isset($data->ovo_active) ){
                $partner->setOvo_active($data->ovo_active);
            }
            if(isset($data->dana_active)){
                $partner->setDana_active($data->dana_active);
            }
            if(isset($data->linkaja_active)){
                $partner->setLinkaja_active($data->linkaja_active);
            }
            if(isset($data->cc_active)){
                $partner->setCc_active($data->cc_active);
            }
            if(isset($data->debit_active)){
                $partner->setDebit_active($data->debit_active);
            }
            if(isset($data->qris_active)){
                $partner->setQris_active($data->qris_active);
            }
            if(isset($data->shopeepay_active)){
                $partner->setShopeepay_active($data->shopeepay_active);
            }
            if(isset($data->partner_type) && !empty($data->partner_type)){
                $partner->setPartner_type($data->partner_type);
            }
            if(isset($data->shipper_location) && !empty($data->shipper_location)){
                $partner->setShipper_location($data->shipper_location);
            }
            if(isset($data->thumbnail) && !empty($data->thumbnail)){
                $partner->setThumbnail($data->thumbnail);
            }
            if(isset($data->is_average_cogs)){
                $partner->setIs_average_cogs($data->is_average_cogs);
            }
            if(isset($data->is_temporary_close)){
                $partner->setIs_temporary_close($data->is_temporary_close);
            }
            if(isset($data->is_temporary_qr)){
                $partner->setIs_temporary_qr($data->is_temporary_qr);
            }
            if(isset($data->is_rounding)){
                $partner->setIs_rounding($data->is_rounding);
            }
            if(isset($data->rounding_digits)){
                $partner->setRounding_digits($data->rounding_digits);
            }
            if(isset($data->rounding_down_below)){
                $partner->setRounding_down_below($data->rounding_down_below);
            }
            if(isset($data->logo) && !empty($data->logo)){
                $partner->setLogo($data->logo);
            }
            
            $update = $Pmanager->update($partner);
            
                if(isset($data->subcategory) && !empty($data->subcategory)){
                $id = $data->id;
                //check subcategory in db for that partner
                $qSubCat = mysqli_query($db_conn, "SELECT psa.subcategory_id FROM partner_subcategory_assignments psa WHERE psa.partner_id='$id' AND psa.deleted_at IS NULL");
                $fetchSubCat = mysqli_fetch_all($qSubCat, MYSQLI_ASSOC);
                $subCatVal = [];
                foreach($fetchSubCat as $sc){
                    array_push($subCatVal,$sc["subcategory_id"]);
                }
                
                $subId = json_decode($data->subcategory);
                
                $error_in = "";
                foreach($subId as $subs){
                    $sub= $subs;
                    if(in_array($sub, $subCatVal)){
                        continue;    
                    } else {
                        $qValidateSubCat = "SELECT psa.id, psa.subcategory_id, psa.category_id FROM partner_subcategory_assignments WHERE partner_id='$id' AND psa.subcategory_id='$sub' AND psa.deleted_at IS NULL";
                        $qSqlValidateSubCat = mysqli_query($db_conn, $qValidateSubCat);
                        $qCat = mysqli_query($db_conn, "SELECT category_id FROM partner_subcategories WHERE id='$sub'");
                        $fetchCat = mysqli_fetch_all($qCat, MYSQLI_ASSOC);
                        $catID = $fetchCat[0]["category_id"];
                        
                        $qInsert = mysqli_query($db_conn, "INSERT INTO partner_subcategory_assignments SET category_id='$catID', subcategory_id='$sub', partner_id='$id'");
                    }
        
                }
                
                //delete partner subcategory
                foreach($subCatVal as $sub){
                     if(in_array($sub, $subId)){
                        continue;
                     } else {
                         $qDelete = mysqli_query($db_conn, "UPDATE  partner_subcategory_assignments SET deleted_at=NOW() WHERE partner_id='$id' AND subcategory_id='$sub'");
                     }
                }
            }
            
            if($update==true && $signupMsg!="Password Lama Salah"){
                $success=1;
                $signupMsg="Success";
                $status = 200;
            }else{
                $success=0;
                $signupMsg="Failed";
                $status = 203;
            }
        }else{
            $success=0;
            $signupMsg="Data Not Registered";
            $status = 400;
        }
    }else{
        $success=0;
        $signupMsg="Missing Mandatory Field";
        $status = 400;
    }
}

        
    $signupJson = json_encode(["msg"=>$signupMsg, "success"=>$success, "status"=>$status]);  
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;

 ?>