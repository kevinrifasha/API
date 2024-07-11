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
    $password = $obj->password;
    $newPassword = md5($password);

    $q = mysqli_query($db_conn, "SELECT employees.id,employees.nama AS name,employees.id_master,employees.id_partner,employees.role_id, partner.name as master_name, partner.is_helper, partner.address, r.id as role_id, r.name as role_name, r.is_owner,employees.nik, employees.gender, employees.email, employees.phone, partner.is_attendance,partner.is_reservation, partner.max_people_reservation, partner.max_days_reservation, partner.is_table_management, partner.is_foodcourt, parent.id AS parentID, partner.partner_type, parent.name AS parentName, parent.is_foodcourt AS parentFC, CASE WHEN partner.fc_parent_id !='0' THEN parent.is_centralized WHEN partner.fc_parent_id ='0' THEN partner.is_centralized ELSE 0 END AS is_centralized, CASE WHEN partner.fc_parent_id !='0' THEN parent.delivery_status_tracking WHEN partner.fc_parent_id ='0' THEN partner.delivery_status_tracking ELSE 0 END AS delivery_status_tracking FROM employees LEFT JOIN master ON master.id=employees.id_master JOIN partner ON employees.id_partner=partner.id JOIN roles r ON employees.role_id=r.id LEFT JOIN partner AS parent on partner.fc_parent_id = parent.id WHERE employees.email='$email' AND employees.pin='$newPassword'");
    if (mysqli_num_rows($q) > 0) {
        $fetched = mysqli_fetch_assoc($q);
        $partnerID = $fetched['id_partner'];

        $jsonToken = json_encode(['id'=>$fetched['id'], 'role_id'=>$fetched['role_id'], 'id_master'=>$fetched['id_master'],'id_partner'=>$fetched['id_partner'], 'created_at'=>$today, 'expired'=>5000]);
        $token = $tokenizer->stringEncryption('encrypt', $jsonToken);
        $success = 1;
        $status = 200;
        $full=0;
        $p = mysqli_query($db_conn, "SELECT name, is_bluetooth, is_pos FROM `partner` WHERE id='$partnerID'");
        $fetchedP = mysqli_fetch_assoc($p);
        $data['employee_id'] = $fetched['id'];
        $data['nik']= $fetched['nik'];
        $data['gender']= $fetched['gender'];
        $data['name']= $fetched['name'];
        $data['Ename']= $fetched['name'];
        $data['email']= $fetched['email'];
        $data['is_centralized']= $fetched['is_centralized'];
        $data['is_reservation']= $fetched['is_reservation'];
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
        $data['parentName'] = $fetched['parentName'];
        $data['parentFC'] = $fetched['parentFC'];
        $role_id = $fetched['role_id'];
        $data['is_owner']=$fetched['is_owner'];
        $data['is_pos']= $fetchedP['is_pos'];
        $data['partner_type'] = $fetched['partner_type'];
        if($fetched['name']==""||$fetched['partner_type']==0){
            $full=0;
        }else{
            $full=1;
        }
        $roleQ = mysqli_query($db_conn, "SELECT `name`, `is_owner`, `mobile`, `m1`, `m2`, `m3`, `m4`, `m5`, `m6`, `m7`, `m8`, `m9`, `m10`, `m11`, `m12`, `m13`, `m14`, `m15`, `m16`, `m17`, `m18`, `m19`, `max_discount`, `m20`, `m21`, `m22` FROM `roles` WHERE id='$role_id'");
        $fetchedRole = mysqli_fetch_assoc($roleQ);
        $data['roles']=$fetchedRole;
        $msg = "Logged";
        http_response_code(200);
    } else {
        $success = 0;
        $status = 204;
        http_response_code(200);
        $msg = "Email atau password salah";
    }

}else{
    $success = 0;
    $status = 400;
    http_response_code(200);
    $msg = "Missing Require Field";
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "detail"=>$data,"token"=>$token, "full"=>$full]);
