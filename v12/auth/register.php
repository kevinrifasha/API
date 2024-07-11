<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../masterModels/masterManager.php");
require_once("./../partnerModels/partnerManager.php");
require_once("./../employeeModels/employeeManager.php");
require_once("./../categoryModels/categoryManager.php");
require '../../db_connection.php';
require_once '../../includes/DbOperation.php';

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
$db = connectBase();
$data = json_decode(json_encode($_POST));
$success = 0;

if (isset($data->name) && !empty($data->name)) {

    $masterManager = new MasterManager($db);
    $emailValidation = $masterManager->getMasterByEmail($data->email);
    $qM = mysqli_query($db_conn, "SELECT id FROM `master` WHERE email='$data->email' AND deleted_at IS NULL AND organization='Natta'");
    $qP = mysqli_query($db_conn, "SELECT id FROM `partner` WHERE email='$data->email' OR phone='$data->phone' AND deleted_at IS NULL AND organization='Natta'");
    $qE = mysqli_query($db_conn, "SELECT id FROM `employees` WHERE deleted_at IS NULL AND (email='$data->email' OR phone='$data->phone') AND organization='Natta'");
    if (mysqli_num_rows($qM) == 0 && mysqli_num_rows($qP) == 0 && mysqli_num_rows($qE) == 0) {
        if (!isset($data->referrer) && empty($data->referrer)) {
            $data->referrer = "";
        }
  
        $master = new Master(array("name" => $data->name, "email" => $data->email, "password" => md5($data->password), "phone" => $data->phone, "referrer" => $data->referrer, "organization" => 'Natta'));
        $addMaster = $masterManager->add($master);

        if ($addMaster != false) {

            $partnerManager = new PartnerManager($db);
            $idPartner = $partnerManager->getLastIdResto();
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

            $insertDept = mysqli_query($db_conn, "INSERT INTO `departments`(`name`, `partner_id`, master_id, `created_at`) VALUES ('Kitchen', '$idPartner', $addMaster, NOW())");
            $idDept = mysqli_insert_id($db_conn);

            $categoryManager = new CategoryManager($db);
            $category = new Category(array("id" => 0, "id_master" => $addMaster, "name" => "Makanan", "sequence" => 1, "department_id" => $idDept));
            $insert = $categoryManager->add($category);

            $insertDept = mysqli_query($db_conn, "INSERT INTO `departments`(`name`, `partner_id`, master_id, `created_at`) VALUES ('Bar', '$idPartner', $addMaster, NOW())");
            $idDept = mysqli_insert_id($db_conn);

            $categoryManager = new CategoryManager($db);
            $category = new Category(array("id" => 0, "id_master" => $addMaster, "name" => "Minuman", "sequence" => 2, "department_id" => $idDept));
            $insert = $categoryManager->add($category);

            $categoryManager = new CategoryManager($db);
            $category = new Category(array("id" => 0, "id_master" => $addMaster, "name" => "Promo", "sequence" => 0));
            $insert = $categoryManager->add($category);

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

            $parent_id = null;
            $partner = new Partner(array("id" => $idPartner, "id_master" => $addMaster, "name" => $data->name, "phone" => $data->phone, "charge_ur" => $charge_ur, "charge_ur_shipper" => $charge_ur_shipper, "trial_until"=>$trialUntil, "parent_id"=>NULL, "organization"=>'Natta'));
            $addPartner = $partnerManager->add($partner); 

            $qIR = mysqli_query($db_conn, "INSERT INTO `roles` (`master_id`, `partner_id`, `name`, `is_owner`, `web`, `mobile`, `w1`, `w2`, `w3`, `w4`, `w5`, `w6`, `w7`, `w8`, `w9`, `w10`, `w11`, `w12`, `w13`, `w14`, `w15`, `m1`, `m2`, `m3`, `m4`, `m5`, `m6`, `m7`, `m8`, `m9`, `m10`, `m11`, `m12`, `m13`, `m14`, `m15`, `m16`, `m17`, `m18`, `m19`, `created_at`, `updated_at`, `deleted_at`, `max_discount`, `m20`, `m21`) VALUES ('$addMaster', '$idPartner', 'Kasir', '0', '0', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '1', '0', '0', '0', '0', NOW(), NULL, NULL, '0', '0', '0')");
            $qIR = mysqli_query($db_conn, "INSERT INTO `roles` (`master_id`, `partner_id`, `name`, `is_owner`, `is_owner_mode`, `web`, `mobile`, `w1`, `w2`, `w3`, `w4`, `w5`, `w6`, `w7`, `w8`, `w9`, `w10`, `w11`, `w12`, `w13`, `w14`, `w15`, `m1`, `m2`, `m3`, `m4`, `m5`, `m6`, `m7`, `m8`, `m9`, `m10`, `m11`, `m12`, `m13`, `m14`, `m15`, `m16`, `m17`, `m18`, `m19`, `m20`, `m21`, `m22`, `m23`, `m24`, `m25`, `m26`, `m27`, `m28`, `m29`, `m30`, `m31`, `m32`, `m33`, `m34`, `m35`, `m36`, `m37`, `m38`, `m39`, `m40`, `m41`, `m42`, `m43`, `m44`, `m45`, `m46`, `m47`, `m48`, `m49`,`m50`, `created_at`, `max_discount`, `w16`, `w17`, `w18`, `w19`, `w20`, `w21`, `w22`, `w23`, `w24`, `w25`, `w26`, `w27`, `w28`, `w29`, `w30`, `w31`, `w32`, `w33`, `w34`, `w35`, `w36`, `w37`, `w38`, `w39`, `w40`, `w41`, `w42`, `w43`, `w44`, `w45`, is_order_notif, is_reservation_notif, is_withdrawal_notif) VALUES ('$addMaster','$idPartner', 'Owner', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1','1', NOW(), '100', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1')");

            $insertedRoleID = mysqli_insert_id($db_conn);
            $insert = mysqli_query($db_conn, "INSERT INTO `surcharges`(`partner_id`, `name`, `surcharge`, `created_at`) VALUES ('$idPartner', 'Gofood', '20', NOW()), ('$idPartner', 'Shopeefood', '20', NOW()), ('$idPartner', 'Grabfood', '20', NOW())");

            $insert = mysqli_query($db_conn, "INSERT INTO operational_expense_categories (master_id, partner_id, name) VALUES ('$addMaster', '$idPartner', 'Sewa'), ('$addMaster', '$idPartner', 'Air'), ('$addMaster', '$idPartner', 'Listrik'), ('$addMaster', '$idPartner', 'Gaji'), ('$addMaster', '$idPartner', 'Keamanan'), ('$addMaster', '$idPartner', 'Pembayaran Supplier')");


            $empPassword = md5($data->password);
            $qAddEmployee = "INSERT INTO employees SET nama='$data->name', email='$data->email', pin='$empPassword', phone='$data->phone', id_master='$addMaster', id_partner='$idPartner', role_id='$insertedRoleID', organization='Natta'";
            $sqlAddEmployee = mysqli_query($db_conn, $qAddEmployee);
            $addEmployee = mysqli_insert_id($db_conn);
            // ganti dengan query end

            $insertOpeningHours = mysqli_query($db_conn, "INSERT INTO partner_opening_hours SET partner_id='$idPartner'");

            $sqlPartner = mysqli_query($db_conn, "SELECT name FROM partner WHERE id = '$idPartner' AND deleted_at IS NULL");
            $partnerData = mysqli_fetch_all($sqlPartner, MYSQLI_ASSOC);
            $partnerName = $partnerData[0]['name'];

            $result = $dbo->mailAddMaster($data->email, $data->name, $partnerName, $idPartner);

            if ($result == "SUCCESS") {
                $success = 1;
                $msg = "Berhasil";
            } else {
                $success = 0;
                $msg = $result;
            }
        } else {
            $msg = "Gagal!, Silahkan Coba Lagi";
        }
    } else {
        $success = 0;
        $idPartner = 0;
        $addMaster = 0;
        $addEmployee = 0;
        $msg = "Email atau No. HP sudah terdaftar. Mohon login atau klik lupa password";
    }
} else {
    $success = 0;
    $idPartner = 0;
    $addMaster = 0;
    $addEmployee = 0;
    $msg = "Data Tidak Boleh Kosong";
}

$signupJson = json_encode(["msg" => $msg, "success" => $success, "partnerID" => $idPartner, "masterID" => $addMaster, "employeeID" => $addEmployee, "test"=>$test]);
echo $signupJson;
