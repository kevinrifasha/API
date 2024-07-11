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

function getMasterID($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT p.id_master FROM transaksi t JOIN partner p ON p.id=t.id_partner WHERE t.id LIKE '$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return (int) $res[0]['id_master'];
    }else{
        return 0;
    }
}

function getPhone($id, $db_conn){
    $q = mysqli_query($db_conn,"SELECT phone  FROM `transaksi` WHERE `id` LIKE '$id'");
    if (mysqli_num_rows($q) > 0) {
        $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
        return $res[0]['phone'];
    }else{
        return 0;
    }
}

function checkSM($id, $phone, $db_conn){
    $q = mysqli_query($db_conn,"SELECT max_disc FROM `special_member` WHERE id_master='$id' AND phone='$phone' AND deleted_at IS NULL");
    if (mysqli_num_rows($q) > 0) {
        return true;
    }else{
        return false;
    }
}

$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));

if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    // if(isset($_GET['page'])&&!empty($_GET['page']) && isset($_GET['load'])&&!empty($_GET['load'])){
        $groupID = $_GET['id'];
        $page = ($_GET['page'] ?? 1);
        $load = ($_GET['load'] ?? 10);
        $finish = $load * $page;
        $start = $finish - $load;

        $q = mysqli_query($db_conn, "SELECT t.id, t.jam, t.phone, t.no_meja, t.status, t.total, t.id_voucher, t.id_voucher_redeemable, t.tipe_bayar, t.promo, t.diskon_spesial, t.employee_discount, t.point, t.queue, t.takeaway, t.notes, t.tax, t.service, t.charge_ur, pm.nama as payment_method, case when u.name is null then t.customer_name or t.is_helper=1 or t.is_pos=1 else u.name end AS uname, t.program_discount, t.group_id AS table_group_id, t.employee_discount_percent, t.rounding FROM transaksi t JOIN payment_method pm ON t.tipe_bayar = pm.id LEFT JOIN users u ON u.phone=t.phone WHERE t.id_partner='$token->id_partner' AND t.deleted_at IS NULL AND (t.status=5 or t.status=6) AND t.group_id = '$groupID' ORDER BY t.jam DESC");
        // LIMIT $start,$load
        if (mysqli_num_rows($q) > 0) {
            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            $i = 0;
            foreach ($res as $value) {
                $transactionID = $value['id'];
                $qStatus = mysqli_query($db_conn, "SELECT t.status, t.partner_note, t.takeaway, pm.nama AS pmName, t.total, t.charge_ur, t.service, t.tax, t.diskon_spesial, t.employee_discount, t.promo, t.program_discount FROM transaksi t JOIN payment_method pm ON t.tipe_bayar = pm.id WHERE t.id='$transactionID'");
                while($row = mysqli_fetch_assoc($qStatus)){
                    $trxStatus = $row['status'];
                    if(!empty($row['partner_note'])){
                        $trxPNote = $row['partner_note'];
                    }
                    $trxPMName = $row['pmName'];
                    $total = $row['total'];
                    $charge_ur = $row['charge_ur'];
                    $service = $row['service'];
                    $tax = $row['tax'];
                    $diskon_spesial = $row['diskon_spesial'];
                    $employee_discount = $row['employee_discount'];
                    $promo = $row['promo'];
                    $program_discount = $row['program_discount'];
                }
                
                $q1 = mysqli_query($db_conn, "SELECT DISTINCT dt.id,dt.id_transaksi,dt.surcharge_id, dt.id_menu, dt.harga_satuan, dt.qty, dt.notes, dt.harga, dt.status, m.nama, dt.variant, dt.bundle_id, c.name AS cName, c.id AS category_id, qty_delivered, bpd.id as bundle_detail_id,CASE WHEN dt.qty-dt.qty_delivered>0 THEN 0 ELSE 1 END AS delivery_done, ss.nama AS serverName, dt.server_id AS serverID, dt.is_consignment,bp.name as bundle_name, s.name AS surchargeName, dt.bundle_qty, co.bundle_length FROM detail_transaksi dt JOIN menu m ON dt.id_menu=m.id JOIN categories c ON m.id_category=c.id JOIN employees ss ON dt.server_id=ss.id LEFT JOIN surcharges s ON dt.surcharge_id = s.id LEFT JOIN bundle_packages bp ON bp.id = dt.bundle_id LEFT JOIN bundle_package_details bpd ON bpd.bundle_id = dt.bundle_id AND dt.id_menu=bpd.menu_id AND bpd.deleted_at IS NULL LEFT JOIN ( SELECT bpc.id, COUNT(bpdc.id) as bundle_length FROM bundle_packages bpc LEFT JOIN bundle_package_details bpdc ON bpc.id = bpdc.bundle_id WHERE bpdc.deleted_at IS NULL and bpc.deleted_at IS NULL GROUP BY bpc.id) co ON co.id = dt.bundle_id WHERE dt.id_transaksi='$transactionID' AND dt.deleted_at IS NULL GROUP BY dt.id");
                
                // $q1 = mysqli_query($db_conn, "SELECT dt.id, dt.id_menu, dt.harga_satuan, dt.qty, dt.notes, dt.harga, dt.status, m.nama, dt.variant, c.name AS cName, c.id AS category_id, dt.is_consignment FROM detail_transaksi dt JOIN menu m ON dt.id_menu=m.id JOIN categories c ON m.id_category=c.id WHERE dt.id_transaksi='$transactionID' AND dt.deleted_at IS NULL");

                $q2 = mysqli_query($db_conn, "SELECT transaction_id, type, created_at, IF(type>0, 'receipt', 'checker') AS str_type FROM `transaction_prints` WHERE transaction_id='$transactionID'");

                if (mysqli_num_rows($q) > 0) {
                    $res[$i]['printed'] = mysqli_fetch_all($q2, MYSQLI_ASSOC);;
                }

                $data = array();
                if (mysqli_num_rows($q1) > 0) {

                    $phone = getPhone($transactionID, $db_conn);
                    $masterID = getMasterID($transactionID, $db_conn);
                    $is_special_member = checkSM($masterID, $phone, $db_conn);
                    $bundle_array = [];
                    $bundle_id = "";
                    $bundle_length = "";
                    $k = 0;
                    $j = 0;
                    $res1 = mysqli_fetch_all($q1, MYSQLI_ASSOC);
                    foreach ($res1 as $value) {
                        $temp = $value;
                        if($value['variant'] != null) {
                            $variant = $value['variant'];
                            $variant =  substr($variant,11);
                            $variant = substr_replace($variant ,"",-1);
                            $variant = str_replace("'",'"',$variant);
                            $temp['variant'] = json_decode($variant);
                        }else{
                            $temp['variant'] = [];
                        }
                        $bundle_length = $res1[$k]['bundle_length'];
                
                        if($value['bundle_id'] == 0){
                            $temp['is_bundle'] = "0";
                            $temp['bundles'] = [];
                            $bundle_id = "";
                            array_push($data, $temp);
                        }else{
                            if($bundle_id == ""){
                                $bundle_id = $res1[$k]['bundle_id'];
                                $j = 0;
                            }
                            $temp['is_bundle'] = "1";
                            if($bundle_id == $res1[$k]['bundle_id'] && $k + 1 <= count($res1))
                            {
                                if($bundle_length != null && ($j + 1) % $bundle_length != 0){
                                    array_push($bundle_array, $temp);
                                    $j++;
                                }else{
                                    array_push($bundle_array, $temp);
                                    $j++;
                                    $tempForBundle=array();
                                    $tempForBundle=$temp;
                                    $tempForBundle["variant"]=[];
                                    $tempForBundle["nama"]=$bundle_array[0]["bundle_name"];
                                    $tempForBundle["id"]=$bundle_array[0]["bundle_id"];
                                    $tempForBundle["id_menu"]="0";
                                    $tempForBundle["harga_satuan"]="";
                                    $tempForBundle["bundles"]=$bundle_array;
                                    $tempForBundle["bundle_id"]=$bundle_array[0]["bundle_id"];
                                    $tempForBundle["is_bundle"]=$bundle_array[0]["is_bundle"];
                                    $tempForBundle["harga_satuan"]=$bundle_array[0]["harga_satuan"];
                                    $tempForBundle["bundle_length"]=$bundle_array[0]["bundle_length"];
                                    $tempForBundle["harga"]=$bundle_array[0]["harga"];
                                    $tempForBundle["bundle_qty"]=$bundle_array[0]["bundle_qty"];
                                    $tempForBundle["cName"]="Paket Bundle";
                                    $tempForBundle["category_id"]="0";
                                    $tempForBundle["bundle_detail_id"]="0";
                                    array_push($data,$tempForBundle);
                                    $bundle_array = [];
                                    $j = 0;
                                }
                            }else{
                                if ($j == 0 && $bundle_length == 1){
                                    $bundle_array = [];
                                    array_push($bundle_array, $temp);
                                    $tempForBundle=array();
                                    $tempForBundle=$temp;
                                    $tempForBundle["variant"]=[];
                                     $tempForBundle["nama"]=$bundle_array[0]["bundle_name"];
                                    $tempForBundle["id"]=$bundle_array[0]["bundle_id"];
                                    $tempForBundle["id_menu"]="0";
                                    $tempForBundle["harga_satuan"]="";
                                    $tempForBundle["bundles"]=$bundle_array;
                                    $tempForBundle["bundle_id"]=$bundle_array[0]["bundle_id"];
                                    $tempForBundle["is_bundle"]=$bundle_array[0]["is_bundle"];
                                    $tempForBundle["harga_satuan"]=$bundle_array[0]["harga_satuan"];
                                    $tempForBundle["bundle_length"]=$bundle_array[0]["bundle_length"];
                                    $tempForBundle["harga"]=$bundle_array[0]["harga"];
                                    $tempForBundle["bundle_qty"]=$bundle_array[0]["bundle_qty"];
                                    $tempForBundle["cName"]="Paket Bundle";
                                    $tempForBundle["category_id"]="0";
                                    $tempForBundle["bundle_detail_id"]="0";
                                    array_push($data,$tempForBundle);
                                    $bundle_array = [];
                                    $j = 0;
                                }else if($bundle_array != []){
                                    $tempForBundle=array();
                                    $tempForBundle=$temp;
                                    $tempForBundle["variant"]=[];
                                     $tempForBundle["nama"]=$bundle_array[0]["bundle_name"];
                                    $tempForBundle["id"]=$bundle_array[0]["bundle_id"];
                                    $tempForBundle["id_menu"]="0";
                                    $tempForBundle["harga_satuan"]="";
                                    $tempForBundle["bundles"]=$bundle_array;
                                    $tempForBundle["bundle_id"]=$bundle_array[0]["bundle_id"];
                                    $tempForBundle["is_bundle"]=$bundle_array[0]["is_bundle"];
                                    $tempForBundle["harga_satuan"]=$bundle_array[0]["harga_satuan"];
                                    $tempForBundle["bundle_length"]=$bundle_array[0]["bundle_length"];
                                    $tempForBundle["harga"]=$bundle_array[0]["harga"];
                                    $tempForBundle["bundle_qty"]=$bundle_array[0]["bundle_qty"];
                                    $tempForBundle["cName"]="Paket Bundle";
                                    $tempForBundle["category_id"]="0";
                                    $tempForBundle["bundle_detail_id"]="0";
                                    array_push($data,$tempForBundle);
                                }
                                $bundle_array = [];
                                $j = 0;
                                $bundle_id = $res1[$k]['bundle_id'];
                                array_push($bundle_array, $temp);
                                $j++;
                            }
                        }
                        
                        $k++;
                    }
                }
                
                $data = array_values($data);
                
                $res[$i]['detail']=$data;
                $res[$i]['sales']=0;
                $res[$i]['gross_profit']=0;
                $i+=1;
            }
            
            $success =1;
            $status =200;
            $msg = "Success";
        } else {
            $success =0;
            $status =204;
            $msg = "Data Not Found";
        }
    // }else{
    //     $success =0;
    //     $status =204;
    //     $msg = "400 Missing Required Field";
    // }
}
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "orders"=>$res, "groupID"=>$groupID, "test"=>$res1]);
?>