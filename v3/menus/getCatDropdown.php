<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../tokenModels/tokenManager.php");
require_once("./../partnerModels/partnerManager.php");
require_once("./../categoryModels/categoryManager.php");

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
$all = "0";

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
    $db = connectBase();
    $tokenizer = new TokenManager($db);
    // $tokens = $tokenizer->validate($token);
    $tokens = $tokenizer->validate($token);
    $tokenDcrpt = json_decode($tokenizer->stringEncryption('decrypt',$token));
    $idMaster = $tokenDcrpt->masterID;
    $res = array();
    $status=200;
    
    if(isset($_GET['all'])) {
        $all = $_GET['all'];
    }
    
    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
        $status = $tokens['status'];
        $msg = $tokens['msg'];
        $success = 0;
    }else{
        $Tmanager = new PartnerManager($db);
        // if(isset($partnerId) && !empty($partnerId)){
        $pId = $_GET['partner_id'];
            $partner = $Tmanager->getPartnerDetails($pId);
            $Cmanager = new CategoryManager($db);
            if($partner!=false){
                $categories = $Cmanager->getByMasterId($partner->getId_master());
                // $categories = $Cmanager->getByPartnerId($pId);
                $res = array();
                if($categories != false ){
                    foreach($categories as $category){
                        $data = $category->getDetails();
                        // $data['id_partner'] = $category->
                        if(isset($data['department_id']) && !empty($data['department_id'])){
                            $dID = $data['department_id'];
                            $sqlQ = mysqli_query($db_conn, "SELECT id, name, partner_id FROM `departments` WHERE id='$dID'");
                            if(mysqli_num_rows($sqlQ) > 0) {
                                $dataQ = mysqli_fetch_all($sqlQ, MYSQLI_ASSOC);
                                $data['department']['id']=$dataQ[0]['id'];
                                $data['department']['value']=$dataQ[0]['id'];
                                $data['department']['label']=$dataQ[0]['name'];
                                $data['id_partner']=$dataQ[0]['partner_id'];
                            }else{
                                $data['department']['id']=0;
                                $data['department']['label']="Belum Dipilih";
                                $data['department']['value']=0;
                            }
                        }else{
                            $data['department']['id']=0;
                            $data['department']['label']="Belum Dipilih";
                            $data['department']['value']=0;
                        }
                        array_push($res,$data);
                    }

                    if(count($categories)>0){
                        
                        // // karena defaultnya ambil berdasarkan id_master, dan di table categories tidak ada id, jadi di filter di sini, supaya tampil berdasar id_partner
                        // if($all !== "1") {
                        //     $array = [];
                        //     foreach ( $res as $value) {
                        //         if ( $value['id_partner'] ==  $_GET['partner_id']) 
                        //             {
                        //                 array_push($array, $value);
                        //             }
                        //     }
                        //     // $res = $array;
                        // }
                        
                        $success = 1;
                        $msg = "Success";
                        $status = 200;
                    }else{
                        $success = 0;
                        $msg = "Data Not Found";
                        $status = 204;
                        $res=array();
                    }

                }else{

                    $success = 0;
                    $msg = "Data Not Registered";
                    $status = 204;
                    $res=array();
                }
            }else{

                $success = 0;
                $msg = "Data Not Registered";
                $status = 400;
                $res=array();
            }


        // }else{

        //     $success = 0;
        //     $msg = "Missing Mandatory Field";
        //     $status = 400;

        // }

    }
    $signupJson = json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "categories"=>$res]);

    echo $signupJson;
 ?>

