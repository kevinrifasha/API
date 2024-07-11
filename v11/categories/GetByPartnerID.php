<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

//init var
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
$tokenizer = new Token();
$token = '';
$res = array();

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg']; 
    $success = 0; 
}else{
    // if(isset($partnerId) && !empty($partnerId)){
    $pId = $token->id_partner;
    if($pId){
        // $categories = $Cmanager->getByMasterId($partner->getId_master());
        $qCat = mysqli_query($db_conn, "SELECT c.*, d.partner_id AS id_partner FROM categories c JOIN departments d ON d.id = c.department_id WHERE d.partner_id='{$pId}' AND c.name != 'Promo' AND c.deleted_at IS NULL ORDER BY c.sequence ASC");
        $categories = mysqli_fetch_all($qCat, MYSQLI_ASSOC);
        $res = array();
        if($categories != []){
            foreach($categories as $category){
                $data = [
        			'id' => $category['id'],
        			'id_master' => $category['id_master'],
        			'name' => $category['name'],
        			'sequence' => $category['sequence'],
        			'department_id' => $category['department_id'],
                ];
                // $data['id_partner'] = $category->
                if(isset($data['department_id']) && !empty($data['department_id'])){
                    $dID = $data['department_id'];
                    $sqlQ = mysqli_query($db_conn, "SELECT id, name, partner_id FROM `departments` WHERE id='$dID'");
                    if(mysqli_num_rows($sqlQ) > 0) {
                        $dataQ = mysqli_fetch_all($sqlQ, MYSQLI_ASSOC);
                        $data['department_name']=$dataQ[0]['name'];
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

