<?php    
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../v3/connection.php");
require_once("./../../v3/partnerModels/partnerManager.php"); 
// require_once("./../tokenModels/tokenManager.php"); 
require '../../db_connection.php';


// $headers = array();
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
$db = connectBase();
$success=0;
$signupMsg = 'Failed'; 

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
            if(isset($data->tax)){
                $partner->setTax($data->tax);
            }
            if(isset($data->service) && !empty($data->service)){
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
            if(isset($data->restaurant_number) && !empty($data->restaurant_number)){
                $partner->setRestaurant_number($data->restaurant_number);
            }
            if(isset($data->delivery_fee) && !empty($data->delivery_fee)){
                $partner->setDelivery_fee($data->delivery_fee);
            }
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
            if(isset($data->id_foodcourt) && !empty($data->id_foodcourt)){
                $partner->setId_foodcourt($data->id_foodcourt);
            }
            if(isset($data->device_token) && !empty($data->device_token)){
                $partner->setDevice_token($data->device_token);
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
            if(isset($data->is_delivery) && !empty($data->is_delivery)){
                $partner->setIs_delivery($data->is_delivery);
            }
            if(isset($data->is_takeaway) && !empty($data->is_takeaway)){
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
            if(isset($data->ip_checker) && !empty($data->ip_checker)){
                $partner->setIp_checker($data->ip_checker);
            }
            if(isset($data->ip_receipt) && !empty($data->ip_receipt)){
                $partner->setIp_receipt($data->ip_receipt);
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
            if(isset($data->partner_type) && !empty($data->partner_type)){
                $partner->setPartner_type($data->partner_type);
            }
            if(isset($data->shipper_location) && !empty($data->shipper_location)){
                $partner->setShipper_location($data->shipper_location);
            }
            $update = $Pmanager->update($partner);


            if(isset($data->jam_buka) && !empty($data->jam_buka) && isset($data->jam_tutup) && !empty($data->jam_tutup)){
                $open       = date("H:i:s",strtotime(date("Y-m-d".$data->jam_buka)));
                $close      = date("H:i:s",strtotime(date("Y-m-d".$data->jam_tutup)));
                $lastOrder  = date("H:i:s",strtotime(date("Y-m-d".$close)." -30 minutes"));
                $sql        = mysqli_query($db_conn, "UPDATE partner_opening_hours SET monday_open='$open', tuesday_open='$open', wednesday_open='$open', thursday_open='$open', friday_open='$open', saturday_open='$open', sunday_open='$open', monday_closed='$close', tuesday_closed='$close', wednesday_closed='$close', thursday_closed='$close', friday_closed='$close', saturday_closed='$close', sunday_closed='$close',monday_last_order='$lastOrder', tuesday_last_order='$lastOrder', wednesday_last_order='$lastOrder', thursday_last_order='$lastOrder', friday_last_order='$lastOrder', saturday_last_order='$lastOrder', sunday_last_order='$lastOrder', updated_at=NOW() WHERE partner_id ='$data->id'");
            }
    
            if($update==true && $signupMsg!="Password Lama Salah"){
                $success=1;
                $signupMsg="Success";
                $status = 200;
            }else{
                $success=0;
                $signupMsg="Failed";
                $status = 204;
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
    http_response_code($status);
    $signupJson = json_encode(["msg"=>$signupMsg, "success"=>$success, "status"=>$status]);  
    echo $signupJson;

 ?>
 