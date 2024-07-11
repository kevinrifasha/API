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
$headers = apache_request_headers();
$tokenizer = new Token();
$token = '';
$arr = array();

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
    $q = mysqli_query($db_conn, "SELECT `id`, `code`, `title`, `description`, `type_id`, `is_percent`, `discount`, `category`, `enabled`, `valid_from`, `valid_until`, `total_usage`, `prerequisite`, `master_id`, `partner_id` AS partnerID, partner_id, `img` from voucher WHERE valid_until>=NOW() AND enabled=1 AND deleted_at IS NULL");
    $q1 = mysqli_query($db_conn, "SELECT voucherid from user_voucher_ownership  WHERE userid='$token->phone' AND transaksi_id IS NULL AND obtained='0'");
    $qRV = mysqli_query($db_conn, "SELECT redeemable_voucher.`id`, redeemable_voucher.`code`, redeemable_voucher.`title`, redeemable_voucher.`description`, redeemable_voucher.`type_id`, redeemable_voucher.`is_percent`, redeemable_voucher.`discount`, redeemable_voucher.`category`, redeemable_voucher.`enabled`, redeemable_voucher.`valid_from`, redeemable_voucher.`valid_until`, redeemable_voucher.`total_usage`, redeemable_voucher.`prerequisite`, redeemable_voucher.`master_id`, redeemable_voucher.`partner_id` AS partnerID, redeemable_voucher.partner_id, redeemable_voucher.`img` from user_voucher_ownership JOIN redeemable_voucher ON redeemable_voucher.code=user_voucher_ownership.voucherid AND redeemable_voucher.valid_until>=NOW() AND redeemable_voucher.enabled=1 AND redeemable_voucher.deleted_at IS NULL WHERE user_voucher_ownership.userid='$token->phone' AND user_voucher_ownership.transaksi_id IS NULL AND user_voucher_ownership.obtained='1'");

    if (mysqli_num_rows($q) > 0 || mysqli_num_rows($q1) > 0 || mysqli_num_rows($q2) > 0) {
        $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        foreach($res1 as $r){
            $code = $r['voucherid'];
            $q2 = mysqli_query($db_conn, "SELECT `code`, `title`, `description`, `point`, `type_id`, `is_percent`, `discount`, `category`, `enabled`, `valid_from`, `valid_until`, `total_usage`, `prerequisite`, `partner_id`, `master_id`, `partnerID`, `img` FROM `membership_voucher` WHERE `deleted_at` IS NULL AND `code`='$code'");
            $res2 = mysqli_fetch_all($q2, MYSQLI_ASSOC);
            $res2[0]['type']='Redeemable';
            $find = $res2[0]['id_master'];
            $q3 = mysqli_query($db_conn, "SELECT name FROM `master` WHERE id='$find'");
            $res3 = mysqli_fetch_all($q3, MYSQLI_ASSOC);
            $res2[0]['owner_name']=$res3[0]['name'];
            array_push($arr, $res2[0]);
        }
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        foreach($res as $r){
            $vCode = $r['code'];
            $used = 0;
            $qU = mysqli_query($db_conn, "SELECT COUNT(id) as used FROM `transaksi` WHERE `id_voucher` = '$vCode' AND transaksi.deleted_at IS NULL");
            if (mysqli_num_rows($qU) > 0){
                $resU = mysqli_fetch_all($qU, MYSQLI_ASSOC);
                $used = $resU[0]['used'];
            }
            $usedU = 0;
            $qUU = mysqli_query($db_conn, "SELECT COUNT(id) as used FROM `transaksi` WHERE `id_voucher` = '$vCode' AND phone='$token->phone' AND transaksi.deleted_at IS NULL");
            if (mysqli_num_rows($qUU) > 0){
                $resUU = mysqli_fetch_all($qUU, MYSQLI_ASSOC);
                $usedU = $resUU[0]['used'];
            }
            if($r['total_usage']>$used){
                $prerequisite = json_decode($r['prerequisite']);
                if(isset($prerequisite->menu_id) ){
                    $find = $prerequisite->menu_id;
                    $qMenu = mysqli_query($db_conn, "SELECT nama FROM `menu` WHERE id='$find'");
                    $resMenu = mysqli_fetch_all($qMenu, MYSQLI_ASSOC);
                    $r['menu_name'][0]=$resMenu[0]['nama'];
                }else{
                    $r['menu_name']=array();
                }

                if(isset($prerequisite->category_id) ){
                    $a = explode(",",$prerequisite->category_id);
                    $arrC = array();
                    foreach ($a as $value) {
                        $qCategories = mysqli_query($db_conn, "SELECT name FROM `categories` WHERE id='$value'");
                        if (mysqli_num_rows($qCategories) > 0) {
                            $resMenu = mysqli_fetch_all($qCategories, MYSQLI_ASSOC);
                            array_push($arrC, $resMenu[0]['name']);
                        }
                    }
                    $r['categories_name']=$arrC;
                }else{
                    $r['categories_name']=$arrC;
                }
                if(isset($prerequisite->order) ){
                    if((int) $prerequisite->order > $usedU){
                        $r['type']='Voucher';
                        if(!is_null($r['master_id']) && $r['master_id']!='0'){
                            $find = $r['master_id'];
                            $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id_master='$find'");
                            $res3 = mysqli_fetch_all($q3, MYSQLI_ASSOC);
                            $no = 0;
                            $r['owner_name'] ="";
                            foreach ($res3 as $value) {
                                if($no>0){
                                    $r['owner_name'] .= ", ";
                                }
                                $r['owner_name'] .=$value['name'];
                                $no+=1;
                            }
                        }else{
                            $find = $r['partner_id'];
                            $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id='$find'");
                            $res3 = mysqli_fetch_all($q3, MYSQLI_ASSOC);
                            $r['owner_name']=$res3[0]['name'];

                        }
                        array_push($arr, $r);
                    }
                }else{
                    $r['type']='Voucher';
                    if(!is_null($r['master_id']) && $r['master_id']!='0'){
                        $find = $r['master_id'];
                        $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id_master='$find'");
                        $res3 = mysqli_fetch_all($q3, MYSQLI_ASSOC);
                        $no = 0;
                        $r['owner_name'] ="";
                        foreach ($res3 as $value) {
                            if($no>0){
                                $r['owner_name'] .= ", ";
                            }
                            $r['owner_name'] .=$value['name'];
                            $no+=1;
                        }
                    }else{
                        $find = $r['partner_id'];
                        $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id='$find'");
                        $res3 = mysqli_fetch_all($q3, MYSQLI_ASSOC);
                        $r['owner_name']=$res3[0]['name'];

                    }
                    array_push($arr, $r);
                }
            }
        }
        $res = mysqli_fetch_all($qRV, MYSQLI_ASSOC);
        foreach($res as $r){
            $vCode = $r['code'];
            $used = 0;
            $qU = mysqli_query($db_conn, "SELECT COUNT(id) as used FROM `transaksi` WHERE `id_voucher` = '$vCode' AND transaksi.deleted_at IS NULL");
            if (mysqli_num_rows($qU) > 0){
                $resU = mysqli_fetch_all($qU, MYSQLI_ASSOC);
                $used = $resU[0]['used'];
            }
            $usedU = 0;
            $qUU = mysqli_query($db_conn, "SELECT COUNT(id) as used FROM `transaksi` WHERE `id_voucher` = '$vCode' AND phone='$token->phone' AND transaksi.deleted_at IS NULL");
            if (mysqli_num_rows($qUU) > 0){
                $resUU = mysqli_fetch_all($qUU, MYSQLI_ASSOC);
                $usedU = $resUU[0]['used'];
            }
            if($r['total_usage']>$used){
                $prerequisite = json_decode($r['prerequisite']);
                if(isset($prerequisite->menu_id) ){
                    $find = $prerequisite->menu_id;
                    $qMenu = mysqli_query($db_conn, "SELECT nama FROM `menu` WHERE id='$find'");
                    $resMenu = mysqli_fetch_all($qMenu, MYSQLI_ASSOC);
                    $r['menu_name'][0]=$resMenu[0]['nama'];
                }else{
                    $r['menu_name']=array();
                }

                if(isset($prerequisite->category_id) ){
                    $a = explode(",",$prerequisite->category_id);
                    $arrC = array();
                    foreach ($a as $value) {
                        $qCategories = mysqli_query($db_conn, "SELECT name FROM `categories` WHERE id='$value'");
                        if (mysqli_num_rows($qCategories) > 0) {
                            $resMenu = mysqli_fetch_all($qCategories, MYSQLI_ASSOC);
                            array_push($arrC, $resMenu[0]['name']);
                        }
                    }
                    $r['categories_name']=$arrC;
                }else{
                    $r['categories_name']=$arrC;
                }
                if(isset($prerequisite->order) ){
                    if((int) $prerequisite->order > $usedU){
                        $r['type']='Redeem Code';
                        if(!is_null($r['master_id']) && $r['master_id']!='0'){
                            $find = $r['master_id'];
                            $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id_master='$find'");
                            $res3 = mysqli_fetch_all($q3, MYSQLI_ASSOC);
                            $no = 0;
                            $r['owner_name'] ="";
                            foreach ($res3 as $value) {
                                if($no>0){
                                    $r['owner_name'] .= ", ";
                                }
                                $r['owner_name'] .=$value['name'];
                                $no+=1;
                            }
                        }else{
                            $find = $r['partner_id'];
                            $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id='$find'");
                            $res3 = mysqli_fetch_all($q3, MYSQLI_ASSOC);
                            $r['owner_name']=$res3[0]['name'];

                        }
                        array_push($arr, $r);
                    }
                }else{
                    $r['type']='Redeem Code';
                    if(!is_null($r['master_id']) && $r['master_id']!='0'){
                        $find = $r['master_id'];
                        $q3 = mysqli_query($db_conn, "SELECT name  FROM `partner` WHERE id_master='$find'");
                        $res3 = mysqli_fetch_all($q3, MYSQLI_ASSOC);
                        $no = 0;
                        $r['owner_name'] ="";
                        foreach ($res3 as $value) {
                            if($no>0){
                                $r['owner_name'] .= ", ";
                            }
                            $r['owner_name'] .=$value['name'];
                            $no+=1;
                        }
                    }else{
                        $find = $r['partner_id'];
                        $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id='$find'");
                        $res3 = mysqli_fetch_all($q3, MYSQLI_ASSOC);
                        $r['owner_name']=$res3[0]['name'];

                    }
                    array_push($arr, $r);
                }
            }
        }
        $success =1;
        $status =200;
        $msg = "Success";
    } else {
        $success =0;
        // $status =204;
        $msg = "Data Not Found";
    }
}

echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "vouchers"=>$arr]);
?>
