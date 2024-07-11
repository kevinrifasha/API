<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../../tokenModels/tokenManager.php");
require_once("../../partnerModels/partnerManager.php");
require_once("../../masterModels/masterManager.php");
require_once("../../connection.php");
require '../../../db_connection.php';

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
if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
    $status = $tokens['status'];
    $msg = $tokens['msg'];
    $success = 0;

}else{
    $obj = json_decode(file_get_contents('php://input'));
    if(isset($obj->name) && !empty($obj->name)){
        $qV = mysqli_query($db_conn,"SELECT value FROM `settings` WHERE id='6'");
        $resV = mysqli_fetch_all($qV, MYSQLI_ASSOC);
        $charge_ur = $resV[0]['value'];

        $qV = mysqli_query($db_conn,"SELECT value FROM `settings` WHERE id='15'");
        $resV = mysqli_fetch_all($qV, MYSQLI_ASSOC);
        $charge_ur_shipper = $resV[0]['value'];

        $masterManager = new MasterManager($db);
        $master = new Master(array("name"=>str_replace("'","''",$obj->name),"email"=>"","password"=>"","phone"=>$obj->phone,"referrer"=>""));
        $addMaster = $masterManager->add($master);

        $partnerManager = new PartnerManager($db);
        $idPartner = $partnerManager->getLastIdResto();
        $idPartner += 1 ;
        if ($idPartner < 10) {
            $idPartner = (string) $idPartner;
            $idPartner = ("00000" . $idPartner);
        } else if ($idPartner < 100) {
            $idPartner = (string) $idPartner;
            $idPartner = ("0000" . $idPartner);
        } else if ($idPartner < 1000) {
            $idPartner = (string) $idPartner;
            $idPartner = ("000" . $idPartner);
        } else if ($idPartner < 10000) {
            $idPartner = (string) $idPartner;
            $idPartner = ("00" . $idPartner);
        }else if ($idPartner < 100000) {
            $idPartner = (string) $idPartner;
            $idPartner = ("0" . $idPartner);
        }else {
            $idPartner = (string) $idPartner;
        }

        $insert = mysqli_query($db_conn, "INSERT INTO `partner`(`id`, `fc_parent_id`, `stall_id`, `name`, `phone`, `img_map`, `thumbnail`, `is_dine_in`, `is_open`, `is_attendance`, `jam_buka`, `jam_tutup`, `created_at`, `partner_type`, `url`, `is_email_report`, `charge_ur`, `charge_ur_shipper`, `status`, `id_master`) VALUES ('$idPartner', '$tokenDecoded->partnerID', '$obj->stall_id', '$obj->name', '$obj->phone', '$obj->img_map', '$obj->thumbnail', '1', '1', '1', '$obj->jam_buka', '$obj->jam_tutup', NOW(), '3', '$obj->url', '1','$charge_ur', '$charge_ur_shipper', '$obj->status', '$addMaster')");
        $qIR = mysqli_query($db_conn, "INSERT INTO `roles` (`master_id`, `partner_id`, `name`, `is_owner`, `web`, `mobile`, `w1`, `w2`, `w3`, `w4`, `w5`, `w6`, `w7`, `w8`, `w9`, `w10`, `w11`, `w12`, `w13`, `w14`, `w15`, `m1`, `m2`, `m3`, `m4`, `m5`, `m6`, `m7`, `m8`, `m9`, `m10`, `m11`, `m12`, `m13`, `m14`, `m15`, `m16`, `m17`, `m18`, `m19`, `created_at`, `updated_at`, `deleted_at`, `max_discount`, `m20`, `m21`) VALUES ('0', '$idPartner', 'Kasir', '0', '0', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '1', '0', '0', '0', '0', NOW(), NULL, NULL, '0', '0', '0')");
        $qIR = mysqli_query($db_conn, "INSERT INTO `roles`(`master_id`, `partner_id`, `name`, `is_owner`, `web`, `mobile`, `w1`, `w2`, `w3`, `w4`, `w5`, `w6`, `w7`, `w8`, `w9`, `w10`, `w11`, `w12`, `w13`, `w14`, `w15`, `m1`, `m2`, `m3`, `m4`, `m5`, `m6`, `m7`, `m8`, `m9`, `m10`, `m11`, `m12`, `m13`, `m14`, `m15`, `m16`, `m17`, `m18`, `m19`, `created_at`, `max_discount`, `m20`, `m21`, `m22`) VALUES ('0','$idPartner', 'Owner', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', NOW(), '100', '1', '1', '1')");
        if($insert) {
            $success = 1;
            $status = 200;
            $msg = "Berhasil tambah data";
        }else{
            $success = 0;
            $status = 204;
            $msg = "Gagal tambah data. mohon coba lagi";
        }
    }else{
        $success = 0;
        $status = 204;
        $msg = "Mohon lengkapi form";
    }


}
// echo "a";
echo json_encode(["success"=>$success, "status"=>$status,"msg"=>$msg]);
