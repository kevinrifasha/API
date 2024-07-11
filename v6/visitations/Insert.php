<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
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
    $data = json_decode(file_get_contents('php://input'));
    if(
        !empty($data->merchantName)
        && !empty($data->businessType)
        && !empty($data->address)
        && !empty($data->picName)
    ){
        if($data->isMock=="false"){
            $data->isMock=0;
        }else{
            $data->isMock=1;
        }
        if(!isset($data->packageType)){
            $data->packageType=0;
        }
        if(!isset($data->currentPOS)){
            $data->currentPOS=0;
        }
        if(!isset($data->wpID)||empty($data->wpID)){
            $data->wpID=0;
        }
        
        $merchantName = mysqli_real_escape_string($db_conn, $data->merchantName);
        $address = mysqli_real_escape_string($db_conn, $data->address);
        $picName = mysqli_real_escape_string($db_conn, $data->picName);
        
        $qInsert = "INSERT INTO sa_visitations SET merchant_name='$merchantName', business_type='$data->businessType', address='$address', pic_name='$picName', pic_phone='$data->picPhone', visit_no='$data->visitNo', customer_input='$data->customerInput', latitude='$data->latitude', longitude='$data->longitude', is_mock='$data->isMock', image='$data->imagePath', created_by='$token->id', package_type='$data->packageType', current_pos='$data->currentPOS', other_pos='$data->otherPOS', work_plan_detail_id='$data->wpID', customer_input_id='$data->customerInputID'";
        $insert = mysqli_query($db_conn,$qInsert);
        if($insert){
            if($data->wpID!=0){
                $updateWP = mysqli_query($db_conn, "UPDATE sa_work_plan_details SET visited=1 WHERE id='$data->wpID'");
            }
            $msg = "Berhasil tambah data";
            $success = 1;
            $status=200;
        }else{
            $msg = "Gagal tambah data";
            $success = 0;
            $status=200;
        }
    }else{
        $success = 0;
        $msg = "Mohon lengkapi data";
        $status = 200;
    }

}
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);

?>