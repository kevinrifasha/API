<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../../db_connection.php';
require_once('./Token.php');
require_once '../../includes/DbOperation.php';


$today = date("Y-m-d H:i:s");
$dbo = new DbOperation();
// $headers = array();
$rx_http = '/\AHTTP_/';
foreach ($_SERVER as $key => $val) {
    if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
            $arh_key = implode('-', $rx_matches);
        }
        $headers[$arh_key] = $val;
    }
}
$insertedMasterID = "0";
$insertedEmpID = "0";
$idPartner = "0";
$token = '';
$success = 0;
$msg = 'Failed';
$referral = "";

$tokenizer = new Token();

$data = json_decode(json_encode($_POST));

if(isset($data->name) && !empty($data->name)) {

    $qM = mysqli_query($db_conn, "SELECT id FROM `master` WHERE email='$data->email'");
    $qP = mysqli_query($db_conn, "SELECT id FROM `partner` WHERE email='$data->email' OR phone='$data->phone'");
    $qE = mysqli_query($db_conn, "SELECT id FROM `employees` WHERE email='$data->email' OR phone='$data->phone'");
    if (isset($data->referral)) {
        $referral = $data->referral;
    } else {
        $referral = "";
    }
    if (mysqli_num_rows($qM) == 0 && mysqli_num_rows($qP)==0 && mysqli_num_rows($qE)==0) {
        $password = md5($data->password);
        $q = mysqli_query($db_conn, "INSERT INTO `master`(`password`, `email`, `name`, `phone`, `organization`) VALUES ('$password','$data->email', '$data->business_name', '$data->phone', 'Natta')");
        $insertedMasterID = mysqli_insert_id($db_conn);
    
        if ($q) {
    
            $qP = mysqli_query($db_conn, "SELECT id FROM partner WHERE id NOT LIKE 'q%' ORDER BY id DESC LIMIT 1");
            $resP = mysqli_fetch_all($qP, MYSQLI_ASSOC);
            $idPartner = (int) $resP[0]['id'];
            $idPartner += 1;
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
            } else if ($idPartner < 100000) {
                $idPartner = (string) $idPartner;
                $idPartner = ("0" . $idPartner);
            } else {
                $idPartner = (string) $idPartner;
            }
            $insertDept = mysqli_query($db_conn, "INSERT INTO `departments`(`name`, `partner_id`) VALUES ('Kitchen', '$idPartner')");
            $idDept = mysqli_insert_id($db_conn);
    
            $insertDept = mysqli_query($db_conn, "INSERT INTO `departments`(`name`, `partner_id`) VALUES ('Bar', '$idPartner')");
            $idDept1 = mysqli_insert_id($db_conn);
    
            $qC = mysqli_query($db_conn, "INSERT INTO `categories`(`id_master`, `name`, `sequence`, `department_id`) VALUES ('$insertedMasterID','Promo','0', '0'),('$insertedMasterID','Makanan','1', '$idDept'),('$insertedMasterID','Minuman','2', '$idDept1');");
            $insertOpeningHours = mysqli_query($db_conn, "INSERT INTO partner_opening_hours SET partner_id='$idPartner'");
    
    
            $qV = mysqli_query($db_conn, "SELECT value FROM `settings` WHERE id='6'");
            $resV = mysqli_fetch_all($qV, MYSQLI_ASSOC);
            $charge_ur = $resV[0]['value'];
    
            $qV = mysqli_query($db_conn, "SELECT value FROM `settings` WHERE id='15'");
            $resV = mysqli_fetch_all($qV, MYSQLI_ASSOC);
            $charge_ur_shipper = $resV[0]['value'];
    
            $qV = mysqli_query($db_conn, "SELECT value FROM `settings` WHERE id=29");
            $resV = mysqli_fetch_all($qV, MYSQLI_ASSOC);
            $trialDuration = $resV[0]['value'];
            $now = strtotime(date('Y-m-d'));
            $dayString = "+" . $trialDuration . " day";
            $trialUntil = strtotime($dayString, $now);
            $trialUntil = date('Y-m-d', $trialUntil);
            $qIP = mysqli_query($db_conn, "INSERT INTO `partner`(`id`, `name`, `phone`, `status`, `id_master`, `charge_ur`, `charge_ur_shipper`, subscription_status, trial_until, referral, `organization`) VALUES ('$idPartner','$data->business_name','$data->phone', '1', '$insertedMasterID', '$charge_ur', '$charge_ur_shipper', 'Trial', '$trialUntil', '$referral', 'Natta')");
    
            $insertedPartnerID = mysqli_insert_id($db_conn);
    
            $qIR = mysqli_query($db_conn, "INSERT INTO `roles` (`master_id`, `partner_id`, `name`, `is_owner`, `web`, `mobile`, `w1`, `w2`, `w3`, `w4`, `w5`, `w6`, `w7`, `w8`, `w9`, `w10`, `w11`, `w12`, `w13`, `w14`, `w15`, `m1`, `m2`, `m3`, `m4`, `m5`, `m6`, `m7`, `m8`, `m9`, `m10`, `m11`, `m12`, `m13`, `m14`, `m15`, `m16`, `m17`, `m18`, `m19`, `created_at`, `updated_at`, `deleted_at`, `max_discount`, `m20`, `m21`, `m22`) VALUES ('$insertedMasterID', '$idPartner', 'Kasir', '0', '0', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '1', '0', '0', '0', '0', NOW(), NULL, NULL, '0', '0','0','0')");
    
            $qIR = mysqli_query($db_conn, "INSERT INTO `roles` (`master_id`, `partner_id`, `name`, `is_owner`, `is_owner_mode`, `web`, `mobile`, `w1`, `w2`, `w3`, `w4`, `w5`, `w6`, `w7`, `w8`, `w9`, `w10`, `w11`, `w12`, `w13`, `w14`, `w15`, `m1`, `m2`, `m3`, `m4`, `m5`, `m6`, `m7`, `m8`, `m9`, `m10`, `m11`, `m12`, `m13`, `m14`, `m15`, `m16`, `m17`, `m18`, `m19`, `m20`, `m21`, `m22`, `m23`, `m24`, `m25`, `m26`, `m27`, `m28`, `m29`, `m30`, `m31`, `m32`, `m33`, `m34`, `m35`, `m36`, `m37`, `m38`, `m39`, `m40`, `m41`, `m42`, `m43`, `m44`, `m45`, `m46`, `m47`, `m48`, `m49`,`m50`, `created_at`, `max_discount`, `w16`, `w17`, `w18`, `w19`, `w20`, `w21`, `w22`, `w23`, `w24`, `w25`, `w26`, `w27`, `w28`, `w29`, `w30`, `w31`, `w32`, `w33`, `w34`, `w35`, `w36`, `w37`, `w38`, `w39`, `w40`, `w41`, `w42`, `w43`, `w44`,`w45`, is_order_notif, is_reservation_notif, is_withdrawal_notif) VALUES ('$insertedMasterID','$idPartner', 'Owner', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1','1', NOW(), '100', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1')");
    
            $insertedRoleID = mysqli_insert_id($db_conn);
            $qE = mysqli_query($db_conn, "INSERT INTO `employees`(`nama`, `phone`, `email`, `pin`, `id_master`, `id_partner`, `role_id`, `created_at`, `organization`) VALUES ('$data->name', '$data->phone', '$data->email', '$password', '$insertedMasterID', '$idPartner','$insertedRoleID', NOW(), 'Natta')");
            $insertedEmpID = mysqli_insert_id($db_conn);
    
    
            $roleQ = mysqli_query($db_conn, "SELECT `name`, `is_owner`, `mobile`, `m1`, `m2`, `m3`, `m4`, `m5`, `m6`, `m7`, `m8`, `m9`, `m10`, `m11`, `m12`, `m13`, `m14`, `m15`, `m16`, `m17`, `m18`, `m19`, `max_discount` FROM `roles` WHERE id='$insertedRoleID'");
            $fetchedRole = mysqli_fetch_assoc($roleQ);
            $roles = $fetchedRole;
    
            $jsonToken = json_encode(['id' => $insertedEmpID, 'role_id' => $insertedRoleID, 'roles' => $roles, 'id_master' => $insertedMasterID, 'id_partner' => $idPartner, 'created_at' => $today, 'expired' => 200]);
            $token = $tokenizer->stringEncryption('encrypt', $jsonToken);
    
            $insert = mysqli_query($db_conn, "INSERT INTO `surcharges`(`partner_id`, `name`, `surcharge`, `created_at`) VALUES ('$idPartner', 'Gofood', '20', NOW()), ('$idPartner', 'Shopeefood', '20', NOW()), ('$idPartner', 'Grabfood', '20', NOW())");
            $insert = mysqli_query($db_conn, "INSERT INTO operational_expense_categories (master_id, name, partner_id) VALUES ('$insertedMasterID', 'Sewa','$idPartner' ), ('$insertedMasterID', 'Air', '$idPartner'), ('$insertedMasterID', 'Listrik', '$idPartner'), ('$insertedMasterID', 'Gaji', '$idPartner'), ('$insertedMasterID', 'Keamanan','$idPartner'), ('$insertedMasterID', 'Pembayaran Supplier', '$idPartner')");
            if ($qIP) {
                $success = 1;
                $msg = "Berhasil";
                
                $sqlPartner = mysqli_query($db_conn, "SELECT name FROM partner WHERE id = '$idPartner' AND deleted_at IS NULL");
                $partnerData = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
                $partnerName = $partnerData[0]['name'];
                
                $result = $dbo->mailAddMaster($data->email, $data->name, $partnerName, $idPartner);
            } else {
                $success = 0;
                $msg = "Daftar partner gagal";
            }
        } else {
            $success = 0;
            $msg = "Gagal mendaftarkan partner. Mohon coba lagi";
        }
    }else{
        $success=0;
        $msg="Email atau No. HP sudah terdaftar. Mohon login atau klik lupa password";
    }
} else {
    $success = 0;
    $msg = "Missing Required Fields";
}

// }

$signupJson = json_encode(["msg" => $msg, "success" => $success, "partnerID" => $idPartner, "masterID" => $insertedMasterID, "employeeID" => $insertedEmpID, "token" => $token]);
// Echo the message.
echo $signupJson;
