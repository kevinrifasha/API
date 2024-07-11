<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("./../tokenModels/tokenManager.php");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../partnerTableModels/partnerTableManager.php");
require_once("./../partnerTableModels/partnerTable.php");

$headers = apache_request_headers();
$token = '';

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}

$db = connectBase();
$tokenizer = new TokenManager($db);
$tokens = $tokenizer->validate($token);
$tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
$value = array();
$success=0;
$msg = 'Failed';
$idList="";
$i = 0;
$validateDuplicateId=0;

if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){

    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $json = file_get_contents('php://input');
    $data = json_decode($json,true);
    $id_partner = $tokenDecoded->partnerID;
    $tableManager = new PartnerTableManager($db);
    $partnerTables = $tableManager->getByPartnerId($id_partner);
    $partnerTableIds = [];
    foreach($partnerTables as $partnerTable){
        array_push($partnerTableIds, $partnerTable->getIdmeja());
    }
    if(
        isset($data) && !empty($data) && 
        isset($data['tables']) && !empty($data['tables'])
    ){
        // validasi nama menu sama yang dari excel
        $vals = array_count_values(array_column($data['tables'], 'tableId'));
        $n = count(array_column($data['tables'], 'tableId'));
        if(count($data['tables']) != count($vals)){
            $validateDuplicateId = 1;
        }
        
        if($validateDuplicateId == 0) {
            foreach($partnerTables as $x){
                $id = $x->getId();
                // cek table apakah sudah ada belum
                // cek dari id table
                $delete = $tableManager->delete($id);
            }
            foreach($data["tables"] as $x){
                $tableId = $x['tableId'];
                $isQueue = $x['isQueue'];
                $table = new PartnerTable(array("idpartner"=>$id_partner, "idmeja"=>$tableId, "is_queue"=>$isQueue));
                $insert = $tableManager->add($table);
            }
            $msg = "Berhasil Import '$n' Data dari Excel";
            $success = 1;
            $status=200;    
        } 
        else {
            $success = 0;
            $msg = "Nomor meja tidak boleh ada yang sama";
            $status = 204;
        }
    }else{
        $success = 0;
        $msg = "Data tidak lengkap";
        $status = 400;
    }

}
echo json_encode(["status"=>$status, "success"=>$success, "msg"=>$msg, "insertData"=>$data]);
?>