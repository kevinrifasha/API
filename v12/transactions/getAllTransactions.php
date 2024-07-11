<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require_once("../connection.php");
require_once("./../tokenModels/tokenManager.php");
require_once("./../transactionModels/transactionManager.php");
require_once("./../userModels/userManager.php");
require_once("./../paymentModels/paymentManager.php");
require '../../db_connection.php';

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

foreach ($headers as $header => $value) {
    if($header=="Authorization" || $header=="AUTHORIZATION"){
        $token=substr($value,7);
    }
}
    $db = connectBase();
    $tokenizer = new TokenManager($db);
    $tokens = $tokenizer->validate($token);
    $tokenDecoded = json_decode($tokenizer->stringEncryption('decrypt',$token));
    $idMaster = $tokenDecoded->masterID;

    $partnerId = $_GET['partnerId'];
    $from = $_GET['from'];
    $to = $_GET['to'];
    $newDateFormat = 0;
    
    if(strlen($to) !== 10 && strlen($from) !== 10){
        $to = str_replace("%20"," ",$to);
        $from = str_replace("%20"," ",$from);
        $newDateFormat = 1;
    } else {
        $to = $to . " 00:00:00";
        $from = $from . " 23:59:59";
        $newDateFormat = 1;
    }

    // $page = $_GET['page'];
    // $load = $_GET['load'];
    $type = 0;
    $all = "0";

    if(isset($_GET['type']) && !empty($_GET['type'])){
        $type=$_GET['type'];
    }

    // $finish = $load * $page;
    // $start = $finish - $load;

    if(isset($tokens['success']) && $tokens['success']=='0' || isset($tokens['success']) && $tokens['success']==0){
            $status = $tokens['status'];
            $signupMsg = $tokens['msg'];
            $success = 0;
    }else{

        if($newDateFormat == 1){
            $Tmanager = new TransactionManager($db);
            $Umanager = new UserManager($db);
            $history = [];
            if(isset($_GET['all'])) {
                $all = $_GET['all'];
            }
            
            if($all != "1") {
                $idMaster = null; 
                if( isset($page) &&!empty($page)
                    && isset($load) &&!empty($load)
                ){
                    $history = $Tmanager->getHistoryWithHour($partnerId, $start, $load, $from, $to);
                }else{
                    $history = $Tmanager->getByPartnerIdAllStatusWithHour($partnerId, $from, $to, $type);
                }
            } else {
                if( isset($page) &&!empty($page)
                    && isset($load) &&!empty($load)
                ){
                    $history = $Tmanager->getHistoryMasterWithHour($idMaster, $start, $load, $from, $to);
                }else{
                    $history = $Tmanager->getByMasterIdWithHour($idMaster, $from, $to, $type);
                }
            }
            
            $i = 0;
    
            if($history) {
                foreach ($history as $val) {
                $status_int = $val['status'];
                //jika tidak dine in maka no meja sesuai jenis pesanan
                if($val['takeaway']=='1'){
                    $history[$i]['no_meja'] = "Take Away";
                }
    
                if($val['id'][0]=='P' && $val['id'][1]=='O' ){
                    $history[$i]['no_meja']="Pre Order";
                }
    
                if($val['id'][0]!='P' && $val['id'][1]!='O' && $val['takeaway']=='0' && $val['no_meja']=="" ){
                    $history[$i]['no_meja']="Delivery";
                }
                // end
    
    
                // get user name
                // $phone = $val['phone'];
                // $user = $Umanager->getUser($phone);
                // if($user!=false){
                //     $user = $user->getName();
                //     $history[$i]['customer_name']=$user;
                // }else{
                //     $history[$i]['customer_name']="Wrong";
    
                // }
                // get user name end
    
                // get payment name
                // $PaymentManager = new PaymentManager($db);
                // $payment = $PaymentManager->getById($val['tipe_bayar']);
                // if($payment!=false){
                //     $history[$i]['paymentName'] = $payment->getNama();
                // }else{
                //     $history[$i]['paymentName'] = "Wrong";
                // }
                // // get payment name end
    
    
                // get nama kasir
                // $shift_id = $val["shift_id"];
                // $history[$i]['shift_id'] = $shift_id;
                // $sql = mysqli_query($db_conn, "SELECT shift.employee_id FROM `shift` WHERE id = '$shift_id'");
    
                // if(mysqli_num_rows($sql) > 0) {
                //     $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                //     $employee_id = $data[0]['employee_id'];
                //     $history[$i]['employee_id'] = $employee_id;
    
                //     if($employee_id) {
                //         $queryGetName = mysqli_query($db_conn, "SELECT employees.nama FROM `employees` WHERE id = '$employee_id'");
                //         if(mysqli_num_rows($queryGetName) > 0) {
                //             $dataName = mysqli_fetch_all($queryGetName, MYSQLI_ASSOC);
                //             $history[$i]['collected_by'] = $dataName[0]['nama'];
                //         } else {
                //             $history[$i]['collected_by'] = "";
                //         }
                //     }
                // } else {
                //     $history[$i]['collected_by'] = "";
                // }
                // get nama kasir end
    
    
                // get nama server
                // 1. Ambil dulu id transaksi
                // 2. get server_id dari table detail_transksi refer to $transactionID
                // $sql = mysqli_query($db_conn, "SELECT detail_transaksi.server_id FROM `detail_transaksi` WHERE id = '$transactionID'");
                // $sql = mysqli_query($db_conn, "SELECT detail_transaksi.server_id FROM `detail_transaksi` WHERE id_transaksi = '$transactionID' ORDER BY `id` DESC");
    
                // if(mysqli_num_rows($sql) > 0) {
                //     $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                //     $server_id = $data[0]['server_id'];
    
                //     if($server_id == 0) {
                //         $history[$i]['served_by'] = "";
                //     }
    
                //     $history[$i]['server_id'] = $server_id;
    
                //     // 3. get nama server
                //     if($server_id) {
                //         $queryGetName = mysqli_query($db_conn, "SELECT employees.nama FROM `employees` WHERE id = '$server_id'");
                //         if(mysqli_num_rows($queryGetName) > 0) {
                //             $dataName = mysqli_fetch_all($queryGetName, MYSQLI_ASSOC);
                //             $history[$i]['served_by'] = $dataName[0]['nama'];
                //         } else {
                //             $history[$i]['served_by'] = "";
                //         }
                //     }
                // } else {
                //     $history[$i]['served_by'] = "";
                // }
                // get nama server end
    
                // get items
                // 1. get semua menu_id dari tabel detail_transaksi refer to id_transaksi
                $history[$i]['paymentName'] = $val['paymentName'];
                $history[$i]['collected_by'] = $val['collected_by'];
                $transactionID = $val['id'];
                $sql = mysqli_query($db_conn, "SELECT dt.id, dt.id_menu, dt.id_transaksi, dt.status, t.surcharge_id FROM `detail_transaksi` dt JOIN transaksi t ON t.id = dt.id_transaksi WHERE dt.id_transaksi = '$transactionID' ORDER BY dt.id DESC");
    
                // 2. get nama menu dari masing-masing id yang di dapat
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
    
                    if($data) {
                        $n = 0;
                        $countData = count($data) - 1;
                        $items = [];
                        $variant = [];
    
                        foreach($data as $val) {
                            $value = $val['id_menu'];
    
                            // sebelum di kasih limit 1 ada error subquery return more than 1 row
                            // $query = "SELECT menu.nama, (SELECT detail_transaksi.qty FROM `detail_transaksi` WHERE id_transaksi = '$transactionID' AND id_menu = '$value') AS qty, (SELECT detail_transaksi.variant FROM `detail_transaksi` WHERE id_transaksi = '$transactionID' AND id_menu = '$value') AS variant FROM `menu` WHERE id = '$value'";
                            
                            // dikasih limit 1
                            $query = "SELECT menu.nama, (SELECT detail_transaksi.qty FROM `detail_transaksi` WHERE id_transaksi = '$transactionID' AND id_menu = '$value' LIMIT 1) AS qty, (SELECT detail_transaksi.variant FROM `detail_transaksi` WHERE id_transaksi = '$transactionID' AND id_menu = '$value' LIMIT 1) AS variant FROM `menu` WHERE id = '$value'";
    
                            $select = mysqli_query($db_conn, $query);
    
                            if($select && mysqli_num_rows($select) > 0) {
                                $data = mysqli_fetch_all($select, MYSQLI_ASSOC);
                                $menuName = $data[0]['nama'];
                                $menuQty = $data[0]['qty'];
                                $menuVariant = $data[0]['variant'];
    
                                 array_push($items, array("name" => "$menuName", "qty" => $menuQty, "variant"=>$menuVariant));
    
                                if($menuVariant) {
                                    $variant = $menuVariant;
                                }
    
                            }
    
                            $n += 1;
                        }
    
                        $history[$i]['items'] = $items;
                        $history[$i]['variant'] = $variant;
                    }
                } else {
                    $history[$i]['items'] = "";
                }
                // get items end
    
                // get alasan refund
                $sql = mysqli_query($db_conn, "SELECT transaction_cancellation.notes FROM `transaction_cancellation` WHERE transaction_id = '$transactionID' ORDER BY `id` DESC");
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $history[$i]['refundNotes'] = $data[0]['notes'];
                } else {
                    $history[$i]['refundNotes'] = "";
                }
                // get alasan refund end
    
                // get notes
                $sql = mysqli_query($db_conn, "SELECT detail_transaksi.notes FROM `detail_transaksi` WHERE id_transaksi = '$transactionID' ORDER BY `id` DESC");
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $history[$i]['notes'] = $data[0]['notes'];
                } else {
                    $history[$i]['notes'] = "";
                }
                // get notes end
    
                // get finish_date
                // $idT = $val['id_transaksi'];
                $statusT = $val['status'];
                // $sql = mysqli_query($db_conn, "SELECT order_status_trackings.created_at as created_at FROM `order_status_trackings` WHERE `transaction_id` = '$idT' AND `status_after` = '$statusT' ORDER BY `id` DESC");
                // $query = "SELECT order_status_trackings.created_at FROM `order_status_trackings` WHERE transaction_id = '$transactionID' AND status_after = '$status_int';";
                $query = "SELECT transaksi.paid_date FROM `transaksi` WHERE id = '$transactionID' AND organization='Natta';";
                $sql = mysqli_query($db_conn, $query);
    
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $date = $data[0]['paid_date'];
    
                    if($date) {
                        $history[$i]['finish_date'] = $date;
                    } else {
                        $history[$i]['finish_date'] = "";
                    }
                }else{
                    $history[$i]['finish_date'] = "";
                }
                // get finish_date end

                // $history[$i]['surcharge_name']=$val['surcharge_name'];
                    
                // $idS = $val['surcharge_id'];
                // $sql = mysqli_query($db_conn, "SELECT name FROM `surcharges` WHERE `id`= '$idS'");
                // if(mysqli_num_rows($sql) > 0) {
                //     $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                //     $history[$i]['surcharge_name'] = $data[0]['name'];
                // }else{
                //     $history[$i]['surcharge_name'] = "";
    
                // }
    
                $i+=1;
            }
            }
    
            if($history && count($history)>0){
                $success = 1;
                $signupMsg = "Success";
                $status = 200;
            }else{
                $history = [];
                $success = 0;
                $signupMsg = "Data not found";
                $status = 204;
            }
        } else {
            $Tmanager = new TransactionManager($db);
            $Umanager = new UserManager($db);
            $history = [];
            if(isset($_GET['all'])) {
                $all = $_GET['all'];
            }
            
            if($all !== "1") {
                $idMaster = null; 
                if( isset($page) &&!empty($page)
                    && isset($load) &&!empty($load)
                ){
                    $history = $Tmanager->getHistory($partnerId, $start, $load, $from, $to);
                }else{
                    $history = $Tmanager->getByPartnerIdAllStatus($partnerId, $from, $to, $type);
                }
            } else {
                if( isset($page) &&!empty($page)
                    && isset($load) &&!empty($load)
                ){
                    $history = $Tmanager->getHistoryMaster($idMaster, $start, $load, $from, $to);
                }else{
                    $history = $Tmanager->getByMasterId($idMaster, $from, $to, $type);
                }
            }
            
            $i = 0;
    
            if($history) {
                foreach ($history as $val) {
                $status_int = $val['status'];
                //jika tidak dine in maka no meja sesuai jenis pesanan
                if($val['takeaway']=='1'){
                    $history[$i]['no_meja'] = "Take Away";
                }
    
                if($val['id'][0]=='P' && $val['id'][1]=='O' ){
                    $history[$i]['no_meja']="Pre Order";
                }
    
                if($val['id'][0]!='P' && $val['id'][1]!='O' && $val['takeaway']=='0' && $val['no_meja']=="" ){
                    $history[$i]['no_meja']="Delivery";
                }
                // end
    
                // get user name
                // $phone = $val['phone'];
                // $user = $Umanager->getUser($phone);
                // if($user!=false){
                //     $user = $user->getName();
                //     $history[$i]['customer_name']=$user;
                // }else{
                //     $history[$i]['customer_name']="Wrong";
    
                // }
                // get user name end
    
                // get payment name
                $PaymentManager = new PaymentManager($db);
                $payment = $PaymentManager->getById($val['tipe_bayar']);
                if($payment!=false){
                    $history[$i]['paymentName'] = $payment->getNama();
                }else{
                    $history[$i]['paymentName'] = "Wrong";
                }
                // get payment name end
    
                // get nama kasir
                $shift_id = $val["shift_id"];
                $history[$i]['shift_id'] = $shift_id;
                $sql = mysqli_query($db_conn, "SELECT shift.employee_id FROM `shift` WHERE id = '$shift_id'");
    
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $employee_id = $data[0]['employee_id'];
                    $history[$i]['employee_id'] = $employee_id;
    
                    if($employee_id) {
                        $queryGetName = mysqli_query($db_conn, "SELECT employees.nama FROM `employees` WHERE id = '$employee_id'  AND organization='Natta'");
                        if(mysqli_num_rows($queryGetName) > 0) {
                            $dataName = mysqli_fetch_all($queryGetName, MYSQLI_ASSOC);
                            $history[$i]['collected_by'] = $dataName[0]['nama'];
                        } else {
                            $history[$i]['collected_by'] = "";
                        }
                    }
                } else {
                    $history[$i]['collected_by'] = "";
                }
                // get nama kasir end
    
    
                // get nama server
                // 1. Ambil dulu id transaksi
                $transactionID = $val['id'];
                // 2. get server_id dari table detail_transksi refer to $transactionID
                // $sql = mysqli_query($db_conn, "SELECT detail_transaksi.server_id FROM `detail_transaksi` WHERE id = '$transactionID'");
                $sql = mysqli_query($db_conn, "SELECT detail_transaksi.server_id FROM `detail_transaksi` WHERE id_transaksi = '$transactionID' ORDER BY `id` DESC");
    
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $server_id = $data[0]['server_id'];
    
                    if($server_id == 0) {
                        $history[$i]['served_by'] = "";
                    }
    
                    $history[$i]['server_id'] = $server_id;
    
                    // 3. get nama server
                    if($server_id) {
                        $queryGetName = mysqli_query($db_conn, "SELECT employees.nama FROM `employees` WHERE id = '$server_id'  AND organization='Natta'");
                        if(mysqli_num_rows($queryGetName) > 0) {
                            $dataName = mysqli_fetch_all($queryGetName, MYSQLI_ASSOC);
                            $history[$i]['served_by'] = $dataName[0]['nama'];
                        } else {
                            $history[$i]['served_by'] = "";
                        }
                    }
                } else {
                    $history[$i]['served_by'] = "";
                }
                // get nama server end
    
                // get items
                // 1. get semua menu_id dari tabel detail_transaksi refer to id_transaksi
                $sql = mysqli_query($db_conn, "SELECT dt.id, dt.id_menu, dt.id_transaksi, dt.status, t.surcharge_id FROM `detail_transaksi` dt JOIN transaksi t ON t.id = dt.id_transaksi WHERE dt.id_transaksi = '$transactionID' ORDER BY dt.id DESC");
    
                // 2. get nama menu dari masing-masing id yang di dapat
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
    
                    if($data) {
                        $n = 0;
                        $countData = count($data) - 1;
                        $items = [];
                        $variant = [];
    
                        foreach($data as $val) {
                            $value = $val['id_menu'];
    
                            // sebelum di kasih limit 1 ada error subquery return more than 1 row
                            // $query = "SELECT menu.nama, (SELECT detail_transaksi.qty FROM `detail_transaksi` WHERE id_transaksi = '$transactionID' AND id_menu = '$value') AS qty, (SELECT detail_transaksi.variant FROM `detail_transaksi` WHERE id_transaksi = '$transactionID' AND id_menu = '$value') AS variant FROM `menu` WHERE id = '$value'";
                            
                            // dikasih limit 1
                            $query = "SELECT menu.nama, (SELECT detail_transaksi.qty FROM `detail_transaksi` WHERE id_transaksi = '$transactionID' AND id_menu = '$value' LIMIT 1) AS qty, (SELECT detail_transaksi.variant FROM `detail_transaksi` WHERE id_transaksi = '$transactionID' AND id_menu = '$value' LIMIT 1) AS variant FROM `menu` WHERE id = '$value'";
    
                            $select = mysqli_query($db_conn, $query);
    
                            if($select && mysqli_num_rows($select) > 0) {
                                $data = mysqli_fetch_all($select, MYSQLI_ASSOC);
                                $menuName = $data[0]['nama'];
                                $menuQty = $data[0]['qty'];
                                $menuVariant = $data[0]['variant'];
    
                                 array_push($items, array("name" => "$menuName", "qty" => $menuQty, "variant"=>$menuVariant));
    
                                if($menuVariant) {
                                    $variant = $menuVariant;
                                }
    
                            }
    
                            $n += 1;
                        }
    
                        $history[$i]['items'] = $items;
                        $history[$i]['variant'] = $variant;
                    }
                } else {
                    $history[$i]['items'] = "";
                }
                // get items end
    
                // get alasan refund
                $sql = mysqli_query($db_conn, "SELECT transaction_cancellation.notes FROM `transaction_cancellation` WHERE transaction_id = '$transactionID' ORDER BY `id` DESC");
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $history[$i]['refundNotes'] = $data[0]['notes'];
                } else {
                    $history[$i]['refundNotes'] = "";
                }
                // get alasan refund end
    
                // get notes
                $sql = mysqli_query($db_conn, "SELECT detail_transaksi.notes FROM `detail_transaksi` WHERE id_transaksi = '$transactionID' ORDER BY `id` DESC");
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $history[$i]['notes'] = $data[0]['notes'];
                } else {
                    $history[$i]['notes'] = "";
                }
                // get notes end
    
                // get finish_date
                // $idT = $val['id_transaksi'];
                $statusT = $val['status'];
                // $sql = mysqli_query($db_conn, "SELECT order_status_trackings.created_at as created_at FROM `order_status_trackings` WHERE `transaction_id` = '$idT' AND `status_after` = '$statusT' ORDER BY `id` DESC");
                // $query = "SELECT order_status_trackings.created_at FROM `order_status_trackings` WHERE transaction_id = '$transactionID' AND status_after = '$status_int';";
                $query = "SELECT transaksi.paid_date FROM `transaksi` WHERE id = '$transactionID' AND organization='Natta';";
                $sql = mysqli_query($db_conn, $query);
    
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $date = $data[0]['paid_date'];
    
                    if($date) {
                        $history[$i]['finish_date'] = $date;
                    } else {
                        $history[$i]['finish_date'] = "";
                    }
                }else{
                    $history[$i]['finish_date'] = "";
                }
                // get finish_date end
    
                $idS = $val['surcharge_id'];
                $sql = mysqli_query($db_conn, "SELECT name FROM `surcharges` WHERE `id`= '$idS'");
                if(mysqli_num_rows($sql) > 0) {
                    $data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
                    $history[$i]['surcharge_name'] = $data[0]['name'];
                }else{
                    $history[$i]['surcharge_name'] = "";
    
                }
    
                $i+=1;
            }
            }
    
            if($history && count($history)>0){
                $success = 1;
                $signupMsg = "Success";
                $status = 200;
            }else{
                $history = [];
                $success = 0;
                $signupMsg = "Data not found";
                $status = 204;
            }
        }


    }

    $signupJson = json_encode(["success"=>$success, "status"=>$status,"msg"=>$signupMsg, "history"=>$history]);

    echo $signupJson;
 ?>
