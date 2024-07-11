<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
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

    $obj = json_decode(file_get_contents("php://input"));
    $data = array();
    if(
        isset($obj->partnerID)&&!empty($obj->partnerID)
    ){
        $partnerID = $obj->partnerID;
        $q = mysqli_query($db_conn, "SELECT employees.id,employees.nama AS name,employees.id_master,employees.id_partner,employees.role_id, partner.name as master_name, partner.address, r.id as role_id, r.name as role_name, r.is_owner,employees.nik, employees.gender, employees.email, employees.phone, employees.pin, partner.is_attendance,partner.is_reservation, partner.max_people_reservation, partner.max_days_reservation, partner.is_table_management, partner.is_foodcourt, parent.id AS parentID, partner.partner_type, parent.name AS parentName, parent.is_foodcourt AS parentFC, CASE WHEN partner.fc_parent_id !='0' THEN parent.is_centralized WHEN partner.fc_parent_id ='0' THEN partner.is_centralized ELSE 0 END AS is_centralized, CASE WHEN partner.fc_parent_id !='0' THEN parent.delivery_status_tracking WHEN partner.fc_parent_id ='0' THEN partner.delivery_status_tracking ELSE 0 END AS delivery_status_tracking FROM employees LEFT JOIN master ON master.id=employees.id_master JOIN partner ON employees.id_partner=partner.id JOIN roles r ON employees.role_id=r.id LEFT JOIN partner AS parent on partner.fc_parent_id = parent.id WHERE employees.id_partner='$token->id_partner'");

        if (mysqli_num_rows($q) > 0) {
            $i=0;
            $success = 1;
            $status = 200;
            $full=0;
            $p = mysqli_query($db_conn, "SELECT name, is_bluetooth, is_pos, is_helper FROM `partner` WHERE id='$partnerID'");
            $fetchedP = mysqli_fetch_assoc($p);
            while($fetched = mysqli_fetch_assoc($q)){
                $data[$i]['partner_name']= $fetchedP['name'];
                $data[$i]['is_bluetooth']= $fetchedP['is_bluetooth'];
                $data[$i]['is_pos']= $fetchedP['is_pos'];
                $data[$i]['is_helper']= $fetchedP['is_helper'];
                $data[$i]['employee_id'] = $fetched['id'];
                $data[$i]['nik']= $fetched['nik'];
                $data[$i]['gender']= $fetched['gender'];
                $data[$i]['name']= $fetched['name'];
                $data[$i]['Ename']= $fetched['name'];
                $data[$i]['email']= $fetched['email'];
                $data[$i]['is_centralized']= $fetched['is_centralized'];
                $data[$i]['is_reservation']= $fetched['is_reservation'];
                $data[$i]['delivery_status_tracking']= $fetched['delivery_status_tracking'];
                $data[$i]['is_attendance']= $fetched['is_attendance'];
                $data[$i]['is_table_management']= $fetched['is_table_management'];
                $data[$i]['phone']= $fetched['phone'];
                $data[$i]['master_id'] = $fetched['id_master'];
                $data[$i]['is_foodcourt'] = $fetched['is_foodcourt'];
                $data[$i]['master_name']= $fetched['master_name'];
                $data[$i]['partner_address']= $fetched['address'];
                $data[$i]['password']= $fetched['pin'];
                $data[$i]['partner_id']= $fetched['id_partner'];
                $data[$i]['role_id'] = $fetched['role_id'];
                $data[$i]['parentID'] = $fetched['parentID'];
                $data[$i]['parentName'] = $fetched['parentName'];
                $data[$i]['parentFC'] = $fetched['parentFC'];
                $data[$i]['is_owner']=$fetched['is_owner'];
                $role_id[$i]['role_id'] = $fetched['role_id'];
                // if($fetched['name']==""||$fetched['partner_type']==0){
                //     $full=0;
                // }else{
                //     $full=1;
                // }
                $x=$fetched['role_id'];
                $roleQ = mysqli_query($db_conn, "SELECT `name`, `is_owner`, `mobile`, `m1`, `m2`, `m3`, `m4`, `m5`, `m6`, `m7`, `m8`, `m9`, `m10`, `m11`, `m12`, `m13`, `m14`, `m15`, `m16`, `m17`, `m18`, `m19`, `max_discount`, `m20`, `m21`, `m22` FROM `roles` WHERE id='$x'");
                $fetchedRole = mysqli_fetch_assoc($roleQ);
                $data[$i]['roles']=$fetchedRole;
                $i++;
            }

            $msg = "Logged";
            http_response_code(200);
        } else {
            $success = 0;
            $status = 204;
            http_response_code(200);
            $msg = "Gagal ambil data";
        }

    }else{
        $success = 0;
        $status = 400;
        http_response_code(200);
        $msg = "Missing Require Field";
    }
}
    echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "detail"=>$data]);
