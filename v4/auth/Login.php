<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../../db_connection.php';
require_once('./Token.php');

$token = "";
$today = date("Y-m-d H:i:s");
$tokenizer = new Token();

$obj = json_decode(file_get_contents("php://input"));
if (gettype($obj) == "NULL") {
    $obj = json_decode(json_encode($_POST));
}
$data = array();
if (
    isset($obj->email) && !empty($obj->email)
    && isset($obj->password) && !empty($obj->password)
) {

    $email = $obj->email;
    $email = strtolower($email);
    $password = $obj->password;
    $newPassword = md5($password);
    $shiftID = "";
    $full = 0;
    $q = mysqli_query($db_conn, "SELECT partner.open_close_table, partner.logo, partner.primary_subscription_id, employees.id,employees.nama AS name,partner.id_master,employees.id_partner,employees.role_id, partner.name as master_name, partner.parent_id, partner.is_pickup_notification, partner.is_queue_tracking, partner.is_temporary_qr, partner.address, r.id as role_id, r.name as role_name, r.is_owner,employees.nik, employees.gender, employees.email, employees.phone, partner.is_attendance,partner.is_reservation, partner.is_ar, partner.max_people_reservation, partner.max_days_reservation, partner.is_table_management, partner.is_foodcourt, parent.id AS parentID, partner.partner_type, parent.name AS parentName, parent.is_foodcourt AS parentFC, CASE WHEN partner.parent_id !='0' THEN parent.is_centralized WHEN partner.parent_id ='0' THEN partner.is_centralized ELSE 0 END AS is_centralized, CASE WHEN partner.fc_parent_id !='0' THEN parent.delivery_status_tracking WHEN partner.fc_parent_id ='0' THEN partner.delivery_status_tracking ELSE 0 END AS delivery_status_tracking, partner.track_server, partner.is_dp, partner.allow_override_stock, partner.is_special_reservation, partner.is_rounding, partner.rounding_digits, partner.rounding_down_below, m.point_pay as membership_point, m.harga_point as membership_point_multiplier, partner.membership_point_multiplier, partner.is_table_required FROM employees LEFT JOIN master ON master.id=employees.id_master JOIN partner ON employees.id_partner=partner.id JOIN roles r ON employees.role_id=r.id LEFT JOIN partner AS parent on partner.fc_parent_id = parent.id LEFT JOIN master m ON m.id = partner.id_master WHERE LOWER(employees.email)='$email' AND employees.pin='$newPassword' AND employees.deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $fetched = mysqli_fetch_assoc($q);
        $partnerID = $fetched['id_partner'];
        $jsonToken = json_encode(['id' => $fetched['id'], 'role_id' => $fetched['role_id'], 'id_master' => $fetched['id_master'], 'id_partner' => $fetched['id_partner'], 'created_at' => $today, 'expired' => 5000]);
        $token = $tokenizer->stringEncryption('encrypt', $jsonToken);

        $p = mysqli_query($db_conn, "SELECT name, is_bluetooth, is_pos, is_helper FROM `partner` WHERE id='$partnerID'");
        $fetchedP = mysqli_fetch_assoc($p);
        $qShift = mysqli_query($db_conn, "SELECT s.id FROM shift s WHERE s.partner_id='$partnerID' AND s.end IS NULL AND s.deleted_at IS NULL");
        if (mysqli_num_rows($qShift) > 0) {
            $fetchedShift = mysqli_fetch_assoc($qShift);
            $shiftID = $fetchedShift['id'];
        } else {
            $shiftID = "";
        }

        
        $q1 = "SELECT id, name, partner_id, name, address, city, state, zip, phone, website, twitter, facebook, instagram, notes, logo FROM partner_printer_template WHERE partner_id = '$partnerID' ";
        
        $qPrinter =  mysqli_query($db_conn, $q1);
        
        $printer_data = [];


        if (mysqli_num_rows($qPrinter) > 0) {
            $printer_data = mysqli_fetch_assoc($qPrinter);
        } else {
            $printer_data = [];
        }

        $data['oct'] = $fetched['open_close_table'];
        $data['psi'] = $fetched['primary_subscription_id'];
        $data['track_server'] = $fetched['track_server'];
        $data['employee_id'] = $fetched['id'];
        $data['nik'] = $fetched['nik'];
        $data['gender'] = $fetched['gender'];
        $data['name'] = $fetched['name'];
        $data['Ename'] = $fetched['name'];
        $data['email'] = $fetched['email'];
        $data['is_centralized'] = $fetched['is_centralized'];
        $data['is_reservation'] = $fetched['is_reservation'];
        $data['is_ar'] = $fetched['is_ar'];
        $data['delivery_status_tracking'] = $fetched['delivery_status_tracking'];
        $data['is_attendance'] = $fetched['is_attendance'];
        $data['is_table_management'] = $fetched['is_table_management'];
        $data['phone'] = $fetched['phone'];
        $data['master_id'] = $fetched['id_master'];
        $data['is_foodcourt'] = $fetched['is_foodcourt'];
        $data['master_name'] = $fetched['master_name'];
        $data['partner_name'] = $fetchedP['name'];
        $data['is_bluetooth'] = $fetchedP['is_bluetooth'];
        $data['partner_address'] = $fetched['address'];
        $data['partner_id'] = $fetched['id_partner'];
        $data['role_id'] = $fetched['role_id'];
        $data['parentID'] = $fetched['parentID'];
        $data['shiftID'] = $shiftID;
        $data['parentName'] = $fetched['parentName'];
        $data['parentFC'] = $fetched['parentFC'];
        $role_id = $fetched['role_id'];
        $data['is_owner'] = $fetched['is_owner'];
        $data['is_pos'] = $fetchedP['is_pos'];
        $data['is_helper'] = $fetchedP['is_helper'];
        $data['partner_type'] = $fetched['partner_type'];
        $data['isDP'] = $fetched['is_dp'];
        $data['logo'] = $printer_data["logo"];
        // $data['logo'] = $fetched['logo'];
        $data['parent_id'] = $fetched['parent_id'];
        $data['is_temporary_qr'] = $fetched['is_temporary_qr'];
        $data['allow_override_stock'] = $fetched['allow_override_stock'];
        $data['is_queue_tracking'] = $fetched['is_queue_tracking'];
        $data['is_pickup_notification'] = $fetched['is_pickup_notification'];
        $data['is_special_reservation'] = $fetched['is_special_reservation'];
        $data['printer_data'] = $printer_data;
        $data['is_rounding'] = $fetched['is_rounding'];
        $data['rounding_digits'] = $fetched['rounding_digits'];
        $data['rounding_down_below'] = $fetched['rounding_down_below'];
        $data['membership_point'] = $fetched['membership_point'];
        $data['membership_point_multiplier'] = $fetched['membership_point_multiplier'];
        $data['is_table_required']=$fetched['is_table_required'];
        if ($fetched['name'] == "" || $fetched['partner_type'] == 0) {
            $full = 0;
        } else {
            $full = 1;
        }
        $roleQ = mysqli_query($db_conn, "SELECT `name`, `is_owner`, is_owner_mode, `mobile`, `m1`, `m2`, `m3`, `m4`, `m5`, `m6`, `m7`, `m8`, `m9`, `m10`, `m11`, `m12`, `m13`, `m14`, `m15`, `m16`, `m17`, `m18`, `m19`, `m20`, `m21`, `m22`, `m23`, `m24`, `m25`, `m26`, `m27`, `m28`, `m29`, `m30`, `m31`, `m32`, `m32`, `m33`, `m34`, `m35`, `m36`, `m37`, `m38`, `m39`, `m40`, `m41`, `m42`, `m43`, `m44`, `m45`, `m46`, `m47`, `m48`, `m49`,`m50`, `m51`, `max_discount` FROM `roles` WHERE id='$role_id'");
        $fetchedRole = mysqli_fetch_assoc($roleQ);
        $data['roles'] = $fetchedRole;

        $idMaster = $data['master_id'];
        $sqlGetAll = mysqli_query($db_conn, "SELECT * FROM partner WHERE id_master = '$idMaster' AND deleted_at IS NULL");
        $getAll = mysqli_fetch_all($sqlGetAll, MYSQLI_ASSOC);
        $data['partners'] = $getAll;

        if ($fetchedRole['mobile'] == "1") {
            $msg = "Logged";
            $success = 1;
            $status = 200;
        } else {
            $msg = "Kamu tidak memiliki akses aplikasi. Mohon hubungi admin";
            $success = 0;
            $status = 204;
        }
    } else {
        $success = 0;
        $status = 204;
        $msg = "Email atau password salah";
    }
} else {
    $success = 0;
    $status = 400;
    $msg = "Missing Require Field";
}

echo json_encode(["success" => $success, "status" => $status, "msg" => $msg, "detail" => $data, "token" => $token, "full" => $full] );
