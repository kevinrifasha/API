<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//import require file
require '../../../db_connection.php';
require_once('../../auth/Token.php');
require  __DIR__ . '/../../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
$dotenv->load();

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
$total = 0;
$promo = 0;
$table_group_id = 0;
$program_discount = 0;
$charge_ur = 0;
$service = 0;
$tax = 0;
$diskon_spesial = 0;
$employee_discount = 0;
$res = array();
$is_special_member = false;
$delivery_data = array();
$trxStatus = 0;
$trxPNote = "";
$trxPMName = "";
$address = array();
$printed = array();
$preOrder = array();

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

//get token
foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
$customer_email="";
$customer_name="";
$tenant_id = "";
$tokenValidate = $tokenizer->validate($token);
$token = json_decode($tokenizer->stringEncryption('decrypt',$token));
if(isset($tokenValidate['success']) && $tokenValidate['success']==0){
    $status = $tokenValidate['status'];
    $msg = $tokenValidate['msg'];
    $success = 0;
}else{
    if(isset($_GET['transactionID'])&&!empty($_GET['transactionID'])){

        $transactionID = $_GET['transactionID'];
        $query = "SELECT status, partner_note, takeaway, pmName, total, charge_ur, service, tax, diskon_spesial, employee_discount, promo, pre_order_id, program_discount, cancel_notes, canceled_by, employee_name, customer_email, customer_name FROM ( SELECT t.status, t.partner_note, t.takeaway, pm.nama AS pmName, t.total, t.charge_ur, t.service, t.tax, t.diskon_spesial, employee_discount,t.promo, t.pre_order_id, t.program_discount, tc.notes AS cancel_notes, tc.created_by AS canceled_by, e.nama AS employee_name, t.tenant_id, case when u.email is null then t.customer_email else u.email end AS customer_email, case when u.name is null then t.customer_name else u.name end AS customer_name  FROM transaksi t JOIN payment_method pm ON t.tipe_bayar = pm.id LEFT JOIN transaction_cancellation tc ON tc.transaction_id=t.id LEFT JOIN users u ON u.phone=t.phone LEFT JOIN employees e ON e.id=tc.created_by WHERE t.id='$transactionID' ) AS tmp ";
        $qStatus = mysqli_query($db_conn, $query);
        while($row = mysqli_fetch_assoc($qStatus)){
            $trxStatus = $row['status'];
            if(!empty($row['partner_note'])){
                $trxPNote = $row['partner_note'];
            }
            $customer_name = $row['customer_name'];
            $customer_email = $row['customer_email'];
            $tenant_id = $row['tenant_id'];
            $trxPMName = $row['pmName'];
            $table_group_id = $row['table_group_id'];
            $total = $row['total'];
            $charge_ur = $row['charge_ur'];
            $service = $row['service'];
            $tax = $row['tax'];
            $diskon_spesial = $row['diskon_spesial'];
            $employee_discount = $row['employee_discount'];
            $promo = $row['promo'];
            $program_discount = $row['program_discount'];
            if($row['takeaway']==='1'){
                $trxType = "takeaway";
            }else{
                $checkDelivery = mysqli_query($db_conn, "SELECT * FROM delivery WHERE transaksi_id='$transactionID'");
                if (mysqli_num_rows($checkDelivery) > 0) {
                    $trxType = "delivery";
                    $delivery_data = mysqli_fetch_all($checkDelivery, MYSQLI_ASSOC);
                    if($delivery_data[0]['user_address_id']!=='0'){
                        $uID = $delivery_data[0]['user_address_id'];
                        $qA = mysqli_query($db_conn, "SELECT recipient_name, recipient_phone, address, note, longitude, latitude, shipper_location FROM `addresses` WHERE id='$uID'");
                        if (mysqli_num_rows($qA) > 0) {
                            $resA = mysqli_fetch_all($qA, MYSQLI_ASSOC);
                            $address = $resA[0];
                        }
                    }
                }else{
                    $trxType = "dine-in";
                }

                if($row['pre_order_id']!='0'){
                    $poID = $row['pre_order_id'];
                    $checkPO = mysqli_query($db_conn, "SELECT name, order_from, order_to, delivery_from, delivery_to FROM `pre_order_schedules` WHERE id='$poID'");
                    if (mysqli_num_rows($checkPO) > 0) {
                        $preOrder = mysqli_fetch_all($checkPO, MYSQLI_ASSOC);
                    }
                }
            }
        }
        $q = mysqli_query($db_conn, "SELECT transaction_id, type, created_at, IF(type>0, 'receipt', 'checker') AS str_type FROM `transaction_prints` WHERE transaction_id='$transactionID'");
        if (mysqli_num_rows($q) > 0) {
            $printed = mysqli_fetch_all($q, MYSQLI_ASSOC);;
        }

        if(!empty($tenant_id)){
            $query = "SELECT id, id_menu, harga_satuan, qty, notes, harga, status, nama, variant, cName, category_id, qty_delivered, delivery_done, cancel_notes, canceled_by, employee_name FROM ( SELECT dt.id, dt.id_menu, dt.harga_satuan, dt.qty, dt.notes, dt.harga, dt.status, m.nama, dt.variant, c.name AS cName, c.id AS category_id, qty_delivered, m.id_partner,
            CASE
                WHEN qty-qty_delivered>0 THEN 0
                ELSE 1
            END AS delivery_done, tc.notes AS cancel_notes, tc.created_by AS canceled_by, e.nama AS employee_name
            FROM detail_transaksi dt JOIN menu m ON dt.id_menu=m.id JOIN categories c ON m.id_category=c.id LEFT JOIN transaction_cancellation tc ON tc.detail_transaction_id=dt.id LEFT JOIN employees e ON e.id=tc.created_by WHERE dt.id_transaksi='$transactionID' AND dt.deleted_at IS NULL AND m.id_partner='$tenant_id' ";

            $queryTrans = "SELECT table_name FROM information_schema.tables
                WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
            $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .=  "SELECT dt.id, dt.id_menu, dt.harga_satuan, dt.qty, dt.notes, dt.harga, dt.status, m.nama, dt.variant, c.name AS cName, c.id AS category_id, qty_delivered, m.id_partner,
                        CASE
                            WHEN qty-qty_delivered>0 THEN 0
                            ELSE 1
                        END AS delivery_done, tc.notes AS cancel_notes, tc.created_by AS canceled_by, e.nama AS employee_name
                        FROM `$detail_transactions` dt JOIN menu m ON dt.id_menu=m.id JOIN categories c ON m.id_category=c.id LEFT JOIN transaction_cancellation tc ON tc.detail_transaction_id=dt.id LEFT JOIN employees e ON e.id=tc.created_by WHERE dt.id_transaksi='$transactionID' AND dt.deleted_at IS NULL AND m.id_partner='$tenant_id' ";
                    // }
            }
            $query .= " ) AS tmp ";
        }else{

            $query = "SELECT id, id_menu, harga_satuan, qty, notes, harga, status, nama, variant, cName, category_id, qty_delivered, delivery_done, cancel_notes, canceled_by, employee_name FROM ( SELECT dt.id, dt.id_menu, dt.harga_satuan, dt.qty, dt.notes, dt.harga, dt.status, m.nama, dt.variant, c.name AS cName, c.id AS category_id, qty_delivered,
            CASE
                WHEN qty-qty_delivered>0 THEN 0
                ELSE 1
            END AS delivery_done, tc.notes AS cancel_notes, tc.created_by AS canceled_by, e.nama AS employee_name
            FROM detail_transaksi dt JOIN menu m ON dt.id_menu=m.id JOIN categories c ON m.id_category=c.id LEFT JOIN transaction_cancellation tc ON tc.detail_transaction_id=dt.id LEFT JOIN employees e ON e.id=tc.created_by WHERE dt.id_transaksi='$transactionID' AND dt.deleted_at IS NULL AND m.id_partner='$token->id_partner' ";

            $queryTrans = "SELECT table_name FROM information_schema.tables
                WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
            $transaksi = mysqli_query($db_conn, $queryTrans);
            while($row=mysqli_fetch_assoc($transaksi)){
                $table_name = explode("_",$row['table_name']);
                $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
            // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                $query .= "UNION ALL " ;
                $query .=  "SELECT dt.id, dt.id_menu, dt.harga_satuan, dt.qty, dt.notes, dt.harga, dt.status, m.nama, dt.variant, c.name AS cName, c.id AS category_id, qty_delivered,
                        CASE
                            WHEN qty-qty_delivered>0 THEN 0
                            ELSE 1
                        END AS delivery_done, tc.notes AS cancel_notes, tc.created_by AS canceled_by, e.nama AS employee_name
                        FROM `$detail_transactions` dt JOIN menu m ON dt.id_menu=m.id JOIN categories c ON m.id_category=c.id LEFT JOIN transaction_cancellation tc ON tc.detail_transaction_id=dt.id LEFT JOIN employees e ON e.id=tc.created_by WHERE dt.id_transaksi='$transactionID' AND dt.deleted_at IS NULL AND m.id_partner='$token->id_partner' ";
                    // }
            }
            $query .= " ) AS tmp ";
            $q = mysqli_query($db_conn, $query);
            if (mysqli_num_rows($q) > 0) {
                $query=$query;
            }else{
                $query = "SELECT id, id_menu, harga_satuan, qty, notes, harga, status, nama, variant, cName, category_id, qty_delivered, delivery_done, cancel_notes, canceled_by, employee_name FROM ( SELECT dt.id, dt.id_menu, dt.harga_satuan, dt.qty, dt.notes, dt.harga, dt.status, m.nama, dt.variant, c.name AS cName, c.id AS category_id, qty_delivered,
                CASE
                    WHEN qty-qty_delivered>0 THEN 0
                    ELSE 1
                END AS delivery_done, tc.notes AS cancel_notes, tc.created_by AS canceled_by, e.nama AS employee_name
                FROM detail_transaksi dt JOIN menu m ON dt.id_menu=m.id JOIN categories c ON m.id_category=c.id LEFT JOIN transaction_cancellation tc ON tc.detail_transaction_id=dt.id LEFT JOIN employees e ON e.id=tc.created_by WHERE dt.id_transaksi='$transactionID' AND dt.deleted_at IS NULL ";

                $queryTrans = "SELECT table_name FROM information_schema.tables
                    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
                $transaksi = mysqli_query($db_conn, $queryTrans);
                while($row=mysqli_fetch_assoc($transaksi)){
                    $table_name = explode("_",$row['table_name']);
                    $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                    $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                    $query .= "UNION ALL " ;
                    $query .=  "SELECT dt.id, dt.id_menu, dt.harga_satuan, dt.qty, dt.notes, dt.harga, dt.status, m.nama, dt.variant, c.name AS cName, c.id AS category_id, qty_delivered,
                            CASE
                                WHEN qty-qty_delivered>0 THEN 0
                                ELSE 1
                            END AS delivery_done, tc.notes AS cancel_notes, tc.created_by AS canceled_by, e.nama AS employee_name
                            FROM `$detail_transactions` dt JOIN menu m ON dt.id_menu=m.id JOIN categories c ON m.id_category=c.id LEFT JOIN transaction_cancellation tc ON tc.detail_transaction_id=dt.id LEFT JOIN employees e ON e.id=tc.created_by WHERE dt.id_transaksi='$transactionID' AND dt.deleted_at IS NULL ";
                        // }
                }
                $query .= " ) AS tmp ";
            }
        }
        $q = mysqli_query($db_conn, $query);

        $data = array();
        if (mysqli_num_rows($q) > 0) {

            $phone = getPhone($transactionID, $db_conn);
            $masterID = getMasterID($transactionID, $db_conn);
            $is_special_member = checkSM($masterID, $phone, $db_conn);

            $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
            foreach ($res as $value) {
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
                array_push($data, $temp);
            }
            $success =1;
            $status =200;
            $msg = "Success";
        } else {
            $query = "SELECT id, id_menu, harga_satuan, qty, notes, harga, status, name, variant FROM ( SELECT dt.id, dt.id_menu, dt.harga_satuan, dt.qty, dt.notes, dt.harga, dt.status, m.name, dt.variant FROM detail_transaksi dt JOIN pre_order_menus m ON dt.id_menu=m.id WHERE dt.id_transaksi='$transactionID' AND AND dt.deleted_at IS NULL ";

    $queryTrans = "SELECT table_name FROM information_schema.tables
    WHERE table_schema = '".$_ENV['DB_NAME']."' AND table_name LIKE 'transactions_%'";
    $transaksi = mysqli_query($db_conn, $queryTrans);
                    while($row=mysqli_fetch_assoc($transaksi)){
                        $table_name = explode("_",$row['table_name']);
                        $transactions = "transactions_".$table_name[1]."_".$table_name[2];
                        $detail_transactions = "detail_transactions_".$table_name[1]."_".$table_name[2];
                        // if(($dateFromStr>=$table_name[1]) || ($dateToStr>=$table_name[2])){
                            $query .= "UNION ALL " ;
                            $query .=  "SELECT dt.id, dt.id_menu, dt.harga_satuan, dt.qty, dt.notes, dt.harga, dt.status, m.name, dt.variant FROM `$detail_transactions` dt JOIN pre_order_menus m ON dt.id_menu=m.id WHERE dt.id_transaksi='$transactionID' AND AND dt.deleted_at IS NULL ";
                        // }
                    }
            $query .= " ) AS tmp ";
            $q = mysqli_query($db_conn, $query);

            $data = array();
            if (mysqli_num_rows($q) > 0) {

                $phone = getPhone($transactionID, $db_conn);
                $masterID = getMasterID($transactionID, $db_conn);
                $is_special_member = checkSM($masterID, $phone, $db_conn);

                $res = mysqli_fetch_all($q, MYSQLI_ASSOC);
                foreach ($res as $value) {
                    $temp = $value;
                    $temp['cName'] = null;
                    $temp['category_id'] = null;
                    if($value['variant'] != null) {
                        $variant = $value['variant'];
                        $variant =  substr($variant,11);
                        $variant = substr_replace($variant ,"",-1);
                        $variant = str_replace("'",'"',$variant);
                        $temp['variant'] = json_decode($variant);
                    }else{
                        $temp['variant'] = [];
                    }
                    array_push($data, $temp);
                }
                $success =1;
                $status =200;
                $msg = "Success";

            } else {
                $success =0;
                $status =200;
                $msg = "Data Not Found";
            }
        }
    }else{
        $success =0;
        $status =200;
        $msg = "400 Missing Required Field";
    }
}
http_response_code($status);
echo json_encode(["success"=>$success, "status"=>$status, "msg"=>$msg, "trxStatus"=>$trxStatus, "partnerNote"=>$trxPNote, "payment_method"=>$trxPMName,"transactionDetails"=>$data, "type"=>$trxType, "deliveryDetail"=>$delivery_data, "type"=>$trxType, "is_special_member"=>$is_special_member, "total"=>$total, "promo"=>$promo, "service"=>$service, "tax"=>$tax, "diskon_spesial"=>$diskon_spesial, "employee_discount"=>$employee_discount,"charge_ur"=>$charge_ur, "address"=>$address, "pre_order"=>$preOrder, "printed"=>$printed, "program_discount"=>$program_discount, "table_group_id"=>$table_group_id, "customer_email"=>$customer_email, "customer_name"=>$customer_name]);
?>