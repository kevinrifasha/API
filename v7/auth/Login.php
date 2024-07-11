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
if(gettype($obj)=="NULL"){
    $obj = json_decode(json_encode($_POST));
}
$data = array();
if(
    isset($obj->email)&&!empty($obj->email)
    && isset($obj->password)&&!empty($obj->password)
){

    $email = $obj->email;
    $email = strtolower($email);
    $password = $obj->password;
    $newPassword = md5($password);
    $shiftID ="";
    $q = mysqli_query($db_conn, "SELECT partner.logo, partner.primary_subscription_id, employees.id,employees.nama AS name,employees.id_master,employees.id_partner,employees.role_id, partner.name as master_name, partner.address, r.id as role_id, r.name as role_name, r.is_owner,employees.nik, employees.gender, employees.email, employees.phone, partner.is_attendance,partner.is_reservation, partner.is_ar, partner.max_people_reservation, partner.max_days_reservation, partner.is_table_management, partner.is_foodcourt, parent.id AS parentID, partner.partner_type, parent.name AS parentName, parent.is_foodcourt AS parentFC, CASE WHEN partner.fc_parent_id !='0' THEN parent.is_centralized WHEN partner.fc_parent_id ='0' THEN partner.is_centralized ELSE 0 END AS is_centralized, CASE WHEN partner.fc_parent_id !='0' THEN parent.delivery_status_tracking WHEN partner.fc_parent_id ='0' THEN partner.delivery_status_tracking ELSE 0 END AS delivery_status_tracking, partner.track_server, partner.is_dp, partner.open_close_table FROM employees LEFT JOIN master ON master.id=employees.id_master JOIN partner ON employees.id_partner=partner.id JOIN roles r ON employees.role_id=r.id LEFT JOIN partner AS parent on partner.fc_parent_id = parent.id WHERE LOWER(employees.email)='$email' AND employees.pin='$newPassword' AND employees.deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        $fetched = mysqli_fetch_assoc($q);
        $partnerID = $fetched['id_partner'];
        $jsonToken = json_encode(['id'=>$fetched['id'], 'roleID'=>$fetched['role_id'], 'masterID'=>$fetched['id_master'],'partnerID'=>$fetched['id_partner'], 'created_at'=>$today, 'expired'=>1000]);
        $token = $tokenizer->stringEncryption('encrypt', $jsonToken);
        $success = 1;
        $status = 200;
        $full=0;
        $p = mysqli_query($db_conn, "SELECT name, is_bluetooth, is_pos, is_helper FROM `partner` WHERE id='$partnerID'");
        $fetchedP = mysqli_fetch_assoc($p);
        $qShift = mysqli_query($db_conn, "SELECT s.id FROM shift s WHERE s.partner_id='$partnerID' AND s.end IS NULL AND s.deleted_at IS NULL");
        if(mysqli_num_rows($qShift)>0){
            $fetchedShift = mysqli_fetch_assoc($qShift);
            $shiftID = $fetchedShift['id'];
        }else{
            $shiftID ="";
        }
        $data['psi'] = $fetched['primary_subscription_id'];
        $data['track_server'] = $fetched['track_server'];
        $data['employee_id'] = $fetched['id'];
        $data['nik']= $fetched['nik'];
        $data['gender']= $fetched['gender'];
        $data['name']= $fetched['name'];
        $data['Ename']= $fetched['name'];
        $data['email']= $fetched['email'];
        $data['is_centralized']= $fetched['is_centralized'];
        $data['is_reservation']= $fetched['is_reservation'];
        $data['is_ar']= $fetched['is_ar'];
        $data['delivery_status_tracking']= $fetched['delivery_status_tracking'];
        $data['is_attendance']= $fetched['is_attendance'];
        $data['is_table_management']= $fetched['is_table_management'];
        $data['phone']= $fetched['phone'];
        $data['master_id'] = $fetched['id_master'];
        $data['is_foodcourt'] = $fetched['is_foodcourt'];
        $data['master_name']= $fetched['master_name'];
        $data['partner_name']= $fetchedP['name'];
        $data['is_bluetooth']= $fetchedP['is_bluetooth'];
        $data['partner_address']= $fetched['address'];
        $data['partner_id']= $fetched['id_partner'];
        $data['role_id'] = $fetched['role_id'];
        $data['parentID'] = $fetched['parentID'];
        $data['shiftID'] = $shiftID;
        $data['parentName'] = $fetched['parentName'];
        $data['parentFC'] = $fetched['parentFC'];
        $role_id = $fetched['role_id'];
        $data['is_owner']=$fetched['is_owner'];
        $data['is_pos']= $fetchedP['is_pos'];
        $data['is_helper']= $fetchedP['is_helper'];
        $data['partner_type'] = $fetched['partner_type'];
        $data['isDP'] = $fetched['is_dp'];
        $data['logo'] = $fetched['logo'];
        $data['open_close_table'] = $fetched['open_close_table'];
        if($fetched['name']==""||$fetched['partner_type']==0){
            $full=0;
        }else{
            $full=1;
        }
        $roleQ = mysqli_query($db_conn, "SELECT `name`, `is_owner`, `mobile`, `m1`, `m2`, `m3`, `m4`, `m5`, `m6`, `m7`, `m8`, `m9`, `m10`, `m11`, `m12`, `m13`, `m14`, `m15`, `m16`, `m17`, `m18`, `m19`, `max_discount`, `m20`, `m21`, `m22` FROM `roles` WHERE id='$role_id'");
        $fetchedRole = mysqli_fetch_assoc($roleQ);
        $data['roles']=$fetchedRole;
        $msg = "Logged";
        $id = $partnerID;
        $today = strtolower(date("l"));
        $q = mysqli_query($db_conn, "SELECT partner.`id`, partner.`partner_type`, partner.`name`, partner.`address`, partner.`phone`, partner.`status`, partner.`tax`, partner.`service`, partner.`restaurant_number`, partner.`delivery_fee`, partner.`ovo_active`, partner.`dana_active`, partner.`linkaja_active`, partner.gopay_active, partner.`cc_active`, partner.`debit_active`, partner.`qris_active`, partner.`shopeepay_active`, partner.`id_master`, partner.`longitude`, partner.`latitude`, partner.`img_map`, partner.`desc_map`, partner.`is_delivery`, partner.`is_takeaway`, partner.`is_open`, partner.`wifi_ssid`, partner.`wifi_password`, partner.`is_booked`, partner.`booked_before`, partner.`created_at`, partner.`hide_charge`, partner.`url`, partner.`shipper_location`, partner.`go_send_active`, partner.`grab_active`, partner.`is_dine_in`, partner.`is_preorder`, partner.`open_bill`, partner.is_temporary_close, partner.is_foodcourt, partner.is_reservation, partner.cash_active, CASE WHEN partner.fc_parent_id = 0 THEN partner.is_centralized WHEN partner.fc_parent_id != 0 THEN parent.is_centralized ELSE 0 END AS is_centralized, day.today, CASE WHEN oh.id IS NULL THEN partner.jam_buka WHEN day.today = 'monday' THEN oh.monday_open WHEN day.today = 'tuesday' THEN oh.tuesday_open WHEN day.today = 'wednesday' THEN oh.wednesday_open WHEN day.today = 'thursday' THEN oh.thursday_open WHEN day.today = 'friday' THEN oh.friday_open WHEN day.today = 'saturday' THEN oh.saturday_open WHEN day.today = 'sunday' THEN oh.sunday_open ELSE partner.jam_buka END jam_buka, CASE WHEN oh.id IS NULL THEN partner.jam_tutup WHEN day.today = 'monday' THEN oh.monday_closed WHEN day.today = 'tuesday' THEN oh.tuesday_closed WHEN day.today = 'wednesday' THEN oh.wednesday_closed WHEN day.today = 'thursday' THEN oh.thursday_closed WHEN day.today = 'friday' THEN oh.friday_closed WHEN day.today = 'saturday' THEN oh.saturday_closed WHEN day.today = 'sunday' THEN oh.sunday_closed ELSE partner.jam_buka END jam_tutup FROM `partner` LEFT JOIN partner parent ON parent.id = partner.fc_parent_id LEFT JOIN partner_opening_hours AS oh ON partner.id = oh.partner_id CROSS JOIN (SELECT '$today' as today) AS day WHERE partner.id = '$id'");
        if (mysqli_num_rows($q) > 0) {
            $resP = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $success =1;
            $status =200;
            $msg = "Success";
        } else {
            $success =0;
            $status =204;
            $msg = "Data Not Found";
        }
    } else {
        $success = 0;
        $status = 204;
        $msg = "Email atau password salah";
    }

}else{
    $success = 0;
    $status = 400;
    $msg = "Missing Require Field";
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "detail"=>$data,"token"=>$token, "full"=>$full, "partner"=>$resP]);
