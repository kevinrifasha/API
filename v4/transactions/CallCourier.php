<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require  __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();
//import require file
require '../../db_connection.php';
require_once('../auth/Token.php');

// date_default_timezone_set('Asia/Jakarta');

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
$res1 = array();
$res2 = array();
$iq = 0;
$id = "";
$data = "";
$dataX = "";
$json = "";
$ch = curl_init();
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
    $json = file_get_contents('php://input');
    $data = json_decode($json,true);
    if( isset($data['transactionID'])
        && !empty($data['transactionID'])){
            
            $transactionID = $data['transactionID'];
            $q = mysqli_query($db_conn, "SELECT t.id, t.phone, t.total,t.id_partner, p.name, p.address, p.shipper_location as partner_shipper_location, u.name as user_name, d.rate_id as user_rate_id, d.user_address_id, d.delivery_detail, d.is_insurance, a.recipient_name, a.recipient_phone, a.address as user_address, a.note, a.longitude as user_longitude, a.latitude as user_latitude, a.shipper_location as user_shipper_location, p.phone as partner_phone, p.longitude, p.latitude  FROM transaksi t JOIN partner p ON p.id=t.id_partner JOIN users u ON u.phone=t.phone JOIN delivery d ON d.transaksi_id=t.id JOIN addresses a ON a.id=d.user_address_id WHERE t.id='$transactionID' AND t.deleted_at IS NULL");
            
            if (mysqli_num_rows($q) > 0) {
                $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                $qD = mysqli_query($db_conn, "SELECT dt.qty, dt.harga_satuan, m.nama FROM detail_transaksi dt JOIN menu m ON m.id=dt.id_menu WHERE dt.id_transaksi='$transactionID'");
                if (mysqli_num_rows($qD) > 0) {
                    $resD = mysqli_fetch_all($qD, MYSQLI_ASSOC);
                }
                $arrM = array();
                $iM=0;
                $uAID = explode("|",json_decode($res[0]['user_shipper_location'])->value);
                $pAID = explode("|",json_decode($res[0]['partner_shipper_location'])->value);
                foreach($resD as $r){
                    $arrM[$iM]['name']=$r['nama'];
                    $arrM[$iM]['qty']=(int) $r['qty'];
                    $arrM[$iM]['value']=(int) $r['harga_satuan'];
                    $iM+=1;
                }
                $json = [
                    "o"=> (int)$pAID[1],
                    "d"=> (int) $uAID[1],
                    "l"=> 40,
                    "w"=> 40,
                    "h"=> 20,
                    "wt"=> 7,
                    "v"=> (int) $res[0]['total'],
                    "consigneeName"=> $res[0]['recipient_name'],
                    "consigneePhoneNumber"=> substr_replace($res[0]['recipient_phone'], "62", 0, 1),
                    "rateID"=> json_decode($res[0]['delivery_detail'])->rate_id,
                    "consignerName"=> $res[0]['name'],
                    "consignerPhoneNumber"=> substr_replace($res[0]['partner_phone'], "62", 0, 1),
                    "originAddress"=> $res[0]['address']."(".json_decode($res[0]['partner_shipper_location'])->label.")",
                    "originDirection"=>"",
                    "destinationAddress"=> $res[0]['user_address']."(".json_decode($res[0]['user_shipper_location'])->label.")",
                    "destinationDirection"=> $res[0]['note'],
                    "itemName"=> $arrM,
                    "contents"=> "Makanan",
                    "useInsurance"=> 0,
                    "externalID"=> $data['transactionID'],
                    "packageType"=> 2,
                    "cod"=> 0,
                    "destinationCoord"=> $res[0]['user_latitude'].",".$res[0]['user_longitude'],
                    "originCoord"=> $res[0]['latitude'].",".$res[0]['longitude'],
                ];
                $url = 'https://'.$_ENV['SHIPPER_URL'].'/public/v1/orders/domestics?apiKey='.$_ENV['SHIPPER_API_KEY'];
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
                
                $headers = array();
                $headers[] = 'Accept: application/json';
                //< adalah key server yang di ubah menggunakan base64>
                $headers[] = 'User-Agent: Shipper/';
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                
                //execute
                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    http_response_code(400);
                    
                    $success =0;
                    $status =400;
                    $msg = curl_error($ch);
                }else{
                    //response json dari API midtrans di ubah menjadi array
                    $result = json_decode($result);
                    foreach ($result as $x => $value) {
                        // echo gettype($value);
                        if(gettype($value)=="array"){
                            foreach ($value as $val) {
                                $val = json_decode(json_encode($val));
                                foreach($val as $y => $v){
                                    $arr[$y] = $v;
                                }
                            }
                        }else{
                            $arr[$x] = $value;
                        }
                    }
                }
                if($arr['status']==="success"){
                    http_response_code(200);
                    $success =1;
                    $status =200;
                    $msg = "Success";
                    $data = $arr['data'];
                    $dataX = $data;
                    $id = $data->id;
                    
                    $ch = curl_init();

                    curl_setopt($ch, CURLOPT_URL, 'https://'.$_ENV['SHIPPER_URL'].'/public/v1/orders?apiKey='.$_ENV['SHIPPER_API_KEY'].'&id='.$id);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    // curl_setopt($ch, CURLOPT_POST, 1);
                    
                    //set headers
                    $headers = array();
                    $headers[] = 'Accept: application/json';
                    //< adalah key server yang di ubah menggunakan base64>
                    $headers[] = 'User-Agent: Shipper/';
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    
                    //execute
                    $arr1 = array();
                    $result = curl_exec($ch);
                    if (curl_errno($ch)) {
                        $res1 = curl_error($ch);
                    }else{
                        //response json dari API midtrans di ubah menjadi array
                        $result = json_decode($result);
                        foreach ($result as $x => $value) {
                            if(gettype($value)=="array"){
                                foreach ($value as $val) {
                                    $val = json_decode(json_encode($val));
                                    foreach($val as $y => $v){
                                        $arr1[$y] = $v;
                                    }
                                }
                            }else{
                                $arr1[$x] = $value;
                            }
                        }
                        $res1 = $arr1;
                    }

                    $active = 1;

                    $url = 'https://'.$_ENV['SHIPPER_URL'].'/public/v1/activations/'.$id.'?apiKey='.$_ENV['SHIPPER_API_KEY'].'';
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($ch, CURLOPT_POSTFIELDS,"active=".$active);
                    
                    $headers = array();
                    $headers[] = 'Accept: application/x-www-form-urlencoded';
                    //< adalah key server yang di ubah menggunakan base64>
                    $headers[] = 'User-Agent: Shipper/';
                    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    
                    //execute
                    $result = curl_exec($ch);
                    if (curl_errno($ch)) {
                        http_response_code(400);
                        echo json_encode(["status"=>400, "success"=>0, "data"=>curl_error($ch)]);
                    }else{
                        //response json dari API midtrans di ubah menjadi array
                        $result = json_decode($result);
                        foreach ($result as $x => $value) {
                            // echo gettype($value);
                            if(gettype($value)=="array"){
                                foreach ($value as $val) {
                                    $val = json_decode(json_encode($val));
                                    foreach($val as $y => $v){
                                        $arr[$y] = $v;
                                    }
                                }
                            }else{
                                $arr[$x] = $value;
                            }
                        }
                    }
                    if($arr['status']==="success"){
                        $success =1;
                        $status =200;
                        $msg = "Success";
                        $res2 = $arr['data'];
                    }else{
                        $success =0;
                        $status =400;
                        $msg = "Failed";
                    }

                }else{
                    $success =0;
                    $status =400;
                    $msg = "Failed";
                    http_response_code(400);
                    $data = $arr['data'];
                }
            } else {
                $success =0;
                $status =204;
                $msg = "Data Tidak Ditemukan";
            }
        }else{
            $success =0;
            $status =400;
            $msg = "Missing Required Field";
        }
}
// http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "data"=>$dataX, "json"=>$json, "tracking"=>$res1, "activation"=>$res2]);
?>