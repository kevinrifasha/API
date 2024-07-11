<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headeras, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require_once("./../partnerTableModels/partnerTableManager.php");


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

    $json = file_get_contents('php://input');
    $obj = json_decode($json,true);

      $Tmanager = new PartnerTableManager($db);
            $tables = $Tmanager->getByPartnerId($obj['partnerId']);
            $allow = true;
            $res = array();
            foreach ($tables as $table) {
                $data = $table->getDetails();
                if($data['idmeja']==str_replace("'","''",$obj['tableId']) && $data['id']!=$obj['id']){
                    $allow = false;
                }
            }


            if($allow == true){
                $tableManager = new PartnerTableManager($db);
                // var_dump(array("id"=>$obj['id'],"idpartner"=>$obj['partnerId'], "idmeja"=>$obj['tableId'],"is_queue"=>$obj['isQueue']));
                $table = new PartnerTable(array("id"=>$obj['id'],"idpartner"=>$obj['partnerId'], "idmeja"=>str_replace("'","''",$obj['tableId']),"is_queue"=>$obj['isQueue']));
                $update = $tableManager->update($table);

                if($update==true){
                    $success=1;
                    $status=200;
                    $msg="Berhasil merubah data meja";
                }else{
                    $success=0;
                    $status=204;
                    $msg="Gagal merubah data meja";
                }
            }else{

                 $success=0;
                    $msg="Nomor Meja Sudah Terdaftar";
                    $status = 203;

            }


}


    $signupJson = json_encode(["msg"=>$msg, "status"=>$status,"success"=>$success]);
    if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
        http_response_code(200);
    }else{
        http_response_code($status);
    }
    echo $signupJson;
