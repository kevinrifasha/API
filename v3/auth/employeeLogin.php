<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require '../../db_connection.php';
require_once("./../employeeModels/employeeManager.php");
require_once("./../partnerModels/partnerManager.php");
require_once("./../tokenModels/tokenManager.php");

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

$db = connectBase();
$tokenizer = new TokenManager($db);

$json = file_get_contents('php://input');
$obj = json_decode($json,true);
$today = DATE("Y-m-d H:i:s");
$success=0;
$email = $obj['email'];
$password = $obj['password'];
$res = array();

    if (!empty($email) || !empty($password)) {
        //validate email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $employeeManager = new EmployeeManager($db);
            $manager = new PartnerManager($db);
            $employee = $employeeManager->loginEmail($email,md5($password));

            if($employee==false){
                $success=0;
                $msg="failed";
            }else{
                if($employee->getRole_id()=='1'){
                    $res = $employee->getDetails();
                    $res['role']="owner";
                }else{
                    $res = $employee->getDetails();
                    $res['role']="not owner";
                }
                $partner = $manager->getPartnerDetails($res['id_partner']);
                if($partner!=false){
                    $res['partner']=$partner->getDetails();
                    // $masterID = $res['partner']['id_master'];
                    // $qMP = mysqli_query($db_conn, "SELECT point_pay, harga_point FROM master WHERE id='$masterID'");
                    // $fetchQMP = mysqli_fetch_all($qMP, MYSQLI_ASSOC);
                    // $res["partner"]["membership_point"] = $fetchQMP[0]["point_pay"];
                    // $res["partner"]["membership_point_multiplier"] = $fetchQMP[0]["harga_point"];
                    $parent = $manager->getPartnerDetails($res['partner']['fc_parent_id']);
                    if($parent!=false){
                        $res['partner']['parent']=$parent->getDetails();
                    }else{
                        $res['partner']['parent']=array();
                    }
                }else{
                    $res['partner']=array();
                    $res['partner']['parent']=array();
                }
                $success=1;
                $role_id = $res['role_id'];

                $sql = mysqli_query($db_conn, "SELECT `id`, `name`, `is_owner`,`is_owner_mode`, `web`, `w1`, `w2`, `w3`, `w4`, `w5`, `w6`, `w7`, `w8`, `w9`, `w10`, `w11`, `w12`, `w13`, `w14`, `w15`, w16, w17, w18, w19, w20, w21, w22, w23, w24, w25, w26, w27, w28, w29, w30, w31, w32, w33, w34, w35, w36, w37, w38, w39, w40, w41, w42, w43, w44,w45,w46,w47, is_order_notif, is_reservation_notif, is_withdrawal_notif FROM `roles` WHERE id='$role_id' AND deleted_at IS NULL");
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $res['roles']=$data[0];
                }
                $tkn = json_encode(['id'=>$res['id'], 'masterID'=>$res['id_master'], 'partnerID'=>$res['id_partner'], 'role'=>$res['role'], 'created_at'=>$today, 'expired'=>600]);
                $encryptT = $tokenizer->stringEncryption('encrypt', $tkn);
                $success=1;
                $msg="Success";
            }

        }else{
            $msg = 'Wrong Email Format' ;
            $encryptT="";
        }
    }else{
        $msg = 'field(s) must not be empty' ;
        $encryptT="";
    }
// }

echo json_encode(["msg"=>$msg, "detail"=>$res, "success"=>$success, "token"=>$encryptT]);
