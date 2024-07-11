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
    $q = mysqli_query($db_conn, "SELECT v.`id`, v.`code`, v.`title`, v.`description`, v.`type_id`, v.`is_percent`, v.`discount`, v.`category`, v.`enabled`, v.`valid_from`, v.`valid_until`, v.`total_usage`, v.`prerequisite`, v.`master_id`, v.`partner_id` AS partnerID, v.`img`, m.organization FROM voucher as v LEFT JOIN `master` as m ON m.id = v.master_id WHERE valid_until>=NOW() AND enabled=1 AND v.deleted_at IS NULL AND show_in_sf=1 AND m.organization='Natta'");
    $q1 = mysqli_query($db_conn, "SELECT voucherid FROM user_voucher_ownership AS uvw LEFT JOIN users AS u ON u.phone = uvw.userid WHERE userid='$token->phone' AND transaksi_id IS NULL AND obtained='0' AND organization='Natta'");
    $qRV = mysqli_query($db_conn, "SELECT redeemable_voucher.`id`, redeemable_voucher.`code`, redeemable_voucher.`title`, redeemable_voucher.`description`, redeemable_voucher.`type_id`, redeemable_voucher.`is_percent`, redeemable_voucher.`discount`, redeemable_voucher.`category`, redeemable_voucher.`enabled`, redeemable_voucher.`valid_from`, redeemable_voucher.`valid_until`, redeemable_voucher.`total_usage`, redeemable_voucher.`prerequisite`, redeemable_voucher.`master_id`, redeemable_voucher.`partner_id` AS partnerID, redeemable_voucher.partner_id, redeemable_voucher.`img` 
        FROM user_voucher_ownership 
        	JOIN redeemable_voucher 
            	ON redeemable_voucher.code=user_voucher_ownership.voucherid 
                AND redeemable_voucher.valid_until>=NOW() 
                AND redeemable_voucher.enabled=1 
                AND redeemable_voucher.deleted_at IS NULL
            LEFT JOIN master
            	ON master.id=redeemable_voucher.master_id
        WHERE user_voucher_ownership.transaksi_id IS NULL
            AND user_voucher_ownership.userid='$token->phone'
        	AND user_voucher_ownership.obtained='1'
        	AND master.organization='Natta'");

    if (mysqli_num_rows($q) > 0 || mysqli_num_rows($q1) > 0 || mysqli_num_rows($q2) > 0) {
        $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
        foreach($res1 as $r){
            $code = $r['voucherid'];
            $q2 = mysqli_query($db_conn, "SELECT mv.`code`, mv.`title`, mv.`description`, mv.`point`, mv.`type_id`, mv.`is_percent`, mv.`discount`, mv.`category`, mv.`enabled`, mv.`valid_from`, mv.`valid_until`, mv.`total_usage`, mv.`prerequisite`, mv.`partner_id`, mv.`master_id`, mv.`partnerID`, mv.`img`, m.`name` as owner_name FROM `membership_voucher` as mv LEFT JOIN `master` as m ON mv.master_id = m.id WHERE `deleted_at` IS NULL AND `code`='$code' AND m.`organization`='Natta'");
            $res2 = mysqli_fetch_all($q2, MYSQLI_ASSOC);
            $res2[0]['type']='Redeemable';
            array_push($arr, $res2[0]);
        }
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        foreach($res as $r){
            $vCode = $r['code'];
            $used = 0;
            $qU = mysqli_query($db_conn, "SELECT COUNT(id) as used FROM `transaksi` WHERE `id_voucher` = '$vCode' AND transaksi.deleted_at IS NULL AND transaksi.organization='Natta'");
            if (mysqli_num_rows($qU) > 0){
                $resU = mysqli_fetch_all($qU, MYSQLI_ASSOC);
                $used = $resU[0]['used'];
            }
            $usedU = 0;
            $qUU = mysqli_query($db_conn, "SELECT COUNT(id) as used FROM `transaksi` WHERE `id_voucher` = '$vCode' AND phone='$token->phone' AND transaksi.deleted_at IS NULL AND transaksi.organization='Natta'");
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
                            
                            // if(mysqli_num_rows($qCategories)>0){
                                // array_push($arrC, $qCategories[0]['name']);
                            // }
                        }
                    }
                    $r['categories_name']=$arrC;
                }else{
                    // $r['categories_name']=$arrC;
                    $r['categories_name']=[];
                }
                if(isset($prerequisite->order) ){
                    if((int) $prerequisite->order > $usedU){
                        $r['type']='Voucher';
                        if(!is_null($r['master_id']) && $r['master_id']!='0'){
                            $find = $r['master_id'];
                            $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id_master='$find' AND organization='Natta'");
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
                            $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id='$find' AND organization='Natta'");
                            $res3 = mysqli_fetch_all($q3, MYSQLI_ASSOC);
                            $r['owner_name']=$res3[0]['name'];

                        }
                        array_push($arr, $r);
                    }
                }else{
                    $r['type']='Voucher';
                    if(!is_null($r['master_id']) && $r['master_id']!='0'){
                        $find = $r['master_id'];
                        $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id_master='$find' AND organization='Natta'");
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
                        $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id='$find' AND organization='Natta'");
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
            $qU = mysqli_query($db_conn, "SELECT COUNT(id) as used FROM `transaksi` WHERE `id_voucher` = '$vCode' AND transaksi.deleted_at IS NULL AND transaksi.organization='Natta'");
            if (mysqli_num_rows($qU) > 0){
                $resU = mysqli_fetch_all($qU, MYSQLI_ASSOC);
                $used = $resU[0]['used'];
            }
            $usedU = 0;
            $qUU = mysqli_query($db_conn, "SELECT COUNT(id) as used FROM `transaksi` WHERE `id_voucher` = '$vCode' AND phone='$token->phone' AND transaksi.deleted_at IS NULL AND transaksi.organization='Natta'");
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
                            $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id_master='$find' AND organization='Natta'");
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
                            $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id='$find' AND organization='Natta'");
                            $res3 = mysqli_fetch_all($q3, MYSQLI_ASSOC);
                            $r['owner_name']=$res3[0]['name'];

                        }
                        array_push($arr, $r);
                    }
                }else{
                    $r['type']='Redeem Code';
                    if(!is_null($r['master_id']) && $r['master_id']!='0'){
                        $find = $r['master_id'];
                        $q3 = mysqli_query($db_conn, "SELECT name  FROM `partner` WHERE id_master='$find' AND organization='Natta'");
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
                        $q3 = mysqli_query($db_conn, "SELECT name FROM `partner` WHERE id='$find' AND organization='Natta'");
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
